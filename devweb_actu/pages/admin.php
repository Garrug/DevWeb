<?php
// pages/admin.php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Administrateur') {
    header('Location: login.php');
    exit();
}

require_once '../includes/db.php';

// ── Feedback session ──────────────────────────────────────────────────────
$success = false;
$error   = '';

if (isset($_SESSION['admin_success'])) {
    $success = $_SESSION['admin_success'];
    unset($_SESSION['admin_success']);
}
if (isset($_SESSION['admin_error'])) {
    $error = $_SESSION['admin_error'];
    unset($_SESSION['admin_error']);
}

$error_messages = [
    'empty'      => 'Tous les champs obligatoires doivent être remplis.',
    'exists'     => 'Cet identifiant ou cette adresse email est déjà utilisé.',
    'role'       => 'Rôle invalide. Seuls Tuteur et Jury peuvent être créés ici.',
    'server'     => 'Erreur serveur. Veuillez réessayer.',
    'db'         => 'Erreur de connexion à la base de données.',
    'not_found'  => 'Compte introuvable.',
    'self'       => 'Vous ne pouvez pas supprimer votre propre compte.',
    'mdp_format' => 'Le mot de passe doit contenir au moins 8 caractères.',
];

// ── Récupération de tous les comptes ─────────────────────────────────────
$tables = [
    'Administrateur' => ['id' => 'idAdmin',       'label' => 'Administrateur', 'color' => '#7C3AED', 'bg' => '#F5F3FF'],
    'Etudiant'       => ['id' => 'idEtudiant',     'label' => 'Étudiant',       'color' => '#4F46E5', 'bg' => '#EEF2FF'],
    'Tuteur'         => ['id' => 'idTuteur',        'label' => 'Tuteur',         'color' => '#0284C7', 'bg' => '#E0F2FE'],
    'Jury'           => ['id' => 'idJury',          'label' => 'Jury',           'color' => '#059669', 'bg' => '#ECFDF5'],
    'Entreprise'     => ['id' => 'idEntreprise',    'label' => 'Entreprise',     'color' => '#D97706', 'bg' => '#FFFBEB'],
];

$tous_comptes = [];
$stats_roles  = [];

foreach ($tables as $table => $cfg) {
    try {
        $cols = ($table === 'Entreprise')
            ? "`{$cfg['id']}` AS id, nom, '' AS prenom, mail, identifiant"
            : "`{$cfg['id']}` AS id, nom, prenom, mail, identifiant";

        $stmt = $conn->query("SELECT $cols FROM `$table` ORDER BY nom ASC");
        $rows = $stmt->fetchAll();

        $stats_roles[$table] = count($rows);

        foreach ($rows as $row) {
            $tous_comptes[] = array_merge($row, [
                'role'        => $table,
                'role_label'  => $cfg['label'],
                'role_color'  => $cfg['color'],
                'role_bg'     => $cfg['bg'],
                'role_id_col' => $cfg['id'],
            ]);
        }
    } catch (PDOException $e) {
        error_log('[StageFlow] admin.php fetch ' . $table . ' : ' . $e->getMessage());
    }
}

$total_comptes = count($tous_comptes);
$page_title    = 'Administration';
?>
<!DOCTYPE html>
<html lang="fr">

<?php include('../components/header.php'); ?>

