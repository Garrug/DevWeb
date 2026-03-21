<?php
// includes/admin_process.php
session_start();

// Réservé à l'administrateur
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Administrateur') {
    header('Location: ../pages/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/admin.php');
    exit();
}

require_once __DIR__ . '/db.php';

$action = $_POST['action'] ?? '';

function redirect_admin(string $key, bool $is_success = false): never {
    if ($is_success) {
        $_SESSION['admin_success'] = $key;
    } else {
        $_SESSION['admin_error'] = $key;
    }
    header('Location: ../pages/admin.php');
    exit();
}

// =========================================================================
// ACTION : CRÉER un compte Tuteur ou Jury
// =========================================================================
if ($action === 'creer') {

    $role        = trim($_POST['role']        ?? '');
    $nom         = trim($_POST['nom']         ?? '');
    $prenom      = trim($_POST['prenom']      ?? '');
    $mail        = trim($_POST['mail']        ?? '');
    $identifiant = trim($_POST['identifiant'] ?? '');
    $mdp         =      $_POST['mdp']         ?? '';

    // Whitelist stricte : seuls Tuteur et Jury peuvent être créés ici
    if (!in_array($role, ['Tuteur', 'Jury'], true)) {
        redirect_admin('role');
    }

    // Champs obligatoires
    if ($nom === '' || $prenom === '' || $mail === '' || $identifiant === '' || $mdp === '') {
        redirect_admin('empty');
    }

    // Format email
    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        redirect_admin('empty');
    }

    // Longueur mot de passe
    if (strlen($mdp) < 8) {
        redirect_admin('mdp_format');
    }

    // Longueurs max
    if (strlen($nom) > 100 || strlen($prenom) > 100 ||
        strlen($mail) > 150 || strlen($identifiant) > 50) {
        redirect_admin('empty');
    }

    // Vérifier unicité identifiant + mail dans toutes les tables
    $toutes_tables = ['Administrateur', 'Etudiant', 'Tuteur', 'Jury', 'Entreprise'];
    $id_cols = [
        'Administrateur' => 'idAdmin',
        'Etudiant'       => 'idEtudiant',
        'Tuteur'         => 'idTuteur',
        'Jury'           => 'idJury',
        'Entreprise'     => 'idEntreprise',
    ];

    try {
        foreach ($toutes_tables as $table) {
            $stmt = $conn->prepare(
                "SELECT 1 FROM `$table` WHERE identifiant = ? OR mail = ? LIMIT 1"
            );
            $stmt->execute([$identifiant, $mail]);
            if ($stmt->fetch()) {
                redirect_admin('exists');
            }
        }
    } catch (PDOException $e) {
        error_log('[StageFlow] admin_process unicité : ' . $e->getMessage());
        redirect_admin('server');
    }

    // Hash du mot de passe
    $mdp_hash = password_hash($mdp, PASSWORD_BCRYPT);

    // Insertion
    try {
        $stmt = $conn->prepare(
            "INSERT INTO `$role` (nom, prenom, mail, identifiant, mdp)
             VALUES (:nom, :prenom, :mail, :identifiant, :mdp)"
        );
        $stmt->execute([
            ':nom'         => $nom,
            ':prenom'      => $prenom,
            ':mail'        => $mail,
            ':identifiant' => $identifiant,
            ':mdp'         => $mdp_hash,
        ]);

        redirect_admin(
            "Compte $role créé avec succès pour $prenom $nom (@$identifiant).",
            true
        );

    } catch (PDOException $e) {
        error_log('[StageFlow] admin_process INSERT : ' . $e->getMessage());
        redirect_admin('server');
    }
}

// =========================================================================
// ACTION : SUPPRIMER un compte
// =========================================================================
if ($action === 'supprimer') {

    $role_cible = trim($_POST['role'] ?? '');
    $id_cible   = (int)($_POST['id']  ?? 0);

    $id_col_map = [
        'Administrateur' => 'idAdmin',
        'Etudiant'       => 'idEtudiant',
        'Tuteur'         => 'idTuteur',
        'Jury'           => 'idJury',
        'Entreprise'     => 'idEntreprise',
    ];

    // Validation
    if (!isset($id_col_map[$role_cible]) || $id_cible === 0) {
        redirect_admin('not_found');
    }

    // Empêcher l'admin de se supprimer lui-même
    $id_col_admin = $id_col_map['Administrateur'];
    if ($role_cible === 'Administrateur') {
        try {
            $stmt = $conn->prepare(
                "SELECT identifiant FROM Administrateur WHERE idAdmin = ? LIMIT 1"
            );
            $stmt->execute([$id_cible]);
            $row = $stmt->fetch();
            if ($row && $row['identifiant'] === $_SESSION['user']) {
                redirect_admin('self');
            }
        } catch (PDOException $e) {
            error_log('[StageFlow] admin_process self-check : ' . $e->getMessage());
            redirect_admin('server');
        }
    }

    $id_col = $id_col_map[$role_cible];

    try {
        $conn->beginTransaction();

        // Suppression des données liées selon le rôle
        if ($role_cible === 'Etudiant') {
            $stmt = $conn->prepare("DELETE FROM candidater WHERE idEtudiant = ?");
            $stmt->execute([$id_cible]);
        }

        if ($role_cible === 'Entreprise') {
            // Supprimer candidatures des offres non validées
            $stmt = $conn->prepare(
                "DELETE c FROM candidater c
                 JOIN Offre_de_stage o ON c.idOffre = o.idOffre
                 WHERE o.idEntreprise = ?
                   AND NOT (
                       LOWER(c.choixEntreprise) = 'accepté'
                       AND LOWER(c.choixEtudiant) = 'accepté'
                   )"
            );
            $stmt->execute([$id_cible]);

            // Supprimer offres non validées
            $stmt = $conn->prepare(
                "DELETE FROM Offre_de_stage
                 WHERE idEntreprise = ?
                   AND idOffre NOT IN (
                       SELECT DISTINCT idOffre FROM candidater
                       WHERE LOWER(choixEntreprise) = 'accepté'
                         AND LOWER(choixEtudiant)   = 'accepté'
                   )"
            );
            $stmt->execute([$id_cible]);
        }

        // Suppression du compte
        $stmt = $conn->prepare("DELETE FROM `$role_cible` WHERE `$id_col` = ?");
        $stmt->execute([$id_cible]);

        $conn->commit();

        redirect_admin("Compte supprimé avec succès.", true);

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log('[StageFlow] admin_process DELETE : ' . $e->getMessage());
        redirect_admin('server');
    }
}

// Action inconnue
header('Location: ../pages/admin.php');
exit();