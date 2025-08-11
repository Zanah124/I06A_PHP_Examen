<?php
session_start();
require_once "livres.php";
require_once "users.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'titre' => $_POST['titre'] ?? '',
        'auteur' => $_POST['auteur'] ?? '',
        'annee' => !empty($_POST['annee']) ? (int)$_POST['annee'] : null,
        'categorie' => $_POST['categorie'] ?? null,
        'nb_exemplaires' => !empty($_POST['nb_exemplaires']) ? (int)$_POST['nb_exemplaires'] : '',
        'photo' => ''
    ];

    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['photo']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            $error = "Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP.";
        } else {
            $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                $data['photo'] = $fileName;
            } else {
                $error = "Erreur lors du téléchargement de la photo.";
            }
        }
    }

    // Validate required fields
    if (empty($data['titre']) || empty($data['auteur']) || empty($data['nb_exemplaires'])) {
        $error = "Les champs titre, auteur et nombre d'exemplaires sont obligatoires.";
    } elseif (!is_numeric($data['nb_exemplaires']) || $data['nb_exemplaires'] <= 0) {
        $error = "Le nombre d'exemplaires doit être un entier positif.";
    } elseif (!is_null($data['annee']) && ($data['annee'] < 0)) {
        $error = "L'année doit être un nombre positif.";
    }

    if (!$error) {
        $livresModel = new Livres();
        if ($livresModel->createLivre($data)) {
            $success = "Livre ajouté avec succès !";
        } else {
            $error = "Erreur lors de l'ajout du livre. Le titre existe peut-être déjà.";
        }
    }
}

$userName = $_SESSION['user']['nom'] ?? 'Administrateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Livre - Bibliothèque en Ligne</title>
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

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(231, 76, 60, 0.1), transparent);
            transition: left 0.6s;
        }

        .form-card:hover::before {
            left: 100%;
        }

        .form-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(231, 76, 60, 0.2);
        }

        .form-icon {
            font-size: 3.5em;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
        }

        .form-label {
            color: #2c3e50;
            font-weight: 600;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 8px rgba(231, 76, 60, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
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

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #e74c3c, #c0392b);
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
            <li><a href="create_livre.php" class="active"><i class="fas fa-plus-circle"></i>Ajouter Livre</a></li>
            <li><a href="manage_reservations.php"><i class="fas fa-calendar-alt"></i>Gérer Réservations</a></li>
            <li><a href="admin_message.php"><i class="fas fa-comments"></i>Communications</a></li>
            <li><a href="dashboard.php"><i class="fas fa-user"></i>Mon Dashboard</a></li>
            <li><a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i>Déconnexion</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="admin-header">
            <h1 class="admin-title">Ajouter un Livre</h1>
            <p class="admin-subtitle">Ajoutez un nouveau livre à votre bibliothèque en ligne</p>
        </div>

        <div class="form-card">
            <div class="form-icon">
                <i class="fas fa-book-medical"></i>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="titre" class="form-label">Titre</label>
                    <input type="text" class="form-control" id="titre" name="titre" required>
                </div>
                <div class="mb-3">
                    <label for="auteur" class="form-label">Auteur</label>
                    <input type="text" class="form-control" id="auteur" name="auteur" required>
                </div>
                <div class="mb-3">
                    <label for="annee" class="form-label">Année (optionnel)</label>
                    <input type="number" class="form-control" id="annee" name="annee">
                </div>
                <div class="mb-3">
                    <label for="categorie" class="form-label">Catégorie (optionnel)</label>
                    <input type="text" class="form-control" id="categorie" name="categorie">
                </div>
                <div class="mb-3">
                    <label for="nb_exemplaires" class="form-label">Nombre d'exemplaires</label>
                    <input type="number" class="form-control" id="nb_exemplaires" name="nb_exemplaires" required min="1">
                </div>
                <div class="mb-3">
                    <label for="photo" class="form-label">Photo du livre (optionnel)</label>
                    <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Ajouter le livre</button>
                    <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
                </div>
            </form>
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
            const card = document.querySelector('.form-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>