<body>
<div style="display:flex;min-height:100vh;">

    <?php include('../components/sidebar.php'); ?>

    <div style="flex:1;display:flex;flex-direction:column;min-width:0;">

        <!-- Topbar -->
        <header class="topbar" style="padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;">
            <div>
                <h1 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#18181B;margin:0;">
                    Administration
                </h1>
                <p style="font-size:12px;color:#A1A1AA;margin:0;">
                    Gestion des comptes utilisateurs
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="role-badge">Administrateur</span>
                <a href="/pages/profil.php">
                    <div class="avatar" aria-hidden="true"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
                </a>
            </div>
        </header>

        <main style="flex:1;padding:28px;overflow-y:auto;">

            <!-- Feedback -->
            <?php if ($success): ?>
            <div role="alert" class="animate-in delay-1"
                 style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:12px;padding:13px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span style="font-size:13px;color:#065F46;font-weight:500;"><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div role="alert" class="animate-in delay-1"
                 style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:13px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#EF4444" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span style="font-size:13px;color:#991B1B;font-weight:500;">
                    <?= htmlspecialchars($error_messages[$error] ?? 'Une erreur est survenue.') ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Stats par rôle -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:28px;" class="animate-in delay-1">
                <?php foreach ($tables as $table => $cfg): ?>
                <div class="stat-card" style="padding:16px;">
                    <p style="font-family:'Syne',sans-serif;font-size:26px;font-weight:700;color:#18181B;margin:0 0 2px;">
                        <?= $stats_roles[$table] ?? 0 ?>
                    </p>
                    <span style="font-size:11.5px;font-weight:600;padding:3px 8px;border-radius:20px;
                                 background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;
                                 border:1px solid <?= $cfg['color'] ?>33;">
                        <?= $cfg['label'] ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Grille : formulaire création + liste comptes -->
            <div style="display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start;">

                <!-- ═══ FORMULAIRE CRÉATION ═══ -->
                <div class="card animate-in delay-2" style="position:sticky;top:80px;">

                    <div style="padding:18px 22px;border-bottom:1px solid #F4F4F5;display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;background:#EEF2FF;border-radius:9px;display:flex;align-items:center;justify-content:center;" aria-hidden="true">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#4F46E5" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;">
                                Créer un compte
                            </h3>
                            <p style="font-size:11.5px;color:#A1A1AA;margin:2px 0 0;">Tuteur ou Jury uniquement</p>
                        </div>
                    </div>

                    <form action="../includes/admin_process.php" method="POST"
                          style="padding:22px;display:flex;flex-direction:column;gap:14px;">

                        <input type="hidden" name="action" value="creer">

                        <!-- Rôle -->
                        <div>
                            <label for="role" style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;">
                                RÔLE <span style="color:#EF4444;">*</span>
                            </label>
                            <select id="role" name="role" required class="input-field" style="cursor:pointer;">
                                <option value="" disabled selected>Sélectionner</option>
                                <option value="Tuteur">Tuteur</option>
                                <option value="Jury">Jury</option>
                            </select>
                        </div>

                        <!-- Nom -->
                        <div>
                            <label for="nom" style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;">
                                NOM <span style="color:#EF4444;">*</span>
                            </label>
                            <input type="text" id="nom" name="nom"
                                   placeholder="Dupont" required class="input-field">
                        </div>

                        <!-- Prénom -->
                        <div>
                            <label for="prenom" style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;">
                                PRÉNOM <span style="color:#EF4444;">*</span>
                            </label>
                            <input type="text" id="prenom" name="prenom"
                                   placeholder="Marie" required class="input-field">
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="mail" style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;">
                                EMAIL <span style="color:#EF4444;">*</span>
                            </label>
                            <input type="email" id="mail" name="mail"
                                   placeholder="marie.dupont@email.com" required class="input-field">
                        </div>

                        <!-- Identifiant -->
                        <div>
                            <label for="identifiant" style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;">
                                IDENTIFIANT <span style="color:#EF4444;">*</span>
                            </label>
                            <input type="text" id="identifiant" name="identifiant"
                                   placeholder="marie.dupont" required class="input-field">
                        </div>

                        <!-- Mot de passe -->
                        <div>
                            <label for="mdp" style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;">
                                MOT DE PASSE <span style="color:#EF4444;">*</span>
                            </label>
                            <input type="password" id="mdp" name="mdp"
                                   placeholder="•••••••• (min. 8 caractères)" required class="input-field">
                        </div>

                        <button type="submit" class="btn-primary"
                                style="justify-content:center;padding:10px;margin-top:4px;">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Créer le compte
                        </button>

                    </form>
                </div>

                <!-- ═══ LISTE DES COMPTES ═══ -->
                <div class="animate-in delay-3">

                    <!-- Barre de recherche -->
                    <div class="card" style="padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:12px;">
                        <div style="position:relative;flex:1;">
                            <div style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                                </svg>
                            </div>
                            <input type="search" id="searchComptes"
                                   placeholder="Rechercher par nom, identifiant…"
                                   class="input-field" style="padding-left:34px;font-size:13px;"
                                   aria-label="Rechercher un compte">
                        </div>
                        <!-- Filtre rôle -->
                        <select id="filterRole" class="input-field"
                                style="width:auto;font-size:13px;cursor:pointer;flex-shrink:0;">
                            <option value="">Tous les rôles</option>
                            <?php foreach ($tables as $table => $cfg): ?>
                            <option value="<?= $table ?>"><?= $cfg['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span style="font-size:12px;color:#A1A1AA;white-space:nowrap;flex-shrink:0;" aria-live="polite">
                            <span id="countComptes"><?= $total_comptes ?></span> compte<?= $total_comptes > 1 ? 's' : '' ?>
                        </span>
                    </div>

                    <!-- Table -->
                    <div class="card">
                        <table style="width:100%;border-collapse:collapse;" role="table">
                            <thead>
                                <tr style="border-bottom:1px solid #F4F4F5;">
                                    <th scope="col" style="text-align:left;padding:11px 20px;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">NOM</th>
                                    <th scope="col" style="text-align:left;padding:11px 20px;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">IDENTIFIANT</th>
                                    <th scope="col" style="text-align:left;padding:11px 20px;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">EMAIL</th>
                                    <th scope="col" style="text-align:left;padding:11px 20px;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">RÔLE</th>
                                    <th scope="col" style="text-align:right;padding:11px 20px;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">ACTION</th>
                                </tr>
                            </thead>
                            <tbody id="comptesTableBody">
                            <?php foreach ($tous_comptes as $compte):
                                $is_self = ($compte['role'] === 'Administrateur'
                                    && $compte['identifiant'] === $_SESSION['user']);
                                $full_name = trim(($compte['prenom'] ?? '') . ' ' . ($compte['nom'] ?? ''));
                            ?>
                            <tr class="table-row compte-row"
                                data-nom="<?= htmlspecialchars(strtolower($full_name)) ?>"
                                data-identifiant="<?= htmlspecialchars(strtolower($compte['identifiant'] ?? '')) ?>"
                                data-role="<?= htmlspecialchars($compte['role']) ?>">

                                <!-- Nom -->
                                <td style="padding:13px 20px;">
                                    <div style="display:flex;align-items:center;gap:9px;">
                                        <div style="width:30px;height:30px;border-radius:50%;
                                                    background:<?= $compte['role_bg'] ?>;
                                                    display:flex;align-items:center;justify-content:center;
                                                    font-size:11px;font-weight:700;
                                                    color:<?= $compte['role_color'] ?>;flex-shrink:0;"
                                             aria-hidden="true">
                                            <?= strtoupper(substr($compte['nom'] ?? '?', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p style="font-size:13px;font-weight:600;color:#18181B;margin:0;">
                                                <?= htmlspecialchars($full_name) ?>
                                            </p>
                                            <?php if ($is_self): ?>
                                            <p style="font-size:10.5px;color:#4F46E5;margin:1px 0 0;font-weight:600;">
                                                Vous
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Identifiant -->
                                <td style="padding:13px 20px;font-size:13px;color:#52525B;">
                                    @<?= htmlspecialchars($compte['identifiant'] ?? '') ?>
                                </td>

                                <!-- Email -->
                                <td style="padding:13px 20px;font-size:13px;color:#A1A1AA;max-width:200px;">
                                    <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;">
                                        <?= htmlspecialchars($compte['mail'] ?? '') ?>
                                    </span>
                                </td>

                                <!-- Rôle badge -->
                                <td style="padding:13px 20px;">
                                    <span style="font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;
                                                 background:<?= $compte['role_bg'] ?>;color:<?= $compte['role_color'] ?>;
                                                 border:1px solid <?= $compte['role_color'] ?>33;">
                                        <?= htmlspecialchars($compte['role_label']) ?>
                                    </span>
                                </td>

                                <!-- Action supprimer -->
                                <td style="padding:13px 20px;text-align:right;">
                                    <?php if ($is_self): ?>
                                    <span style="font-size:12px;color:#D4D4D8;">Votre compte</span>
                                    <?php else: ?>
                                    <form action="../includes/admin_process.php" method="POST"
                                          onsubmit="return confirmDelete('<?= htmlspecialchars(addslashes($full_name)) ?>', '<?= htmlspecialchars($compte['role_label']) ?>')">
                                        <input type="hidden" name="action"   value="supprimer">
                                        <input type="hidden" name="role"     value="<?= htmlspecialchars($compte['role']) ?>">
                                        <input type="hidden" name="id"       value="<?= (int)$compte['id'] ?>">
                                        <button type="submit" class="btn-delete"
                                                style="font-size:12px;padding:5px 12px;">
                                            Supprimer
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>

                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Empty state JS -->
                        <div id="emptySearch"
                             style="display:none;text-align:center;padding:40px 24px;">
                            <p style="font-size:13.5px;color:#A1A1AA;">
                                Aucun compte ne correspond à votre recherche.
                            </p>
                        </div>

                    </div>

                </div>

            </div>

        </main>
    </div>
</div>

<style>
.btn-delete {
    background: white;
    border: 1.5px solid #FECACA;
    color: #DC2626;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: background 0.15s;
}
.btn-delete:hover { background: #FEF2F2; }
</style>

<script>
// ── Recherche + filtre ────────────────────────────────────────────────────
const searchInput  = document.getElementById('searchComptes');
const filterSelect = document.getElementById('filterRole');
const rows         = document.querySelectorAll('.compte-row');
const countEl      = document.getElementById('countComptes');
const emptySearch  = document.getElementById('emptySearch');

function filtrerComptes() {
    const terme = searchInput.value.toLowerCase().trim();
    const role  = filterSelect.value;
    let nb = 0;

    rows.forEach(row => {
        const matchNom   = row.dataset.nom.includes(terme) || row.dataset.identifiant.includes(terme);
        const matchRole  = role === '' || row.dataset.role === role;
        const visible    = (terme === '' || matchNom) && matchRole;
        row.style.display = visible ? '' : 'none';
        if (visible) nb++;
    });

    countEl.textContent  = nb;
    emptySearch.style.display = (nb === 0 && rows.length > 0) ? 'block' : 'none';
}

searchInput.addEventListener('input',  filtrerComptes);
filterSelect.addEventListener('change', filtrerComptes);

// ── Confirmation suppression ──────────────────────────────────────────────
function confirmDelete(nom, role) {
    return confirm(
        'Supprimer le compte de ' + nom + ' (' + role + ') ?\n\n' +
        'Cette action est irréversible.'
    );
}
</script>

</body>
</html>