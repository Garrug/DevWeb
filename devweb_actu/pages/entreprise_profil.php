<?php
// pages/entreprise_profil.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/db.php';

// ── Récupération et validation de l'ID ───────────────────────────────────
$id_entreprise = (int)($_GET['id'] ?? 0);

if ($id_entreprise === 0) {
    header('Location: entreprises.php');
    exit();
}

// ── Requête entreprise ────────────────────────────────────────────────────
$entreprise = null;
$db_error   = false;

try {
    $stmt = $conn->prepare(
        "SELECT idEntreprise, nom, description, mail
         FROM Entreprise
         WHERE idEntreprise = ?
         LIMIT 1"
    );
    $stmt->execute([$id_entreprise]);
    $entreprise = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[StageFlow] entreprise_profil SELECT : ' . $e->getMessage());
    $db_error = true;
}

// Entreprise introuvable → retour à la liste
if (!$entreprise && !$db_error) {
    header('Location: entreprises.php');
    exit();
}

// ── Requête offres de cette entreprise ────────────────────────────────────
$offres    = [];
$nb_offres = 0;

if ($entreprise) {
    try {
        $stmt = $conn->prepare(
            "SELECT idOffre, description, niveau, duree, dateDepot, debutStage
             FROM Offre_de_stage
             WHERE idEntreprise = ?
             ORDER BY dateDepot DESC"
        );
        $stmt->execute([$id_entreprise]);
        $offres    = $stmt->fetchAll();
        $nb_offres = count($offres);
    } catch (PDOException $e) {
        error_log('[StageFlow] entreprise_profil offres : ' . $e->getMessage());
    }
}

// ── Données formatées ─────────────────────────────────────────────────────
$niveau_styles = [
    'Bac+2' => ['bg' => '#F4F4F5', 'color' => '#71717A', 'border' => '#E4E4E7'],
    'Bac+3' => ['bg' => '#EEF2FF', 'color' => '#4F46E5', 'border' => '#C7D2FE'],
    'Bac+4' => ['bg' => '#F5F3FF', 'color' => '#7C3AED', 'border' => '#DDD6FE'],
    'Bac+5' => ['bg' => '#ECFDF5', 'color' => '#059669', 'border' => '#A7F3D0'],
];
$niveau_default = ['bg' => '#F4F4F5', 'color' => '#71717A', 'border' => '#E4E4E7'];

// Avatar entreprise
$palette = ['#4F46E5','#059669','#D97706','#DC2626','#7C3AED','#0284C7','#0891B2','#65A30D'];
$avatarColor = $entreprise
    ? $palette[abs(crc32($entreprise['nom'])) % count($palette)]
    : '#4F46E5';

$words    = $entreprise ? explode(' ', trim($entreprise['nom'])) : ['?'];
$initials = strtoupper(
    substr($words[0], 0, 1) .
    (isset($words[1]) ? substr($words[1], 0, 1) : substr($words[0], 1, 1))
);

// L'entreprise connectée peut modifier son propre profil
$is_own_profile = ($_SESSION['role'] === 'Entreprise'
    && (int)($_SESSION['id_metier'] ?? 0) === $id_entreprise);

