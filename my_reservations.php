<?php
ob_start(); 
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

$newNotificationsCount = $reservationsModel->countNewNotifications($userId);

$reservations = $reservationsModel->getReservationsByUserId($userId);

$bookDetails = [];
foreach ($reservations as $reservation) {
    if ($reservation['livre_id']) {
        $book = $livresModel->getLivreById($reservation['livre_id']);
        if ($book) {
            $bookDetails[] = [
                'reservation_id' => $reservation['id'],
                'titre' => $book['titre'],
                'auteur' => $book['auteur'],
                'photo' => $book['photo'],
                'statut' => $reservation['statut'],
                'user_id' => $reservation['user_id'],
                'date_reservation' => $reservation['date_reservation']
            ];
        }
    }
}

$userName = $_SESSION['user']['nom'] ?? 'Utilisateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - Bibliothèque en Ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            padding: 20px 0;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
        }

        .sidebar-logo {
            color: #ecf0f1;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
        }

        .sidebar-nav li {
            margin-bottom: 5px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border-left-color: #3498db;
        }

        .sidebar-nav i {
            width: 20px;
            margin-right: 15px;
            text-align: center;
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        .dashboard-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .dashboard-title {
            color: #2c3e50;
            font-size: 2.5em;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .dashboard-subtitle {
            color: #7f8c8d;
            font-size: 1.1em;
        }

        .reservation-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .reservation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.6s;
        }

        .reservation-card:hover::before {
            left: 100%;
        }

        .reservation-image img {
            max-width: 100px;
            height: auto;
            border-radius: 10px;
            margin-right: 20px;
            border: 2px solid rgba(102, 126, 234, 0.3);
        }

        .reservation-details {
            flex-grow: 1;
        }

        .reservation-title {
            font-size: 1.4em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .reservation-author {
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .reservation-status {
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 10px;
        }

        .reservation-date {
            color: #95a5a6;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-validee { 
            background: #d4edda; 
            color: #155724; 
        }
        
        .status-en-attente { 
            background: #fff3cd; 
            color: #856404; 
        }
        
        .status-annulee { 
            background: #f8d7da; 
            color: #721c24; 
        }

        .reservation-actions {
            margin-left: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-cancel {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .btn-cancel:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .no-reservations {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.2em;
            padding: 40px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .no-reservations i {
            font-size: 3em;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #2c3e50;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }

        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .sidebar-toggle {
                display: block;
            }

            .reservation-card {
                flex-direction: column;
                align-items: flex-start;
                text-align: center;
            }

            .reservation-image img {
                margin-bottom: 15px;
                margin-right: 0;
            }

            .reservation-actions {
                margin-left: 0;
                margin-top: 15px;
                width: 100%;
            }

            .btn-cancel {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Annulation en cours...</p>
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-logo">
                <i class="fas fa-book"></i>
                Bibliothèque Acacia
            </a>
        </div>
        <ul class="sidebar-nav">
            <li><a href="index.php"><i class="fas fa-home"></i>Accueil</a></li>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
            <li><a href="list_livres.php"><i class="fas fa-book-open"></i>Livres</a></li>
            <li><a href="my_reservations.php" class="active"><i class="fas fa-calendar-check"></i>Mes Réservations</a></li>
             <li>
                <a href="notifications.php">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($newNotificationsCount > 0): ?>
                        <span class="notification-badge"><?php echo htmlspecialchars($newNotificationsCount); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="edit_profile.php"><i class="fas fa-user-edit"></i>Modifier Profil</a></li>
            <li><a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i>Déconnexion</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Mes Réservations</h1>
            <p class="dashboard-subtitle">Consultez l'état de vos réservations de livres</p>
        </div>

        <?php if (empty($bookDetails)): ?>
            <div class="no-reservations">
                <i class="fas fa-calendar-times"></i>
                <h3>Aucune réservation trouvée</h3>
                <p>Vous n'avez pas encore effectué de réservation.</p>
                <a href="list_livres.php" class="btn btn-primary mt-3">
                    <i class="fas fa-book"></i> Découvrir nos livres
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($bookDetails as $detail): ?>
                <div class="reservation-card">
                    <div class="reservation-image">
                        <?php if ($detail['photo'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/' . $detail['photo'])): ?>
                             <img src="/assets/<?php echo htmlspecialchars($detail['photo']); ?>" alt="<?php echo htmlspecialchars($detail['titre']); ?>">
                        <?php else: ?>
                             <img src="/assets/images/default-book.jpg" alt="Photo du livre par défaut" onerror="this.parentNode.innerHTML='<i class=\'fas fa-book\'></i>'">
                            <?php endif; ?>
                    </div>
                    <div class="reservation-details">
                        <h5 class="reservation-title"><?php echo htmlspecialchars($detail['titre']); ?></h5>
                        <p class="reservation-author">
                            <i class="fas fa-user"></i> Auteur : <?php echo htmlspecialchars($detail['auteur']); ?>
                        </p>
                        <?php if ($detail['date_reservation']): ?>
                        <p class="reservation-date">
                            <i class="fas fa-calendar"></i> Réservé le : <?php echo date('d/m/Y', strtotime($detail['date_reservation'])); ?>
                        </p>
                        <?php endif; ?>
                        <div class="reservation-status">
                            <i class="fas fa-info-circle"></i> Statut : 
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $detail['statut'])); ?>">
                                <?php echo htmlspecialchars($detail['statut']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($detail['statut'] === 'en attente'): ?>
                    <div class="reservation-actions">
                        <button class="btn-cancel" onclick="cancelReservation(<?php echo $detail['reservation_id']; ?>)">
                            <i class="fas fa-times"></i>
                            Annuler la réservation
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="d-flex justify-content-center mt-4">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Retour à l'accueil
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }

        function logout() {
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                fetch('logout.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'index.php';
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        }

        function cancelReservation(reservationId) {
            if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
                // Afficher l'overlay de chargement
                document.getElementById('loadingOverlay').style.display = 'flex';
                
                // Désactiver le bouton pendant le traitement
                const button = event.target.closest('.btn-cancel');
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Annulation...';

                fetch('cancel_my_reservation.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded' 
                    },
                    body: 'reservation_id=' + encodeURIComponent(reservationId)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    // Masquer l'overlay de chargement
                    document.getElementById('loadingOverlay').style.display = 'none';
                    
                    if (data.success) {
                        // Afficher un message de succès avec animation
                        const successAlert = document.createElement('div');
                        successAlert.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                        successAlert.style.zIndex = '9999';
                        successAlert.innerHTML = '<i class="fas fa-check-circle me-2"></i>Réservation annulée avec succès !';
                        document.body.appendChild(successAlert);
                        
                        // Recharger la page après 2 secondes
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        alert('Erreur : ' + (data.message || 'Erreur inconnue lors de l\'annulation'));
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-times"></i> Annuler la réservation';
                    }
                })
                .catch(error => {
                    // Masquer l'overlay de chargement
                    document.getElementById('loadingOverlay').style.display = 'none';
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de l\'annulation: ' + error.message);
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-times"></i> Annuler la réservation';
                });
            }
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.reservation-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>