<?php
// pages/entreprises.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Connexion centralisée — plus de credentials en dur ici
require_once '../includes/db.php';

$entreprises = [];
$db_error    = false;

try {
    $stmt = $conn->query(
        "SELECT e.idEntreprise,
                e.nom,
                e.description,
                e.mail,
                COUNT(o.idOffre) AS nb_offres
         FROM Entreprise e
         LEFT JOIN Offre_de_stage o ON o.idEntreprise = e.idEntreprise
         GROUP BY e.idEntreprise, e.nom, e.description, e.mail
         ORDER BY e.nom ASC"
    );
    $entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[StageFlow] entreprises.php requête : ' . $e->getMessage());
    $db_error = true;
}

$total   = count($entreprises);
$palette = ['#4F46E5','#059669','#D97706','#DC2626','#7C3AED','#0284C7','#0891B2','#65A30D'];
?>
<!DOCTYPE html>
<html lang="fr">

<?php
    $page_title = 'Entreprises';
    include('../components/header.php');
?>

<body>

<div style="display:flex;min-height:100vh;">

    <?php include('../components/sidebar.php'); ?>

    <div style="flex:1;display:flex;flex-direction:column;min-width:0;">

        <!-- Topbar -->
        <header class="topbar" style="padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;">
            <div>
                <h1 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#18181B;margin:0;">Entreprises</h1>
                <p style="font-size:12px;color:#A1A1AA;margin:0;">Découvrez les entreprises partenaires</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="role-badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
                <a href="/pages/profil.php">
                    <div class="avatar" aria-hidden="true"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
                </a>
            </div>
        </header>

        <main style="flex:1;padding:28px;overflow-y:auto;">

            <?php if ($db_error): ?>
            <div role="alert" style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:13px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#EF4444" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span style="font-size:13px;color:#DC2626;">Impossible de charger les entreprises.</span>
            </div>
            <?php endif; ?>

            <!-- Barre recherche -->
            <div class="card animate-in delay-1" style="padding:14px 18px;margin-bottom:22px;display:flex;align-items:center;gap:14px;">
                <div style="position:relative;flex:1;">
                    <div style="position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;" aria-hidden="true">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                        </svg>
                    </div>
                    <input type="search" id="searchInput"
                           placeholder="Rechercher une entreprise…"
                           class="input-field"
                           style="padding-left:36px;"
                           aria-label="Rechercher une entreprise">
                </div>
                <span style="font-size:13px;color:#A1A1AA;white-space:nowrap;flex-shrink:0;" aria-live="polite">
                    <span id="countVisible"><?= $total ?></span> entreprise<?= $total > 1 ? 's' : '' ?>
                </span>
            </div>

            <!-- Grille entreprises -->
            <div id="entreprisesGrid"
                 style="display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:16px;"
                 class="animate-in delay-2">

                <?php if (empty($entreprises) && !$db_error): ?>
                <div style="grid-column:1/-1;text-align:center;padding:60px 0;">
                    <div style="width:48px;height:48px;background:#F4F4F5;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;" aria-hidden="true">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <p style="font-size:14px;color:#A1A1AA;">Aucune entreprise enregistrée.</p>
                </div>
                <?php endif; ?>

                <?php foreach ($entreprises as $e):
                    $avatarColor = $palette[abs(crc32($e['nom'])) % count($palette)];
                    $words       = explode(' ', trim($e['nom']));
                    $initials    = strtoupper(
                        substr($words[0], 0, 1) .
                        (isset($words[1]) ? substr($words[1], 0, 1) : substr($words[0], 1, 1))
                    );
                    $nb_offres = (int)$e['nb_offres'];
                ?>
                <article class="offre-card entreprise-card"
                         data-nom="<?= htmlspecialchars(strtolower($e['nom'])) ?>"
                         style="gap:16px;">

                    <!-- Header : avatar + nom -->
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:44px;height:44px;border-radius:12px;background:<?= $avatarColor ?>;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:white;flex-shrink:0;box-shadow:0 4px 10px <?= $avatarColor ?>44;"
                             aria-hidden="true">
                            <?= $initials ?>
                        </div>
                        <div style="min-width:0;">
                            <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars($e['nom']) ?>
                            </h3>
                            <p style="font-size:11.5px;color:#A1A1AA;margin:2px 0 0;">Entreprise #<?= (int)$e['idEntreprise'] ?></p>
                        </div>
                    </div>

                    <!-- Description -->
                    <p style="font-size:13px;color:#52525B;line-height:1.6;margin:0;flex:1;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                        <?php if ($e['description']): ?>
                            <?= htmlspecialchars($e['description']) ?>
                        <?php else: ?>
                            <span style="color:#D4D4D8;font-style:italic;">Aucune description disponible.</span>
                        <?php endif; ?>
                    </p>

                    <!-- Footer : email + offres + bouton -->
                    <div style="border-top:1px solid #F4F4F5;padding-top:14px;display:flex;flex-direction:column;gap:10px;">
                        <?php if ($e['mail']): ?>
                        <div style="display:flex;align-items:center;gap:7px;overflow:hidden;">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="2" style="flex-shrink:0;" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <a href="mailto:<?= htmlspecialchars($e['mail']) ?>"
                               style="font-size:12.5px;color:#71717A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-decoration:none;"
                               onmouseover="this.style.color='#4F46E5'" onmouseout="this.style.color='#71717A'">
                                <?= htmlspecialchars($e['mail']) ?>
                            </a>
                        </div>
                        <?php endif; ?>

                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                            <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:#71717A;">
                                <span style="width:8px;height:8px;border-radius:50%;background:<?= $nb_offres > 0 ? '#4F46E5' : '#D4D4D8' ?>;display:inline-block;" aria-hidden="true"></span>
                                <?= $nb_offres ?> offre<?= $nb_offres > 1 ? 's' : '' ?>
                            </span>
                            <a href="entreprise_profil.php?id=<?= (int)$e['idEntreprise'] ?>"
                               class="btn-primary"
                               style="font-size:12px;padding:6px 14px;">
                                Voir profil →
                            </a>
                        </div>
                    </div>

                </article>
                <?php endforeach; ?>

            </div>

            <!-- Empty state après filtre JS -->
            <div id="emptyState" style="display:none;text-align:center;padding:60px 0;" aria-live="polite">
                <p style="font-size:14px;color:#A1A1AA;">Aucune entreprise ne correspond à votre recherche.</p>
            </div>

        </main>

    </div>

</div>

<?php include('../components/footer.php'); ?>

<script>
const searchInput  = document.getElementById('searchInput');
const cards        = document.querySelectorAll('.entreprise-card');
const emptyState   = document.getElementById('emptyState');
const countVisible = document.getElementById('countVisible');

searchInput.addEventListener('input', () => {
    const terme = searchInput.value.toLowerCase().trim();
    let nb = 0;

    cards.forEach(card => {
        const match = terme === '' || card.dataset.nom.includes(terme);
        card.style.display = match ? '' : 'none';
        if (match) nb++;
    });

    countVisible.textContent = nb;
    emptyState.style.display = (nb === 0 && cards.length > 0) ? 'block' : 'none';
});
</script>

</body>
</html>