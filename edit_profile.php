<?php
session_start();
require_once "users.php";

if (!isset($_SESSION['user']) || !$_SESSION['user']['id']) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$usersModel = new Users();
$user = $usersModel->getUserById($userId);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => $_POST['nom'] ?? $user['nom'],
        'email' => $_POST['email'] ?? $user['email'],
        'telephone' => $_POST['telephone'] ?? $user['telephone'] ?? '',
        'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
        'profil' => $user['profil'] ?? '' // Préserver la photo existante par défaut
    ];

    // Handle file upload for profil
    if (isset($_FILES['profil']) && $_FILES['profil']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/users/';
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
                $data['profil'] = '/assets/images/users/' . $fileName;
                if (!empty($user['profil']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['profil'])) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $user['profil']);
                }
            } else {
                $error = "Erreur lors du téléchargement de la photo. Vérifiez les permissions du dossier ou le chemin.";
                error_log("Échec de move_uploaded_file vers $uploadPath. Erreur: " . print_r(error_get_last(), true));
            }
        }
    }

    // Valider les champs
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
    <style>
        .preview-image {
            max-width: 200px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Modifier Mon Profil</h1>
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
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">Retour</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>