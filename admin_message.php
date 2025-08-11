<?php
session_start();
require_once "users.php";
require_once "archive.php";
require_once "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$sender = 'walseanito@gmail.com';
$password = 'mfrp qoba iraf uzrq';

if (!isset($_SESSION['user']) || $_SESSION['user']['email'] !== 'walseanito@gmail.com') {
    header("Location: login.php");
    exit;
}

$usersModel = new Users();
$users = $usersModel->getAllUsers();
$archiveModel = new Archive();
$error = '';
$success = '';

// Get query parameters for pre-selection
$preselectedEmail = $_GET['email'] ?? '';
$defaultSubject = $_GET['subject'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['to'] ?? [];
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($to) || empty($subject) || empty($message)) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $sender;
            $mail->Password = $password;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('admin@bibliothequeacacia.com', 'Admin Bibliothèque');
            foreach ($to as $email) {
                $mail->addAddress($email);
            }

            $mail->isHTML(true);
            $mail->Subject = htmlspecialchars($subject);
            $mail->Body = nl2br(htmlspecialchars($message));

            $mail->send();
            $success = "Email(s) envoyé(s) avec succès !";

            $archiveModel->archiveEmail(implode(', ', $to), $subject, $message);
        } catch (Exception $e) {
            $error = "Erreur lors de l'envoi : {$mail->ErrorInfo}";
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
    <title>Communications - Admin Bibliothèque</title>
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
            background: linear-gradient(90deg, #e74c3c, #c0392b);
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

        .message-form-container {
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
            border-color: #e74c3c;
            box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
            background-color: white;
        }

        .form-label {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .recipients-select {
            min-height: 150px;
            background: white;
        }

        .recipients-help {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 15px;
            margin-top: 10px;
            color: #5d4e75;
            font-size: 0.95em;
        }

        .message-textarea {
            min-height: 200px;
            resize: vertical;
        }

        .btn-send {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            color: white;
            font-size: 1.1em;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
        }

        .btn-send:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(231, 76, 60, 0.4);
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

        .user-count-badge {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            margin-left: 10px;
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
            .message-form-container {
                padding: 25px;
            }

            .page-title {
                font-size: 2em;
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .recipients-list {
            max-height: 150px;
            overflow-y: auto;
        }

        .recipients-list option {
            padding: 8px;
            margin: 2px 0;
        }

        .recipients-list option:hover {
            background-color: #e74c3c;
            color: white;
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
            <li><a href="create_livre.php"><i class="fas fa-plus-circle"></i>Ajouter Livre</a></li>
            <li><a href="manage_reservations.php"><i class="fas fa-calendar-alt"></i>Gérer Réservations</a></li>
            <li><a href="admin_message.php" class="active"><i class="fas fa-comments"></i>Communications</a></li>
            <li><a href="dashboard.php"><i class="fas fa-user"></i>Mon Dashboard</a></li>
            <li><a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i>Déconnexion</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header fade-in">
            <h1 class="page-title">
                <i class="fas fa-envelope-open-text"></i>
                Communications
            </h1>
            <p class="page-subtitle">Envoyez des messages aux utilisateurs de la bibliothèque</p>
        </div>

        <div class="message-form-container fade-in">
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

            <form method="POST" id="messageForm">
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-users"></i>
                        Destinataires
                        <span class="user-count-badge"><?php echo count($users); ?> utilisateurs</span>
                    </h3>
                    <div class="mb-4">
                        <label for="to" class="form-label">Sélectionnez les destinataires</label>
                        <select multiple class="form-select recipients-select recipients-list" id="to" name="to[]" required>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['email']); ?>" 
                                        <?php echo ($user['email'] === $preselectedEmail) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['nom'] . ' (' . $user['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="recipients-help">
                            <i class="fas fa-info-circle me-2"></i>
                            Maintenez Ctrl (ou Cmd sur Mac) + clic pour sélectionner plusieurs utilisateurs
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-edit"></i>
                        Contenu du Message
                    </h3>
                    <div class="mb-4">
                        <label for="subject" class="form-label">Objet du message</label>
                        <input type="text" class="form-control" id="subject" name="subject" 
                               value="<?php echo htmlspecialchars($defaultSubject); ?>" 
                               placeholder="Saisissez l'objet de votre message..." required>
                    </div>
                    <div class="mb-4">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control message-textarea" id="message" name="message" 
                                  placeholder="Rédigez votre message ici..." required></textarea>
                    </div>
                </div>

                <div class="d-flex gap-3 justify-content-end">
                    <a href="admin.php" class="btn-back">
                        <i class="fas fa-arrow-left me-2"></i>
                        Retour
                    </a>
                    <button type="submit" class="btn-send">
                        <i class="fas fa-paper-plane me-2"></i>
                        Envoyer le Message
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
            const form = document.getElementById('messageForm');
            const selectElement = document.getElementById('to');
            const submitButton = document.querySelector('.btn-send');

            function updateRecipientCount() {
                const selectedCount = selectElement.selectedOptions.length;
                const badge = document.querySelector('.user-count-badge');
                if (selectedCount > 0) {
                    badge.textContent = `${selectedCount} sélectionné(s) sur ${selectElement.options.length}`;
                    badge.style.background = 'linear-gradient(45deg, #27ae60, #2ecc71)';
                } else {
                    badge.textContent = `${selectElement.options.length} utilisateurs`;
                    badge.style.background = 'linear-gradient(45deg, #e74c3c, #c0392b)';
                }
            }

            selectElement.addEventListener('change', updateRecipientCount);
            updateRecipientCount(); // Update count on page load

            form.addEventListener('submit', function(e) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi en cours...';
                submitButton.disabled = true;
            });

            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                field.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        this.style.borderColor = '#27ae60';
                    } else {
                        this.style.borderColor = '#e74c3c';
                    }
                });
            });

            const elements = document.querySelectorAll('.form-section');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    element.style.transition = 'all 0.6s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                document.getElementById('messageForm').submit();
            }
            if (e.key === 'Escape') {
                window.location.href = 'admin.php';
            }
        });
    </script>
</body>
</html>