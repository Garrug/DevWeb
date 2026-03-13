<?php

if (!isset($_POST["identifiant"]) or !isset($_POST["mdp"])) {
	header('Location: register.html');
	exit();
}
 

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connexion à la base
try {
	$db_name = "mysql:host=localhost;dbname=monsite";
	$username = "test";
	$password = "Test123!";
	
	$conn = new PDO($db_name, $username, $password);
		
} catch (PDOException $e) {
	echo "Error : " . $e->getMessage() . "<br>";
	die();
	
}

// Vérifie que le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $mail = $_POST["mail"];
    $identifiant = $_POST["identifiant"];
    $mdp = hash("sha256", $_POST["mdp"]);
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
        die("Erreur préparation : " . $conn->errorInfo()[2]);
    }

    if ($stmt->execute($params)) {
        echo "Utilisateur ajouté avec succès !";
    } else {
        echo "Erreur lors de l'ajout : " . $stmt->error;
    }

    $stmt = null;
}

$conn = null;
?>


