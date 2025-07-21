<?php
session_start();
require_once "users.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$userName = $_SESSION['user']['nom'] ?? 'Administrateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin - Bibliothèque en Ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-card {
            background-color: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
        }
        .admin-card:hover {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Tableau de Bord Administrateur</h1>
        <h4 class="text-center mb-4">Bienvenue, <?php echo htmlspecialchars($userName); ?> !</h4>
        <div class="row">
            <div class="col-md-4">
                <a href="create_livre.php" class="admin-card">
                    <h5>Ajouter un Livre</h5>
                    <p>Gérez les nouveaux livres de la bibliothèque.</p>
                </a>
            </div>
            <div class="col-md-4">
                <a href="manage_reservations.php" class="admin-card">
                    <h5>Gérer les Réservations</h5>
                    <p>Voir et gérer les réservations des utilisateurs.</p>
                </a>
            </div>
            <div class="col-md-4">
                <a href="index.php" class="admin-card">
                    <h5>Retour à l'Accueil</h5>
                    <p>Revenir à la page principale.</p>
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>