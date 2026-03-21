<?php
// pages/offres.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/db.php';

$offres   = [];
$db_error = false;

try {
    $stmt = $conn->query(
        "SELECT o.idOffre,
                o.description,
                o.niveau,
                o.duree,
                o.dateDepot,
                o.debutStage,
                e.nom          AS entreprise_nom,
                e.idEntreprise
         FROM Offre_de_stage o
         JOIN Entreprise e ON o.idEntreprise = e.idEntreprise
         ORDER BY o.dateDepot DESC"
    );
    $offres = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('[StageFlow] offres.php : ' . $e->getMessage());
    $db_error = true;
}

$total = count($offres);

$niveau_badges = [
    'Bac+2' => ['bg' => '#F4F4F5', 'color' => '#71717A', 'border' => '#E4E4E7'],
    'Bac+3' => ['bg' => '#EEF2FF', 'color' => '#4F46E5', 'border' => '#C7D2FE'],
    'Bac+4' => ['bg' => '#F5F3FF', 'color' => '#7C3AED', 'border' => '#DDD6FE'],
    'Bac+5' => ['bg' => '#ECFDF5', 'color' => '#059669', 'border' => '#A7F3D0'],
];
$niveau_default = ['bg' => '#F4F4F5', 'color' => '#71717A', 'border' => '#E4E4E7'];

$is_entreprise = ($_SESSION['role'] === 'Entreprise');
$is_etudiant   = ($_SESSION['role'] === 'Etudiant');

// Palette avatar — déclarée une fois, pas dans la boucle
$avatar_colors = ['#4F46E5', '#059669', '#D97706', '#DC2626', '#7C3AED', '#0284C7'];

// Compteur entreprises unique — calculé une fois
$nb_entreprises = count(array_unique(array_column($offres, 'idEntreprise')));
?>
<!DOCTYPE html>
<html lang="fr">

<?php
    $page_title = 'Offres de stage';
    include('../components/header.php');
?>

<body>

