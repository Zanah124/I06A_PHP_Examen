<?php
require_once "db.php";

class Users {
    private $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function getAllUsers() {
        $stmt = $this->conn->prepare("SELECT * FROM users");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteUser($id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function toggleActive($id) {
        $stmt = $this->conn->prepare("UPDATE users SET active = NOT active WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
   
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createUser($data) {
        $stmt = $this->conn->prepare("INSERT INTO users (nom, email, mot_de_passe, telephone, role, profil) VALUES (:nom, :email, :mot_de_passe, :telephone, :role, :profil)");
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':mot_de_passe', $data['mot_de_passe']); // Pas de hachage ici temporairement
        $stmt->bindParam(':telephone', $data['telephone']);
        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':profil', $data['profil']);
        return $stmt->execute();
    }

    public function verifyMotdePasse($inputMotdePasse, $storedMotdePasse) {
        return $inputMotdePasse === $storedMotdePasse; // Comparaison en clair temporaire
    }

    public function register($data) {
        return $this->createUser($data);
    }

    public function authenticate($email, $mot_de_passe) {
        $user = $this->getUserByEmail($email);
        if ($user && $this->verifyMotdePasse($mot_de_passe, $user['mot_de_passe'])) {
            return $user;
        }
        return false;
    }

    public function updateUser($id, $data) {
        $setClauses = [];
        $params = [':id' => $id];
        
        if (isset($data['nom'])) {
            $setClauses[] = "nom = :nom";
            $params[':nom'] = $data['nom'];
        }
        if (isset($data['email'])) {
            $setClauses[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        if (isset($data['mot_de_passe'])) {
            $setClauses[] = "mot_de_passe = :mot_de_passe";
            $params[':mot_de_passe'] = $data['mot_de_passe']; // Pas de hachage ici temporairement
        }
        if (isset($data['telephone'])) {
            $setClauses[] = "telephone = :telephone";
            $params[':telephone'] = $data['telephone'];
        }
        if (isset($data['profil'])) {
            $setClauses[] = "profil = :profil";
            $params[':profil'] = $data['profil'];
        }

        if (empty($setClauses)) {
            return false; // Aucun champ à mettre à jour
        }

        $sql = "UPDATE users SET " . implode(", ", $setClauses) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function getUserCount() {
    $query = "SELECT COUNT(*) as count FROM users";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}
}
?>