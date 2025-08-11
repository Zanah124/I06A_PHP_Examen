<?php
require_once "db.php";

class Archive {
    private $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function archiveEmail($email, $subject, $message) {
        $stmt = $this->conn->prepare("INSERT INTO archive (email, Objet, message) VALUES (:email, :Objet, :message)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':Objet', $subject);
        $stmt->bindParam(':message', $message);
        return $stmt->execute();
    }

    public function getMessageCount() {
    $query = "SELECT COUNT(*) as count FROM archive";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}
}
?>