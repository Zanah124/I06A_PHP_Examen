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
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/livres/';
        
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
                $data['photo'] = '/assets/images/livres/' . $fileName;
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Livre - Bibliothèque en Ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Ajouter un Livre</h1>
        <div class="row justify-content-center">
            <div class="col-md-6">
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
                    <button type="submit" class="btn btn-primary">Ajouter le livre</button>
                    <p class="mt-3"><a href="index.php" class="btn btn-secondary">Retour à l'accueil</a></p>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>