<?php
// pages/candidature_new.php
session_start();

// ── Garde : étudiant connecté uniquement ──────────────────────────────────
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: login.php');
    exit();
}

require_once '../includes/db.php';

// ── Récupération et validation de l'ID offre ─────────────────────────────
$id_offre = (int)($_GET['offre'] ?? 0);

if ($id_offre === 0) {
    header('Location: offres.php');
    exit();
}

$id_etudiant = (int)($_SESSION['id_metier'] ?? 0);

// Fallback si id_metier absent (ancienne session)
if ($id_etudiant === 0) {
    try {
        $stmt = $conn->prepare("SELECT idEtudiant FROM Etudiant WHERE identifiant = ? LIMIT 1");
        $stmt->execute([$_SESSION['user']]);
        $row = $stmt->fetch();
        if ($row) {
            $id_etudiant           = (int)$row['idEtudiant'];
            $_SESSION['id_metier'] = $id_etudiant;
        }
    } catch (PDOException $e) {
        error_log('[StageFlow] candidature_new id_etudiant : ' . $e->getMessage());
    }
}

if ($id_etudiant === 0) {
    header('Location: login.php');
    exit();
}

// ── Récupération de l'offre ───────────────────────────────────────────────
$offre    = null;
$db_error = false;

try {
    $stmt = $conn->prepare(
        "SELECT o.idOffre,
                o.description,
                o.niveau,
                o.duree,
                o.debutStage,
                e.nom AS entreprise_nom
         FROM Offre_de_stage o
         JOIN Entreprise e ON o.idEntreprise = e.idEntreprise
         WHERE o.idOffre = ?
         LIMIT 1"
    );
    $stmt->execute([$id_offre]);
    $offre = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[StageFlow] candidature_new offre : ' . $e->getMessage());
    $db_error = true;
}

// Offre introuvable
if (!$offre && !$db_error) {
    header('Location: offres.php');
    exit();
}

// ── Vérifier si l'étudiant a déjà postulé à cette offre ──────────────────
$deja_candidat = false;

if ($offre) {
    try {
        $stmt = $conn->prepare(
            "SELECT 1 FROM candidater
             WHERE idOffre = ? AND idEtudiant = ?
             LIMIT 1"
        );
        $stmt->execute([$id_offre, $id_etudiant]);
        $deja_candidat = (bool)$stmt->fetch();
    } catch (PDOException $e) {
        error_log('[StageFlow] candidature_new vérif doublon : ' . $e->getMessage());
    }
}

