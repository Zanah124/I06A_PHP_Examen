<?php
ob_start(); // Démarrer la mise en mémoire tampon pour éviter les sorties inattendues
header('Content-Type: application/json');
session_start();
require_once "reservations.php";

$response = ['success' => false, 'message' => 'Erreur inconnue'];

try {
    // Vérifier si l'utilisateur est un admin
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
        throw new Exception('Accès non autorisé');
    }

    // Vérifier les paramètres POST
    if (!isset($_POST['reservation_id']) || !isset($_POST['action'])) {
        throw new Exception('Paramètres manquants');
    }

    $reservationId = (int)$_POST['reservation_id'];
    $action = $_POST['action'];

    if ($reservationId <= 0 || !in_array($action, ['validate', 'cancel'])) {
        throw new Exception('Paramètres invalides');
    }

    $reservationsModel = new Reservations();
    $reservation = $reservationsModel->getReservationById($reservationId);

    if (!$reservation) {
        throw new Exception('Réservation introuvable');
    }

    // Mettre à jour la réservation
    $data = [
        'user_id' => $reservation['user_id'],
        'livre_id' => $reservation['livre_id'],
        'date_reservation' => $reservation['date_reservation'],
        'statut' => $action === 'validate' ? 'validee' : 'annulee'
    ];

    if (!$reservationsModel->updateReservation($reservationId, $data)) {
        throw new Exception('Erreur lors de la mise à jour de la réservation');
    }

    if ($action === 'validate') {
        // Récupérer les détails pour l'email
        $details = $reservationsModel->getReservationDetails($reservationId);
        if ($details && isset($details['email'], $details['nom'], $details['titre'])) {
            // Calculer le prochain jour ouvrable
            $currentDate = new DateTime();
            $currentDate->modify('+1 day');
            while ($currentDate->format('N') >= 6) { // 6 = Samedi, 7 = Dimanche
                $currentDate->modify('+1 day');
            }
            $pickupDate = $currentDate->format('d/m/Y');

            // Préparer l'email
            $to = $details['email'];
            $subject = 'Validation de votre réservation - Bibliothèque Acacia';
            $message = "Bonjour {$details['nom']},\n\n";
            $message .= "Nous vous informons que votre réservation pour le livre '{$details['titre']}' a été validée.\n";
            $message .= "Vous pouvez récupérer votre livre à partir du {$pickupDate} (jour ouvrable), de 8h00 à 17h30, à la bibliothèque.\n";
            $message .= "Merci de votre confiance !\n\nCordialement,\nL'équipe Bibliothèque Acacia";
            $headers = "From: no-reply@bibliotheque-acacia.com\r\n";
            $headers .= "Reply-To: support@bibliotheque-acacia.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            // Envoyer l'email
            if (mail($to, $subject, $message, $headers)) {
                $response['message'] = 'Réservation validée et email envoyé avec succès.';
            } else {
                // Log l'erreur pour débogage
                error_log("Échec de l'envoi de l'email à {$to} pour la réservation {$reservationId}");
                $response['message'] = 'Réservation validée, mais échec de l\'envoi de l\'email.';
            }

            // Ajouter l'URL de redirection pour la validation
            $response['redirect'] = "admin_message.php?email=" . urlencode($details['email']) . "&subject=" . urlencode($subject);
        } else {
            error_log("Détails manquants pour la réservation {$reservationId}: " . json_encode($details));
            $response['message'] = 'Réservation validée, mais impossible de récupérer les détails pour l\'email.';
        }
    } else {
        $response['message'] = 'Réservation annulée avec succès.';
    }

    $response['success'] = true;
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Vider la mémoire tampon et envoyer la réponse JSON
ob_end_clean();
echo json_encode($response);
exit;
?>