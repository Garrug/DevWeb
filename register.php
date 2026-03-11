<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connexion à la base
$conn = new mysqli("localhost", "test", "Test123!", "monsite");

if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Vérifie que le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $mail = $_POST["mail"];
    $identifiant = $_POST["identifiant"];
    $mdp = $_POST["mdp"];
    $role = $_POST["role"];

    $sql = "";
    $params = [];

    switch ($role) {
        case "Etudiant":
            $sql = "INSERT INTO Etudiant (nom, prenom, mail, filiere, annee, groupe, identifiant, mdp) VALUES (?, ?, ?, '', '', '', ?, ?)";
            $params = [$nom, $prenom, $mail, $identifiant, $mdp];
            break;
        case "Tuteur":
            $sql = "INSERT INTO Tuteur (nom, prenom, mail, identifiant, mdp) VALUES (?, ?, ?, ?, ?)";
            $params = [$nom, $prenom, $mail, $identifiant, $mdp];
            break;
        case "Entreprise":
            $sql = "INSERT INTO Entreprise (nom, description, mail, identifiant, mdp) VALUES (?, '', ?, ?, ?)";
            $params = [$nom, $mail, $identifiant, $mdp];
            break;
        case "Administrateur":
            $sql = "INSERT INTO Administrateur (nom, prenom, mail, identifiant, mdp) VALUES (?, ?, ?, ?, ?)";
            $params = [$nom, $prenom, $mail, $identifiant, $mdp];
            break;
        case "Jury":
            $sql = "INSERT INTO Jury (nom, prenom, mail, identifiant, mdp) VALUES (?, ?, ?, ?, ?)";
            $params = [$nom, $prenom, $mail, $identifiant, $mdp];
            break;
        default:
            die("Rôle invalide !");
    }

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erreur préparation : " . $conn->error);
    }

    $types = str_repeat("s", count($params));
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo "Utilisateur ajouté avec succès !";
    } else {
        echo "Erreur lors de l'ajout : " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>