// ── Traitement POST ───────────────────────────────────────────────────────
$success      = false;
$error_msg    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $offre && !$deja_candidat) {

    // La table candidater n'a pas de champ message — on insère avec les
    // valeurs initiales : choixEntreprise et choixEtudiant à 'En attente'
    // idDocument est la clé primaire auto-increment

    try {
        $stmt = $conn->prepare(
            "INSERT INTO candidater (idOffre, idEtudiant, choixEntreprise, choixEtudiant)
             VALUES (:idOffre, :idEtudiant, 'En attente', 'Accepté')"
            // choixEtudiant = 'Accepté' car l'étudiant vient de postuler
            // choixEntreprise = 'En attente' car l'entreprise n'a pas encore répondu
        );
        $stmt->execute([
            ':idOffre'    => $id_offre,
            ':idEtudiant' => $id_etudiant,
        ]);

        $success      = true;
        $deja_candidat = true; // Empêcher un second envoi sur refresh

    } catch (PDOException $e) {
        // Code 23000 = contrainte d'unicité — doublon
        if ($e->getCode() === '23000') {
            $deja_candidat = true;
        } else {
            error_log('[StageFlow] candidature_new INSERT : ' . $e->getMessage());
            $error_msg = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}

// ── Données affichage ─────────────────────────────────────────────────────
$date_debut = ($offre && $offre['debutStage'])
    ? date('d/m/Y', strtotime($offre['debutStage']))
    : '—';

$niveau_styles = [
    'Bac+2' => ['bg' => '#F4F4F5', 'color' => '#71717A', 'border' => '#E4E4E7'],
    'Bac+3' => ['bg' => '#EEF2FF', 'color' => '#4F46E5', 'border' => '#C7D2FE'],
    'Bac+4' => ['bg' => '#F5F3FF', 'color' => '#7C3AED', 'border' => '#DDD6FE'],
    'Bac+5' => ['bg' => '#ECFDF5', 'color' => '#059669', 'border' => '#A7F3D0'],
];
$niveau_default = ['bg' => '#F4F4F5', 'color' => '#71717A', 'border' => '#E4E4E7'];
$nb = $offre ? ($niveau_styles[$offre['niveau']] ?? $niveau_default) : $niveau_default;

$page_title = 'Postuler — ' . ($offre ? htmlspecialchars($offre['entreprise_nom']) : 'Offre');
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
                <a href="offre_detail.php?id=<?= $id_offre ?>"
                   class="btn-secondary"
                   style="padding:6px 12px;font-size:12px;">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Retour à l'offre
                </a>
                <div style="width:1px;height:20px;background:#E4E4E7;"></div>
                <div>
                    <h1 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#18181B;margin:0;">
                        Nouvelle candidature
                    </h1>
                    <p style="font-size:11.5px;color:#A1A1AA;margin:0;">
                        Offre #<?= $id_offre ?>
                        <?= $offre ? '· ' . htmlspecialchars($offre['entreprise_nom']) : '' ?>
                    </p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="role-badge">Étudiant</span>
                <a href="/pages/profil.php">
                    <div class="avatar" aria-hidden="true"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
                </a>
            </div>
        </header>

        <main style="flex:1;padding:28px;overflow-y:auto;">

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

            <div style="max-width:680px;margin:0 auto;display:flex;flex-direction:column;gap:20px;">

                <!-- ── Résumé de l'offre ────────────────────────────────── -->
                <div class="card animate-in delay-1" style="padding:20px;">
                    <p style="font-size:10.5px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin:0 0 12px;">
                        OFFRE CONCERNÉE
                    </p>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:14px;font-weight:600;color:#18181B;margin:0 0 4px;
                                      overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars(mb_strimwidth($offre['description'] ?? '', 0, 70, '…')) ?>
                            </p>
                            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:6px;">
                                <span style="font-size:12.5px;color:#71717A;font-weight:600;">
                                    <?= htmlspecialchars($offre['entreprise_nom']) ?>
                                </span>
                                <?php if ($offre['duree']): ?>
                                <span style="display:flex;align-items:center;gap:4px;font-size:12px;color:#A1A1AA;">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <?= htmlspecialchars($offre['duree']) ?>
                                </span>
                                <?php endif; ?>
                                <span style="display:flex;align-items:center;gap:4px;font-size:12px;color:#A1A1AA;">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Début : <?= $date_debut ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($offre['niveau']): ?>
                        <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;
                                     background:<?= $nb['bg'] ?>;color:<?= $nb['color'] ?>;
                                     border:1px solid <?= $nb['border'] ?>;flex-shrink:0;">
                            <?= htmlspecialchars($offre['niveau']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── Confirmation succès ──────────────────────────────── -->
                <?php if ($success): ?>
                <div role="alert" class="card animate-in delay-1"
                     style="padding:28px;text-align:center;border-color:#A7F3D0;">

                    <div style="width:52px;height:52px;border-radius:50%;background:#ECFDF5;
                                display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"
                         aria-hidden="true">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24"
                             stroke="#059669" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;
                               color:#18181B;margin:0 0 8px;">
                        Candidature envoyée !
                    </h2>
                    <p style="font-size:13.5px;color:#71717A;margin:0 0 6px;">
                        Votre candidature à
                        <strong style="color:#18181B;"><?= htmlspecialchars($offre['entreprise_nom']) ?></strong>
                        a bien été transmise.
                    </p>
                    <p style="font-size:12.5px;color:#A1A1AA;margin:0 0 22px;">
                        Vous serez notifié dès qu'une réponse sera disponible.
                    </p>

                    <div style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap;">
                        <a href="candidatures.php" class="btn-primary" style="padding:9px 20px;">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Voir mes candidatures
                        </a>
                        <a href="offres.php" class="btn-secondary" style="padding:9px 18px;">
                            Parcourir les offres
                        </a>
                    </div>

                </div>

                <!-- ── Déjà candidat (sans succès = doublon détecté avant POST) ── -->
                <?php elseif ($deja_candidat): ?>
                <div role="alert" class="card animate-in delay-2"
                     style="padding:24px;text-align:center;border-color:#FDE68A;">

                    <div style="width:44px;height:44px;border-radius:50%;background:#FFFBEB;
                                display:flex;align-items:center;justify-content:center;margin:0 auto 14px;"
                         aria-hidden="true">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24"
                             stroke="#D97706" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>

                    <h2 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;
                               color:#18181B;margin:0 0 8px;">
                        Vous avez déjà postulé
                    </h2>
                    <p style="font-size:13px;color:#71717A;margin:0 0 18px;">
                        Vous avez déjà envoyé une candidature pour cette offre.
                    </p>
                    <div style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap;">
                        <a href="candidatures.php" class="btn-primary" style="padding:8px 18px;">
                            Voir mes candidatures
                        </a>
                        <a href="offres.php" class="btn-secondary" style="padding:8px 16px;">
                            Retour aux offres
                        </a>
                    </div>

                </div>

                <!-- ── Formulaire de candidature ────────────────────────── -->
                <?php else: ?>

                <!-- Message erreur serveur -->
                <?php if ($error_msg): ?>
                <div role="alert"
                     style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:13px 16px;display:flex;align-items:center;gap:10px;">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24"
                         stroke="#EF4444" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <span style="font-size:13px;color:#DC2626;"><?= htmlspecialchars($error_msg) ?></span>
                </div>
                <?php endif; ?>

                <div class="card animate-in delay-2" style="padding:28px;">

                    <h2 style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;
                               color:#18181B;margin:0 0 6px;">
                        Confirmer votre candidature
                    </h2>
                    <p style="font-size:13px;color:#71717A;margin:0 0 24px;">
                        En cliquant sur "Envoyer", votre candidature sera transmise à
                        <strong style="color:#3F3F46;"><?= htmlspecialchars($offre['entreprise_nom']) ?></strong>.
                        L'entreprise pourra alors accepter ou refuser votre dossier.
                    </p>

                    <!-- Récapitulatif étudiant -->
                    <div style="background:#FAFAFA;border:1px solid #F4F4F5;border-radius:10px;
                                padding:14px 16px;margin-bottom:22px;display:flex;align-items:center;gap:12px;">
                        <div style="width:36px;height:36px;border-radius:50%;
                                    background:linear-gradient(135deg,#4F46E5,#818CF8);
                                    display:flex;align-items:center;justify-content:center;
                                    font-size:13px;font-weight:700;color:white;flex-shrink:0;"
                             aria-hidden="true">
                            <?= strtoupper(substr($_SESSION['user'], 0, 1)) ?>
                        </div>
                        <div>
                            <p style="font-size:13px;font-weight:600;color:#18181B;margin:0;">
                                <?= htmlspecialchars($_SESSION['user']) ?>
                            </p>
                            <p style="font-size:11.5px;color:#A1A1AA;margin:2px 0 0;">
                                Candidature en tant qu'Étudiant
                            </p>
                        </div>
                    </div>

                    <!-- Statuts initiaux expliqués -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:24px;">
                        <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:12px 14px;">
                            <p style="font-size:10.5px;font-weight:700;color:#059669;letter-spacing:0.05em;margin:0 0 4px;">
                                VOTRE DÉCISION
                            </p>
                            <p style="font-size:13px;font-weight:600;color:#065F46;margin:0;">
                                ✓ Accepté
                            </p>
                            <p style="font-size:11px;color:#6EE7B7;margin:2px 0 0;">
                                Vous confirmez postuler
                            </p>
                        </div>
                        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:12px 14px;">
                            <p style="font-size:10.5px;font-weight:700;color:#D97706;letter-spacing:0.05em;margin:0 0 4px;">
                                DÉCISION ENTREPRISE
                            </p>
                            <p style="font-size:13px;font-weight:600;color:#92400E;margin:0;">
                                ⏳ En attente
                            </p>
                            <p style="font-size:11px;color:#FCD34D;margin:2px 0 0;">
                                En attente de réponse
                            </p>
                        </div>
                    </div>

                    <!-- Formulaire -->
                    <form method="POST"
                          action="candidature_new.php?offre=<?= $id_offre ?>">

                        <button type="submit"
                                class="btn-primary"
                                style="width:100%;justify-content:center;padding:12px;font-size:14px;">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Envoyer ma candidature
                        </button>

                    </form>

                    <p style="font-size:11.5px;color:#A1A1AA;text-align:center;margin:12px 0 0;">
                        Vous ne pourrez postuler qu'une seule fois à cette offre.
                    </p>

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