<?php
// includes/offre_process.php
session_start();

// ── Garde ─────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Entreprise') {
    header('Location: ../pages/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/mes_offres.php');
    exit();
}

require_once __DIR__ . '/db.php';

$id_entreprise = (int)($_SESSION['id_metier'] ?? 0);

if ($id_entreprise === 0) {
    $_SESSION['offre_feedback'] = 'session';
    header('Location: ../pages/mes_offres.php');
    exit();
}

$action = $_POST['action'] ?? '';

// ── Utilitaire : redirection avec feedback session ────────────────────────
function redirect_offre(string $key): never {
    $_SESSION['offre_feedback'] = $key;
    header('Location: ../pages/mes_offres.php');
    exit();
}

// ── Validation partagée (ajouter + modifier) ──────────────────────────────
function valider_champs(string $description, string $debut_stage, string $duree, string $niveau): ?string {
    if ($description === '' || $debut_stage === '') return 'empty';

    $date_obj = DateTime::createFromFormat('Y-m-d', $debut_stage);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $debut_stage) return 'date';

    if ($duree !== '' && (!ctype_digit($duree) || (int)$duree <= 0)) return 'duree';

    $niveaux_ok = ['', 'Bac+2', 'Bac+3', 'Bac+4', 'Bac+5'];
    if (!in_array($niveau, $niveaux_ok, true)) return 'niveau';

    return null;
}

// ── Vérification propriété ────────────────────────────────────────────────
function verifier_propriete(PDO $conn, int $id_offre, int $id_entreprise): bool {
    $check = $conn->prepare(
        "SELECT 1 FROM Offre_de_stage WHERE idOffre = ? AND idEntreprise = ? LIMIT 1"
    );
    $check->execute([$id_offre, $id_entreprise]);
    return (bool)$check->fetch();
}


// ── ACTION : AJOUTER ──────────────────────────────────────────────────────
if ($action === 'ajouter') {

    $description = trim($_POST['description'] ?? '');
    $niveau      = trim($_POST['niveau']      ?? '');
    $duree       = trim($_POST['duree']       ?? '');
    $debut_stage = trim($_POST['debutStage']  ?? '');

    $err = valider_champs($description, $debut_stage, $duree, $niveau);
    if ($err) { redirect_offre($err); }

    try {
        $stmt = $conn->prepare(
            "INSERT INTO Offre_de_stage (dateDepot, description, niveau, duree, debutStage, idEntreprise)
             VALUES (CURDATE(), :description, :niveau, :duree, :debutStage, :idEntreprise)"
        );
        $stmt->execute([
            ':description'  => $description,
            ':niveau'       => $niveau       ?: null,
            ':duree'        => $duree !== '' ? (int)$duree : null,
            ':debutStage'   => $debut_stage,
            ':idEntreprise' => $id_entreprise,
        ]);
        redirect_offre('ajoute');

    } catch (PDOException $e) {
        error_log('[StageFlow] offre_process INSERT : ' . $e->getMessage());
        redirect_offre('server');
    }
}


// ── ACTION : SUPPRIMER ────────────────────────────────────────────────────
if ($action === 'supprimer') {

    $id_offre = (int)($_POST['idOffre'] ?? 0);

    if ($id_offre === 0) { redirect_offre('forbidden'); }

    try {
        if (!verifier_propriete($conn, $id_offre, $id_entreprise)) {
            redirect_offre('forbidden');
        }

        $stmt = $conn->prepare(
            "DELETE FROM Offre_de_stage WHERE idOffre = ? AND idEntreprise = ?"
        );
        $stmt->execute([$id_offre, $id_entreprise]);
        redirect_offre('supprime');

    } catch (PDOException $e) {
        error_log('[StageFlow] offre_process DELETE : ' . $e->getMessage());
        redirect_offre('server');
    }
}


// ── ACTION : MODIFIER ─────────────────────────────────────────────────────
if ($action === 'modifier') {

    $id_offre    = (int)($_POST['idOffre']    ?? 0);
    $description = trim($_POST['description'] ?? '');
    $niveau      = trim($_POST['niveau']      ?? '');
    $duree       = trim($_POST['duree']       ?? '');
    $debut_stage = trim($_POST['debutStage']  ?? '');

    if ($id_offre === 0) { redirect_offre('forbidden'); }

    $err = valider_champs($description, $debut_stage, $duree, $niveau);
    if ($err) { redirect_offre($err); }

    try {
        if (!verifier_propriete($conn, $id_offre, $id_entreprise)) {
            redirect_offre('forbidden');
        }

        $stmt = $conn->prepare(
            "UPDATE Offre_de_stage
             SET description = :description,
                 niveau      = :niveau,
                 duree       = :duree,
                 debutStage  = :debutStage
             WHERE idOffre = :idOffre AND idEntreprise = :idEntreprise"
        );
        $stmt->execute([
            ':description'  => $description,
            ':niveau'       => $niveau       ?: null,
            ':duree'        => $duree !== '' ? (int)$duree : null,
            ':debutStage'   => $debut_stage,
            ':idOffre'      => $id_offre,
            ':idEntreprise' => $id_entreprise,
        ]);
        redirect_offre('modifie');

    } catch (PDOException $e) {
        error_log('[StageFlow] offre_process UPDATE : ' . $e->getMessage());
        redirect_offre('server');
    }
}

// Action inconnue
header('Location: ../pages/mes_offres.php');
exit();