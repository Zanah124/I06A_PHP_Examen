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
$userName = $_SESSION['user']['nom'] ?? 'Administrateur';

// Utiliser la nouvelle méthode pour récupérer les statistiques
$reservationStats = $reservationsModel->getReservationStats();

// Organiser les réservations par statut
$reservationsByStatus = [
    'en_attente' => [],
    'validee' => [],
    'prise' => [],
    'rendu' => [],
    'annulee' => []
];

foreach ($reservations as $reservation) {
    $status = str_replace(' ', '_', $reservation['statut']);
    if (isset($reservationsByStatus[$status])) {
        $reservationsByStatus[$status][] = $reservation;
    }
}

// Récupérer les livres en retard
$overdueBooks = $reservationsModel->getOverdueBooks();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Réservations - Bibliothèque en Ligne</title>
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

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.pending::before { background: #f39c12; }
        .stat-card.validated::before { background: #2ecc71; }
        .stat-card.taken::before { background: #3498db; }
        .stat-card.returned::before { background: #9b59b6; }
        .stat-card.cancelled::before { background: #e74c3c; }
        .stat-card.total::before { background: #34495e; }
        .stat-card.overdue::before { background: #e67e22; }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }

        .stat-card.pending .stat-icon { color: #f39c12; }
        .stat-card.validated .stat-icon { color: #2ecc71; }
        .stat-card.taken .stat-icon { color: #3498db; }
        .stat-card.returned .stat-icon { color: #9b59b6; }
        .stat-card.cancelled .stat-icon { color: #e74c3c; }
        .stat-card.total .stat-icon { color: #34495e; }
        .stat-card.overdue .stat-icon { color: #e67e22; }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 1.1em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .table-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .table-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(231, 76, 60, 0.1), transparent);
            transition: left 0.6s;
        }

        .table-card:hover::before {
            left: 100%;
        }

        .table-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(231, 76, 60, 0.2);
        }

        .table-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }

        .table-icon {
            font-size: 2.5em;
            margin-right: 15px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .table-title {
            font-size: 1.8em;
            color: #2c3e50;
            font-weight: 600;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-validated { background: #d1edff; color: #0c5460; }
        .status-taken { background: #cce5ff; color: #004085; }
        .status-returned { background: #e2d5f5; color: #6f42c1; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .overdue-badge {
            background: #ffeccc;
            color: #cc7a00;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
            margin-left: 10px;
        }

        .days-left {
            font-size: 0.9em;
            color: #7f8c8d;
            font-style: italic;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .table th, .table td {
            padding: 15px;
            vertical-align: middle;
            border: none;
        }

        .table tbody tr {
            transition: background-color 0.3s ease;
            border-bottom: 1px solid #ecf0f1;
        }

        .table tbody tr:hover {
            background-color: rgba(231, 76, 60, 0.05);
        }

        .btn-success {
            background: #2ecc71;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            transition: all 0.3s ease;
            margin-right: 5px;
        }

        .btn-success:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }

        .btn-primary {
            background: #3498db;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            transition: all 0.3s ease;
            margin-right: 5px;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .btn-warning {
            background: #f39c12;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            transition: all 0.3s ease;
            margin-right: 5px;
        }

        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.4);
        }

        .btn-danger {
            background: #e74c3c;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .btn-secondary {
            background: #7f8c8d;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .no-reservations {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.2em;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px dashed #dee2e6;
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

        .alert-overdue {
            background: #ffe6cc;
            border: 1px solid #ff9500;
            color: #cc7a00;
            margin-bottom: 20px;
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

            .stats-row {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 15px;
            }

            .table {
                font-size: 0.9em;
            }

            .table th, .table td {
                padding: 10px;
            }

            .admin-title {
                font-size: 2.2em;
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
            <li><a href="admin.php"><i class="fas fa-tachometer-alt"></i>Dashboard Admin</a></li>
            <li><a href="create_livre.php"><i class="fas fa-plus-circle"></i>Ajouter Livre</a></li>
            <li><a href="manage_reservations.php" class="active"><i class="fas fa-calendar-alt"></i>Gérer Réservations</a></li>
            <li><a href="admin_message.php"><i class="fas fa-comments"></i>Communications</a></li>
            <li><a href="dashboard.php"><i class="fas fa-user"></i>Mon Dashboard</a></li>
            <li><a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i>Déconnexion</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="admin-header">
            <h1 class="admin-title">Gérer les Réservations</h1>
            <p class="admin-subtitle">Consultez et gérez les réservations des utilisateurs</p>
        </div>

        <?php if (!empty($overdueBooks)): ?>
        <div class="alert alert-overdue">
            <h5><i class="fas fa-exclamation-triangle"></i> Livres en Retard</h5>
            <p>Il y a <strong><?php echo count($overdueBooks); ?></strong> livre(s) en retard qui doivent être retournés.</p>
        </div>
        <?php endif; ?>

        <!-- Statistiques des réservations -->
        <div class="stats-row">
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $reservationStats['en_attente']; ?></div>
                <div class="stat-label">En Attente</div>
            </div>
            <div class="stat-card validated">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $reservationStats['validee']; ?></div>
                <div class="stat-label">Validées</div>
            </div>
            <div class="stat-card taken">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-number"><?php echo $reservationStats['prise']; ?></div>
                <div class="stat-label">Prises</div>
            </div>
            <div class="stat-card returned">
                <div class="stat-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <div class="stat-number"><?php echo $reservationStats['rendu']; ?></div>
                <div class="stat-label">Rendues</div>
            </div>
            <div class="stat-card cancelled">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number"><?php echo $reservationStats['annulee']; ?></div>
                <div class="stat-label">Annulées</div>
            </div>
            <div class="stat-card overdue">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?php echo count($overdueBooks); ?></div>
                <div class="stat-label">En Retard</div>
            </div>
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-number"><?php echo $reservationStats['total']; ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>

        <!-- Réservations en attente -->
        <?php if (!empty($reservationsByStatus['en_attente'])): ?>
        <div class="table-card">
            <div class="table-header">
                <div class="table-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="table-title">Réservations en Attente (<?php echo count($reservationsByStatus['en_attente']); ?>)</div>
            </div>
            <div class="table-responsive">
                <table class="table">
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
                        <?php foreach ($reservationsByStatus['en_attente'] as $reservation): ?>
                            <?php
                            $livre = $reservation['livre_id'] ? $livresModel->getLivreById($reservation['livre_id']) : null;
                            $userId = $reservation['user_id'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['id']); ?></td>
                                <td><?php echo htmlspecialchars($livre ? $livre['titre'] : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($userId ? "Utilisateur ID: $userId" : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_reservation'] ?? 'Non définie'); ?></td>
                                <td><span class="status-badge status-pending"><?php echo htmlspecialchars($reservation['statut']); ?></span></td>
                                <td>
                                    <button class="btn btn-success" onclick="validateReservation(<?php echo $reservation['id']; ?>)">
                                        <i class="fas fa-check"></i> Valider
                                    </button>
                                    <button class="btn btn-danger" onclick="cancelReservation(<?php echo $reservation['id']; ?>)">
                                        <i class="fas fa-times"></i> Annuler
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Réservations validées -->
        <?php if (!empty($reservationsByStatus['validee'])): ?>
        <div class="table-card">
            <div class="table-header">
                <div class="table-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="table-title">Réservations Validées (<?php echo count($reservationsByStatus['validee']); ?>)</div>
            </div>
            <div class="table-responsive">
                <table class="table">
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
                        <?php foreach ($reservationsByStatus['validee'] as $reservation): ?>
                            <?php
                            $livre = $reservation['livre_id'] ? $livresModel->getLivreById($reservation['livre_id']) : null;
                            $userId = $reservation['user_id'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['id']); ?></td>
                                <td><?php echo htmlspecialchars($livre ? $livre['titre'] : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($userId ? "Utilisateur ID: $userId" : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_reservation'] ?? 'Non définie'); ?></td>
                                <td><span class="status-badge status-validated"><?php echo htmlspecialchars($reservation['statut']); ?></span></td>
                                <td>
                                    <button class="btn btn-primary" onclick="markAsTaken(<?php echo $reservation['id']; ?>)">
                                        <i class="fas fa-book-open"></i> Marquer comme Pris
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Livres pris -->
        <?php if (!empty($reservationsByStatus['prise'])): ?>
        <div class="table-card">
            <div class="table-header">
                <div class="table-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="table-title">Livres Empruntés (<?php echo count($reservationsByStatus['prise']); ?>)</div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre du Livre</th>
                            <th>Utilisateur</th>
                            <th>Date de Prise</th>
                            <th>Date Limite</th>
                            <th>Temps Restant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservationsByStatus['prise'] as $reservation): ?>
                            <?php
                            $livre = $reservation['livre_id'] ? $livresModel->getLivreById($reservation['livre_id']) : null;
                            $userId = $reservation['user_id'];
                            $daysLeft = $reservationsModel->getDaysUntilDue($reservation['date_limite_retour']);
                            $isOverdue = $daysLeft < 0;
                            ?>
                            <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                <td><?php echo htmlspecialchars($reservation['id']); ?></td>
                                <td><?php echo htmlspecialchars($livre ? $livre['titre'] : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($userId ? "Utilisateur ID: $userId" : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_prise'] ? date('d/m/Y H:i', strtotime($reservation['date_prise'])) : 'Non définie'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_limite_retour'] ? date('d/m/Y', strtotime($reservation['date_limite_retour'])) : 'Non définie'); ?></td>
                                <td>
                                    <?php if ($isOverdue): ?>
                                        <span class="overdue-badge">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            <?php echo abs($daysLeft); ?> jour(s) de retard
                                        </span>
                                    <?php else: ?>
                                        <span class="days-left"><?php echo $daysLeft; ?> jour(s) restant(s)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-taken">
                                        <?php echo htmlspecialchars($reservation['statut']); ?>
                                        <?php if ($isOverdue): ?>
                                            <span class="overdue-badge">RETARD</span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-warning" onclick="markAsReturned(<?php echo $reservation['id']; ?>)">
                                        <i class="fas fa-undo"></i> Marquer comme Rendu
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Livres rendus -->
        <?php if (!empty($reservationsByStatus['rendu'])): ?>
        <div class="table-card">
            <div class="table-header">
                <div class="table-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <div class="table-title">Livres Rendus (<?php echo count($reservationsByStatus['rendu']); ?>)</div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre du Livre</th>
                            <th>Utilisateur</th>
                            <th>Date de Prise</th>
                            <th>Date de Retour</th>
                            <th>Durée d'Emprunt</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservationsByStatus['rendu'] as $reservation): ?>
                            <?php
                            $livre = $reservation['livre_id'] ? $livresModel->getLivreById($reservation['livre_id']) : null;
                            $userId = $reservation['user_id'];
                            $dureeEmprunt = '';
                            if ($reservation['date_prise'] && $reservation['date_retour']) {
                                $datePrise = new DateTime($reservation['date_prise']);
                                $dateRetour = new DateTime($reservation['date_retour']);
                                $diff = $datePrise->diff($dateRetour);
                                $dureeEmprunt = $diff->days . ' jour(s)';
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['id']); ?></td>
                                <td><?php echo htmlspecialchars($livre ? $livre['titre'] : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($userId ? "Utilisateur ID: $userId" : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_prise'] ? date('d/m/Y H:i', strtotime($reservation['date_prise'])) : 'Non définie'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_retour'] ? date('d/m/Y H:i', strtotime($reservation['date_retour'])) : 'Non définie'); ?></td>
                                <td><?php echo htmlspecialchars($dureeEmprunt); ?></td>
                                <td><span class="status-badge status-returned"><?php echo htmlspecialchars($reservation['statut']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Réservations annulées -->
        <?php if (!empty($reservationsByStatus['annulee'])): ?>
        <div class="table-card">
            <div class="table-header">
                <div class="table-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="table-title">Réservations Annulées (<?php echo count($reservationsByStatus['annulee']); ?>)</div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre du Livre</th>
                            <th>Utilisateur</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservationsByStatus['annulee'] as $reservation): ?>
                            <?php
                            $livre = $reservation['livre_id'] ? $livresModel->getLivreById($reservation['livre_id']) : null;
                            $userId = $reservation['user_id'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['id']); ?></td>
                                <td><?php echo htmlspecialchars($livre ? $livre['titre'] : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($userId ? "Utilisateur ID: $userId" : 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_reservation'] ?? 'Non définie'); ?></td>
                                <td><span class="status-badge status-cancelled"><?php echo htmlspecialchars($reservation['statut']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($reservations)): ?>
        <div class="table-card">
            <div class="no-reservations">
                <i class="fas fa-calendar-times" style="font-size: 3em; color: #bdc3c7; margin-bottom: 20px;"></i>
                <h3>Aucune réservation trouvée</h3>
                <p>Il n'y a actuellement aucune réservation dans le système.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-center mt-4">
            <a href="admin.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour au Tableau de Bord
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

        function validateReservation(reservationId) {
            if (confirm('Valider cette réservation ?')) {
                updateReservationStatus(reservationId, 'validate');
            }
        }

        function cancelReservation(reservationId) {
            if (confirm('Annuler cette réservation ?')) {
                updateReservationStatus(reservationId, 'cancel');
            }
        }

        function markAsTaken(reservationId) {
            if (confirm('Marquer ce livre comme pris ?')) {
                updateReservationStatus(reservationId, 'take');
            }
        }

        function markAsReturned(reservationId) {
            if (confirm('Marquer ce livre comme rendu ?')) {
                updateReservationStatus(reservationId, 'return');
            }
        }

        function updateReservationStatus(reservationId, action) {
            const loadingAlert = document.createElement('div');
            loadingAlert.className = 'alert alert-info position-fixed top-0 start-50 translate-middle-x mt-3';
            loadingAlert.style.zIndex = '9999';
            
            let actionText = '';
            switch(action) {
                case 'validate': actionText = 'Validation'; break;
                case 'cancel': actionText = 'Annulation'; break;
                case 'take': actionText = 'Marquage comme pris'; break;
                case 'return': actionText = 'Marquage comme rendu'; break;
            }
            
            loadingAlert.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${actionText} en cours...`;
            document.body.appendChild(loadingAlert);

            fetch('update_reservation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'reservation_id=' + encodeURIComponent(reservationId) + '&action=' + encodeURIComponent(action)
            })
            .then(response => {
                document.body.removeChild(loadingAlert);
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Erreur réseau: ' + response.status + ' - Réponse: ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification(data.message || `${actionText} effectuée avec succès.`, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification('Erreur : ' + (data.message || 'Erreur inconnue'), 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification(`Une erreur est survenue lors du ${actionText.toLowerCase()}: ` + error.message, 'error');
            });
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
            `;
            
            Object.assign(notification.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                background: type === 'success' ? 'linear-gradient(135deg, #27ae60, #2ecc71)' : 'linear-gradient(135deg, #e74c3c, #c0392b)',
                color: 'white',
                padding: '15px 20px',
                borderRadius: '10px',
                boxShadow: '0 10px 30px rgba(0,0,0,0.3)',
                zIndex: '9999',
                display: 'flex',
                alignItems: 'center',
                gap: '10px',
                animation: 'slideInRight 0.3s ease-out'
            });
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 4000);
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

        // Animation d'apparition des cartes
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.table-card, .stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });

        // Ajouter les animations CSS pour les notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes slideOutRight {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>