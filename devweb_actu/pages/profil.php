<?php
// pages/profil.php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || !isset($_SESSION['id_metier'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../includes/db.php';

$role      = $_SESSION['role'];
$id_metier = (int) $_SESSION['id_metier'];

$id_col_map = [
    'Administrateur' => 'idAdmin',
    'Etudiant'       => 'idEtudiant',
    'Tuteur'         => 'idTuteur',
    'Jury'           => 'idJury',
    'Entreprise'     => 'idEntreprise',
];

$tables_autorisees = array_keys($id_col_map);

if (!in_array($role, $tables_autorisees, true)) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$id_col = $id_col_map[$role];

try {
    $stmt = $conn->prepare("SELECT * FROM `$role` WHERE `$id_col` = ? LIMIT 1");
    $stmt->execute([$id_metier]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[StageFlow] profil.php SELECT : ' . $e->getMessage());
    $user = null;
}

if (!$user) {
    session_destroy();
    header('Location: login.php?error=session');
    exit();
}

$role_labels = [
    'Administrateur' => 'Administrateur',
    'Etudiant'       => 'Étudiant',
    'Tuteur'         => 'Tuteur',
    'Jury'           => 'Jury',
    'Entreprise'     => 'Entreprise',
];
$role_label = $role_labels[$role] ?? $role;

if ($role === 'Entreprise') {
    $initiales    = strtoupper(substr($user['nom'] ?? '?', 0, 2));
    $display_name = htmlspecialchars($user['nom'] ?? '');
} else {
    $initiales    = strtoupper(
        substr($user['prenom'] ?? '?', 0, 1) . substr($user['nom'] ?? '?', 0, 1)
    );
    $display_name = htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
}

// ── Feedback via session (jamais via $_GET) ───────────────────────────────
$success = false;
$error   = '';

if (isset($_SESSION['profil_success'])) {
    $success = true;
    unset($_SESSION['profil_success']);
}
if (isset($_SESSION['profil_error'])) {
    $error = $_SESSION['profil_error'];
    unset($_SESSION['profil_error']);
}

$error_messages = [
    'empty'       => 'Veuillez remplir tous les champs obligatoires.',
    'mail'        => 'Cette adresse e-mail est déjà utilisée.',
    'db'          => 'Erreur de base de données. Veuillez réessayer.',
    'server'      => 'Une erreur serveur est survenue.',
    'mdp_match'   => 'Les mots de passe ne correspondent pas.',
    'mdp_actuel'  => 'Le mot de passe actuel est incorrect.',
    'mail_format' => "L'adresse e-mail n'est pas valide.",
];
?>
<!DOCTYPE html>
<html lang="fr">

<?php 
    $page_title = 'Mon profil';
    include('../components/header.php');
?>

<body>

<div style="display:flex;min-height:100vh;">

    <?php include('../components/sidebar.php'); ?>

    <div style="flex:1;display:flex;flex-direction:column;min-width:0;">

        <!-- Topbar -->
        <header class="topbar" style="padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;">
            <div>
                <h1 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#18181B;margin:0;">Mon profil</h1>
                <p style="font-size:12px;color:#A1A1AA;margin:0;">Gérez vos informations personnelles</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="role-badge"><?= htmlspecialchars($role_label) ?></span>
                <a href="/pages/profil.php">
                    <div class="avatar" aria-hidden="true"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
                </a>
            </div>
        </header>

        <!-- Contenu -->
        <main style="flex:1;padding:28px;overflow-y:auto;max-width:900px;width:100%;margin:0 auto;">

            <!-- Alertes -->
            <?php if ($success): ?>
            <div role="alert" style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:12px;padding:13px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;" class="animate-in delay-1">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span style="font-size:13px;color:#065F46;font-weight:500;">Profil mis à jour avec succès.</span>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div role="alert" style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:13px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;" class="animate-in delay-1">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#EF4444" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                </svg>
                <span style="font-size:13px;color:#991B1B;font-weight:500;">
                    <?= htmlspecialchars($error_messages[$error] ?? 'Une erreur est survenue.') ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Carte identité -->
            <div class="card animate-in delay-1" style="padding:24px;margin-bottom:20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                <div style="width:60px;height:60px;border-radius:16px;background:linear-gradient(135deg,#4F46E5,#818CF8);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:white;box-shadow:0 4px 14px rgba(79,70,229,0.3);flex-shrink:0;">
                        <?= strtoupper(substr($_SESSION['user'], 0, 1)) ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:#18181B;margin:0 0 4px;"><?= $display_name ?></h2>
                    <p style="font-size:13px;color:#71717A;margin:0 0 8px;"><?= htmlspecialchars($user['mail'] ?? '') ?></p>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        <span class="role-badge"><?= htmlspecialchars($role_label) ?></span>
                        <span style="font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;background:#F4F4F5;color:#71717A;">
                            @<?= htmlspecialchars($user['identifiant'] ?? '') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Grille formulaires -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="animate-in delay-2">

                <!-- Informations personnelles (full width) -->
                <div class="card" style="grid-column:1/-1;">
                    <div style="padding:18px 22px;border-bottom:1px solid #F4F4F5;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;">Informations personnelles</h3>
                            <p style="font-size:12px;color:#A1A1AA;margin:4px 0 0;">Modifiez vos données de contact</p>
                        </div>
                        <div style="width:34px;height:34px;background:#EEF2FF;border-radius:9px;display:flex;align-items:center;justify-content:center;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#4F46E5" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    </div>

                    <form action="../includes/profil_process.php" method="POST" style="padding:22px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

                            <div>
                                <label style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;" for="nom">
                                    <?= $role === 'Entreprise' ? 'RAISON SOCIALE' : 'NOM' ?>
                                </label>
                                <input type="text" id="nom" name="nom"
                                       value="<?= htmlspecialchars($user['nom'] ?? '') ?>"
                                       required class="input-field">
                            </div>

                            <?php if ($role !== 'Entreprise'): ?>
                            <div>
                                <label style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;" for="prenom">PRÉNOM</label>
                                <input type="text" id="prenom" name="prenom"
                                       value="<?= htmlspecialchars($user['prenom'] ?? '') ?>"
                                       required class="input-field">
                            </div>
                            <?php endif; ?>

                            <div>
                                <label style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;" for="mail">ADRESSE E-MAIL</label>
                                <input type="email" id="mail" name="mail"
                                       value="<?= htmlspecialchars($user['mail'] ?? '') ?>"
                                       required class="input-field">
                            </div>

                            <?php if ($role === 'Etudiant'): ?>
                            <div>
                                <label style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;" for="filiere">FILIÈRE</label>
                                <input type="text" id="filiere" name="filiere"
                                       value="<?= htmlspecialchars($user['filiere'] ?? '') ?>"
                                       class="input-field">
                            </div>
                            <div>
                                <label style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;" for="annee">ANNÉE</label>
                                <input type="text" id="annee" name="annee"
                                       value="<?= htmlspecialchars($user['annee'] ?? '') ?>"
                                       class="input-field">
                            </div>
                            <div>
                                <label style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;" for="groupe">GROUPE</label>
                                <input type="text" id="groupe" name="groupe"
                                       value="<?= htmlspecialchars($user['groupe'] ?? '') ?>"
                                       class="input-field">
                            </div>
                            <?php endif; ?>

                            <?php if ($role === 'Entreprise'): ?>
                            <div style="grid-column:1/-1;">
                                <label style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;" for="description">DESCRIPTION</label>
                                <textarea id="description" name="description" rows="4"
                                          class="input-field" style="resize:none;"><?= htmlspecialchars($user['description'] ?? '') ?></textarea>
                            </div>
                            <?php endif; ?>

                            <div style="grid-column:1/-1;">
                                <label style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;">IDENTIFIANT</label>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <input type="text"
                                           value="<?= htmlspecialchars($user['identifiant'] ?? '') ?>"
                                           disabled
                                           class="input-field"
                                           style="background:#F9F9F9;color:#A1A1AA;cursor:not-allowed;flex:1;">
                                    <span style="font-size:12px;color:#A1A1AA;white-space:nowrap;">Non modifiable</span>
                                </div>
                            </div>

                        </div>

                        <div style="margin-top:20px;display:flex;justify-content:flex-end;">
                            <button type="submit" class="btn-primary" style="padding:10px 20px;font-size:13.5px;">
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sécurité -->
                <div class="card animate-in delay-3">
                    <div style="padding:18px 22px;border-bottom:1px solid #F4F4F5;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;">Sécurité</h3>
                            <p style="font-size:12px;color:#A1A1AA;margin:4px 0 0;">Modifiez votre mot de passe</p>
                        </div>
                        <div style="width:34px;height:34px;background:#FEF3C7;border-radius:9px;display:flex;align-items:center;justify-content:center;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#D97706" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                    </div>

                    <form action="../includes/profil_process.php" method="POST"
                          style="padding:22px;display:flex;flex-direction:column;gap:14px;">

                        <!-- Champ caché pour distinguer les deux formulaires -->
                        <input type="hidden" name="action" value="password">

                        <div>
                            <label style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;" for="mdp_actuel">MOT DE PASSE ACTUEL</label>
                            <input type="password" id="mdp_actuel" name="mdp_actuel"
                                   placeholder="••••••••" required class="input-field">
                        </div>
                        <div>
                            <label style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;" for="mdp_nouveau">NOUVEAU MOT DE PASSE</label>
                            <input type="password" id="mdp_nouveau" name="mdp_nouveau"
                                   placeholder="••••••••" required class="input-field">
                        </div>
                        <div>
                            <label style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;" for="mdp_confirm">CONFIRMER</label>
                            <input type="password" id="mdp_confirm" name="mdp_confirm"
                                   placeholder="••••••••" required class="input-field">
                        </div>
                        <div style="margin-top:4px;">
                            <button type="submit" class="btn-secondary" style="width:100%;justify-content:center;">
                                Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Info compte -->
                <div class="card animate-in delay-4" style="padding:22px;">
                    <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0 0 16px;">Informations du compte</h3>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:12px;color:#71717A;">Rôle</span>
                            <span class="role-badge"><?= htmlspecialchars($role_label) ?></span>
                        </div>
                        <div style="height:1px;background:#F4F4F5;"></div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:12px;color:#71717A;">Statut</span>
                            <span style="font-size:12px;font-weight:600;color:#059669;display:flex;align-items:center;gap:5px;">
                                <span style="width:6px;height:6px;border-radius:50%;background:#059669;display:inline-block;" aria-hidden="true"></span>
                                Actif
                            </span>
                        </div>
                        <div style="height:1px;background:#F4F4F5;"></div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:12px;color:#71717A;">Identifiant</span>
                            <span style="font-size:12px;font-weight:600;color:#3F3F46;">
                                @<?= htmlspecialchars($user['identifiant'] ?? '') ?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

        </main>

    </div>

</div>

</body>
</html>