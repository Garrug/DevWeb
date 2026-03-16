

<?php

if (!isset($_POST['identifiant']) or !isset($_POST['mdp'])) {
	header('Location: login.html');
	exit();
}

session_start();

$db_name = "mysql:host=localhost;dbname=monsite";
$username = "test";
$password = "Test123!";

try {
	$conn = new PDO($db_name, $username, $password);
		
} catch (PDOException $e) {
	echo "Error : " . $e->getMessage() . "<br>";
	die();
	
}

$identifiant = $_POST['identifiant'];
$mdp = $_POST['mdp'];

$tables = [
    "Administrateur",
    "Etudiant",
    "Tuteur",
    "Jury",
    "Entreprise"
];

foreach ($tables as $table) {

    $sql = "SELECT * FROM $table WHERE identifiant=? AND mdp=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$identifiant, hash("sha256", $mdp)]);
	$result = $stmt->fetchAll();
    if ($stmt->rowCount() === 1) {

        $_SESSION["user"] = $identifiant;
        $_SESSION["role"] = $table;

        header("Location: dashboard.php");
        exit();
    }
    
    $stmt = null;
}

$conn = null;
echo "Identifiant ou mot de passe incorrect";
?>