<div style="display:flex;min-height:100vh;">

    <?php include('../components/sidebar.php'); ?>

    <div style="flex:1;display:flex;flex-direction:column;min-width:0;">

        <!-- Topbar -->
        <header class="topbar" style="padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;">
            <div>
                <h1 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#18181B;margin:0;">Offres de stage</h1>
                <p style="font-size:12px;color:#A1A1AA;margin:0;">Consultez et postulez aux offres disponibles</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <?php if ($is_entreprise): ?>
                <a href="mes_offres.php" class="btn-primary">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Gérer mes offres
                </a>
                <?php endif; ?>
                <span class="role-badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
                <div class="avatar" aria-hidden="true"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
            </div>
        </header>

        <main style="flex:1;padding:28px;overflow-y:auto;">

            <?php if ($db_error): ?>
            <div role="alert" style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#EF4444" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span style="font-size:13px;color:#DC2626;">Impossible de charger les offres. Vérifiez la connexion à la base de données.</span>
            </div>
            <?php endif; ?>

            <!-- Stats mini -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px;" class="animate-in delay-1">
                <div class="stat-card" style="padding:18px;">
                    <p style="font-family:'Syne',sans-serif;font-size:28px;font-weight:700;color:#18181B;margin:0 0 2px;"><?= $total ?></p>
                    <p style="font-size:12px;color:#71717A;margin:0;">Offres disponibles</p>
                </div>
                <div class="stat-card" style="padding:18px;">
                    <p style="font-family:'Syne',sans-serif;font-size:28px;font-weight:700;color:#18181B;margin:0 0 2px;"><?= $nb_entreprises ?></p>
                    <p style="font-size:12px;color:#71717A;margin:0;">Entreprises partenaires</p>
                </div>
            </div>

            <!-- Barre recherche + filtres -->
            <div class="card animate-in delay-2" style="padding:16px 20px;margin-bottom:20px;">
                <div style="position:relative;margin-bottom:12px;">
                    <div style="position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                        </svg>
                    </div>
                    <input type="search" id="searchInput"
                           placeholder="Rechercher par description ou entreprise…"
                           class="input-field"
                           style="padding-left:36px;"
                           aria-label="Rechercher une offre">
                </div>
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;">
                    <span style="font-size:11.5px;font-weight:600;color:#A1A1AA;margin-right:2px;">Niveau :</span>
                    <?php
                    $niveaux_presents = array_unique(array_filter(array_column($offres, 'niveau')));
                    sort($niveaux_presents);
                    foreach (array_merge([''], $niveaux_presents) as $niv):
                        $label  = $niv === '' ? 'Tous' : htmlspecialchars($niv);
                        $active = $niv === '';
                    ?>
                    <button class="filter-btn"
                            data-filter="<?= htmlspecialchars($niv) ?>"
                            aria-pressed="<?= $active ? 'true' : 'false' ?>"
                            style="font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;border:1.5px solid <?= $active ? '#4F46E5' : '#E4E4E7' ?>;background:<?= $active ? '#4F46E5' : 'white' ?>;color:<?= $active ? 'white' : '#71717A' ?>;cursor:pointer;transition:all 0.15s;">
                        <?= $label ?>
                    </button>
                    <?php endforeach; ?>
                    <span style="margin-left:auto;font-size:11.5px;color:#A1A1AA;" aria-live="polite">
                        <span id="countVisible"><?= $total ?></span> résultat<span id="pluriel"><?= $total > 1 ? 's' : '' ?></span>
                    </span>
                </div>
            </div>

            <!-- Grille de cards -->
            <div id="offresGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;" class="animate-in delay-3">

                <?php if (empty($offres) && !$db_error): ?>
                <div style="grid-column:1/-1;text-align:center;padding:60px 0;">
                    <div style="width:48px;height:48px;background:#F4F4F5;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <p style="font-size:14px;color:#A1A1AA;">Aucune offre de stage disponible pour le moment.</p>
                </div>
                <?php endif; ?>

                <?php foreach ($offres as $o):
                    $nb          = $niveau_badges[$o['niveau']] ?? $niveau_default;
                    $date_depot  = $o['dateDepot']  ? date('d/m/Y', strtotime($o['dateDepot']))  : '—';
                    $date_debut  = $o['debutStage'] ? date('d/m/Y', strtotime($o['debutStage'])) : '—';
                    $words       = explode(' ', trim($o['entreprise_nom']));
                    $initials    = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                    $avatarColor = $avatar_colors[abs(crc32($o['entreprise_nom'])) % count($avatar_colors)];
                ?>
                <article class="offre-card"
                     data-description="<?= htmlspecialchars(strtolower($o['description'] ?? '')) ?>"
                     data-entreprise="<?= htmlspecialchars(strtolower($o['entreprise_nom'])) ?>"
                     data-niveau="<?= htmlspecialchars($o['niveau'] ?? '') ?>">

                    <!-- Header card -->
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:38px;height:38px;border-radius:10px;background:<?= $avatarColor ?>;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:white;flex-shrink:0;" aria-hidden="true">
                                <?= $initials ?>
                            </div>
                            <div>
                                <h3 style="font-size:14px;font-weight:700;color:#18181B;margin:0;font-family:'Syne',sans-serif;"><?= htmlspecialchars($o['entreprise_nom']) ?></h3>
                                <p style="font-size:11px;color:#A1A1AA;margin:2px 0 0;">Offre #<?= (int)$o['idOffre'] ?></p>
                            </div>
                        </div>
                        <span style="font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:<?= $nb['bg'] ?>;color:<?= $nb['color'] ?>;border:1px solid <?= $nb['border'] ?>;flex-shrink:0;">
                            <?= $o['niveau'] ? htmlspecialchars($o['niveau']) : 'Tous niveaux' ?>
                        </span>
                    </div>

                    <!-- Description -->
                    <p style="font-size:13px;color:#52525B;line-height:1.6;margin:0;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;flex:1;">
                        <?= htmlspecialchars($o['description'] ?? 'Aucune description.') ?>
                    </p>

                    <!-- Métadonnées -->
                    <div style="display:flex;flex-wrap:wrap;gap:10px;">
                        <?php if ($o['duree']): ?>
                        <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717A;">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?= htmlspecialchars($o['duree']) ?>
                        </span>
                        <?php endif; ?>
                        <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717A;">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Début : <?= $date_debut ?>
                        </span>
                        <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#A1A1AA;margin-left:auto;">
                            <?= $date_depot ?>
                        </span>
                    </div>

                    <!-- Actions -->
                    <div style="display:flex;gap:8px;padding-top:4px;border-top:1px solid #F4F4F5;margin-top:2px;">
                        <a href="offre_detail.php?id=<?= (int)$o['idOffre'] ?>"
                           class="btn-secondary"
                           style="flex:1;justify-content:center;font-size:12.5px;padding:8px;">
                            Voir détails
                        </a>
                        <?php if ($is_etudiant): ?>
                        <a href="candidature_new.php?offre=<?= (int)$o['idOffre'] ?>"
                           class="btn-primary"
                           style="flex:1;justify-content:center;font-size:12.5px;padding:8px;">
                            Postuler
                        </a>
                        <?php endif; ?>
                    </div>

                </article>
                <?php endforeach; ?>

            </div>

            <!-- Empty state filtres JS -->
            <div id="emptyState" style="display:none;text-align:center;padding:60px 0;" aria-live="polite">
                <div style="width:48px;height:48px;background:#F4F4F5;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p style="font-size:14px;color:#A1A1AA;margin-bottom:12px;">Aucune offre ne correspond à votre recherche.</p>
                <button onclick="resetFiltres()"
                        style="font-size:12px;font-weight:600;color:#4F46E5;background:none;border:none;cursor:pointer;text-decoration:underline;">
                    Réinitialiser les filtres
                </button>
            </div>

        </main>

    </div>

