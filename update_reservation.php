<?php
header('Content-Type: application/json');
session_start();
require_once "reservations.php";

$response = ['success' => false, 'message' => 'Accès non autorisé'];

if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    $reservationId = $_POST['reservation_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($reservationId && in_array($action, ['validate', 'cancel'])) {
        $reservationsModel = new Reservations();
        $reservation = $reservationsModel->getReservationById($reservationId);

        if ($reservation) {
            $newStatus = ($action === 'validate') ? 'validee' : 'annulee';
            $data = [
                'user_id' => $reservation['user_id'],
                'livre_id' => $reservation['livre_id'],
                'date_reservation' => $reservation['date_reservation'],
                'statut' => $newStatus
            ];
            if ($reservationsModel->updateReservation($reservationId, $data)) {
                $response['success'] = true;
                $response['message'] = 'Statut mis à jour avec succès.';
            } else {
                $response['message'] = 'Erreur lors de la mise à jour du statut.';
            }
        } else {
            $response['message'] = 'Réservation introuvable.';
        }
    } else {
        $response['message'] = 'Paramètres invalides.';
    }
}

echo json_encode($response);
?>