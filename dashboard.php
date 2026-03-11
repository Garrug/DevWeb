<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.html");
    exit();
}

echo "Bienvenue " . $_SESSION["user"];
echo "<br>Rôle : " . $_SESSION["role"];
if ($_SESSION["role"] == "Etudiant") {
    echo "Interface étudiant";
}

if ($_SESSION["role"] == "Tuteur") {
    echo "Interface tuteur";
}

if ($_SESSION["role"] == "Entreprise") {
    echo "Interface entreprise";
}

if ($_SESSION["role"] == "Administrateur") {
    echo "Interface admin";
}
?>