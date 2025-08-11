<?php
// update_reservation.php
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once "reservations.php"; // ta classe Reservations

$response = ['success' => false, 'message' => 'Erreur inconnue'];

try {
    // Vérifier si l'utilisateur est admin
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
        throw new Exception('Accès non autorisé');
    }

    // Vérifier méthode POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Paramètres
    $reservationId = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    if ($reservationId <= 0 || !in_array($action, ['validate', 'cancel', 'take', 'return'])) {
        throw new Exception('Paramètres invalides');
    }

    $reservationsModel = new Reservations();
    $reservation = $reservationsModel->getReservationById($reservationId);

    if (!$reservation) {
        throw new Exception('Réservation introuvable');
    }

    // Préparer les données à mettre à jour
    $data = [
        'user_id' => $reservation['user_id'],
        'livre_id' => $reservation['livre_id'],
        'date_reservation' => $reservation['date_reservation'],
    ];

    // Aiguillage selon action
    if ($action === 'validate') {
        $data['statut'] = 'validee';
        // on peut aussi définir une date_limite_retour par défaut ici si tu veux
    } elseif ($action === 'cancel') {
        $data['statut'] = 'annulee';
    } elseif ($action === 'take') {
        $data['statut'] = 'prise';
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $data['date_prise'] = $now;
        // définir une durée d'emprunt (ex: 14 jours) — adapte si besoin
        $limit = (new DateTime())->modify('+14 days')->format('Y-m-d H:i:s');
        $data['date_limite_retour'] = $limit;
    } elseif ($action === 'return') {
        $data['statut'] = 'rendu';
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $data['date_retour'] = $now;
        // si tu veux, tu peux aussi garder date_prise existante
    }

    // Effectuer la mise à jour (la méthode updateReservation doit accepter les champs passés)
    $ok = $reservationsModel->updateReservation($reservationId, $data);

    if (!$ok) {
        throw new Exception('Erreur lors de la mise à jour de la réservation');
    }

    // Actions après mise à jour
    if ($action === 'validate') {
        // Récupérer les détails pour email
        $details = $reservationsModel->getReservationDetails($reservationId);
        if ($details && isset($details['email'], $details['nom'], $details['titre'])) {
            // Calculer prochain jour ouvrable pour la prise (comme avant)
            $currentDate = new DateTime();
            $currentDate->modify('+1 day');
            while ($currentDate->format('N') >= 6) { // 6 = Samedi, 7 = Dimanche
                $currentDate->modify('+1 day');
            }
            $pickupDate = $currentDate->format('d/m/Y');

            $to = $details['email'];
            $subject = 'Validation de votre réservation - Bibliothèque Acacia';
            $message = "Bonjour {$details['nom']},\n\n";
            $message .= "Votre réservation pour le livre '{$details['titre']}' a été validée.\n";
            $message .= "Vous pouvez récupérer votre livre à partir du {$pickupDate} (jour ouvrable), de 8h00 à 17h30, à la bibliothèque.\n\nCordialement,\nL'équipe Bibliothèque Acacia";
            $headers = "From: no-reply@bibliotheque-acacia.com\r\n";
            $headers .= "Reply-To: support@bibliotheque-acacia.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (mail($to, $subject, $message, $headers)) {
                $response['message'] = 'Réservation validée et email envoyé avec succès.';
            } else {
                error_log("Échec de l'envoi de l'email à {$to} pour la réservation {$reservationId}");
                $response['message'] = "Réservation validée, mais échec de l'envoi de l'email.";
            }

            $response['redirect'] = "admin_message.php?email=" . urlencode($to) . "&subject=" . urlencode($subject);
        } else {
            error_log("Détails manquants pour l'email de la réservation {$reservationId}: " . json_encode($details));
            $response['message'] = "Réservation validée, mais impossible de récupérer les détails pour l'email.";
        }
    } else {
        // messages courts pour les autres actions
        if ($action === 'cancel') $response['message'] = 'Réservation annulée avec succès.';
        if ($action === 'take') $response['message'] = 'Réservation marquée comme prise.';
        if ($action === 'return') $response['message'] = 'Réservation marquée comme rendue.';
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("update_reservation error: " . $e->getMessage());
}

ob_end_clean();
echo json_encode($response);
exit;
