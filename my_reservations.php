<?php
session_start();
require_once "reservations.php";
require_once "livres.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$reservationsModel = new Reservations();
$livresModel = new Livres();
$userId = $_SESSION['user']['id'];
$userName = $_SESSION['user']['nom'] ?? 'Utilisateur';

// Récupérer toutes les réservations de l'utilisateur
$userReservations = $reservationsModel->getReservationsByUserId($userId);

// Organiser les réservations par statut
$reservationsByStatus = [
    'en_attente' => [],
    'validee' => [],
    'prise' => [],
    'rendu' => [],
    'annulee' => []
];

foreach ($userReservations as $reservation) {
    $status = str_replace(' ', '_', $reservation['statut']);
    if (isset($reservationsByStatus[$status])) {
        $reservationsByStatus[$status][] = $reservation;
    }
}

// Calculer les statistiques
$stats = [
    'en_attente' => count($reservationsByStatus['en_attente']),
    'validee' => count($reservationsByStatus['validee']),
    'prise' => count($reservationsByStatus['prise']),
    'rendu' => count($reservationsByStatus['rendu']),
    'annulee' => count($reservationsByStatus['annulee']),
    'total' => count($userReservations)
];

// Vérifier les livres en retard
$overdueBooks = [];
foreach ($reservationsByStatus['prise'] as $reservation) {
    if ($reservation['date_limite_retour']) {
        $daysLeft = $reservationsModel->getDaysUntilDue($reservation['date_limite_retour']);
        if ($daysLeft < 0) {
            $reservation['days_overdue'] = abs($daysLeft);
            $overdueBooks[] = $reservation;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - Bibliothèque</title>
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

        .header {
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
        }

        .header-title {
            font-size: 2.2em;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            color: #7f8c8d;
            font-size: 1.1em;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-back {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
        }

        .alert-overdue {
            background: #ffe6cc;
            border: 1px solid #ff9500;
            color: #cc7a00;
            margin-bottom: 20px;
            border-radius: 15px;
            padding: 20px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .section-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }

        .section-icon {
            font-size: 2em;
            margin-right: 15px;
            color: #667eea;
        }

        .section-title {
            font-size: 1.8em;
            color: #2c3e50;
            font-weight: 600;
        }

        .reservation-item {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border-left: 5px solid transparent;
        }

        .reservation-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .reservation-item.pending { border-left-color: #f39c12; }
        .reservation-item.validated { border-left-color: #2ecc71; }
        .reservation-item.taken { border-left-color: #3498db; }
        .reservation-item.returned { border-left-color: #9b59b6; }
        .reservation-item.cancelled { border-left-color: #e74c3c; }

        .book-title {
            font-size: 1.3em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .book-author {
            color: #7f8c8d;
            margin-bottom: 15px;
        }

        .reservation-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
            font-size: 0.9em;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-validated { background: #d1edff; color: #0c5460; }
        .status-taken { background: #cce5ff; color: #004085; }
        .status-returned { background: #e2d5f5; color: #6f42c1; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .overdue-warning {
            background: #ffeccc;
            color: #cc7a00;
            padding: 10px 15px;
            border-radius: 10px;
            margin-top: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .days-remaining {
            background: #e8f5e8;
            color: #2d5a2d;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .no-reservations {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.2em;
            padding: 60px 40px;
            background: #f8f9fa;
            border-radius: 15px;
            border: 2px dashed #dee2e6;
        }

        .no-reservations i {
            font-size: 4em;
            margin-bottom: 20px;
            color: #bdc3c7;
        }

        .btn-browse {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-browse:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.3);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .main-content {
                padding: 0 20px;
            }

            .stats-row {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }

            .reservation-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div>
                <h1 class="header-title">
                    <i class="fas fa-bookmark"></i>
                    Mes Réservations
                </h1>
                <p class="user-info">Bienvenue, <?php echo htmlspecialchars($userName); ?></p>
            </div>
            <div class="header-actions">
                <a href="list_livres.php" class="btn-back">
                    <i class="fas fa-book"></i> Parcourir les livres
                </a>
                <a href="index.php" class="btn-back">
                    <i class="fas fa-home"></i> Accueil
                </a>
            </div>
        </div>
    </header>

    <div class="main-content">
        <?php if (!empty($overdueBooks)): ?>
        <div class="alert alert-overdue">
            <h5><i class="fas fa-exclamation-triangle"></i> Livres en Retard</h5>
            <p>Vous avez <strong><?php echo count($overdueBooks); ?></strong> livre(s) en retard. Veuillez les retourner rapidement à la bibliothèque.</p>
        </div>
        <?php endif; ?>

        <!-- Statistiques des réservations -->
        <div class="stats-row">
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $stats['en_attente']; ?></div>
                <div class="stat-label">En Attente</div>
            </div>
            <div class="stat-card validated">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['validee']; ?></div>
                <div class="stat-label">Validées</div>
            </div>
            <div class="stat-card taken">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-number"><?php echo $stats['prise']; ?></div>
                <div class="stat-label">Empruntées</div>
            </div>
            <div class="stat-card returned">
                <div class="stat-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <div class="stat-number"><?php echo $stats['rendu']; ?></div>
                <div class="stat-label">Rendues</div>
            </div>
            <div class="stat-card cancelled">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['annulee']; ?></div>
                <div class="stat-label">Annulées</div>
            </div>
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>

        <?php if (empty($userReservations)): ?>
        <div class="section-card">
            <div class="no-reservations">
                <i class="fas fa-bookmark"></i>
                <h3>Aucune réservation</h3>
                <p>Vous n'avez encore fait aucune réservation.</p>
                <a href="list_livres.php" class="btn-browse">
                    <i class="fas fa-search"></i> Parcourir les livres
                </a>
            </div>
        </div>
        <?php else: ?>

        <!-- Réservations en attente -->
        <?php if (!empty($reservationsByStatus['en_attente'])): ?>
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="section-title">Réservations en Attente (<?php echo count($reservationsByStatus['en_attente']); ?>)</div>
            </div>
            <?php foreach ($reservationsByStatus['en_attente'] as $reservation): ?>
            <div class="reservation-item pending">
                <div class="book-title"><?php echo htmlspecialchars($reservation['titre'] ?? 'Titre inconnu'); ?></div>
                <div class="book-author">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($reservation['auteur'] ?? 'Auteur inconnu'); ?>
                </div>
                <div class="reservation-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        Demandé le: <?php echo date('d/m/Y à H:i', strtotime($reservation['date_reservation'])); ?>
                    </div>
                </div>
                <div class="status-badge status-pending">
                    <i class="fas fa-clock"></i>
                    En attente de validation
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Réservations validées -->
        <?php if (!empty($reservationsByStatus['validee'])): ?>
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="section-title">Réservations Validées (<?php echo count($reservationsByStatus['validee']); ?>)</div>
            </div>
            <?php foreach ($reservationsByStatus['validee'] as $reservation): ?>
            <div class="reservation-item validated">
                <div class="book-title"><?php echo htmlspecialchars($reservation['titre'] ?? 'Titre inconnu'); ?></div>
                <div class="book-author">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($reservation['auteur'] ?? 'Auteur inconnu'); ?>
                </div>
                <div class="reservation-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        Réservé le: <?php echo date('d/m/Y à H:i', strtotime($reservation['date_reservation'])); ?>
                    </div>
                </div>
                <div class="status-badge status-validated">
                    <i class="fas fa-check-circle"></i>
                    Prêt à être retiré
                </div>
                <div style="margin-top: 15px; background: #d1f2eb; color: #0d5b4d; padding: 10px; border-radius: 8px;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Votre livre est prêt !</strong> Vous pouvez venir le récupérer à la bibliothèque.
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Livres empruntés -->
        <?php if (!empty($reservationsByStatus['prise'])): ?>
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="section-title">Livres Empruntés (<?php echo count($reservationsByStatus['prise']); ?>)</div>
            </div>
            <?php foreach ($reservationsByStatus['prise'] as $reservation): ?>
            <?php 
            $daysLeft = $reservationsModel->getDaysUntilDue($reservation['date_limite_retour']);
            $isOverdue = $daysLeft < 0;
            ?>
            <div class="reservation-item taken">
                <div class="book-title"><?php echo htmlspecialchars($reservation['titre'] ?? 'Titre inconnu'); ?></div>
                <div class="book-author">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($reservation['auteur'] ?? 'Auteur inconnu'); ?>
                </div>
                <div class="reservation-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar-check"></i>
                        Emprunté le: <?php echo date('d/m/Y à H:i', strtotime($reservation['date_prise'])); ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-times"></i>
                        À retourner le: <?php echo date('d/m/Y', strtotime($reservation['date_limite_retour'])); ?>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="status-badge status-taken">
                        <i class="fas fa-book-open"></i>
                        Emprunté
                    </div>
                    <?php if ($isOverdue): ?>
                        <span class="overdue-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            En retard de <?php echo abs($daysLeft); ?> jour(s)
                        </span>
                    <?php else: ?>
                        <span class="days-remaining">
                            <?php echo $daysLeft; ?> jour(s) restant(s)
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($isOverdue): ?>
                <div class="overdue-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Ce livre est en retard ! Veuillez le retourner rapidement à la bibliothèque.
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Livres rendus -->
        <?php if (!empty($reservationsByStatus['rendu'])): ?>
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <div class="section-title">Livres Rendus (<?php echo count($reservationsByStatus['rendu']); ?>)</div>
            </div>
            <?php foreach ($reservationsByStatus['rendu'] as $reservation): ?>
            <?php 
            $dureeEmprunt = '';
            if ($reservation['date_prise'] && $reservation['date_retour']) {
                $datePrise = new DateTime($reservation['date_prise']);
                $dateRetour = new DateTime($reservation['date_retour']);
                $diff = $datePrise->diff($dateRetour);
                $dureeEmprunt = $diff->days . ' jour(s)';
            }
            ?>
            <div class="reservation-item returned">
                <div class="book-title"><?php echo htmlspecialchars($reservation['titre'] ?? 'Titre inconnu'); ?></div>
                <div class="book-author">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($reservation['auteur'] ?? 'Auteur inconnu'); ?>
                </div>
                <div class="reservation-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar-check"></i>
                        Emprunté le: <?php echo $reservation['date_prise'] ? date('d/m/Y', strtotime($reservation['date_prise'])) : 'Non défini'; ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-undo"></i>
                        Rendu le: <?php echo $reservation['date_retour'] ? date('d/m/Y', strtotime($reservation['date_retour'])) : 'Non défini'; ?>
                    </div>
                    <?php if ($dureeEmprunt): ?>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        Durée: <?php echo $dureeEmprunt; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="status-badge status-returned">
                    <i class="fas fa-undo"></i>
                    Rendu
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Réservations annulées -->
        <?php if (!empty($reservationsByStatus['annulee'])): ?>
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="section-title">Réservations Annulées (<?php echo count($reservationsByStatus['annulee']); ?>)</div>
            </div>
            <?php foreach ($reservationsByStatus['annulee'] as $reservation): ?>
            <div class="reservation-item cancelled">
                <div class="book-title"><?php echo htmlspecialchars($reservation['titre'] ?? 'Titre inconnu'); ?></div>
                <div class="book-author">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($reservation['auteur'] ?? 'Auteur inconnu'); ?>
                </div>
                <div class="reservation-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        Demandé le: <?php echo date('d/m/Y à H:i', strtotime($reservation['date_reservation'])); ?>
                    </div>
                </div>
                <div class="status-badge status-cancelled">
                    <i class="fas fa-times-circle"></i>
                    Annulée
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>

        <div class="d-flex justify-content-center mt-4 gap-3">
            <a href="list_livres.php" class="btn-browse">
                <i class="fas fa-search"></i> Parcourir les livres
            </a>
            <a href="index.php" class="btn-back">
                <i class="fas fa-home"></i> Retour à l'accueil
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation d'apparition des éléments
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.section-card, .stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });

            // Animation pour les éléments de réservation
            setTimeout(() => {
                const items = document.querySelectorAll('.reservation-item');
                items.forEach((item, index) => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        item.style.transition = 'all 0.4s ease';
                        item.style.opacity = '1';
                        item.style.transform = 'translateX(0)';
                    }, 50 * index);
                });
            }, 500);
        });

        // Mise à jour automatique de l'heure restante (optionnel)
        function updateCountdowns() {
            const daysElements = document.querySelectorAll('.days-remaining');
            // Ici vous pourriez ajouter une logique pour mettre à jour en temps réel
            // les jours restants, mais ce n'est généralement pas nécessaire pour une bibliothèque
        }

        // Rafraîchir les données toutes les 5 minutes (optionnel)
        setInterval(() => {
            // Vous pourriez ajouter un appel AJAX pour rafraîchir les données
            // sans recharger la page entière
        }, 300000); // 5 minutes
    </script>
</body>
</html>