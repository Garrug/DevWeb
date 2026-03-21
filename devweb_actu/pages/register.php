<?php
// pages/register.php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

$error_messages = [
    'empty'       => 'Tous les champs obligatoires doivent être remplis.',
    'mdp_match'   => 'Les mots de passe ne correspondent pas.',
    'role'        => 'Le rôle sélectionné est invalide.',
    'exists'      => 'Cet identifiant ou cette adresse email est déjà utilisé.',
    'mail_format' => 'L\'adresse email n\'est pas valide.',
    'db'          => 'Impossible de se connecter à la base de données.',
    'server'      => 'Une erreur serveur est survenue. Veuillez réessayer.',
];

// Lecture depuis $_SESSION (register_process.php redirige via session)
$error_code = null;
$error_text = null;
$old        = [];

if (isset($_SESSION['register_error'])) {
    $error_code = $_SESSION['register_error'];
    $error_text = $error_messages[$error_code] ?? null;
    $old        = $_SESSION['register_fields'] ?? [];
    unset($_SESSION['register_error'], $_SESSION['register_fields']);
}

$old = array_map('htmlspecialchars', array_merge([
    'nom' => '', 'prenom' => '', 'mail' => '', 'identifiant' => '',
    'role' => '', 'filiere' => '', 'annee' => '', 'groupe' => '', 'description' => '',
], $old));

$show_success = !$error_text && isset($_GET['success']);

$roles        = ['Etudiant', 'Jury', 'Tuteur', 'Entreprise', 'Administrateur'];
?>
<!DOCTYPE html>
<html lang="fr">

<?php
    $page_title = 'Inscription';
    include('../components/header.php'); 
?>

<body style="background:#0F0F14;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:32px 16px;">

<div style="position:fixed;inset:0;background:radial-gradient(ellipse at 20% 20%,rgba(79,70,229,0.1) 0%,transparent 55%),radial-gradient(ellipse at 80% 80%,rgba(129,140,248,0.07) 0%,transparent 50%);pointer-events:none;"></div>

