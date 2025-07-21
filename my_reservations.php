<?php
session_start();
require_once "livres.php";
require_once "reservations.php";

if (!isset($_SESSION['user']) || !$_SESSION['user']['id']) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$reservationsModel = new Reservations();
$livresModel = new Livres();

$reservations = $reservationsModel->getReservationsByUserId($userId);

$bookDetails = [];
foreach ($reservations as $reservation) {
    if ($reservation['livre_id']) {
        $book = $livresModel->getLivreById($reservation['livre_id']);
        if ($book) {
            $bookDetails[] = [
                'titre' => $book['titre'],
                'auteur' => $book['auteur'],
                'photo' => $book['photo'],
                'statut' => $reservation['statut'],
                'user_id' => $reservation['user_id']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - Bibliothèque en Ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .reservation-card {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .reservation-image img {
            max-width: 100px;
            height: auto;
            margin-right: 15px;
        }
        .reservation-details {
            flex-grow: 1;
        }
        .reservation-status {
            font-weight: bold;
            color: #28a745;
        }
        .status-pending { color: #ffc107; }
        .status-canceled { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Mes Réservations</h1>
        <?php if (empty($bookDetails)): ?>
            <p class="text-center">Aucune réservation trouvée.</p>
        <?php else: ?>
            <?php foreach ($bookDetails as $detail): ?>
                <div class="reservation-card">
                    <div class="reservation-image">
                        <img src="<?php echo htmlspecialchars($detail['photo'] ?? 'assets/default-book.jpg'); ?>" alt="Photo du livre">
                    </div>
                    <div class="reservation-details">
                        <h5><?php echo htmlspecialchars($detail['titre']); ?></h5>
                        <p>Auteur : <?php echo htmlspecialchars($detail['auteur']); ?></p>
                        <p class="reservation-status <?php echo strtolower(str_replace(' ', '-', $detail['statut'])); ?>">
                            Statut : <?php echo htmlspecialchars($detail['statut']); ?>
                        </p>
                        <p>Utilisateur : <?php echo htmlspecialchars($detail['user_id'] ? "Utilisateur ID: " . $detail['user_id'] : "Non spécifié"); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <p class="mt-3 text-center"><a href="index.php" class="btn btn-primary">Retour à l'accueil</a></p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>