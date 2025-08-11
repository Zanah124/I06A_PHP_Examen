<?php
session_start();
require_once "livres.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login");
    exit;
}

$livresModel = new Livres();
$error = '';
$success = '';

// Récupérer l'ID du livre à modifier
$livreId = $_GET['id'] ?? '';
if (empty($livreId)) {
    header("Location: admin");
    exit;
}

// Récupérer les données du livre
$livre = $livresModel->getLivreById($livreId);
if (!$livre) {
    header("Location: admin");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $auteur = trim($_POST['auteur'] ?? '');
    $annee = trim($_POST['annee'] ?? '');
    $categorie = trim($_POST['categorie'] ?? '');
    $nb_exemplaires = (int)($_POST['nb_exemplaires'] ?? 1);
    
    // Gestion de l'upload de photo
    $photoName = $livre['photo']; // Garder l'ancienne photo par défaut
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/';
        
        // Créer le dossier assets s'il n'existe pas
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $originalName = $_FILES['photo']['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($extension, $allowedExtensions)) {
            // Générer un nom unique pour éviter les conflits
            $photoName = 'livre_' . $livreId . '_' . uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $photoName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                // Supprimer l'ancienne photo si elle existe et est différente
                $oldPhotoPath = $uploadDir . $livre['photo'];
                if ($livre['photo'] && file_exists($oldPhotoPath) && $livre['photo'] !== $photoName) {
                    unlink($oldPhotoPath);
                }
            } else {
                $error = "Erreur lors de l'upload de l'image.";
            }
        } else {
            $error = "Format d'image non supporté. Utilisez JPG, PNG, GIF ou WEBP.";
        }
    }

    if (empty($error)) {
        if (empty($titre) || empty($auteur) || empty($annee) || empty($categorie)) {
            $error = "Tous les champs obligatoires doivent être remplis.";
        } elseif (!is_numeric($annee) || $annee < 1000 || $annee > date('Y')) {
            $error = "L'année doit être valide.";
        } elseif ($nb_exemplaires < 1) {
            $error = "Le nombre d'exemplaires doit être au moins 1.";
        } else {
            $data = [
                'titre' => $titre,
                'auteur' => $auteur,
                'annee' => $annee,
                'categorie' => $categorie,
                'nb_exemplaires' => $nb_exemplaires,
                'photo' => $photoName
            ];

            if ($livresModel->updateLivre($livreId, $data)) {
                $success = "Livre modifié avec succès !";
                // Recharger les données du livre
                $livre = $livresModel->getLivreById($livreId);
            } else {
                $error = "Erreur lors de la modification du livre.";
            }
        }
    }
}

