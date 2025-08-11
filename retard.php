<?php
/**
 * Script de gestion des livres en retard
 * Ce script peut être exécuté via un cron job quotidiennement
 * pour vérifier les livres en retard et envoyer des notifications
 */

require_once "reservations.php";
require_once "livres.php";

class OverdueManager {
    private $reservationsModel;
    private $livresModel;
    
    public function __construct() {
        $this->reservationsModel = new Reservations();
        $this->livresModel = new Livres();
    }
    
    /**
     * Obtenir tous les livres en retard
     */
    public function getOverdueBooks() {
        return $this->reservationsModel->getOverdueBooks();
    }
    
    /**
     * Obtenir les livres qui arrivent à échéance bientôt
     */
    public function getBooksNearDueDate($days = 3) {
        return $this->reservationsModel->getBooksNearDueDate($days);
    }
    
    /**
     * Générer un rapport des livres en retard
     */
    public function generateOverdueReport() {
        $overdueBooks = $this->getOverdueBooks();
        $nearDueBooks = $this->getBooksNearDueDate(2); // 2 jours avant échéance
        
        $report = [
            'date_generation' => date('Y-m-d H:i:s'),
            'livres_en_retard' => [],
            'livres_bientot_dus' => [],
            'statistiques' => [
                'total_en_retard' => count($overdueBooks),
                'total_bientot_dus' => count($nearDueBooks)
            ]
        ];
        
        // Traiter les livres en retard
        foreach ($overdueBooks as $book) {
            $daysOverdue = $this->reservationsModel->getDaysUntilDue($book['date_limite_retour']);
            $report['livres_en_retard'][] = [
                'reservation_id' => $book['id'],
                'titre' => $book['titre'],
                'utilisateur' => $book['nom'],
                'email' => $book['email'],
                'date_limite' => $book['date_limite_retour'],
                'jours_retard' => abs($daysOverdue),
                'date_prise' => $book['date_prise']
            ];
        }
        
        // Traiter les livres bientôt dus
        foreach ($nearDueBooks as $book) {
            $daysLeft = $this->reservationsModel->getDaysUntilDue($book['date_limite_retour']);
            $report['livres_bientot_dus'][] = [
                'reservation_id' => $book['id'],
                'titre' => $book['titre'],
                'utilisateur' => $book['nom'],
                'email' => $book['email'],
                'date_limite' => $book['date_limite_retour'],
                'jours_restants' => $daysLeft,
                'date_prise' => $book['date_prise']
            ];
        }
        
        return $report;
    }
    
    /**
     * Simuler l'envoi d'emails de rappel
     * (Vous devrez intégrer une vraie solution d'email comme PHPMailer)
     */
    public function sendReminders($report) {
        $emailsSent = 0;
        
        // Rappels pour les livres en retard
        foreach ($report['livres_en_retard'] as $book) {
            $subject = "⚠️ Livre en retard - " . $book['titre'];
            $message = $this->generateOverdueEmailContent($book);
            
            // Simuler l'envoi d'email
            if ($this->sendEmail($book['email'], $subject, $message)) {
                $emailsSent++;
                error_log("Email de rappel envoyé à " . $book['email'] . " pour le livre: " . $book['titre']);
            }
        }
        
        // Rappels pour les livres bientôt dus
        foreach ($report['livres_bientot_dus'] as $book) {
            $subject = "📅 Rappel - Retour de livre dans " . $book['jours_restants'] . " jour(s)";
            $message = $this->generateReminderEmailContent($book);
            
            // Simuler l'envoi d'email
            if ($this->sendEmail($book['email'], $subject, $message)) {
                $emailsSent++;
                error_log("Email de rappel envoyé à " . $book['email'] . " pour le livre: " . $book['titre']);
            }
        }
        
        return $emailsSent;
    }
    
    /**
     * Générer le contenu email pour les livres en retard
     */
    private function generateOverdueEmailContent($book) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #e74c3c;'>⚠️ Livre en Retard</h2>
                
                <p>Bonjour <strong>" . htmlspecialchars($book['utilisateur']) . "</strong>,</p>
                
                <p>Nous vous informons que le livre suivant est en retard :</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #2c3e50;'>" . htmlspecialchars($book['titre']) . "</h3>
                    <p><strong>Date d'échéance :</strong> " . date('d/m/Y', strtotime($book['date_limite'])) . "</p>
                    <p><strong>Retard :</strong> " . $book['jours_retard'] . " jour(s)</p>
                </div>
                
                <p>Merci de retourner ce livre à la bibliothèque dans les plus brefs délais.</p>
                
                <p>Cordialement,<br>
                L'équipe de la Bibliothèque Acacia</p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Générer le contenu email pour les rappels
     */
    private function generateReminderEmailContent($book) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #f39c12;'>📅 Rappel de Retour</h2>
                
                <p>Bonjour <strong>" . htmlspecialchars($book['utilisateur']) . "</strong>,</p>
                
                <p>Nous vous rappelons que le livre suivant doit être retourné bientôt :</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #2c3e50;'>" . htmlspecialchars($book['titre']) . "</h3>
                    <p><strong>Date d'échéance :</strong> " . date('d/m/Y', strtotime($book['date_limite'])) . "</p>
                    <p><strong>Temps restant :</strong> " . $book['jours_restants'] . " jour(s)</p>
                </div>
                
                <p>N'oubliez pas de retourner ce livre avant la date d'échéance pour éviter tout retard.</p>
                
                <p>Cordialement,<br>
                L'équipe de la Bibliothèque Acacia</p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Simuler l'envoi d'email
     * Remplacez cette fonction par une vraie implémentation d'email
     */
    private function sendEmail($to, $subject, $message) {
        // Simulation - toujours retourner true
        // Dans un vrai projet, utilisez PHPMailer ou mail()
        
        // Exemple avec mail() (nécessite une configuration serveur)
        /*
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: bibliotheque@acacia.com' . "\r\n";
        
        return mail($to, $subject, $message, $headers);
        */
        
        // Pour les tests, on log juste l'email
        error_log("Email simulé - À: $to, Sujet: $subject");
        return true;
    }
    
    /**
     * Sauvegarder le rapport dans un fichier
     */
    public function saveReportToFile($report, $filename = null) {
        if (!$filename) {
            $filename = 'overdue_report_' . date('Y-m-d_H-i-s') . '.json';
        }
        
        $reportsDir = __DIR__ . '/reports';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0755, true);
        }
        
        $filepath = $reportsDir . '/' . $filename;
        return file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT));
    }
    
    /**
     * Exécuter le processus complet de gestion des retards
     */
    public function runDailyCheck() {
        echo "=== Vérification quotidienne des livres - " . date('Y-m-d H:i:s') . " ===\n";
        
        // Générer le rapport
        $report = $this->generateOverdueReport();
        
        echo "Livres en retard: " . $report['statistiques']['total_en_retard'] . "\n";
        echo "Livres bientôt dus: " . $report['statistiques']['total_bientot_dus'] . "\n";
        
        // Envoyer les rappels
        $emailsSent = $this->sendReminders($report);
        echo "Emails de rappel envoyés: $emailsSent\n";
        
        // Sauvegarder le rapport
        if ($this->saveReportToFile($report)) {
            echo "Rapport sauvegardé avec succès\n";
        }
        
        echo "=== Fin de la vérification ===\n\n";
        
        return $report;
    }
}

// Si le script est exécuté directement (par exemple via cron)
if (php_sapi_name() === 'cli') {
    $manager = new OverdueManager();
    $manager->runDailyCheck();
}
?>