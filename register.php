<?php
require_once "users.php";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => $_POST['nom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
        'telephone' => $_POST['telephone'] ?? '',
        'role' => $_POST['role'] ?? 'user',
        'profil' => ''
    ];

    // Handle file upload
    if (isset($_FILES['profil']) && $_FILES['profil']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/users/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['profil']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            $error = "Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP";
        } else {
            $fileName = uniqid() . '_' . basename($_FILES['profil']['name']);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['profil']['tmp_name'], $uploadPath)) {
                $data['profil'] = '/assets/images/users/' . $fileName;
            } else {
                $error = "Erreur lors du téléchargement du fichier.";
            }
        }
    }

    if (!$error) {
        $usersModel = new Users();
        if ($usersModel->register($data)) {
            $success = "Compte créé avec succès! Vous pouvez maintenant vous connecter.";
        } else {
            $error = "Erreur lors de l'inscription. L'email est peut-être déjà utilisé.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Bibliothèque en Ligne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Créer un compte</h1>
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
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                    </div>
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone (optionnel)</label>
                        <input type="text" class="form-control" id="telephone" name="telephone">
                    </div>
                    <div class="mb-3">
                        <label for="profil" class="form-label">Photo de profil (optionnel)</label>
                        <input type="file" class="form-control" id="profil" name="profil" accept="image/jpeg,image/png,image/gif,image/webp">
                    </div>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle</label>
                            <select class="form-select" id="role" name="role">
                                <option value="user">Utilisateur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">S'inscrire</button>
                    <p class="mt-3">Déjà un compte ? <a href="login.php">Se connecter</a> | <a href="index.php">Retour à l'accueil</a></p>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>