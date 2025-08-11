
<?php
ob_start(); // Prévenir les sorties parasites
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

// Récupérer le nombre de nouvelles notifications
$newNotificationsCount = $reservationsModel->countNewNotifications($userId);

try {
    $reservations = $reservationsModel->getReservationsByUserId($userId);
    $allReservations = $reservationsModel->getAllReservations();
    $user = $usersModel->getUserById($userId);

    if (!$user) {
        throw new Exception('Utilisateur introuvable');
    }

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
} catch (Exception $e) {
    error_log("Erreur dans dashboard.php : " . $e->getMessage());
    $stats = ['validee' => 0, 'en attente' => 0, 'annulee' => 0];
    $userReservationsCount = 0;
    $allReservationsCount = 0;
    $user = ['nom' => 'Utilisateur', 'email' => 'Non spécifié', 'profil' => ''];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Bibliothèque en Ligne</title>
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

        .profile-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .profile-card:hover {
            transform: translateY(-5px);
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 4px solid rgba(255,255,255,0.3);
            transition: transform 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.1);
        }

        .profile-name {
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .profile-email {
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-title {
            font-size: 1.1em;
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
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

            .stats-grid {
                grid-template-columns: 1fr;
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
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
            <li><a href="list_livres.php"><i class="fas fa-book-open"></i>Livres</a></li>
            <li><a href="my_reservations.php"><i class="fas fa-calendar-check"></i>Mes Réservations</a></li>
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
            <h1 class="dashboard-title">Tableau de Bord</h1>
            <p class="dashboard-subtitle">Bienvenue sur votre espace personnel, <?php echo htmlspecialchars($user['nom'] ?? 'Utilisateur'); ?></p>
        </div>

        <div class="profile-card" ondblclick="window.location.href='profile.php'">
            <?php if (!empty($user['profil']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/' . $user['profil'])): ?>
                <img src="assets/<?php echo htmlspecialchars($user['profil']); ?>" alt="Photo de profil" class="profile-image">
            <?php else: ?>
                <div class="profile-image" style="background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user" style="font-size: 3em; color: rgba(255,255,255,0.7);"></i>
                </div>
            <?php endif; ?>
            <div class="profile-name"><?php echo htmlspecialchars($user['nom'] ?? 'Utilisateur'); ?></div>
            <div class="profile-email"><?php echo htmlspecialchars($user['email'] ?? 'Non spécifié'); ?></div>
            <a href="edit_profile.php" class="btn btn-light">Modifier Profil</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-title">Réservations Validées</div>
                <div class="stat-value"><?php echo $stats['validee']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-title">En Attente</div>
                <div class="stat-value"><?php echo $stats['en attente']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-title">Annulées</div>
                <div class="stat-value"><?php echo $stats['annulee']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-circle"></i></div>
                <div class="stat-title">Mes Réservations</div>
                <div class="stat-value"><?php echo $userReservationsCount; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="stat-title">Total Système</div>
                <div class="stat-value"><?php echo $allReservationsCount; ?></div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="my_reservations.php" class="btn-custom">
                <i class="fas fa-eye"></i> Voir toutes mes réservations
            </a>
            <a href="list_livres.php" class="btn-custom">
                <i class="fas fa-search"></i> Parcourir les livres
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }

        function logout() {
            console.log('Fonction logout appelée');
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                fetch('logout.php')
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error('Erreur réseau: ' + response.status + ' - Réponse: ' + text);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'index.php';
                        } else {
                            alert('Erreur : ' + (data.message || 'Erreur inconnue'));
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors de la déconnexion:', error);
                        alert('Une erreur est survenue lors de la déconnexion: ' + error.message);
                    });
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

            const cards = document.querySelectorAll('.stat-card, .profile-card');
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
<?php
ob_end_flush(); // Libérer la mémoire tampon
?>
