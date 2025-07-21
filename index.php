<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque Acacia - Livres et Culture</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
    <?php
    session_start();
    $loggedIn = isset($_SESSION['user']) && $_SESSION['user']['id'];
    $userName = $loggedIn ? ($_SESSION['user']['nom'] ?? 'Utilisateur') : '';
    $isAdmin = $loggedIn && $_SESSION['user']['role'] === 'admin';
    ?>
    <!-- App Screen -->
    <div id="app-container">
        <header>
            <div class="header-content">
                <div class="logo-container">
                    <img src="assets/logo.png" alt="Bibliothèque Acacia Logo" class="logo-img">
                </div>
                <nav id="main-nav">
                    <a href="index.php" class="nav-tab active">Accueil</a>
                    <a href="list_livres.php" class="nav-tab">Livres</a>
                    <a href="my_reservations.php" class="nav-tab">Mes Réservations</a>
                    <?php if ($loggedIn): ?>
                        <a href="dashboard.php" class="nav-tab">Dashboard</a>
                        <?php if ($isAdmin): ?>
                            <a href="admin.php" class="nav-tab">Admin Panel</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </nav>
                <div class="user-menu">
                    <span id="user-greeting"><?php echo $loggedIn ? "Bonjour, $userName" : ''; ?></span>
                    <a id="login-button" href="login.php" class="btn" <?php echo $loggedIn ? 'style="display:none;"' : ''; ?>>Connexion</a>
                    <button id="logout-button" <?php echo $loggedIn ? '' : 'style="display:none;"'; ?>>Déconnexion</button>
                </div>
            </div>
        </header>
        <main>
            <div id="home-view">
                <section class="hero">
                    <div class="hero-content">
                        <h2>Un Monde de Savoir à Votre Portée.</h2>
                        <p>Découvrez, lisez et réservez vos prochains livres favoris.</p>
                        <a href="list_livres.php" id="go-to-books-btn" class="cta-button">Voir tous les livres</a>
                    </div>
                </section>
                <section id="info-section" class="content-section">
                    <h3>Bienvenue à la Bibliothèque Acacia</h3>
                    <p class="intro-text">Votre destination pour la connaissance et l'aventure. Parcourez notre collection, réservez en ligne et venez simplement récupérer vos livres. Nous nous engageons à rendre la culture accessible à tous.</p>
                </section>
            </div>
        </main>
    </div>
    <script>
        // Gérer la déconnexion
        document.getElementById('logout-button').addEventListener('click', function() {
            fetch('logout.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'index.php';
                    }
                })
                .catch(error => console.error('Erreur:', error));
        });
    </script>
</body>
</html>