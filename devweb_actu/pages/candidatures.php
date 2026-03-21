<?php
// pages/candidatures.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Connexion centralisée — plus de credentials en dur
require_once '../includes/db.php';

$candidatures = [];
$db_error     = false;
$role         = $_SESSION['role'] ?? '';

try {
    if ($role === 'Etudiant') {
        // Utilise id_metier stocké en session — évite une requête supplémentaire
        $id_etudiant = (int)($_SESSION['id_metier'] ?? 0);

        if ($id_etudiant === 0) {
            // Fallback si id_metier absent (ancienne session)
            $stmt_id = $conn->prepare("SELECT idEtudiant FROM Etudiant WHERE identifiant = ? LIMIT 1");
            $stmt_id->execute([$_SESSION['user']]);
            $row = $stmt_id->fetch();
            $id_etudiant = $row ? (int)$row['idEtudiant'] : 0;
        }

        if ($id_etudiant > 0) {
            $stmt = $conn->prepare(
                "SELECT c.idDocument, c.idOffre, c.idEtudiant,
                        c.choixEntreprise, c.choixEtudiant,
                        o.description AS offre_description,
                        o.dateDepot   AS offre_date,
                        o.debutStage,
                        e.nom         AS entreprise_nom
                 FROM candidater c
                 JOIN Offre_de_stage o ON c.idOffre      = o.idOffre
                 JOIN Entreprise e     ON o.idEntreprise  = e.idEntreprise
                 WHERE c.idEtudiant = ?
                 ORDER BY o.dateDepot DESC"
            );
            $stmt->execute([$id_etudiant]);
            $candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } else {
        $stmt = $conn->query(
            "SELECT c.idDocument, c.idOffre, c.idEtudiant,
                    c.choixEntreprise, c.choixEtudiant,
                    o.description AS offre_description,
                    o.dateDepot   AS offre_date,
                    o.debutStage,
                    e.nom         AS entreprise_nom,
                    CONCAT(et.prenom, ' ', et.nom) AS etudiant_nom
             FROM candidater c
             JOIN Offre_de_stage o ON c.idOffre      = o.idOffre
             JOIN Entreprise e     ON o.idEntreprise  = e.idEntreprise
             JOIN Etudiant et      ON c.idEtudiant    = et.idEtudiant
             ORDER BY o.dateDepot DESC"
        );
        $candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('[StageFlow] candidatures.php requête : ' . $e->getMessage());
    $db_error = true;
}

function get_statut(string $ce, string $cet): string {
    $ce  = strtolower(trim($ce));
    $cet = strtolower(trim($cet));
    if ($ce === 'accepté' && $cet === 'accepté') return 'Accepté';
    if ($ce === 'refusé'  || $cet === 'refusé')  return 'Refusé';
    return 'En attente';
}

$total      = count($candidatures);
$en_attente = 0;
$acceptees  = 0;
$refusees   = 0;

foreach ($candidatures as $c) {
    $s = get_statut($c['choixEntreprise'] ?? '', $c['choixEtudiant'] ?? '');
    if ($s === 'En attente')    $en_attente++;
    elseif ($s === 'Accepté')   $acceptees++;
    else                        $refusees++;
}

$page_title = $role === 'Etudiant' ? 'Mes candidatures' : 'Toutes les candidatures';

$badge_styles = [
    'En attente' => 'background:#FFFBEB;color:#D97706;border:1px solid #FDE68A;',
    'Accepté'    => 'background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;',
    'Refusé'     => 'background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;',
];
?>
<!DOCTYPE html>
<html lang="fr">

<?php
    $page_title = 'Candidatures'; 
    include('../components/header.php');
?>

<body>

<div style="display:flex;min-height:100vh;">

    <?php include('../components/sidebar.php'); ?>

    <div style="flex:1;display:flex;flex-direction:column;min-width:0;">

        <!-- Topbar -->
        <header class="topbar" style="padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;">
            <div>
                <h1 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#18181B;margin:0;"><?= htmlspecialchars($page_title) ?></h1>
                <p style="font-size:12px;color:#A1A1AA;margin:0;">Suivez l'état de vos candidatures</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="role-badge"><?= htmlspecialchars($role) ?></span>
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
                <span style="font-size:13px;color:#DC2626;">Impossible de charger les candidatures. Vérifiez la connexion BDD.</span>
            </div>
            <?php endif; ?>

            <!-- Cartes statistiques -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:24px;" class="animate-in delay-1">
                <?php
                $stats = [
                    ['label' => 'Total',       'value' => $total,       'color' => '#4F46E5', 'bg' => '#EEF2FF',
                     'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>'],
                    ['label' => 'En attente',  'value' => $en_attente,  'color' => '#D97706', 'bg' => '#FFFBEB',
                     'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                    ['label' => 'Acceptées',   'value' => $acceptees,   'color' => '#059669', 'bg' => '#ECFDF5',
                     'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                    ['label' => 'Refusées',    'value' => $refusees,    'color' => '#DC2626', 'bg' => '#FEF2F2',
                     'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                ];
                foreach ($stats as $s): ?>
                <div class="stat-card" style="padding:18px;display:flex;align-items:center;gap:12px;">
                    <div style="width:38px;height:38px;border-radius:10px;background:<?= $s['bg'] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;" aria-hidden="true">
                        <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="<?= $s['color'] ?>" stroke-width="1.8">
                            <?= $s['icon'] ?>
                        </svg>
                    </div>
                    <div>
                        <p style="font-family:'Syne',sans-serif;font-size:24px;font-weight:700;color:#18181B;margin:0;"><?= $s['value'] ?></p>
                        <p style="font-size:12px;color:#71717A;margin:2px 0 0;"><?= $s['label'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Table card -->
            <div class="card animate-in delay-2">

                <div style="padding:16px 20px;border-bottom:1px solid #F4F4F5;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
                    <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;">Liste des candidatures</h3>
                    <div style="display:flex;align-items:center;gap:6px;" role="group" aria-label="Filtrer par statut">
                        <?php
                        $filtres = ['' => 'Tous', 'En attente' => 'En attente', 'Accepté' => 'Accepté', 'Refusé' => 'Refusé'];
                        foreach ($filtres as $val => $label):
                            $active = $val === '';
                        ?>
                        <button class="filter-btn"
                                data-filter="<?= htmlspecialchars($val) ?>"
                                aria-pressed="<?= $active ? 'true' : 'false' ?>"
                                style="font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;border:1.5px solid <?= $active ? '#4F46E5' : '#E4E4E7' ?>;background:<?= $active ? '#4F46E5' : 'white' ?>;color:<?= $active ? 'white' : '#71717A' ?>;cursor:pointer;transition:all 0.15s;">
                            <?= htmlspecialchars($label) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (empty($candidatures) && !$db_error): ?>
                <div style="text-align:center;padding:60px 24px;">
                    <div style="width:48px;height:48px;background:#F4F4F5;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;" aria-hidden="true">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p style="font-size:14px;color:#A1A1AA;">Aucune candidature enregistrée.</p>
                </div>
                <?php else: ?>

                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid #F4F4F5;">
                                <?php if ($role !== 'Etudiant'): ?>
                                <th scope="col" style="text-align:left;padding:11px 20px;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">ÉTUDIANT</th>
                                <?php endif; ?>
                                <th scope="col" style="text-align:left;padding:11px 20px;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">OFFRE</th>
                                <th scope="col" style="text-align:left;padding:11px 20px;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">ENTREPRISE</th>
                                <th scope="col" style="text-align:left;padding:11px 20px;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">DATE</th>
                                <th scope="col" style="text-align:left;padding:11px 20px;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">STATUT</th>
                                <th scope="col" style="text-align:right;padding:11px 20px;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($candidatures as $c):
                            $statut      = get_statut($c['choixEntreprise'] ?? '', $c['choixEtudiant'] ?? '');
                            $date        = $c['offre_date'] ? date('d/m/Y', strtotime($c['offre_date'])) : '—';
                            $badge_style = $badge_styles[$statut] ?? '';
                        ?>
                        <tr class="table-row" data-statut="<?= $statut ?>">
                            <?php if ($role !== 'Etudiant'): ?>
                            <td style="padding:14px 20px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#4F46E5,#818CF8);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:white;flex-shrink:0;" aria-hidden="true">
                                        <?= strtoupper(substr($c['etudiant_nom'] ?? '?', 0, 1)) ?>
                                    </div>
                                    <span style="font-size:13px;font-weight:600;color:#18181B;"><?= htmlspecialchars($c['etudiant_nom'] ?? '—') ?></span>
                                </div>
                            </td>
                            <?php endif; ?>
                            <td style="padding:14px 20px;max-width:240px;">
                                <p style="font-size:13px;font-weight:500;color:#18181B;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= htmlspecialchars(mb_strimwidth($c['offre_description'] ?? 'Sans description', 0, 55, '…')) ?>
                                </p>
                                <p style="font-size:11.5px;color:#A1A1AA;margin:3px 0 0;">Offre #<?= (int)$c['idOffre'] ?></p>
                            </td>
                            <td style="padding:14px 20px;font-size:13px;color:#52525B;"><?= htmlspecialchars($c['entreprise_nom']) ?></td>
                            <td style="padding:14px 20px;font-size:13px;color:#A1A1AA;white-space:nowrap;"><?= $date ?></td>
                            <td style="padding:14px 20px;">
                                <span style="font-size:11.5px;font-weight:600;padding:4px 10px;border-radius:20px;<?= $badge_style ?>">
                                    <?= $statut ?>
                                </span>
                            </td>
                            <td style="padding:14px 20px;text-align:right;">
                                <a href="candidature_detail.php?doc=<?= (int)$c['idDocument'] ?>&offre=<?= (int)$c['idOffre'] ?>"
                                   class="btn-secondary"
                                   style="font-size:12px;padding:5px 12px;">
                                    Voir →
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php endif; ?>

                <div id="emptyState" style="display:none;text-align:center;padding:50px 24px;" aria-live="polite">
                    <p style="font-size:14px;color:#A1A1AA;">Aucune candidature pour ce statut.</p>
                </div>

            </div>

        </main>

    </div>

</div>

<?php include('../components/footer.php'); ?>

<script>
const filterBtns = document.querySelectorAll('.filter-btn');
const tableRows  = document.querySelectorAll('.table-row');
const emptyState = document.getElementById('emptyState');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
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

        const filtre = btn.dataset.filter;
        let nb = 0;
        tableRows.forEach(row => {
            const match = filtre === '' || row.dataset.statut === filtre;
            row.style.display = match ? '' : 'none';
            if (match) nb++;
        });
        emptyState.style.display = (nb === 0 && tableRows.length > 0) ? 'block' : 'none';
    });
});
</script>

</body>
</html>