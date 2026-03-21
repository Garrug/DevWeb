<?php
// includes/login_process.php
session_start();

// ── 1. Méthode HTTP ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit();
}

// ── 2. Présence des champs ────────────────────────────────────────────────
if (empty($_POST['identifiant']) || empty($_POST['mdp'])) {
    header('Location: ../pages/login.php?error=invalid');
    exit();
}

require_once __DIR__ . '/db.php'; // → $conn

// ── 3. Nettoyage ──────────────────────────────────────────────────────────
$identifiant = trim($_POST['identifiant']);
$mdp         = $_POST['mdp']; // ne pas trim le mot de passe

// ── 4. Recherche dans toutes les tables ───────────────────────────────────
$tables = ['Administrateur', 'Etudiant', 'Tuteur', 'Jury', 'Entreprise'];

$id_map = [
    'Administrateur' => 'idAdmin',
    'Etudiant'       => 'idEtudiant',
    'Tuteur'         => 'idTuteur',
    'Jury'           => 'idJury',
    'Entreprise'     => 'idEntreprise',
];

foreach ($tables as $table) {
    // Récupérer uniquement les colonnes utiles, pas le SELECT *
    $id_col = $id_map[$table];
    $stmt   = $conn->prepare(
        "SELECT `$id_col`, identifiant, mdp
         FROM `$table`
         WHERE identifiant = ?
         LIMIT 1"
    );
    $stmt->execute([$identifiant]);
    $user = $stmt->fetch();

    // Vérification bcrypt — cohérent avec register_process.php
    if ($user && password_verify($mdp, $user['mdp'])) {

        // Régénérer l'ID de session pour éviter la fixation de session
        session_regenerate_id(true);

        $_SESSION['user']      = $identifiant;
        $_SESSION['role']      = $table;
        $_SESSION['id_metier'] = (int) $user[$id_col];

        header('Location: ../pages/dashboard.php');
        exit();
    }
}

// ── 5. Échec : aucun utilisateur trouvé ───────────────────────────────────
header('Location: ../pages/login.php?error=invalid');
exit();