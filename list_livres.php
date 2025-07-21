<?php
session_start();
require_once "livres.php";
require_once "reservations.php";

$livresModel = new Livres();
$reservationsModel = new Reservations();
$livres = $livresModel->getAllLivres();

// Calcul du nombre d'exemplaires disponibles par livre
$availableCopies = [];
foreach ($livres as $livre) {
    $livreId = $livre['id'];
    $reservations = $reservationsModel->getReservationsByLivreId($livreId);
    $reservedCount = 0;
    foreach ($reservations as $reservation) {
        if ($reservation['statut'] === 'validee') {
            $reservedCount++;
        }
    }
    $availableCopies[$livreId] = max(0, $livre['nb_exemplaires'] - $reservedCount);
}

// Calcul du nombre total de livres disponibles
$livresDisponibles = array_sum(array_map(function($livre) use ($availableCopies) {
    return $availableCopies[$livre['id']] > 0 ? 1 : 0;
}, $livres));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Livres - Bibliothèque en Ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-grid {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            padding: 20px;
        }
        .product-card {
            width: 200px;
            text-align: center;
            margin: 10px;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }
        .product-image img {
            max-width: 100%;
            height: auto;
        }
        .product-title {
            font-size: 1.1em;
            font-weight: bold;
            margin: 10px 0;
        }
        .product-author {
            color: #666;
            margin-bottom: 10px;
        }
        .btn-reserve {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .btn-reserve:hover {
            background-color: #0056b3;
        }
        .availability {
            text-align: center;
            margin-top: 20px;
            font-weight: bold;
        }
        .copy-info {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .btn {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .btn a {
            padding: 6px 10px;
            text-decoration: none;
            color: #fff;
            border-radius: 3px;
            border: 1px solid #fff;
        }
        .btn a:hover {
            background: #fff;
            color: greenyellow;
            transition: .4s;
        }
    </style>
</head>
<body>
    <a href="index.php" class="btn">Retour</a>
    <div class="container mt-5">
        <h1 class="text-center">Liste des Livres</h1>
        <div class="product-grid">
            <?php foreach ($livres as $livre): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($livre['photo'] ?? 'assets/default-book.jpg'); ?>" alt="Photo du livre">
                    </div>
                    <div class="product-title"><?php echo htmlspecialchars($livre['titre']); ?></div>
                    <div class="product-author">Auteur : <?php echo htmlspecialchars($livre['auteur']); ?></div>
                    <button class="btn-reserve" onclick="reserveBook('<?php echo htmlspecialchars($livre['titre']); ?>', <?php echo $livre['id']; ?>)">Réserver</button>
                    <div class="copy-info">
                        Exemplaires totaux : <?php echo htmlspecialchars($livre['nb_exemplaires']); ?><br>
                        Disponibles : <?php echo htmlspecialchars($availableCopies[$livre['id']]); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="availability">
            Nombre de livres disponibles : <?php echo $livresDisponibles; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Logique de réservation (placeholder avec appel côté serveur)
        function reserveBook(titre, livreId) {
            if (confirm('Voulez-vous réserver ' + titre + ' ?')) {
                fetch('reserve.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'livre_id=' + encodeURIComponent(livreId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Réservation réussie pour ' + titre);
                        location.reload(); // Recharger la page pour mettre à jour les disponibilités
                    } else {
                        alert('Erreur : ' + (data.message || 'Réservation impossible'));
                    }
                })
                .catch(error => console.error('Erreur:', error));
            }
        }
    </script>
</body>
</html>