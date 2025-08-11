<?php
session_start();
require_once "users.php";
require_once "livres.php";
require_once "reservations.php";
require_once "archive.php";


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Instantiate models
$usersModel = new Users();
$livresModel = new Livres();
$reservationsModel = new Reservations();
$archiveModel = new Archive();

// Fetch counts
$totalUsers = $usersModel->getUserCount();
$totalLivres = $livresModel->getLivreCount();
$totalReservations = $reservationsModel->getReservationCount();
$totalMessages = $archiveModel->getMessageCount();

$userName = $_SESSION['user']['nom'] ?? 'Administrateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Bibliothèque en Ligne</title>
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 20px 0;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0,0,0,0.3);
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(231, 76, 60, 0.3);
            margin-bottom: 30px;
        }

        .sidebar-logo {
            color: #e74c3c;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-badge {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 10px;
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
            background-color: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
            border-left-color: #e74c3c;
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

        .admin-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #e74c3c, #c0392b);
        }

        .admin-title {
            color: #2c3e50;
            font-size: 2.8em;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .admin-subtitle {
            color: #7f8c8d;
            font-size: 1.2em;
        }

        .admin-welcome {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .admin-welcome::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .welcome-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .welcome-text {
            font-size: 1.5em;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .welcome-subtitle {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .admin-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
        }

        .admin-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(231, 76, 60, 0.1), transparent);
            transition: left 0.6s;
        }

        .admin-card:hover::before {
            left: 100%;
        }

        .admin-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(231, 76, 60, 0.2);
            text-decoration: none;
            color: inherit;
        }

        .card-icon {
            font-size: 3.5em;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card-title {
            font-size: 1.4em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .card-description {
            color: #7f8c8d;
            line-height: 1.6;
            font-size: 1em;
        }

        .stats-overview {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .stats-title {
            color: #2c3e50;
            font-size: 1.5em;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            transition: transform 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #1a1a1a;
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

            .admin-grid {
                grid-template-columns: 1fr;
            }

            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
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
                <i class="fas fa-shield-alt"></i>
                Admin Panel
                <span class="admin-badge">ADMIN</span>
            </a>
        </div>
        <ul class="sidebar-nav">
            <li><a href="index.php"><i class="fas fa-home"></i>Accueil</a></li>
            <li><a href="admin.php" class="active"><i class="fas fa-tachometer-alt"></i>Dashboard Admin</a></li>
            <li><a href="create_livre.php"><i class="fas fa-plus-circle"></i>Ajouter Livre</a></li>
            <li><a href="manage_reservations.php"><i class="fas fa-calendar-alt"></i>Gérer Réservations</a></li>
            <li><a href="admin_message.php"><i class="fas fa-comments"></i>Communications</a></li>
            <li><a href="dashboard.php"><i class="fas fa-user"></i>Mon Compte</a></li>
            <li><a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i>Déconnexion</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="admin-header">
            <h1 class="admin-title">Panel Administrateur</h1>
            <p class="admin-subtitle">Gérez votre bibliothèque en ligne</p>
        </div>

        <div class="admin-welcome">
            <div class="welcome-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="welcome-text">Bienvenue, <?php echo htmlspecialchars($userName); ?> !</div>
            <div class="welcome-subtitle">Vous avez accès à tous les outils d'administration</div>
        </div>

        <div class="stats-overview">
            <h3 class="stats-title">Aperçu Rapide</h3>
            <div class="quick-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($totalLivres); ?></div>
                    <div class="stat-label">Livres Totaux</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($totalReservations); ?></div>
                    <div class="stat-label">Réservations</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($totalUsers); ?></div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($totalMessages); ?></div>
                    <div class="stat-label">Messages</div>
                </div>
            </div>
        </div>

        <div class="admin-grid">
            <a href="create_livre.php" class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-book-medical"></i>
                </div>
                <h5 class="card-title">Ajouter un Livre</h5>
                <p class="card-description">Ajoutez de nouveaux livres à votre collection et gérez l'inventaire de la bibliothèque.</p>
            </a>

            <a href="manage_reservations.php" class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h5 class="card-title">Gérer les Réservations</h5>
                <p class="card-description">Consultez, approuvez ou rejetez les réservations des utilisateurs en temps réel.</p>
            </a>

            <a href="admin_message.php" class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <h5 class="card-title">Communications</h5>
                <p class="card-description">Gérez les messages des utilisateurs et communiquez avec la communauté.</p>
            </a>

            <a href="list_livres.php" class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-books"></i>
                </div>
                <h5 class="card-title">Bibliothèque</h5>
                <p class="card-description">Parcourez tous les livres disponibles et gérez leur statut et informations.</p>
            </a>

            <a href="dashboard.php" class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h5 class="card-title">Mon Dashboard</h5>
                <p class="card-description">Accédez à votre tableau de bord personnel et consultez vos statistiques.</p>
            </a>

            <a href="index.php" class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-home"></i>
                </div>
                <h5 class="card-title">Retour à l'Accueil</h5>
                <p class="card-description">Retournez à la page principale de la bibliothèque pour une vue utilisateur.</p>
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
            const cards = document.querySelectorAll('.admin-card');
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