$page_title = $entreprise ? htmlspecialchars($entreprise['nom']) : 'Profil entreprise';
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
            <div style="display:flex;align-items:center;gap:12px;">
                <a href="entreprises.php" class="btn-secondary" style="padding:6px 12px;font-size:12px;">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Retour aux entreprises
                </a>
                <div style="width:1px;height:20px;background:#E4E4E7;"></div>
                <div>
                    <h1 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#18181B;margin:0;">
                        <?= $entreprise ? htmlspecialchars($entreprise['nom']) : 'Profil entreprise' ?>
                    </h1>
                    <p style="font-size:11.5px;color:#A1A1AA;margin:0;">
                        Entreprise #<?= $id_entreprise ?>
                    </p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="role-badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
                <a href="/pages/profil.php">
                    <div class="avatar" aria-hidden="true"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
                </a>
            </div>
        </header>

        <main style="flex:1;padding:28px;overflow-y:auto;">

            <!-- Erreur BDD -->
            <?php if ($db_error): ?>
            <div role="alert"
                 style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
                     stroke="#EF4444" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span style="font-size:13px;color:#DC2626;">
                    Impossible de charger ce profil. Vérifiez la connexion à la base de données.
                </span>
            </div>
            <?php endif; ?>

            <?php if ($entreprise): ?>

            <div style="max-width:800px;margin:0 auto;display:flex;flex-direction:column;gap:20px;">

                <!-- ── Card profil entreprise ───────────────────────────── -->
                <div class="card animate-in delay-1" style="padding:28px;">

                    <!-- Header : avatar + nom + actions -->
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:22px;flex-wrap:wrap;">

                        <div style="display:flex;align-items:center;gap:16px;">
                            <div style="width:56px;height:56px;border-radius:14px;
                                        background:<?= $avatarColor ?>;
                                        display:flex;align-items:center;justify-content:center;
                                        font-family:'Syne',sans-serif;font-size:20px;font-weight:700;
                                        color:white;flex-shrink:0;
                                        box-shadow:0 4px 14px <?= $avatarColor ?>44;"
                                 aria-hidden="true">
                                <?= $initials ?>
                            </div>
                            <div>
                                <h2 style="font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:#18181B;margin:0 0 4px;">
                                    <?= htmlspecialchars($entreprise['nom']) ?>
                                </h2>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span style="width:7px;height:7px;border-radius:50%;background:<?= $nb_offres > 0 ? '#4F46E5' : '#D4D4D8' ?>;display:inline-block;" aria-hidden="true"></span>
                                    <span style="font-size:12.5px;color:#71717A;">
                                        <?= $nb_offres ?> offre<?= $nb_offres > 1 ? 's' : '' ?> disponible<?= $nb_offres > 1 ? 's' : '' ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if ($is_own_profile): ?>
                        <a href="profil.php" class="btn-secondary" style="padding:7px 14px;font-size:12.5px;">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Modifier mon profil
                        </a>
                        <?php endif; ?>

                    </div>

                    <!-- Séparateur -->
                    <div style="height:1px;background:#F4F4F5;margin-bottom:20px;"></div>

                    <!-- Infos de contact -->
                    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:22px;">

                        <?php if ($entreprise['mail']): ?>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:8px;background:#EEF2FF;
                                        display:flex;align-items:center;justify-content:center;flex-shrink:0;"
                                 aria-hidden="true">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24"
                                     stroke="#4F46E5" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p style="font-size:10.5px;font-weight:600;color:#A1A1AA;letter-spacing:0.05em;margin:0;">
                                    EMAIL DE CONTACT
                                </p>
                                <a href="mailto:<?= htmlspecialchars($entreprise['mail']) ?>"
                                   style="font-size:13.5px;color:#4F46E5;text-decoration:none;font-weight:500;"
                                   onmouseover="this.style.textDecoration='underline'"
                                   onmouseout="this.style.textDecoration='none'">
                                    <?= htmlspecialchars($entreprise['mail']) ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>

                    <!-- Description -->
                    <?php if ($entreprise['description']): ?>
                    <div>
                        <h3 style="font-family:'Syne',sans-serif;font-size:12px;font-weight:700;
                                   color:#18181B;margin:0 0 10px;letter-spacing:0.05em;">
                            À PROPOS
                        </h3>
                        <p style="font-size:14px;color:#52525B;line-height:1.75;margin:0;
                                  background:#FAFAFA;border:1px solid #F4F4F5;
                                  border-radius:10px;padding:16px;">
                            <?= htmlspecialchars($entreprise['description']) ?>
                        </p>
                    </div>
                    <?php else: ?>
                    <p style="font-size:13px;color:#D4D4D8;font-style:italic;">
                        Aucune description disponible pour cette entreprise.
                    </p>
                    <?php endif; ?>

                </div>

                <!-- ── Card offres de l'entreprise ──────────────────────── -->
                <div class="card animate-in delay-2">

                    <!-- Header section offres -->
                    <div style="padding:18px 22px;border-bottom:1px solid #F4F4F5;
                                display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;">
                                Offres de stage
                            </h3>
                            <p style="font-size:12px;color:#A1A1AA;margin:4px 0 0;">
                                <?= $nb_offres > 0
                                    ? $nb_offres . ' offre' . ($nb_offres > 1 ? 's' : '') . ' publiée' . ($nb_offres > 1 ? 's' : '')
                                    : 'Aucune offre publiée pour le moment' ?>
                            </p>
                        </div>
                        <?php if ($nb_offres > 0): ?>
                        <span style="font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;
                                     background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE;">
                            <?= $nb_offres ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Liste des offres -->
                    <?php if (empty($offres)): ?>
                    <div style="padding:48px 24px;text-align:center;">
                        <div style="width:44px;height:44px;background:#F4F4F5;border-radius:12px;
                                    display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"
                             aria-hidden="true">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24"
                                 stroke="#A1A1AA" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p style="font-size:13.5px;color:#A1A1AA;">
                            Cette entreprise n'a pas encore publié d'offre.
                        </p>
                    </div>

                    <?php else: ?>

                    <?php foreach ($offres as $o):
                        $nb         = $niveau_styles[$o['niveau']] ?? $niveau_default;
                        $date_depot = $o['dateDepot']  ? date('d/m/Y', strtotime($o['dateDepot']))  : '—';
                        $date_debut = $o['debutStage'] ? date('d/m/Y', strtotime($o['debutStage'])) : '—';
                    ?>
                    <div class="table-row"
                         style="padding:16px 22px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">

                        <!-- Infos offre -->
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                                <?php if ($o['niveau']): ?>
                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;
                                             background:<?= $nb['bg'] ?>;color:<?= $nb['color'] ?>;
                                             border:1px solid <?= $nb['border'] ?>;">
                                    <?= htmlspecialchars($o['niveau']) ?>
                                </span>
                                <?php endif; ?>
                                <span style="font-size:11.5px;color:#A1A1AA;">
                                    Publiée le <?= $date_depot ?>
                                </span>
                            </div>

                            <p style="font-size:13.5px;font-weight:500;color:#18181B;margin:0 0 6px;
                                      overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars(mb_strimwidth($o['description'] ?? 'Sans description', 0, 80, '…')) ?>
                            </p>

                            <div style="display:flex;flex-wrap:wrap;gap:14px;">
                                <?php if ($o['duree']): ?>
                                <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717A;">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24"
                                         stroke="#A1A1AA" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <?= htmlspecialchars($o['duree']) ?>
                                </span>
                                <?php endif; ?>
                                <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717A;">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24"
                                         stroke="#A1A1AA" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Début : <?= $date_debut ?>
                                </span>
                            </div>
                        </div>

                        <!-- Bouton voir l'offre -->
                        <a href="offre_detail.php?id=<?= (int)$o['idOffre'] ?>"
                           class="btn-secondary"
                           style="font-size:12.5px;padding:7px 14px;flex-shrink:0;">
                            Voir l'offre
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>

                    </div>
                    <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>

            <?php endif; ?>

        </main>

    </div>

</div>

<?php include('../components/footer.php'); ?>

</body>
</html>