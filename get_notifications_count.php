```php
<?php
ob_start();
header('Content-Type: application/json');
session_start();
require_once "reservations.php";

$response = ['success' => false, 'count' => 0, 'message' => 'Erreur inconnue'];

try {
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        throw new Exception('Utilisateur non connecté');
    }

    $userId = $_SESSION['user']['id'];
    $reservationsModel = new Reservations();
    $count = $reservationsModel->countNewNotifications($userId);

    $response['success'] = true;
    $response['count'] = (int)$count;
    $response['message'] = 'Nombre de notifications récupéré avec succès';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
exit;
?>