<div style="width:100%;max-width:480px;position:relative;z-index:1;">

    <!-- Logo -->
    <div style="text-align:center;margin-bottom:28px;" class="animate-in delay-1">
        <a href="login.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:8px;">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#4F46E5,#818CF8);border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 18px rgba(79,70,229,0.35);">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <span style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:#FAFAFA;">Stage<span style="color:#818CF8;">Flow</span></span>
        </a>
        <p style="font-size:13px;color:#52525B;margin:0;">Créer un compte</p>
    </div>

    <!-- Card -->
    <div class="animate-in delay-2" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:30px;backdrop-filter:blur(12px);">

        <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:#FAFAFA;margin:0 0 6px;">Inscription</h2>
        <p style="font-size:13px;color:#71717A;margin:0 0 22px;">Renseignez vos informations pour rejoindre StageFlow.</p>

        <?php if ($error_text): ?>
        <div role="alert" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:10px;padding:12px 14px;margin-bottom:20px;display:flex;align-items:flex-start;gap:8px;">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#FCA5A5" stroke-width="2" style="flex-shrink:0;margin-top:1px;" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span style="font-size:13px;color:#FCA5A5;"><?= $error_text ?></span>
        </div>
        <?php elseif ($show_success): ?>
        <div role="alert" style="background:rgba(5,150,105,0.1);border:1px solid rgba(5,150,105,0.25);border-radius:10px;padding:12px 14px;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#6EE7B7" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span style="font-size:13px;color:#6EE7B7;">Compte créé avec succès. Vous pouvez maintenant vous connecter.</span>
        </div>
        <?php endif; ?>

        <form action="../includes/register_process.php" method="POST" style="display:flex;flex-direction:column;gap:14px;" novalidate>

            <!-- Rôle -->
            <div>
                <label for="role" style="display:block;font-size:11px;font-weight:700;color:#71717A;letter-spacing:0.06em;margin-bottom:6px;">
                    RÔLE <span style="color:#EF4444;">*</span>
                </label>
                <select id="role" name="role" required class="sf-input" style="cursor:pointer;">
                    <option value="" disabled <?= $old['role'] === '' ? 'selected' : '' ?> style="background:#1A1A2E;">Sélectionner un rôle</option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= $old['role'] === $r ? 'selected' : '' ?> style="background:#1A1A2E;"><?= $r ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Nom -->
            <div>
                <label for="nom" style="display:block;font-size:11px;font-weight:700;color:#71717A;letter-spacing:0.06em;margin-bottom:6px;">
                    <span id="labelNom">NOM</span> <span style="color:#EF4444;">*</span>
                </label>
                <input type="text" id="nom" name="nom"
                       value="<?= $old['nom'] ?>"
                       placeholder="Dupont"
                       autocomplete="family-name"
                       required
                       class="sf-input">
            </div>

            <!-- Prénom -->
            <div id="fieldPrenom">
                <label for="prenom" style="display:block;font-size:11px;font-weight:700;color:#71717A;letter-spacing:0.06em;margin-bottom:6px;">
                    PRÉNOM <span style="color:#EF4444;">*</span>
                </label>
                <input type="text" id="prenom" name="prenom"
                       value="<?= $old['prenom'] ?>"
                       placeholder="Marie"
                       autocomplete="given-name"
                       class="sf-input">
            </div>

            <!-- Description (Entreprise uniquement) -->
            <div id="fieldDescription" style="display:none;">
                <label for="description" style="display:block;font-size:11px;font-weight:700;color:#71717A;letter-spacing:0.06em;margin-bottom:6px;">
                    DESCRIPTION
                </label>
                <textarea id="description" name="description" rows="3"
                          placeholder="Présentez brièvement votre entreprise…"
                          class="sf-input" style="resize:none;"
                ><?= $old['description'] ?></textarea>
            </div>

            <!-- Champs Étudiant uniquement -->
            <div id="fieldsEtudiant" style="display:none;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                    <div>
                        <label for="filiere" style="display:block;font-size:11px;font-weight:700;color:#71717A;letter-spacing:0.06em;margin-bottom:6px;">FILIÈRE</label>
                        <input type="text" id="filiere" name="filiere"
                               value="<?= $old['filiere'] ?>"
                               placeholder="Informatique"
                               class="sf-input">
                    </div>
                    <div>
                        <label for="annee" style="display:block;font-size:11px;font-weight:700;color:#71717A;letter-spacing:0.06em;margin-bottom:6px;">ANNÉE</label>
                        <input type="text" id="annee" name="annee"
                               value="<?= $old['annee'] ?>"
                               placeholder="3A"
                               class="sf-input">
                    </div>
                </div>
                <div>
                    <label for="groupe" style="display:block;font-size:11px;font-weight:700;color:#71717A;letter-spacing:0.06em;margin-bottom:6px;">GROUPE</label>
                    <input type="text" id="groupe" name="groupe"
                           value="<?= $old['groupe'] ?>"
                           placeholder="G1"
                           class="sf-input">
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="mail" style="display:block;font-size:11px;font-weight:700;color:#71717A;letter-spacing:0.06em;margin-bottom:6px;">
                    ADRESSE EMAIL <span style="color:#EF4444;">*</span>
                </label>
                <input type="email" id="mail" name="mail"
                       value="<?= $old['mail'] ?>"
                       placeholder="marie.dupont@email.com"
                       autocomplete="email"
                       required
                       class="sf-input">
            </div>

            <!-- Identifiant -->
            <div>
                <label for="identifiant" style="display:block;font-size:11px;font-weight:700;color:#71717A;letter-spacing:0.06em;margin-bottom:6px;">
                    IDENTIFIANT <span style="color:#EF4444;">*</span>
                </label>
                <input type="text" id="identifiant" name="identifiant"
                       value="<?= $old['identifiant'] ?>"
                       placeholder="marie.dupont"
                       autocomplete="username"
                       required
                       class="sf-input">
            </div>

            <!-- Mot de passe -->
            <div>
                <label for="mdp" style="display:block;font-size:11px;font-weight:700;color:#71717A;letter-spacing:0.06em;margin-bottom:6px;">
                    MOT DE PASSE <span style="color:#EF4444;">*</span>
                </label>
                <div style="position:relative;">
                    <input type="password" id="mdp" name="mdp"
                           placeholder="••••••••"
                           autocomplete="new-password"
                           required
                           class="sf-input" style="padding-right:44px;">
                    <button type="button" id="toggleMdp"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#52525B;padding:4px;"
                            aria-label="Afficher/masquer le mot de passe">
                        <svg id="eyeIcon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Confirmation mot de passe -->
            <div>
                <label for="mdp_confirm" style="display:block;font-size:11px;font-weight:700;color:#71717A;letter-spacing:0.06em;margin-bottom:6px;">
                    CONFIRMER LE MOT DE PASSE <span style="color:#EF4444;">*</span>
                </label>
                <input type="password" id="mdp_confirm" name="mdp_confirm"
                       placeholder="••••••••"
                       autocomplete="new-password"
                       required
                       class="sf-input">
                <p id="mdpMatchMsg" style="font-size:12px;margin:6px 0 0;display:none;" role="status" aria-live="polite"></p>
            </div>

            <!-- Submit -->
            <button type="submit" class="sf-submit-btn" style="margin-top:4px;">
                Créer mon compte →
            </button>

        </form>

    </div>

    <p style="text-align:center;font-size:13px;color:#52525B;margin-top:18px;" class="animate-in delay-3">
        Déjà un compte ?
        <a href="login.php" style="color:#818CF8;text-decoration:none;font-weight:600;"
           onmouseover="this.style.textDecoration='underline'"
           onmouseout="this.style.textDecoration='none'">Se connecter</a>
    </p>

