<?php
session_start();
require_once "reservations.php";
require_once "livres.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$reservationsModel = new Reservations();
$livresModel = new Livres();
$reservations = $reservationsModel->getAllReservations();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Réservations - Bibliothèque en Ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .reservation-table {
            margin-top: 20px;
        }
        .action-btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Gérer les Réservations</h1>
        <?php if (empty($reservations)): ?>
            <p class="text-center">Aucune réservation trouvée.</p>
        <?php else: ?>
            <table class="table reservation-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre du Livre</th>
                        <th>Utilisateur</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <?php if ($reservation['statut'] === 'en attente'): ?>
                            <?php
                            $livre = $reservation['livre_id'] ? $livresModel->getLivreById($reservation['livre_id']) : null;
                            $userId = $reservation['user_id'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['id']); ?></td>
                                <td><?php echo htmlspecialchars($livre ? $livre['titre'] : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($userId ? "Utilisateur ID: $userId" : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_reservation'] ?? 'Non définie'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['statut']); ?></td>
                                <td>
                                    <button class="btn btn-success action-btn" onclick="validateReservation(<?php echo $reservation['id']; ?>)">Valider</button>
                                    <button class="btn btn-danger action-btn" onclick="cancelReservation(<?php echo $reservation['id']; ?>)">Annuler</button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p class="mt-3 text-center"><a href="admin.php" class="btn btn-secondary">Retour au Tableau de Bord</a></p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateReservation(reservationId) {
            if (confirm('Valider cette réservation ?')) {
                fetch('update_reservation.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'reservation_id=' + encodeURIComponent(reservationId) + '&action=validate'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Réservation validée avec succès.');
                        location.reload();
                    } else {
                        alert('Erreur : ' + data.message);
                    }
                })
                .catch(error => console.error('Erreur:', error));
            }
        }

        function cancelReservation(reservationId) {
            if (confirm('Annuler cette réservation ?')) {
                fetch('update_reservation.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'reservation_id=' + encodeURIComponent(reservationId) + '&action=cancel'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Réservation annulée avec succès.');
                        location.reload();
                    } else {
                        alert('Erreur : ' + data.message);
                    }
                })
                .catch(error => console.error('Erreur:', error));
            }
        }
    </script>
</body>
</html>