$categories = [
    'Romans', 'Sciences Fiction', 'Fantastique', 'Policier', 'Thriller', 
    'Romance', 'Biographie', 'Histoire', 'Sciences', 'Philosophie', 
    'Art', 'Cuisine', 'Voyage', 'Jeunesse', 'Bande Dessinée', 'Autre'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Livre - Admin Bibliothèque</title>
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
            background: linear-gradient(180deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 20px 0;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0,0,0,0.3);
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(102, 126, 234, 0.3);
            margin-bottom: 30px;
        }

        .sidebar-logo {
            color: #667eea;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-badge {
            background: linear-gradient(45deg, #667eea, #764ba2);
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
            background-color: rgba(102, 126, 234, 0.15);
            color: #667eea;
            border-left-color: #667eea;
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

        .page-header {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .page-title {
            color: #2c3e50;
            font-size: 2.5em;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-subtitle {
            color: #7f8c8d;
            font-size: 1.2em;
            margin-bottom: 0;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section-title {
            color: #2c3e50;
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 15px 20px;
            font-size: 1.1em;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background-color: white;
        }

        .form-label {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .photo-preview {
            max-width: 200px;
            max-height: 250px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            margin-top: 15px;
        }

        .btn-update {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            color: white;
            font-size: 1.1em;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-back {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            color: white;
            font-size: 1.1em;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        .alert {
            border: none;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            font-size: 1.1em;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 5px solid #dc3545;
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

            .page-header,
            .form-container {
                padding: 25px;
            }

            .page-title {
                font-size: 2em;
            }
        }

        .current-photo {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index" class="sidebar-logo">
                <i class="fas fa-shield-alt"></i>
                Admin Panel
                <span class="admin-badge">ADMIN</span>
            </a>
        </div>
        <ul class="sidebar-nav">
            <li><a href="index.php"><i class="fas fa-home"></i>Accueil</a></li>
            <li><a href="admin.php"><i class="fas fa-tachometer-alt"></i>Dashboard Admin</a></li>
            <li><a href="create_livre.php"><i class="fas fa-plus-circle"></i>Ajouter Livre</a></li>
            <li><a href="manage_reservations.php"><i class="fas fa-calendar-alt"></i>Gérer Réservations</a></li>
            <li><a href="admin_message.php"><i class="fas fa-comments"></i>Communications</a></li>
            <li><a href="dashboard.php"><i class="fas fa-user"></i>Mon Dashboard</a></li>
            <li><a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i>Déconnexion</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-edit"></i>
                Modifier le Livre
            </h1>
            <p class="page-subtitle">Modifiez les informations du livre "<?php echo htmlspecialchars($livre['titre']); ?>"</p>
        </div>

        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="editLivreForm">
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-book"></i>
                        Informations du Livre
                    </h3>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="titre" class="form-label">Titre *</label>
                            <input type="text" class="form-control" id="titre" name="titre" 
                                   value="<?php echo htmlspecialchars($livre['titre']); ?>" 
                                   placeholder="Titre du livre" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="auteur" class="form-label">Auteur *</label>
                            <input type="text" class="form-control" id="auteur" name="auteur" 
                                   value="<?php echo htmlspecialchars($livre['auteur']); ?>" 
                                   placeholder="Nom de l'auteur" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label for="annee" class="form-label">Année de publication *</label>
                            <input type="number" class="form-control" id="annee" name="annee" 
                                   value="<?php echo htmlspecialchars($livre['annee']); ?>" 
                                   min="1000" max="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label for="categorie" class="form-label">Catégorie *</label>
                            <input type="text" class="form-control" id="categorie" name="categorie"
                                    value="<?php echo htmlspecialchars($livre['categorie']); ?>"
                                    placeholder="Catégories" required>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <label for="nb_exemplaires" class="form-label">Nombre d'exemplaires *</label>
                            <input type="number" class="form-control" id="nb_exemplaires" name="nb_exemplaires" 
                                   value="<?php echo htmlspecialchars($livre['nb_exemplaires']); ?>" 
                                   min="1" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-image"></i>
                        Photo du Livre
                    </h3>
                    
                    <div class="current-photo">
                        <p><strong>Photo actuelle :</strong></p>
                        <?php if ($livre['photo'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/' . $livre['photo'])): ?>
                            <img src="/assets/<?php echo htmlspecialchars($livre['photo']); ?>" 
                                 alt="Photo actuelle" class="photo-preview">
                        <?php else: ?>
                            <div class="text-muted">
                                <i class="fas fa-image fa-3x mb-3"></i>
                                <p>Aucune photo disponible</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <label for="photo" class="form-label">Nouvelle photo (facultative)</label>
                        <input type="file" class="form-control" id="photo" name="photo" 
                               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Formats supportés : JPG, PNG, GIF, WEBP (Max: 5MB)
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-3 justify-content-end">
                    <a href="admin.php" class="btn-back">
                        <i class="fas fa-arrow-left me-2"></i>
                        Retour
                    </a>
                    <button type="submit" class="btn-update">
                        <i class="fas fa-save me-2"></i>
                        Modifier le Livre
                    </button>
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
                            window.location.href = 'index';
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        }

        // Prévisualisation de l'image
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const currentPhoto = document.querySelector('.current-photo');
                    const existingPreview = currentPhoto.querySelector('.new-photo-preview');
                    
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'new-photo-preview mt-3';
                    previewDiv.innerHTML = `
                        <p><strong>Nouvelle photo :</strong></p>
                        <img src="${e.target.result}" alt="Nouvelle photo" class="photo-preview">
                    `;
                    currentPhoto.appendChild(previewDiv);
                };
                reader.readAsDataURL(file);
            }
        });

        // Validation du formulaire
        document.getElementById('editLivreForm').addEventListener('submit', function(e) {
            const submitButton = document.querySelector('.btn-update');
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Modification en cours...';
            submitButton.disabled = true;
        });

        // Animation des champs
        document.addEventListener('DOMContentLoaded', function() {
            const requiredFields = document.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                field.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        this.style.borderColor = '#28a745';
                    } else {
                        this.style.borderColor = '#dc3545';
                    }
                });
            });
        });

        // Gestion responsive
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>