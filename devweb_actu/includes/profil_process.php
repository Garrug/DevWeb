<?php
// includes/profil_process.php
session_start();

// ── Gardes ────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || !isset($_SESSION['id_metier'])) {
    header('Location: ../pages/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/profil.php');
    exit();
}

// Connexion centralisée — une seule fois en haut
require_once __DIR__ . '/db.php';

$role      = $_SESSION['role'];
$id_metier = (int) $_SESSION['id_metier'];

$id_col_map = [
    'Administrateur' => 'idAdmin',
    'Etudiant'       => 'idEtudiant',
    'Tuteur'         => 'idTuteur',
    'Jury'           => 'idJury',
    'Entreprise'     => 'idEntreprise',
];

if (!in_array($role, array_keys($id_col_map), true)) {
    session_destroy();
    header('Location: ../pages/login.php');
    exit();
}

$id_col = $id_col_map[$role];

// ── Utilitaire feedback ───────────────────────────────────────────────────
function redirect_profil(string $key): never {
    if ($key === 'success') {
        $_SESSION['profil_success'] = true;
    } else {
        $_SESSION['profil_error'] = $key;
    }
    header('Location: ../pages/profil.php');
    exit();
}

// ── Distinguer les deux formulaires ──────────────────────────────────────
$action = $_POST['action'] ?? 'profil';

// ── Formulaire mot de passe ───────────────────────────────────────────────
if ($action === 'password') {
    $mdp_actuel  = $_POST['mdp_actuel']  ?? '';
    $mdp_nouveau = $_POST['mdp_nouveau'] ?? '';
    $mdp_confirm = $_POST['mdp_confirm'] ?? '';

    if ($mdp_actuel === '' || $mdp_nouveau === '' || $mdp_confirm === '') {
        redirect_profil('empty');
    }

    if ($mdp_nouveau !== $mdp_confirm) {
        redirect_profil('mdp_match');
    }

    try {
        $stmt = $conn->prepare("SELECT mdp FROM `$role` WHERE `$id_col` = ? LIMIT 1");
        $stmt->execute([$id_metier]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($mdp_actuel, $row['mdp'])) {
            redirect_profil('mdp_actuel');
        }

        $nouveau_hash = password_hash($mdp_nouveau, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE `$role` SET mdp = ? WHERE `$id_col` = ?");
        $stmt->execute([$nouveau_hash, $id_metier]);
        redirect_profil('success');

    } catch (PDOException $e) {
        error_log('[StageFlow] profil_process password : ' . $e->getMessage());
        redirect_profil('db');
    }
}

// ── Formulaire profil ─────────────────────────────────────────────────────
$nom  = trim($_POST['nom']  ?? '');
$mail = trim($_POST['mail'] ?? '');

if ($nom === '' || $mail === '') {
    redirect_profil('empty');
}

if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    redirect_profil('mail_format');
}

if (strlen($nom) > 100 || strlen($mail) > 150) {
    redirect_profil('empty');
}

// ── Unicité email ─────────────────────────────────────────────────────────
try {
    foreach ($id_col_map as $table => $col_id) {
        if ($table === $role) {
            $stmt = $conn->prepare(
                "SELECT 1 FROM `$table` WHERE mail = ? AND `$col_id` != ? LIMIT 1"
            );
            $stmt->execute([$mail, $id_metier]);
        } else {
            $stmt = $conn->prepare(
                "SELECT 1 FROM `$table` WHERE mail = ? LIMIT 1"
            );
            $stmt->execute([$mail]);
        }

        if ($stmt->fetch()) {
            redirect_profil('mail');
        }
    }
} catch (PDOException $e) {
    error_log('[StageFlow] profil_process vérif mail : ' . $e->getMessage());
    redirect_profil('db');
}

// ── UPDATE selon le rôle ──────────────────────────────────────────────────
try {
    switch ($role) {

        case 'Etudiant':
            $prenom  = trim($_POST['prenom']  ?? '');
            $filiere = trim($_POST['filiere'] ?? '');
            $annee   = trim($_POST['annee']   ?? '');
            $groupe  = trim($_POST['groupe']  ?? '');

            if ($prenom === '') { redirect_profil('empty'); }

            $stmt = $conn->prepare(
                "UPDATE `Etudiant`
                 SET nom = :nom, prenom = :prenom, mail = :mail,
                     filiere = :filiere, annee = :annee, groupe = :groupe
                 WHERE idEtudiant = :id"
            );
            $stmt->execute([
                ':nom'     => $nom,
                ':prenom'  => $prenom,
                ':mail'    => $mail,
                ':filiere' => $filiere ?: null,
                ':annee'   => $annee   ?: null,
                ':groupe'  => $groupe  ?: null,
                ':id'      => $id_metier,
            ]);
            break;

        case 'Entreprise':
            $description = trim($_POST['description'] ?? '');

            $stmt = $conn->prepare(
                "UPDATE `Entreprise`
                 SET nom = :nom, description = :description, mail = :mail
                 WHERE idEntreprise = :id"
            );
            $stmt->execute([
                ':nom'         => $nom,
                ':description' => $description ?: null,
                ':mail'        => $mail,
                ':id'          => $id_metier,
            ]);
            break;

        case 'Jury':
        case 'Tuteur':
        case 'Administrateur':
            $prenom = trim($_POST['prenom'] ?? '');

            if ($prenom === '') { redirect_profil('empty'); }

            $stmt = $conn->prepare(
                "UPDATE `$role`
                 SET nom = :nom, prenom = :prenom, mail = :mail
                 WHERE `$id_col` = :id"
            );
            $stmt->execute([
                ':nom'    => $nom,
                ':prenom' => $prenom,
                ':mail'   => $mail,
                ':id'     => $id_metier,
            ]);
            break;

        default:
            redirect_profil('server');
    }

} catch (PDOException $e) {
    error_log('[StageFlow] profil_process UPDATE `' . $role . '` : ' . $e->getMessage());
    redirect_profil('db');
}

redirect_profil('success');