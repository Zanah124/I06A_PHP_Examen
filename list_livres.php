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

$loggedIn = isset($_SESSION['user']) && $_SESSION['user']['id'];
$userName = $loggedIn ? ($_SESSION['user']['nom'] ?? 'Utilisateur') : '';
$isAdmin = $loggedIn && $_SESSION['user']['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque - Livres Disponibles</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Header moderne */
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

        .btn-edit {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-edit:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(243, 156, 18, 0.3);
        }

        /* Section de recherche */
        .search-section {
            background: white;
            margin: 0 30px 40px;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .search-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .search-title {
            font-size: 1.5em;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .search-subtitle {
            color: #7f8c8d;
        }

        .search-container {
            position: relative;
            max-width: 600px;
            margin: 0 auto 25px;
        }

        .search-input {
            width: 100%;
            padding: 18px 60px 18px 25px;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            font-size: 1.1em;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }

        .search-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 1.2em;
        }

        .filter-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e9ecef;
            background: white;
            color: #7f8c8d;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-btn.active,
        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        /* Statistiques */
        .stats-bar {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            margin: 0 30px 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .stats-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-number {
            font-size: 1.8em;
            font-weight: bold;
        }

        .stat-label {
            font-size: 1em;
            opacity: 0.9;
        }

        /* Grille des livres */
        .books-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .book-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .book-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.6s;
        }

        .book-card:hover::before {
            left: 100%;
        }

        .book-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }

        .book-image {
            width: 100%;
            height: 250px;
            border-radius: 15px;
            object-fit: cover;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-size: 3em;
        }

        .book-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 15px;
        }

        .book-title {
            font-size: 1.3em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .book-author {
            color: #7f8c8d;
            font-size: 1em;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .book-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .availability-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .total-copies {
            font-size: 0.9em;
            color: #7f8c8d;
        }

        .available-copies {
            font-weight: 600;
            color: #27ae60;
        }

        .unavailable {
            color: #e74c3c;
        }

        .reserve-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            border: none;
            border-radius: 15px;
            color: white;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .reserve-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.3);
        }

        .reserve-btn:disabled {
            background: #7f8c8d;
            cursor: not-allowed;
        }

        .no-results {
            text-align: center;
            margin: 40px 0;
            color: #7f8c8d;
            font-size: 1.2em;
            display: none;
        }

        .no-results i {
            font-size: 2em;
            margin-bottom: 15px;
            color: #e74c3c;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .books-container,
            .search-section,
            .stats-bar {
                margin: 0 20px;
                padding: 20px;
            }

            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .header-title {
                font-size: 1.8em;
            }
        }

        @media (max-width: 576px) {
            .books-grid {
                grid-template-columns: 1fr;
            }

            .search-input {
                padding: 15px 50px 15px 20px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1 class="header-title">
                <i class="fas fa-book"></i>
                Bibliothèque Acacia
            </h1>
            <div class="header-actions">
                <?php if ($loggedIn): ?>
                    <span class="text-muted">Bienvenue, <?php echo htmlspecialchars($userName); ?>!</span>
                    <a href="logout.php" class="btn-back">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-back">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </a>
                    <a href="register.php" class="btn-back">
                        <i class="fas fa-user-plus"></i> Inscription
                    </a>
                <?php endif; ?>
                <a href="index.php" class="btn-back">
                    <i class="fas fa-home"></i> Accueil
                </a>
            </div>
        </div>
    </header>

    <div class="search-section">
        <div class="search-header">
            <h2 class="search-title">Trouvez votre livre</h2>
            <p class="search-subtitle">Utilisez la barre de recherche ou les filtres pour parcourir notre collection</p>
        </div>
        <div class="search-container">
            <input type="text" class="search-input" id="searchInput" placeholder="Tapez votre recherche...">
            <i class="fas fa-search search-icon"></i>
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">Tous</button>
            <button class="filter-btn" data-filter="available">Disponibles</button>
            <button class="filter-btn" data-filter="unavailable">Non disponibles</button>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stats-content">
            <div class="stat-item">
                <i class="fas fa-book fa-2x"></i>
                <div>
                    <div class="stat-number"><?php echo count($livres); ?></div>
                    <div class="stat-label">Livres au total</div>
                </div>
            </div>
            <div class="stat-item">
                <i class="fas fa-check-circle fa-2x"></i>
                <div>
                    <div class="stat-number"><?php echo $livresDisponibles; ?></div>
                    <div class="stat-label">Livres disponibles</div>
                </div>
            </div>
            <div class="stat-item">
                <i class="fas fa-users fa-2x"></i>
                <div>
                    <div class="stat-number" id="visibleCount"><?php echo count($livres); ?></div>
                    <div class="stat-label">Livres affichés</div>
                </div>
            </div>
        </div>
    </div>

    <div class="books-container">
        <div class="books-grid" id="booksGrid">
            <?php foreach ($livres as $livre): ?>
                <div class="book-card" 
                     data-title="<?php echo htmlspecialchars(strtolower($livre['titre'])); ?>"
                     data-author="<?php echo htmlspecialchars(strtolower($livre['auteur'])); ?>"
                     data-category="<?php echo htmlspecialchars(strtolower($livre['categorie'])); ?>"
                     data-available="<?php echo $availableCopies[$livre['id']] > 0 ? 'true' : 'false'; ?>">
                    <div class="book-image">
                        <?php if ($livre['photo'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/' . $livre['photo'])): ?>
                            <img src="/assets/<?php echo htmlspecialchars($livre['photo']); ?>" alt="<?php echo htmlspecialchars($livre['titre']); ?>">
                        <?php else: ?>
                            <i class="fas fa-book"></i>
                        <?php endif; ?>
                    </div>
                    <h3 class="book-title"><?php echo htmlspecialchars($livre['titre']); ?></h3>
                    <p class="book-author">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($livre['auteur']); ?>
                    </p>
                    <div class="book-info">
                        <div class="availability-info">
                            <span class="total-copies">Total : <?php echo htmlspecialchars($livre['nb_exemplaires']); ?> exemplaires</span>
                            <span class="available-copies <?php echo $availableCopies[$livre['id']] == 0 ? 'unavailable' : ''; ?>">
                                Disponible : <?php echo $availableCopies[$livre['id']]; ?>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="reserve-btn" 
                                onclick="reserveBook('<?php echo htmlspecialchars($livre['titre']); ?>', '<?php echo $livre['id']; ?>')"
                                <?php echo $availableCopies[$livre['id']] == 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-bookmark"></i> Réserver ce livre
                        </button>
                        <?php if ($isAdmin): ?>
                            <a href="edit_livre.php?id=<?php echo $livre['id']; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="no-results" id="noResults">
            <i class="fas fa-exclamation-circle"></i>
            <p>Aucun livre ne correspond à votre recherche.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const searchInput = document.getElementById('searchInput');
        const booksGrid = document.getElementById('booksGrid');
        const noResults = document.getElementById('noResults');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const visibleCount = document.getElementById('visibleCount');
        let currentFilter = 'all';

        // Fonction de recherche et filtrage
        function filterBooks() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const bookCards = document.querySelectorAll('.book-card');
            let visibleBooks = 0;

            bookCards.forEach(card => {
                const title = card.dataset.title;
                const author = card.dataset.author;
                const category = card.dataset.category;
                const isAvailable = card.dataset.available === 'true';

                // Vérifier si le livre correspond à la recherche
                const matchesSearch = !searchTerm || 
                    title.includes(searchTerm) || 
                    author.includes(searchTerm) || 
                    category.includes(searchTerm);

                // Vérifier si le livre correspond au filtre
                const matchesFilter = currentFilter === 'all' || 
                    (currentFilter === 'available' && isAvailable) ||
                    (currentFilter === 'unavailable' && !isAvailable);

                // Afficher ou masquer le livre
                if (matchesSearch && matchesFilter) {
                    card.style.display = 'block';
                    visibleBooks++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Mettre à jour le compteur
            visibleCount.textContent = visibleBooks;

            // Afficher le message "aucun résultat" si nécessaire
            if (visibleBooks === 0) {
                booksGrid.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                booksGrid.style.display = 'grid';
                noResults.style.display = 'none';
            }
        }

        // Écouteur d'événement pour la recherche
        searchInput.addEventListener('input', filterBooks);

        // Écouteurs d'événements pour les filtres
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Retirer la classe active de tous les boutons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Ajouter la classe active au bouton cliqué
                button.classList.add('active');
                
                // Mettre à jour le filtre actuel
                currentFilter = button.dataset.filter;
                
                // Filtrer les livres
                filterBooks();
            });
        });

        // Fonction de réservation
        function reserveBook(titre, livreId) {
            <?php if (!$loggedIn): ?>
                if (confirm('Vous devez être connecté pour réserver un livre. Voulez-vous vous connecter maintenant ?')) {
                    window.location.href = 'login.php';
                }
                return;
            <?php endif; ?>

            if (confirm('Voulez-vous réserver "' + titre + '" ?')) {
                // Désactiver le bouton pendant la requête
                const buttons = document.querySelectorAll(`[onclick*="${livreId}"]`);
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Réservation...';
                });

                fetch('reserve.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'livre_id=' + encodeURIComponent(livreId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Animation de succès
                        buttons.forEach(btn => {
                            btn.innerHTML = '<i class="fas fa-check"></i> Réservé !';
                            btn.style.background = 'linear-gradient(135deg, #27ae60, #2ecc71)';
                        });
                        
                        // Notification de succès
                        showNotification('Réservation réussie pour "' + titre + '"', 'success');
                        
                        // Recharger après 2 secondes
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        // Réactiver le bouton en cas d'erreur
                        buttons.forEach(btn => {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-bookmark"></i> Réserver ce livre';
                        });
                        
                        showNotification('Erreur : ' + (data.message || 'Réservation impossible'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    
                    // Réactiver le bouton en cas d'erreur
                    buttons.forEach(btn => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-bookmark"></i> Réserver ce livre';
                    });
                    
                    showNotification('Erreur de connexion', 'error');
                });
            }
        }

        // Fonction pour afficher les notifications
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
            `;
            
            // Styles pour la notification
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
            
            // Supprimer la notification après 4 secondes
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 4000);
        }

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

        // Animation d'entrée pour les cartes
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.book-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });

        // Raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K pour focus sur la recherche
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
            
            // Escape pour vider la recherche
            if (e.key === 'Escape' && document.activeElement === searchInput) {
                searchInput.value = '';
                filterBooks();
            }
        });

        // Indice pour les raccourcis clavier
        searchInput.placeholder = 'Tapez votre recherche... (Ctrl+K pour accès rapide)';
    </script>
</body>
</html>