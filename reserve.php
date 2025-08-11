<?php
header('Content-Type: application/json');
session_start();
require_once "reservations.php";
require_once "livres.php";

$response = ['success' => false, 'message' => 'Utilisateur non connecté ou erreur'];

if (isset($_SESSION['user']) && $_SESSION['user']['id']) {
    $livreId = $_POST['livre_id'] ?? null;
    $userId = $_SESSION['user']['id'];

    if ($livreId) {
        $livresModel = new Livres();
        $reservationsModel = new Reservations();
        $livre = $livresModel->getLivreById($livreId);

        if ($livre && $livre['nb_exemplaires'] > 0) {
            // Check for existing reservations by the user for this book
            $existingUserReservations = $reservationsModel->getReservationsByUserId($userId);
            $hasReservation = false;
            foreach ($existingUserReservations as $reservation) {
                if ($reservation['livre_id'] == $livreId && in_array($reservation['statut'], ['en attente', 'validee'])) {
                    $hasReservation = true;
                    break;
                }
            }

            if ($hasReservation) {
                $response['message'] = 'Vous avez déjà une réservation pour ce livre.';
            } else {
                $existingReservations = $reservationsModel->getReservationsByLivreId($livreId);
                $reservedCount = 0;
                foreach ($existingReservations as $reservation) {
                    if ($reservation['statut'] === 'validee') {
                        $reservedCount++;
                    }
                }

                if ($livre['nb_exemplaires'] > $reservedCount) {
                    $data = [
                        'user_id' => $userId,
                        'livre_id' => $livreId,
                        'date_reservation' => date('Y-m-d H:i:s'),
                        'statut' => 'en attente'
                    ];
                    if ($reservationsModel->createReservation($data)) {
                        $response['success'] = true;
                        $response['message'] = 'Réservation enregistrée avec succès en attente de validation.';
                    } else {
                        $response['message'] = 'Erreur lors de l\'enregistrement de la réservation.';
                    }
                } else {
                    $response['message'] = 'Plus d\'exemplaires disponibles pour ce livre.';
                }
            }
        } else {
            $response['message'] = 'Livre introuvable ou plus d\'exemplaires disponibles.';
        }
    } else {
        $response['message'] = 'ID du livre non spécifié.';
    }
} else {
    $response['message'] = 'Utilisateur non connecté.';
}

echo json_encode($response);
?>