</div>

<style>
.sf-input {
    width: 100%;
    background: rgba(255,255,255,0.05);
    border: 1.5px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 14px;
    color: #FAFAFA;
    outline: none;
    font-family: 'DM Sans', sans-serif;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.sf-input:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.2);
}
.sf-input::placeholder {
    color: #52525B;
}

.sf-submit-btn {
    width: 100%;
    background: linear-gradient(135deg, #4F46E5, #6366F1);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 12px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 14px rgba(79,70,229,0.3);
}
.sf-submit-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(79,70,229,0.4);
}
.sf-submit-btn:active {
    transform: translateY(0);
}
</style>

<script>
// ── Toggle mot de passe ───────────────────────────────────────────────────
const toggleBtn = document.getElementById('toggleMdp');
const mdpInput  = document.getElementById('mdp');
const eyeIcon   = document.getElementById('eyeIcon');

const svgOpen   = `<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                   <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
const svgClosed = `<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;

toggleBtn.addEventListener('click', () => {
    const show    = mdpInput.type === 'password';
    mdpInput.type = show ? 'text' : 'password';
    eyeIcon.innerHTML = show ? svgClosed : svgOpen;
});

// ── Vérification mot de passe en temps réel ───────────────────────────────
const mdpConfirm  = document.getElementById('mdp_confirm');
const mdpMatchMsg = document.getElementById('mdpMatchMsg');

function checkMdpMatch() {
    if (!mdpConfirm.value) {
        mdpMatchMsg.style.display = 'none';
        return;
    }
    const ok = mdpInput.value === mdpConfirm.value;
    mdpMatchMsg.textContent   = ok ? '✓ Les mots de passe correspondent.' : '✗ Les mots de passe ne correspondent pas.';
    mdpMatchMsg.style.display = 'block';
    mdpMatchMsg.style.color   = ok ? '#6EE7B7' : '#FCA5A5';
}

mdpInput.addEventListener('input', checkMdpMatch);
mdpConfirm.addEventListener('input', checkMdpMatch);

// ── Affichage dynamique selon le rôle ─────────────────────────────────────
const roleSelect       = document.getElementById('role');
const fieldPrenom      = document.getElementById('fieldPrenom');
const fieldDescription = document.getElementById('fieldDescription');
const fieldsEtudiant   = document.getElementById('fieldsEtudiant');
const labelNom         = document.getElementById('labelNom');
const inputPrenom      = document.getElementById('prenom');

function updateChamps() {
    const role         = roleSelect.value;
    const isEntreprise = role === 'Entreprise';
    const isEtudiant   = role === 'Etudiant';

    fieldPrenom.style.display      = isEntreprise ? 'none' : '';
    inputPrenom.required           = !isEntreprise;
    labelNom.textContent           = isEntreprise ? 'RAISON SOCIALE' : 'NOM';
    fieldDescription.style.display = isEntreprise ? '' : 'none';
    fieldsEtudiant.style.display   = isEtudiant   ? 'flex' : 'none';
    fieldsEtudiant.style.flexDirection = 'column';
    fieldsEtudiant.style.gap       = '14px';
}

roleSelect.addEventListener('change', updateChamps);
updateChamps();
</script>

</body>
</html>