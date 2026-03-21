<?php
// includes/register_process.php

session_start();

// ── Utilitaire : erreur → session → redirect ──────────────────────────────
function redirect_error(string $code, array $champs = []): never
{
    $_SESSION['register_error']  = $code;
    $_SESSION['register_fields'] = $champs;
    header('Location: ../pages/register.php');
    exit();
}

// ── 1. Méthode HTTP ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/register.php');
    exit();
}

// ── 2. Récupération & nettoyage ───────────────────────────────────────────
$nom         = trim($_POST['nom']         ?? '');
$prenom      = trim($_POST['prenom']      ?? '');
$mail        = trim($_POST['mail']        ?? '');
$identifiant = trim($_POST['identifiant'] ?? '');
$mdp         =      $_POST['mdp']         ?? '';
$mdp_confirm =      $_POST['mdp_confirm'] ?? '';
$role        = trim($_POST['role']        ?? '');
$filiere     = trim($_POST['filiere']     ?? '');
$annee       = trim($_POST['annee']       ?? '');
$groupe      = trim($_POST['groupe']      ?? '');
$description = trim($_POST['description'] ?? '');

$repopulation = compact('nom', 'prenom', 'mail', 'identifiant',
                        'role', 'filiere', 'annee', 'groupe', 'description');

// ── 3. Validation rôle (whitelist) ────────────────────────────────────────
$tables_autorisees = ['Administrateur', 'Etudiant', 'Jury', 'Tuteur', 'Entreprise'];

if ($role === '' || !in_array($role, $tables_autorisees, true)) {
    redirect_error('role', $repopulation);
}

// ── 4. Champs obligatoires ────────────────────────────────────────────────
$champs_vides  = ($nom === '' || $mail === '' || $identifiant === '' || $mdp === '');
$prenom_requis = ($role !== 'Entreprise') && ($prenom === '');

if ($champs_vides || $prenom_requis) {
    redirect_error('empty', $repopulation);
}

// ── 5. Format email ───────────────────────────────────────────────────────
if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    redirect_error('mail_format', $repopulation);
}

// ── 6. Longueurs max ──────────────────────────────────────────────────────
if (strlen($nom) > 100 || strlen($prenom) > 100 ||
    strlen($mail) > 150 || strlen($identifiant) > 50) {
    redirect_error('empty', $repopulation);
}

// ── 7. Mots de passe identiques ───────────────────────────────────────────
if ($mdp !== $mdp_confirm) {
    redirect_error('mdp_match', $repopulation);
}

// ── 8. Connexion PDO (centralisée) ───────────────────────────────────────
require_once __DIR__ . '/db.php'; // → $conn

// ── 9. Unicité identifiant + mail ─────────────────────────────────────────
try {
    foreach ($tables_autorisees as $table) {
        $stmt = $conn->prepare(
            "SELECT 1 FROM `$table` WHERE identifiant = ? OR mail = ? LIMIT 1"
        );
        $stmt->execute([$identifiant, $mail]);

        if ($stmt->fetch()) {
            redirect_error('exists', $repopulation);
        }
    }
} catch (PDOException $e) {
    error_log('[StageFlow] Unicité : ' . $e->getMessage());
    redirect_error('server', $repopulation);
}

// ── 10. Hash du mot de passe (bcrypt) ────────────────────────────────────
$mdp_hash = password_hash($mdp, PASSWORD_BCRYPT);

// ── 11. Insertion selon le rôle ───────────────────────────────────────────
try {
    switch ($role) {

        case 'Etudiant':
            $stmt = $conn->prepare(
                "INSERT INTO `Etudiant`
                    (nom, prenom, mail, filiere, annee, groupe, identifiant, mdp)
                 VALUES
                    (:nom, :prenom, :mail, :filiere, :annee, :groupe, :identifiant, :mdp)"
            );
            $stmt->execute([
                ':nom'         => $nom,
                ':prenom'      => $prenom,
                ':mail'        => $mail,
                ':filiere'     => $filiere ?: null,
                ':annee'       => $annee   ?: null,
                ':groupe'      => $groupe  ?: null,
                ':identifiant' => $identifiant,
                ':mdp'         => $mdp_hash,
            ]);
            break;

        case 'Jury':
            $stmt = $conn->prepare(
                "INSERT INTO `Jury` (nom, prenom, mail, identifiant, mdp)
                 VALUES (:nom, :prenom, :mail, :identifiant, :mdp)"
            );
            $stmt->execute([
                ':nom'         => $nom,
                ':prenom'      => $prenom,
                ':mail'        => $mail,
                ':identifiant' => $identifiant,
                ':mdp'         => $mdp_hash,
            ]);
            break;

        case 'Tuteur':
            $stmt = $conn->prepare(
                "INSERT INTO `Tuteur` (nom, prenom, mail, identifiant, mdp)
                 VALUES (:nom, :prenom, :mail, :identifiant, :mdp)"
            );
            $stmt->execute([
                ':nom'         => $nom,
                ':prenom'      => $prenom,
                ':mail'        => $mail,
                ':identifiant' => $identifiant,
                ':mdp'         => $mdp_hash,
            ]);
            break;

        case 'Entreprise':
            $stmt = $conn->prepare(
                "INSERT INTO `Entreprise` (nom, description, mail, identifiant, mdp)
                 VALUES (:nom, :description, :mail, :identifiant, :mdp)"
            );
            $stmt->execute([
                ':nom'         => $nom,
                ':description' => $description ?: null,
                ':mail'        => $mail,
                ':identifiant' => $identifiant,
                ':mdp'         => $mdp_hash,
            ]);
            break;

        case 'Administrateur':
            $stmt = $conn->prepare(
                "INSERT INTO `Administrateur` (nom, prenom, mail, identifiant, mdp)
                 VALUES (:nom, :prenom, :mail, :identifiant, :mdp)"
            );
            $stmt->execute([
                ':nom'         => $nom,
                ':prenom'      => $prenom,
                ':mail'        => $mail,
                ':identifiant' => $identifiant,
                ':mdp'         => $mdp_hash,
            ]);
            break;

        default:
            redirect_error('role', $repopulation);
    }

} catch (PDOException $e) {
    error_log('[StageFlow] INSERT `' . $role . '` : ' . $e->getMessage());
    redirect_error('server', $repopulation);
}

// ── 12. Succès ────────────────────────────────────────────────────────────
header('Location: ../pages/login.php?success=1');
exit();