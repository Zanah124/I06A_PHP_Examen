<?php
ob_start(); // Start output buffering to prevent unexpected output
session_start();
require_once "users.php";
require_once "reservations.php";

if (!isset($_SESSION['user']) || !$_SESSION['user']['id']) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$usersModel = new Users();
$user = $usersModel->getUserById($userId);
$newNotificationsCount = (new Reservations())->countNewNotifications($userId);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => $_POST['nom'] ?? $user['nom'],
        'email' => $_POST['email'] ?? $user['email'],
        'telephone' => $_POST['telephone'] ?? $user['telephone'] ?? '',
        'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
        'profil' => $user['profil'] ?? ''
    ];

    if (isset($_FILES['profil']) && $_FILES['profil']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['profil']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            $error = "Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP.";
        } else {
            $fileName = uniqid() . '_' . basename($_FILES['profil']['name']);
            $uploadPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['profil']['tmp_name'], $uploadPath)) {
                $data['profil'] = '/assets/' . $fileName;
                if (!empty($user['profil']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['profil'])) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $user['profil']);
                }
            } else {
                $error = "Erreur lors du téléchargement de la photo. Vérifiez les permissions du dossier ou le chemin.";
                error_log("Échec de move_uploaded_file vers $uploadPath. Erreur: " . print_r(error_get_last(), true));
            }
        }
    }

    if (empty($data['nom']) || empty($data['email'])) {
        $error = "Le nom et l'email sont obligatoires.";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    }

    if (!$error) {
        if (!empty($data['mot_de_passe'])) {
            $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        } else {
            unset($data['mot_de_passe']);
        }

        if ($usersModel->updateUser($userId, $data)) {
            $_SESSION['user']['nom'] = $data['nom'];
            $_SESSION['user']['email'] = $data['email'];
            $_SESSION['user']['telephone'] = $data['telephone'] ?? $_SESSION['user']['telephone'];
            $_SESSION['user']['profil'] = $data['profil'] ?? $_SESSION['user']['profil'];
            $success = "Profil mis à jour avec succès !";
        } else {
            $error = "Erreur lors de la mise à jour du profil.";
        }
    }

 }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Profil - Bibliothèque en Ligne</title>
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
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.6s;
        }

        .form-card:hover::before {
            left: 100%;
        }

        .form-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(102, 126, 234, 0.2);
        }

        .form-icon {
            font-size: 3.5em;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            border-color: #667eea;
            box-shadow: 0 0 8px rgba(102, 126, 234, 0.3);
        }

        .form-control[readonly] {
            background-color: #f8f9fa;
            opacity: 0.8;
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
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .preview-image {
            max-width: 200px;
            border-radius: 10px;
            margin-top: 10px;
            border: 2px solid rgba(102, 126, 234, 0.3);
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
                <a href="notifications.php">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($newNotificationsCount > 0): ?>
                        <span class="notification-badge"><?php echo htmlspecialchars($newNotificationsCount); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="edit_profile.php" class="active"><i class="fas fa-user-edit"></i>Modifier Profil</a></li>
            <li><a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i>Déconnexion</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Modifier Mon Profil</h1>
            <p class="dashboard-subtitle">Mettez à jour vos informations personnelles</p>
        </div>

        <div class="form-card">
            <div class="form-icon">
                <i class="fas fa-user-edit"></i>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="telephone" class="form-label">Téléphone (optionnel)</label>
                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="profil" class="form-label">Photo de Profil (optionnel)</label>
                    <input type="file" class="form-control" id="profil" name="profil" accept="image/jpeg,image/png,image/gif,image/webp">
                    <?php if (!empty($user['profil'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profil']); ?>" alt="Photo actuelle" class="preview-image">
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rôle (non modifiable)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['role'] ?? 'Non spécifié'); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="mot_de_passe" class="form-label">Nouveau Mot de Passe (laisser vide pour ne pas changer)</label>
                    <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe">
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    <a href="dashboard.php" class="btn btn-secondary">Retour</a>
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