<?php
session_start();
$loggedIn = isset($_SESSION['user']) && $_SESSION['user']['id'];
$userName = $loggedIn ? ($_SESSION['user']['nom'] ?? 'Utilisateur') : '';
$isAdmin = $loggedIn && $_SESSION['user']['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque Acacia - Livres et Culture</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        /* Header moderne */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 50px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .logo-text {
            font-family: 'Oswald', sans-serif;
            font-size: 1.8em;
            font-weight: 700;
            color: #2c3e50;
            text-decoration: none;
        }

        #main-nav {
            display: flex;
            gap: 30px;
        }

        .nav-tab {
            text-decoration: none;
            color: #555;
            font-weight: 500;
            padding: 10px 0;
            position: relative;
            transition: all 0.3s ease;
        }

        .nav-tab::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }

        .nav-tab:hover,
        .nav-tab.active {
            color: #667eea;
        }

        .nav-tab:hover::after,
        .nav-tab.active::after {
            width: 100%;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        #user-greeting {
            font-weight: 500;
            color: #2c3e50;
        }

        .btn {
            padding: 10px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        #login-button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        #login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        #logout-button {
            background: #e74c3c;
            color: white;
        }

        #logout-button:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        /* Section Hero moderne */
        .hero {
            height: 100vh;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9)),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="books" patternUnits="userSpaceOnUse" width="100" height="100"><rect width="100" height="100" fill="%23f8f9fa" opacity="0.1"/><rect x="10" y="20" width="15" height="60" fill="%23ffffff" opacity="0.1"/><rect x="30" y="15" width="12" height="70" fill="%23ffffff" opacity="0.1"/><rect x="50" y="25" width="18" height="50" fill="%23ffffff" opacity="0.1"/><rect x="75" y="10" width="14" height="75" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23books)"/></svg>');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .hero-content {
            max-width: 800px;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .hero h2 {
            font-family: 'Oswald', sans-serif;
            font-size: 4em;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: slideInDown 1s ease-out;
        }

        .hero p {
            font-size: 1.4em;
            margin-bottom: 40px;
            opacity: 0.95;
            animation: slideInUp 1s ease-out 0.3s both;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cta-button {
            background: white;
            color: #667eea;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.1em;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: slideInUp 1s ease-out 0.6s both;
        }

        .cta-button:hover {
            background: #667eea;
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }

        /* Section contenu */
        .content-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px;
            text-align: center;
        }

        .content-section h3 {
            font-family: 'Oswald', sans-serif;
            font-size: 2.5em;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        .intro-text {
            font-size: 1.2em;
            max-width: 800px;
            margin: 0 auto;
            color: #555;
        }

        /* Section fonctionnalités */
        .features-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 80px 0;
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .features-title {
            font-family: 'Oswald', sans-serif;
            font-size: 2.5em;
            text-align: center;
            margin-bottom: 50px;
            color: #2c3e50;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 3em;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 1.4em;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .feature-description {
            color: #555;
            font-size: 1em;
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 30px 0;
            font-size: 0.9em;
        }

        /* Menu mobile */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: #2c3e50;
            font-size: 1.5em;
            cursor: pointer;
        }

        @media (max-width: 992px) {
            .header-content {
                padding: 15px 30px;
            }

            #main-nav {
                display: none;
                position: absolute;
                top: 70px;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                gap: 0;
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }

            #main-nav.active {
                display: flex;
            }

            .nav-tab {
                padding: 15px 30px;
                border-bottom: 1px solid #f0f0f0;
            }

            .nav-tab:last-child {
                border-bottom: none;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .hero h2 {
                font-size: 3em;
            }

            .hero p {
                font-size: 1.2em;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo-container">
                <img src="assets/logo.png" alt="Logo Bibliothèque Acacia" class="logo-img">
                <a href="index.php" class="logo-text">Bibliothèque Acacia</a>
            </div>
            <nav id="main-nav">
                <a href="index.php" class="nav-tab active">Accueil</a>
                <a href="list_livres.php" class="nav-tab">Livres</a>
                <a href="my_reservations.php" class="nav-tab">Mes Réservations</a>
                <?php if ($loggedIn): ?>
                    <a href="dashboard.php" class="nav-tab">Dashboard</a>
                    <?php if ($isAdmin): ?>
                        <a href="admin.php" class="nav-tab">Admin</a>
                    <?php endif; ?>
                <?php endif; ?>
                 <a href="notifications.php" class="nav-tab">Notifications</a>
            </nav>
            <div class="user-menu">
                <span id="user-greeting"><?php echo $loggedIn ? "Bonjour, $userName" : ''; ?></span>
                <a id="login-button" href="login.php" class="btn" <?php echo $loggedIn ? 'style="display:none;"' : ''; ?>>
                    <i class="fas fa-sign-in-alt"></i> Connexion
                </a>
                <button id="logout-button" class="btn" <?php echo $loggedIn ? '' : 'style="display:none;"'; ?>>
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </button>
            </div>
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <h2>Un Monde de Savoir à Votre Portée</h2>
                <p>Découvrez, lisez et réservez vos prochains livres favoris dans notre bibliothèque moderne</p>
                <a href="list_livres.php" class="cta-button">
                    <i class="fas fa-book-open"></i> Découvrir nos livres
                </a>
            </div>
        </section>

        <section class="content-section">
            <h3>Bienvenue à la Bibliothèque Acacia</h3>
            <p class="intro-text">
                Votre destination privilégiée pour la connaissance et l'aventure littéraire. 
                Parcourez notre vaste collection, réservez facilement en ligne et venez 
                simplement récupérer vos livres. Nous nous engageons à rendre la culture 
                et le savoir accessibles à tous, dans un environnement moderne et convivial.
            </p>
        </section>

        <section class="features-section">
            <div class="features-container">
                <h3 class="features-title">Pourquoi choisir Bibliothèque Acacia ?</h3>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4 class="feature-title">Recherche Avancée</h4>
                        <p class="feature-description">
                            Trouvez rapidement le livre parfait grâce à notre système de recherche intelligent par titre, auteur ou genre.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4 class="feature-title">Réservation Simple</h4>
                        <p class="feature-description">
                            Réservez vos livres en quelques clics et recevez des notifications pour vos réservations.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4 class="feature-title">Interface Moderne</h4>
                        <p class="feature-description">
                            Profitez d'une expérience utilisateur optimisée sur tous vos appareils, partout et à tout moment.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Bibliothèque Acacia. Tous droits réservés. | Fait avec ❤️ pour les amoureux de la lecture</p>
    </footer>

    <script>
        // Gérer la déconnexion
        document.getElementById('logout-button').addEventListener('click', function() {
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
        });

        // Animation du header au scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 2px 30px rgba(0,0,0,0.15)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
            }
        });

        // Animation des cartes au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.feature-card');
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>