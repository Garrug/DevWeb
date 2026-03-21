<?php
// pages/offre_detail.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/db.php';

// ── Récupération et validation de l'ID ───────────────────────────────────
$id_offre = (int)($_GET['id'] ?? 0);

if ($id_offre === 0) {
    header('Location: offres.php');
    exit();
}

// ── Requête — offre + entreprise ──────────────────────────────────────────
$offre    = null;
$db_error = false;

try {
    $stmt = $conn->prepare(
        "SELECT o.idOffre,
                o.description,
                o.niveau,
                o.duree,
                o.dateDepot,
                o.debutStage,
                e.nom          AS entreprise_nom,
                e.mail         AS entreprise_mail,
                e.description  AS entreprise_description,
                e.idEntreprise
         FROM Offre_de_stage o
         JOIN Entreprise e ON o.idEntreprise = e.idEntreprise
         WHERE o.idOffre = ?
         LIMIT 1"
    );
    $stmt->execute([$id_offre]);
    $offre = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[StageFlow] offre_detail.php : ' . $e->getMessage());
    $db_error = true;
}

// ── Vérifier si l'offre existe ────────────────────────────────────────────
if (!$offre && !$db_error) {
    // Offre introuvable → retour à la liste
    header('Location: offres.php');
    exit();
}

// ── Données formatées ─────────────────────────────────────────────────────
$date_depot  = ($offre && $offre['dateDepot'])  ? date('d/m/Y', strtotime($offre['dateDepot']))  : '—';
$date_debut  = ($offre && $offre['debutStage']) ? date('d/m/Y', strtotime($offre['debutStage'])) : '—';

$is_etudiant   = ($_SESSION['role'] === 'Etudiant');
$is_entreprise = ($_SESSION['role'] === 'Entreprise');

$niveau_styles = [
    'Bac+2' => ['bg' => '#F4F4F5', 'color' => '#71717A', 'border' => '#E4E4E7'],
    'Bac+3' => ['bg' => '#EEF2FF', 'color' => '#4F46E5', 'border' => '#C7D2FE'],
    'Bac+4' => ['bg' => '#F5F3FF', 'color' => '#7C3AED', 'border' => '#DDD6FE'],
    'Bac+5' => ['bg' => '#ECFDF5', 'color' => '#059669', 'border' => '#A7F3D0'],
];
$niveau_default = ['bg' => '#F4F4F5', 'color' => '#71717A', 'border' => '#E4E4E7'];
$nb = $offre ? ($niveau_styles[$offre['niveau']] ?? $niveau_default) : $niveau_default;

