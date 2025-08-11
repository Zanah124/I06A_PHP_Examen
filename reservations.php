<?php
require_once "db.php";

class Reservations {
    private $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    // Récupérer toutes les réservations
    public function getAllReservations() {
        $stmt = $this->conn->prepare("SELECT * FROM reservations ORDER BY date_reservation DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer une réservation par son ID
    public function getReservationById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM reservations WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer les détails d'une réservation avec email utilisateur et titre du livre
    public function getReservationDetails($id) {
        $stmt = $this->conn->prepare("
            SELECT r.*, u.email, u.nom, l.titre
            FROM reservations r
            JOIN users u ON r.user_id = u.id
            JOIN livres l ON r.livre_id = l.id
            WHERE r.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Créer une nouvelle réservation
    public function createReservation($data) {
        $stmt = $this->conn->prepare("INSERT INTO reservations (user_id, livre_id, date_reservation, statut) VALUES (:user_id, :livre_id, :date_reservation, :statut)");
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':livre_id', $data['livre_id']);
        $stmt->bindParam(':date_reservation', $data['date_reservation']);
        $stmt->bindParam(':statut', $data['statut']);
        return $stmt->execute();
    }

    // Mettre à jour une réservation existante
    public function updateReservation($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['user_id', 'livre_id', 'date_reservation', 'statut', 'date_prise', 'date_limite_retour', 'date_retour'])) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE reservations SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    // Marquer un livre comme pris
    public function markAsTaken($reservationId) {
        $datePrise = date('Y-m-d H:i:s');
        $dateLimite = date('Y-m-d H:i:s', strtotime('+2 weeks'));
        
        $stmt = $this->conn->prepare("
            UPDATE reservations 
            SET statut = 'prise', 
                date_prise = :date_prise, 
                date_limite_retour = :date_limite_retour 
            WHERE id = :id AND statut = 'validee'
        ");
        
        return $stmt->execute([
            ':date_prise' => $datePrise,
            ':date_limite_retour' => $dateLimite,
            ':id' => $reservationId
        ]);
    }

    // Marquer un livre comme rendu
    public function markAsReturned($reservationId) {
        $dateRetour = date('Y-m-d H:i:s');
        
        $stmt = $this->conn->prepare("
            UPDATE reservations 
            SET statut = 'rendu', 
                date_retour = :date_retour 
            WHERE id = :id AND statut = 'prise'
        ");
        
        return $stmt->execute([
            ':date_retour' => $dateRetour,
            ':id' => $reservationId
        ]);
    }

    // Récupérer les livres en retard
    public function getOverdueBooks() {
        $stmt = $this->conn->prepare("
            SELECT r.*, u.nom, u.email, l.titre
            FROM reservations r
            JOIN users u ON r.user_id = u.id
            JOIN livres l ON r.livre_id = l.id
            WHERE r.statut = 'prise' 
            AND r.date_limite_retour < NOW()
            ORDER BY r.date_limite_retour ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les livres qui arrivent à échéance dans X jours
    public function getBooksNearDueDate($days = 3) {
        $stmt = $this->conn->prepare("
            SELECT r.*, u.nom, u.email, l.titre
            FROM reservations r
            JOIN users u ON r.user_id = u.id
            JOIN livres l ON r.livre_id = l.id
            WHERE r.statut = 'prise' 
            AND r.date_limite_retour BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
            ORDER BY r.date_limite_retour ASC
        ");
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Supprimer une réservation
    public function deleteReservation($id) {
        $stmt = $this->conn->prepare("DELETE FROM reservations WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Récupérer les réservations d'un utilisateur
    public function getReservationsByUserId($user_id) {
        $stmt = $this->conn->prepare("
            SELECT r.*, l.titre, l.auteur 
            FROM reservations r
            LEFT JOIN livres l ON r.livre_id = l.id
            WHERE r.user_id = :user_id 
            ORDER BY r.date_reservation DESC
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les réservations d'un livre (exclure les livres rendus pour le calcul de disponibilité)
    public function getReservationsByLivreId($livre_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM reservations 
            WHERE livre_id = :livre_id 
            AND statut IN ('en attente', 'validee', 'prise')
            ORDER BY date_reservation ASC
        ");
        $stmt->bindParam(':livre_id', $livre_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compter les réservations par statut
    public function getReservationStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                statut,
                COUNT(*) as count
            FROM reservations 
            GROUP BY statut
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'en_attente' => 0,
            'validee' => 0,
            'prise' => 0,
            'rendu' => 0,
            'annulee' => 0,
            'total' => 0
        ];
        
        foreach ($results as $result) {
            $status = str_replace(' ', '_', $result['statut']);
            if (isset($stats[$status])) {
                $stats[$status] = $result['count'];
            }
            $stats['total'] += $result['count'];
        }
        
        return $stats;
    }

    public function getReservationCount() {
        $query = "SELECT COUNT(*) as count FROM reservations";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function countNewNotifications($userId) {
        try {
            $query = "SELECT COUNT(*) as count 
                     FROM reservations 
                     WHERE user_id = :user_id 
                     AND statut IN ('validee', 'prise') 
                     AND is_viewed = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des notifications : " . $e->getMessage());
            return 0;
        }
    }

    // Marquer les notifications comme vues
    public function markNotificationsAsViewed($userId) {
        try {
            $query = "UPDATE reservations 
                     SET is_viewed = 1 
                     WHERE user_id = :user_id 
                     AND statut IN ('validee', 'prise') 
                     AND is_viewed = 0";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute(['user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour des notifications : " . $e->getMessage());
            return false;
        }
    }

    // Calculer les jours restants avant l'échéance
    public function getDaysUntilDue($dateLimiteRetour) {
        $now = new DateTime();
        $dueDate = new DateTime($dateLimiteRetour);
        $interval = $now->diff($dueDate);
        
        if ($now > $dueDate) {
            return -$interval->days; // Nombre négatif pour les retards
        }
        
        return $interval->days;
    }
}
?>