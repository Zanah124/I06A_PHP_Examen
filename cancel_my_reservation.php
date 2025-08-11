
<?php
ob_start(); // Start output buffering to prevent unexpected output
header('Content-Type: application/json');
session_start();
require_once "reservations.php";

$response = ['success' => false, 'message' => 'Erreur inconnue'];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        throw new Exception('Utilisateur non connecté');
    }

    $userId = $_SESSION['user']['id'];
    $reservationId = $_POST['reservation_id'] ?? null;

    if ($reservationId === null) {
        throw new Exception('ID de réservation manquant');
    }

    $reservationId = (int)$reservationId;

    if ($reservationId <= 0) {
        throw new Exception('ID de réservation invalide');
    }

    $reservationsModel = new Reservations();
    $reservation = $reservationsModel->getReservationById($reservationId);

    if (!$reservation) {
        throw new Exception('Réservation introuvable');
    }

    // Verify that the reservation belongs to the current user and is in 'en attente' status
    if ($reservation['user_id'] !== $userId || $reservation['statut'] !== 'en attente') {
        throw new Exception('Vous ne pouvez pas annuler cette réservation');
    }

    // Update the reservation status to 'annulee'
    $data = [
        'user_id' => $reservation['user_id'],
        'livre_id' => $reservation['livre_id'],
        'date_reservation' => $reservation['date_reservation'],
        'statut' => 'annulee',
        'is_viewed' => 1 // Mark as viewed since it's cancelled
    ];

    if (!$reservationsModel->updateReservation($reservationId, $data)) {
        throw new Exception('Erreur lors de l\'annulation de la réservation');
    }

    $response['success'] = true;
    $response['message'] = 'Réservation annulée avec succès';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Clean output buffer and send JSON response
ob_end_clean();
echo json_encode($response);
exit;
?>
