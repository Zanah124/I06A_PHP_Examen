<?php
session_start();
require_once "users.php";

if (!isset($_SESSION['user']) || !$_SESSION['user']['id']) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$usersModel = new Users();
$user = $usersModel->getUserById($userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Bibliothèque en Ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Mon Profil</h1>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informations Personnelles</h5>
                        <p class="card-text"><strong>Nom :</strong> <?php echo htmlspecialchars($user['nom'] ?? 'Non spécifié'); ?></p>
                        <p class="card-text"><strong>Email :</strong> <?php echo htmlspecialchars($user['email'] ?? 'Non spécifié'); ?></p>
                        <p class="card-text"><strong>ID :</strong> <?php echo htmlspecialchars($user['id'] ?? 'Non spécifié'); ?></p>
                        <p class="card-text"><strong>Rôle :</strong> <?php echo htmlspecialchars($user['role'] ?? 'Non spécifié'); ?></p>
                        <a href="edit_profile.php" class="btn btn-primary">Modifier le Profil</a>
                        <a href="dashboard.php" class="btn btn-secondary ms-2">Retour</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>