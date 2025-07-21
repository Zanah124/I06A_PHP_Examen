<?php
require_once "db.php";

class Reservations {
    private $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    // Récupérer toutes les réservations
    public function getAllReservations() {
        $stmt = $this->conn->prepare("SELECT * FROM reservations");
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
        $stmt = $this->conn->prepare("UPDATE reservations SET user_id = :user_id, livre_id = :livre_id, date_reservation = :date_reservation, statut = :statut WHERE id = :id");
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':livre_id', $data['livre_id']);
        $stmt->bindParam(':date_reservation', $data['date_reservation']);
        $stmt->bindParam(':statut', $data['statut']);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Supprimer une réservation
    public function deleteReservation($id) {
        $stmt = $this->conn->prepare("DELETE FROM reservations WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Récupérer les réservations d'un utilisateur
    public function getReservationsByUserId($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM reservations WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les réservations d'un livre
    public function getReservationsByLivreId($livre_id) {
        $stmt = $this->conn->prepare("SELECT * FROM reservations WHERE livre_id = :livre_id");
        $stmt->bindParam(':livre_id', $livre_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>