</div>

<?php include('../components/footer.php'); ?>

<script>
const searchInput = document.getElementById('searchInput');
const filterBtns  = document.querySelectorAll('.filter-btn');
const cards       = document.querySelectorAll('.offre-card');
const emptyState  = document.getElementById('emptyState');
const countEl     = document.getElementById('countVisible');
const plurielEl   = document.getElementById('pluriel');
let activeNiveau  = '';

function filtrer() {
    const terme = searchInput.value.toLowerCase().trim();
    let nb = 0;

    cards.forEach(card => {
        const ok = (terme === '' || card.dataset.description.includes(terme) || card.dataset.entreprise.includes(terme))
                && (activeNiveau === '' || card.dataset.niveau === activeNiveau);
        card.style.display = ok ? '' : 'none';
        if (ok) nb++;
    });

    countEl.textContent      = nb;
    plurielEl.textContent    = nb > 1 ? 's' : '';
    emptyState.style.display = (nb === 0 && cards.length > 0) ? 'block' : 'none';
}

function resetFiltres() {
    searchInput.value = '';
    activeNiveau      = '';
    filterBtns.forEach(b => {
        const isAll         = b.dataset.filter === '';
        b.style.background  = isAll ? '#4F46E5' : 'white';
        b.style.color       = isAll ? 'white'   : '#71717A';
        b.style.borderColor = isAll ? '#4F46E5' : '#E4E4E7';
        b.setAttribute('aria-pressed', isAll ? 'true' : 'false');
    });
    filtrer();
}

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        activeNiveau = btn.dataset.filter;

        filterBtns.forEach(b => {
            b.style.background  = 'white';
            b.style.color       = '#71717A';
            b.style.borderColor = '#E4E4E7';
            b.setAttribute('aria-pressed', 'false');
        });

        btn.style.background  = '#4F46E5';
        btn.style.color       = 'white';
        btn.style.borderColor = '#4F46E5';
        btn.setAttribute('aria-pressed', 'true');

        filtrer();
    });
});

searchInput.addEventListener('input', filtrer);
</script>

</body>
</html>