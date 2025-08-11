
<?php
session_start();
require_once "reservations.php";
require_once "livres.php";

if (!isset($_SESSION['user']) || !$_SESSION['user']['id'] || $_SESSION['user']['role'] === 'admin') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$userName = $_SESSION['user']['nom'] ?? 'Utilisateur';
$reservationsModel = new Reservations();
$livresModel = new Livres();

// Récupérer le nombre de nouvelles notifications
$newNotificationsCount = $reservationsModel->countNewNotifications($userId);

// Marquer les notifications comme vues
if ($newNotificationsCount > 0) {
    $reservationsModel->markNotificationsAsViewed($userId);
}

// Récupérer uniquement les réservations validées de l'utilisateur
$reservations = $reservationsModel->getReservationsByUserId($userId);
$validatedReservations = array_filter($reservations, function($reservation) {
    return $reservation['statut'] === 'validee';
});

$bookDetails = [];
foreach ($validatedReservations as $reservation) {
    if ($reservation['livre_id']) {
        $book = $livresModel->getLivreById($reservation['livre_id']);
        if ($book) {
            $bookDetails[] = [
                'reservation_id' => $reservation['id'],
                'titre' => $book['titre'],
                'auteur' => $book['auteur'],
                'photo' => $book['photo'],
                'date_reservation' => $reservation['date_reservation']
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
    <title>Mes Notifications - Bibliothèque en Ligne</title>
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

        .notification-badge {
            display: inline-block;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 5px 10px;
            font-size: 0.8em;
            margin-left: 10px;
            min-width: 24px;
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

        .notification-card {
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

        .notification-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .notification-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.6s;
        }

        .notification-card:hover::before {
            left: 100%;
        }

        .notification-image {
            width: 100px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            margin-right: 20px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            overflow: hidden;
        }

        .notification-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        .notification-image i {
            font-size: 3em;
            color: #7f8c8d;
        }

        .notification-details {
            flex-grow: 1;
        }

        .notification-title {
            font-size: 1.4em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .notification-message {
            font-size: 1.2em;
            color: #27ae60;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .notification-author {
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .notification-date {
            color: #95a5a6;
            font-size: 0.9em;
        }

        .no-notifications {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.2em;
            padding: 40px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .no-notifications i {
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

            .notification-card {
                flex-direction: column;
                align-items: flex-start;
                text-align: center;
            }

            .notification-image {
                margin-bottom: 15px;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

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
            <li><a href="my_reservations.php"><i class="fas fa-calendar-check"></i>Mes Réservations</a></li>
            <li>
                <a href="notifications.php" class="active">
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
            <h1 class="dashboard-title">Mes Notifications</h1>
            <p class="dashboard-subtitle">Consultez les notifications concernant vos réservations</p>
        </div>

        <?php if (empty($bookDetails)): ?>
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <h3>Aucune notification</h3>
                <p>Vous n'avez aucune réservation validée pour le moment.</p>
                <a href="list_livres.php" class="btn btn-primary mt-3">
                    <i class="fas fa-book"></i> Découvrir nos livres
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($bookDetails as $detail): ?>
                <div class="notification-card">
                    <div class="notification-image">
                        <?php if ($detail['photo'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/' . $detail['photo'])): ?>
                            <img src="/assets/<?php echo htmlspecialchars($detail['photo']); ?>" alt="<?php echo htmlspecialchars($detail['titre']); ?>">
                        <?php else: ?>
                            <img src="/assets/images/default-book.jpg" alt="Photo du livre par défaut" onerror="this.parentNode.innerHTML='<i class=\'fas fa-book\'></i>'">
                        <?php endif; ?>
                    </div>
                    <div class="notification-details">
                        <h5 class="notification-title"><?php echo htmlspecialchars($detail['titre']); ?></h5>
                        <p class="notification-message">
                            <i class="fas fa-check-circle"></i> Votre réservation est validée !
                        </p>
                        <p class="notification-author">
                            <i class="fas fa-user"></i> Auteur : <?php echo htmlspecialchars($detail['auteur']); ?>
                        </p>
                        <?php if ($detail['date_reservation']): ?>
                        <p class="notification-date">
                            <i class="fas fa-calendar"></i> Réservé le : <?php echo date('d/m/Y', strtotime($detail['date_reservation'])); ?>
                        </p>
                        <?php endif; ?>
                    </div>
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

        function updateNotificationBadge() {
            fetch('get_notifications_count.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.count > 0) {
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.textContent = data.count;
                            badge.style.display = 'inline-block';
                        } else {
                            const notificationLink = document.querySelector('a[href="notifications.php"]');
                            if (notificationLink) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'notification-badge';
                                newBadge.textContent = data.count;
                                notificationLink.appendChild(newBadge);
                            }
                        }
                    } else {
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Erreur lors de la récupération des notifications:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateNotificationBadge();

            const cards = document.querySelectorAll('.notification-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            document.addEventListener('click', function(event) {
                const sidebar = document.getElementById('sidebar');
                const toggle = document.querySelector('.sidebar-toggle');
                
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                        sidebar.classList.remove('show');
                    }
                }
            });
        });
    </script>
</body>
</html>
