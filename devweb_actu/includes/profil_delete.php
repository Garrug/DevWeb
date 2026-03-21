<?php
// includes/profil_delete.php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || !isset($_SESSION['id_metier'])) {
    header('Location: ../pages/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/profil.php');
    exit();
}

require_once __DIR__ . '/db.php';

$role      = $_SESSION['role'];
$id_metier = (int)$_SESSION['id_metier'];

$id_col_map = [
    'Administrateur' => 'idAdmin',
    'Etudiant'       => 'idEtudiant',
    'Tuteur'         => 'idTuteur',
    'Jury'           => 'idJury',
    'Entreprise'     => 'idEntreprise',
];

if (!isset($id_col_map[$role])) {
    session_destroy();
    header('Location: ../pages/login.php');
    exit();
}

// ── Confirmation par mot de passe ─────────────────────────────────────────
$mdp_saisi = $_POST['mdp_confirmation'] ?? '';

if ($mdp_saisi === '') {
    $_SESSION['profil_error'] = 'delete_empty';
    header('Location: ../pages/profil.php');
    exit();
}

// Vérifier le mot de passe
try {
    $id_col = $id_col_map[$role];
    $stmt   = $conn->prepare("SELECT mdp FROM `$role` WHERE `$id_col` = ? LIMIT 1");
    $stmt->execute([$id_metier]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($mdp_saisi, $row['mdp'])) {
        $_SESSION['profil_error'] = 'delete_mdp';
        header('Location: ../pages/profil.php');
        exit();
    }
} catch (PDOException $e) {
    error_log('[StageFlow] profil_delete vérif mdp : ' . $e->getMessage());
    $_SESSION['profil_error'] = 'server';
    header('Location: ../pages/profil.php');
    exit();
}

// =========================================================================
// SUPPRESSION SELON LE RÔLE
// Les FK avec ON DELETE CASCADE gèrent les tables liées si configurées.
// Sinon on supprime manuellement dans le bon ordre.
// =========================================================================
try {
    $conn->beginTransaction();

    // ── ÉTUDIANT ──────────────────────────────────────────────────────────
    // Supprime ses candidatures (non validées ET validées)
    // Un stage déjà validé = les deux ont accepté, on supprime quand même
    // car sans l'étudiant le dossier n'a plus de sens
    if ($role === 'Etudiant') {

        // Supprimer toutes ses candidatures
        $stmt = $conn->prepare("DELETE FROM candidater WHERE idEtudiant = ?");
        $stmt->execute([$id_metier]);

        // Supprimer le compte étudiant
        $stmt = $conn->prepare("DELETE FROM Etudiant WHERE idEtudiant = ?");
        $stmt->execute([$id_metier]);
    }

    // ── ENTREPRISE ────────────────────────────────────────────────────────
    // - Supprimer les candidatures des offres NON validées
    // - Supprimer les offres NON validées
    // - Conserver les offres et candidatures où les deux ont accepté
    //   (trace du stage validé pour l'étudiant et le tuteur)
    // - Supprimer le compte entreprise
    elseif ($role === 'Entreprise') {

        // Supprimer les candidatures des offres non encore validées
        // (validée = choixEntreprise = 'accepté' ET choixEtudiant = 'accepté')
        $stmt = $conn->prepare(
            "DELETE c FROM candidater c
             JOIN Offre_de_stage o ON c.idOffre = o.idOffre
             WHERE o.idEntreprise = ?
               AND NOT (
                   LOWER(c.choixEntreprise) = 'accepté'
                   AND LOWER(c.choixEtudiant) = 'accepté'
               )"
        );
        $stmt->execute([$id_metier]);

        // Supprimer les offres non validées
        // (une offre est "validée" si au moins une candidature est acceptée des deux côtés)
        $stmt = $conn->prepare(
            "DELETE FROM Offre_de_stage
             WHERE idEntreprise = ?
               AND idOffre NOT IN (
                   SELECT DISTINCT idOffre FROM candidater
                   WHERE LOWER(choixEntreprise) = 'accepté'
                     AND LOWER(choixEtudiant)   = 'accepté'
               )"
        );
        $stmt->execute([$id_metier]);

        // Supprimer le compte entreprise
        // Les offres validées restent en base (idEntreprise devient orphelin,
        // acceptable car elles servent de trace historique)
        $stmt = $conn->prepare("DELETE FROM Entreprise WHERE idEntreprise = ?");
        $stmt->execute([$id_metier]);
    }

    // ── TUTEUR / JURY / ADMINISTRATEUR ───────────────────────────────────
    // Suppression simple du compte, pas de données liées critiques
    else {
        $id_col = $id_col_map[$role];
        $stmt   = $conn->prepare("DELETE FROM `$role` WHERE `$id_col` = ?");
        $stmt->execute([$id_metier]);
    }

    $conn->commit();

} catch (PDOException $e) {
    $conn->rollBack();
    error_log('[StageFlow] profil_delete DELETE : ' . $e->getMessage());
    $_SESSION['profil_error'] = 'server';
    header('Location: ../pages/profil.php');
    exit();
}

// ── Déconnexion propre après suppression ─────────────────────────────────
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

header('Location: ../pages/login.php?deleted=1');
exit();