<?php
require_once "db.php";

class Livres {
    private $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    // Récupérer tous les livres
    public function getAllLivres() {
        $stmt = $this->conn->prepare("SELECT * FROM livres");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un livre par son ID
    public function getLivreById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM livres WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Créer un nouveau livre
    public function createLivre($data) {
        $stmt = $this->conn->prepare("INSERT INTO livres (titre, auteur, annee, categorie, nb_exemplaires, photo) VALUES (:titre, :auteur, :annee, :categorie, :nb_exemplaires, :photo)");
        $stmt->bindParam(':titre', $data['titre']);
        $stmt->bindParam(':auteur', $data['auteur']);
        $stmt->bindParam(':annee', $data['annee']);
        $stmt->bindParam(':categorie', $data['categorie']);
        $stmt->bindParam(':nb_exemplaires', $data['nb_exemplaires']);
        $stmt->bindParam(':photo', $data['photo']);
        return $stmt->execute();
    }

    // Mettre à jour un livre existant
    public function updateLivre($id, $data) {
        $stmt = $this->conn->prepare("UPDATE livres SET titre = :titre, auteur = :auteur, annee = :annee, categorie = :categorie, nb_exemplaires = :nb_exemplaires, photo = :photo WHERE id = :id");
        $stmt->bindParam(':titre', $data['titre']);
        $stmt->bindParam(':auteur', $data['auteur']);
        $stmt->bindParam(':annee', $data['annee']);
        $stmt->bindParam(':categorie', $data['categorie']);
        $stmt->bindParam(':nb_exemplaires', $data['nb_exemplaires']);
        $stmt->bindParam(':photo', $data['photo']);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Supprimer un livre
    public function deleteLivre($id) {
        $stmt = $this->conn->prepare("DELETE FROM livres WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getLivreCount() {
    $query = "SELECT COUNT(*) as count FROM livres";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}
}
?>