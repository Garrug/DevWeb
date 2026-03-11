

<?php
session_start();

$conn = new mysqli("localhost", "test", "Test123!", "monsite");

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
    $stmt->bind_param("ss", $identifiant, $mdp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $_SESSION["user"] = $identifiant;
        $_SESSION["role"] = $table;

        header("Location: dashboard.php");
        exit();
    }
}

echo "Identifiant ou mot de passe incorrect";
?>