$page_title = $offre ? htmlspecialchars($offre['entreprise_nom']) . ' — Offre #' . $id_offre : 'Offre de stage';
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
                <a href="offres.php"
                   class="btn-secondary"
                   style="padding:6px 12px;font-size:12px;">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Retour aux offres
                </a>
                <div style="width:1px;height:20px;background:#E4E4E7;"></div>
                <div>
                    <h1 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#18181B;margin:0;">
                        Détail de l'offre
                    </h1>
                    <p style="font-size:11.5px;color:#A1A1AA;margin:0;">
                        Offre #<?= $id_offre ?>
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
                    Impossible de charger cette offre. Vérifiez la connexion à la base de données.
                </span>
            </div>
            <?php endif; ?>

            <?php if ($offre): ?>

            <div style="max-width:760px;margin:0 auto;display:flex;flex-direction:column;gap:20px;">

                <!-- ── Card principale ──────────────────────────────────── -->
                <div class="card animate-in delay-1" style="padding:28px;">

                    <!-- Header : entreprise + badge niveau -->
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:22px;flex-wrap:wrap;">

                        <div style="display:flex;align-items:center;gap:14px;">
                            <!-- Avatar entreprise -->
                            <?php
                            $words    = explode(' ', trim($offre['entreprise_nom']));
                            $initials = strtoupper(
                                substr($words[0], 0, 1) .
                                (isset($words[1]) ? substr($words[1], 0, 1) : substr($words[0], 1, 1))
                            );
                            $palette     = ['#4F46E5','#059669','#D97706','#DC2626','#7C3AED','#0284C7'];
                            $avatarColor = $palette[abs(crc32($offre['entreprise_nom'])) % count($palette)];
                            ?>
                            <div style="width:52px;height:52px;border-radius:14px;background:<?= $avatarColor ?>;
                                        display:flex;align-items:center;justify-content:center;
                                        font-family:'Syne',sans-serif;font-size:18px;font-weight:700;
                                        color:white;flex-shrink:0;box-shadow:0 4px 12px <?= $avatarColor ?>44;"
                                 aria-hidden="true">
                                <?= $initials ?>
                            </div>
                            <div>
                                <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#18181B;margin:0 0 4px;">
                                    <?= htmlspecialchars($offre['entreprise_nom']) ?>
                                </h2>
                                <p style="font-size:12.5px;color:#A1A1AA;margin:0;">
                                    Offre #<?= (int)$offre['idOffre'] ?> · Publiée le <?= $date_depot ?>
                                </p>
                            </div>
                        </div>

                        <!-- Badge niveau -->
                        <?php if ($offre['niveau']): ?>
                        <span style="font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px;
                                     background:<?= $nb['bg'] ?>;color:<?= $nb['color'] ?>;
                                     border:1px solid <?= $nb['border'] ?>;flex-shrink:0;">
                            <?= htmlspecialchars($offre['niveau']) ?>
                        </span>
                        <?php endif; ?>

                    </div>

                    <!-- Séparateur -->
                    <div style="height:1px;background:#F4F4F5;margin-bottom:22px;"></div>

                    <!-- Métadonnées : durée + début -->
                    <div style="display:flex;flex-wrap:wrap;gap:20px;margin-bottom:22px;">
                        <?php if ($offre['duree']): ?>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:34px;height:34px;border-radius:9px;background:#EEF2FF;
                                        display:flex;align-items:center;justify-content:center;" aria-hidden="true">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24"
                                     stroke="#4F46E5" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p style="font-size:10.5px;font-weight:600;color:#A1A1AA;letter-spacing:0.05em;margin:0;">
                                    DURÉE
                                </p>
                                <p style="font-size:13.5px;font-weight:600;color:#18181B;margin:2px 0 0;">
                                    <?= htmlspecialchars($offre['duree']) ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:34px;height:34px;border-radius:9px;background:#ECFDF5;
                                        display:flex;align-items:center;justify-content:center;" aria-hidden="true">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24"
                                     stroke="#059669" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p style="font-size:10.5px;font-weight:600;color:#A1A1AA;letter-spacing:0.05em;margin:0;">
                                    DÉBUT
                                </p>
                                <p style="font-size:13.5px;font-weight:600;color:#18181B;margin:2px 0 0;">
                                    <?= $date_debut ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Description de l'offre -->
                    <div style="margin-bottom:24px;">
                        <h3 style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;
                                   color:#18181B;margin:0 0 10px;letter-spacing:0.03em;">
                            DESCRIPTION DU POSTE
                        </h3>
                        <div style="font-size:14px;color:#52525B;line-height:1.75;
                                    white-space:pre-wrap;background:#FAFAFA;
                                    border:1px solid #F4F4F5;border-radius:10px;padding:16px;">
                            <?= htmlspecialchars($offre['description'] ?? 'Aucune description fournie.') ?>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div style="display:flex;gap:10px;flex-wrap:wrap;padding-top:4px;
                                border-top:1px solid #F4F4F5;margin-top:4px;">
                        <a href="offres.php" class="btn-secondary" style="padding:9px 18px;">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Retour aux offres
                        </a>

                        <?php if ($is_etudiant): ?>
                        <a href="candidature_new.php?offre=<?= (int)$offre['idOffre'] ?>"
                           class="btn-primary"
                           style="padding:9px 20px;">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Postuler à cette offre
                        </a>
                        <?php endif; ?>

                        <?php if ($is_entreprise && (int)$offre['idEntreprise'] === (int)($_SESSION['id_metier'] ?? 0)): ?>
                        <a href="mes_offres.php?edit=<?= (int)$offre['idOffre'] ?>"
                           class="btn-secondary"
                           style="padding:9px 18px;margin-left:auto;">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Modifier cette offre
                        </a>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- ── Card entreprise ──────────────────────────────────── -->
                <?php if ($offre['entreprise_description'] || $offre['entreprise_mail']): ?>
                <div class="card animate-in delay-2" style="padding:22px;">

                    <h3 style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;
                               color:#18181B;margin:0 0 14px;letter-spacing:0.03em;">
                        À PROPOS DE L'ENTREPRISE
                    </h3>

                    <?php if ($offre['entreprise_description']): ?>
                    <p style="font-size:13.5px;color:#52525B;line-height:1.7;margin:0 0 14px;">
                        <?= htmlspecialchars($offre['entreprise_description']) ?>
                    </p>
                    <?php endif; ?>

                    <?php if ($offre['entreprise_mail']): ?>
                    <div style="display:flex;align-items:center;gap:7px;">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24"
                             stroke="#A1A1AA" stroke-width="2" aria-hidden="true" style="flex-shrink:0;">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:<?= htmlspecialchars($offre['entreprise_mail']) ?>"
                           style="font-size:13px;color:#4F46E5;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'"
                           onmouseout="this.style.textDecoration='none'">
                            <?= htmlspecialchars($offre['entreprise_mail']) ?>
                        </a>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endif; ?>

            </div>

            <?php endif; ?>

        </main>

    </div>

</div>

<?php include('../components/footer.php'); ?>

</body>
</html>