<?php
session_start();
require_once "reservations.php";
require_once "users.php";

if (!isset($_SESSION['user']) || !$_SESSION['user']['id']) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$reservationsModel = new Reservations();
$usersModel = new Users();

$reservations = $reservationsModel->getReservationsByUserId($userId);
$allReservations = $reservationsModel->getAllReservations();

$stats = [
    'validee' => 0,
    'en attente' => 0,
    'annulee' => 0
];

foreach ($reservations as $reservation) {
    if (isset($stats[$reservation['statut']])) {
        $stats[$reservation['statut']]++;
    }
}

$userReservationsCount = count($reservations);
$allReservationsCount = count($allReservations);
$user = $usersModel->getUserById($userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Bibliothèque en Ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stat-card {
            background-color: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
        }
        .stat-title {
            font-size: 1.2em;
            color: #333;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .see-more-btn {
            display: block;
            margin: 0 auto;
            width: 200px;
        }
        .profile-card {
            background-color: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
        .profile-name {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Tableau de Bord</h1>
        <div class="profile-card" ondblclick="window.location.href='profile.php'">
            <?php if (!empty($user['profil'])): ?>
                <img src="<?php echo htmlspecialchars($user['profil']); ?>" alt="Photo de profil" class="profile-image">
            <?php else: ?>
                <div class="profile-image" style="background-color: #ddd; border-radius: 50%; width: 100px; height: 100px; margin: 0 auto;"></div>
            <?php endif; ?>
            <div class="profile-name"><?php echo htmlspecialchars($user['nom'] ?? 'Utilisateur'); ?></div>
            <div class="stat-title">Email: <?php echo htmlspecialchars($user['email'] ?? 'Non spécifié'); ?></div>
            <a href="edit_profile.php" class="btn btn-primary mt-2">Modifier Profil</a>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-title">Réservations en cours</div>
                    <div class="stat-value"><?php echo $stats['validee']; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-title">Réservations en attente</div>
                    <div class="stat-value"><?php echo $stats['en attente']; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-title">Réservations annulées</div>
                    <div class="stat-value"><?php echo $stats['annulee']; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-title">Mes Réservations</div>
                    <div class="stat-value"><?php echo $userReservationsCount; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-title">Total Réservations</div>
                    <div class="stat-value"><?php echo $allReservationsCount; ?></div>
                </div>
            </div>
        </div>
        <a href="my_reservations.php" class="btn btn-primary see-more-btn">Voir plus</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>