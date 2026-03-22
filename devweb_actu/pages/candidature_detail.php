<?php
// pages/candidature_detail.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/db.php';

$role      = $_SESSION['role'];
$id_metier = (int)($_SESSION['id_metier'] ?? 0);

// ── Paramètres GET ────────────────────────────────────────────────────────
$id_document = (int)($_GET['doc']   ?? 0);
$id_offre    = (int)($_GET['offre'] ?? 0);

if ($id_document === 0 || $id_offre === 0) {
    header('Location: candidatures.php');
    exit();
}

// ── Récupération de la candidature ────────────────────────────────────────
$candidature = null;
$db_error    = false;

try {
    $stmt = $conn->prepare(
        "SELECT
            c.idDocument,
            c.idOffre,
            c.idEtudiant,
            c.choixEntreprise,
            c.choixEtudiant,
            -- Offre
            o.description     AS offre_description,
            o.niveau          AS offre_niveau,
            o.duree           AS offre_duree,
            o.dateDepot       AS offre_date_depot,
            o.debutStage      AS offre_debut,
            -- Entreprise
            e.idEntreprise,
            e.nom             AS entreprise_nom,
            e.mail            AS entreprise_mail,
            -- Étudiant
            et.nom            AS etudiant_nom,
            et.prenom         AS etudiant_prenom,
            et.mail           AS etudiant_mail,
            et.filiere,
            et.annee,
            et.groupe,
            -- Document
            d.nom             AS document_nom,
            d.dateDepot       AS document_date,
            d.lettre_motivation,
            d.cv_filename
         FROM candidater c
         JOIN Offre_de_stage o  ON c.idOffre     = o.idOffre
         JOIN Entreprise e      ON o.idEntreprise = e.idEntreprise
         JOIN Etudiant et       ON c.idEtudiant  = et.idEtudiant
         JOIN Document d        ON c.idDocument  = d.idDocument
         WHERE c.idDocument = ? AND c.idOffre = ?
         LIMIT 1"
    );
    $stmt->execute([$id_document, $id_offre]);
    $candidature = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[StageFlow] candidature_detail SELECT : ' . $e->getMessage());
    $db_error = true;
}

// ── Contrôle d'accès ──────────────────────────────────────────────────────
// Étudiant : ne voit que ses propres candidatures
// Entreprise : ne voit que les candidatures de ses offres
// Tuteur / Jury / Admin : voient tout
if ($candidature) {
    $acces_ok = match($role) {
        'Etudiant'   => (int)$candidature['idEtudiant']   === $id_metier,
        'Entreprise' => (int)$candidature['idEntreprise'] === $id_metier,
        default      => true,
    };
    if (!$acces_ok) {
        header('Location: candidatures.php');
        exit();
    }
}

// ── Traitement : réponse entreprise ──────────────────────────────────────
$feedback_success = '';
$feedback_error   = '';

if (isset($_SESSION['candidature_success'])) {
    $feedback_success = $_SESSION['candidature_success'];
    unset($_SESSION['candidature_success']);
}
if (isset($_SESSION['candidature_error'])) {
    $feedback_error = $_SESSION['candidature_error'];
    unset($_SESSION['candidature_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && $role === 'Entreprise'
    && $candidature
    && (int)$candidature['idEntreprise'] === $id_metier) {

    $choix = trim($_POST['choix_entreprise'] ?? '');

    if (!in_array($choix, ['Accepté', 'Refusé', 'En attente'], true)) {
        $_SESSION['candidature_error'] = 'Choix invalide.';
    } else {
        try {
            $stmt = $conn->prepare(
                "UPDATE candidater
                 SET choixEntreprise = ?
                 WHERE idDocument = ? AND idOffre = ?"
            );
            $stmt->execute([$choix, $id_document, $id_offre]);

            $_SESSION['candidature_success'] = 'Décision enregistrée : ' . $choix . '.';

            // Rafraîchir les données
            header('Location: candidature_detail.php?doc=' . $id_document . '&offre=' . $id_offre);
            exit();
        } catch (PDOException $e) {
            error_log('[StageFlow] candidature_detail UPDATE : ' . $e->getMessage());
            $_SESSION['candidature_error'] = 'Erreur lors de la mise à jour.';
        }
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────
function get_statut(string $ce, string $cet): string {
    $ce  = strtolower(trim($ce));
    $cet = strtolower(trim($cet));
    if ($ce === 'accepté' && $cet === 'accepté') return 'Accepté';
    if ($ce === 'refusé'  || $cet === 'refusé')  return 'Refusé';
    return 'En attente';
}

$statut = $candidature
    ? get_statut($candidature['choixEntreprise'] ?? '', $candidature['choixEtudiant'] ?? '')
    : '—';

$badge_statut = [
    'En attente' => ['bg'=>'#FFFBEB','color'=>'#D97706','border'=>'#FDE68A'],
    'Accepté'    => ['bg'=>'#ECFDF5','color'=>'#059669','border'=>'#A7F3D0'],
    'Refusé'     => ['bg'=>'#FEF2F2','color'=>'#DC2626','border'=>'#FECACA'],
];
$bs = $badge_statut[$statut] ?? $badge_statut['En attente'];

$niveau_styles = [
    'Bac+2' => ['bg'=>'#F4F4F5','color'=>'#71717A','border'=>'#E4E4E7'],
    'Bac+3' => ['bg'=>'#EEF2FF','color'=>'#4F46E5','border'=>'#C7D2FE'],
    'Bac+4' => ['bg'=>'#F5F3FF','color'=>'#7C3AED','border'=>'#DDD6FE'],
    'Bac+5' => ['bg'=>'#ECFDF5','color'=>'#059669','border'=>'#A7F3D0'],
];
$niveau_default = ['bg'=>'#F4F4F5','color'=>'#71717A','border'=>'#E4E4E7'];
$nb_niveau = $candidature
    ? ($niveau_styles[$candidature['offre_niveau']] ?? $niveau_default)
    : $niveau_default;

$date_depot  = ($candidature && $candidature['document_date'])
    ? date('d/m/Y', strtotime($candidature['document_date'])) : '—';
$date_debut  = ($candidature && $candidature['offre_debut'])
    ? date('d/m/Y', strtotime($candidature['offre_debut'])) : '—';

// Chemin du CV téléchargeable
$cv_path = ($candidature && $candidature['cv_filename'])
    ? '../uploads/cv/' . $candidature['cv_filename']
    : null;

$page_title = 'Candidature #' . $id_document;
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
                <a href="candidatures.php" class="btn-secondary" style="padding:6px 12px;font-size:12px;">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Retour
                </a>
                <div style="width:1px;height:20px;background:#E4E4E7;"></div>
                <div>
                    <h1 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#18181B;margin:0;">
                        Détail de la candidature
                    </h1>
                    <p style="font-size:11.5px;color:#A1A1AA;margin:0;">
                        Document #<?= $id_document ?> · Offre #<?= $id_offre ?>
                    </p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="role-badge"><?= htmlspecialchars($role) ?></span>
                <a href="profil.php" title="Mon profil">
                    <div class="avatar"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
                </a>
            </div>
        </header>

        <main style="flex:1;padding:28px;overflow-y:auto;">

            <!-- Erreur BDD -->
            <?php if ($db_error): ?>
            <div role="alert" style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#EF4444" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span style="font-size:13px;color:#DC2626;">Impossible de charger cette candidature.</span>
            </div>
            <?php endif; ?>

            <!-- Feedback -->
            <?php if ($feedback_success): ?>
            <div role="alert" class="animate-in delay-1" style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:12px;padding:13px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span style="font-size:13px;color:#065F46;font-weight:500;"><?= htmlspecialchars($feedback_success) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($feedback_error): ?>
            <div role="alert" style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:13px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <span style="font-size:13px;color:#DC2626;"><?= htmlspecialchars($feedback_error) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($candidature): ?>
            <div style="max-width:820px;margin:0 auto;display:flex;flex-direction:column;gap:20px;">

                <!-- ── Bandeau statut global ───────────────────────────── -->
                <div class="card animate-in delay-1"
                     style="padding:20px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-color:<?= $bs['border'] ?>;">
                    <div>
                        <p style="font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin:0 0 5px;">
                            STATUT DE LA CANDIDATURE
                        </p>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:16px;font-weight:700;padding:5px 14px;border-radius:20px;background:<?= $bs['bg'] ?>;color:<?= $bs['color'] ?>;border:1px solid <?= $bs['border'] ?>;">
                                <?= $statut ?>
                            </span>
                            <span style="font-size:12px;color:#A1A1AA;">Déposée le <?= $date_depot ?></span>
                        </div>
                    </div>
                    <!-- Décisions individuelles -->
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <?php
                        $decisions = [
                            'Étudiant'   => $candidature['choixEtudiant']   ?? 'En attente',
                            'Entreprise' => $candidature['choixEntreprise'] ?? 'En attente',
                        ];
                        foreach ($decisions as $qui => $dec):
                            $dec_badge = $badge_statut[$dec] ?? $badge_statut['En attente'];
                        ?>
                        <div style="text-align:center;">
                            <p style="font-size:10px;font-weight:700;color:#A1A1AA;letter-spacing:0.05em;margin:0 0 4px;">
                                <?= strtoupper($qui) ?>
                            </p>
                            <span style="font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:20px;background:<?= $dec_badge['bg'] ?>;color:<?= $dec_badge['color'] ?>;border:1px solid <?= $dec_badge['border'] ?>;">
                                <?= htmlspecialchars($dec) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ── Grille principale ───────────────────────────────── -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

                    <!-- Offre -->
                    <div class="card animate-in delay-2" style="padding:22px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                            <div style="width:32px;height:32px;background:#EEF2FF;border-radius:8px;display:flex;align-items:center;justify-content:center;" aria-hidden="true">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#4F46E5" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:#18181B;margin:0;">
                                Offre de stage
                            </h3>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <div>
                                <p style="font-size:10.5px;font-weight:700;color:#A1A1AA;letter-spacing:0.05em;margin:0 0 4px;">ENTREPRISE</p>
                                <p style="font-size:13.5px;font-weight:600;color:#18181B;margin:0;">
                                    <?= htmlspecialchars($candidature['entreprise_nom']) ?>
                                </p>
                                <?php if ($candidature['entreprise_mail']): ?>
                                <a href="mailto:<?= htmlspecialchars($candidature['entreprise_mail']) ?>"
                                   style="font-size:12px;color:#4F46E5;text-decoration:none;">
                                    <?= htmlspecialchars($candidature['entreprise_mail']) ?>
                                </a>
                                <?php endif; ?>
                            </div>

                            <div style="height:1px;background:#F4F4F5;"></div>

                            <div>
                                <p style="font-size:10.5px;font-weight:700;color:#A1A1AA;letter-spacing:0.05em;margin:0 0 4px;">DESCRIPTION</p>
                                <p style="font-size:13px;color:#52525B;margin:0;line-height:1.6;">
                                    <?= htmlspecialchars($candidature['offre_description'] ?? 'Aucune description.') ?>
                                </p>
                            </div>

                            <div style="height:1px;background:#F4F4F5;"></div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <div>
                                    <p style="font-size:10.5px;font-weight:700;color:#A1A1AA;letter-spacing:0.05em;margin:0 0 3px;">NIVEAU</p>
                                    <?php if ($candidature['offre_niveau']): ?>
                                    <span style="font-size:12px;font-weight:600;padding:2px 8px;border-radius:20px;background:<?= $nb_niveau['bg'] ?>;color:<?= $nb_niveau['color'] ?>;border:1px solid <?= $nb_niveau['border'] ?>;">
                                        <?= htmlspecialchars($candidature['offre_niveau']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span style="font-size:12px;color:#A1A1AA;">Tous niveaux</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p style="font-size:10.5px;font-weight:700;color:#A1A1AA;letter-spacing:0.05em;margin:0 0 3px;">DURÉE</p>
                                    <p style="font-size:13px;color:#3F3F46;margin:0;font-weight:500;">
                                        <?= $candidature['offre_duree'] ? htmlspecialchars($candidature['offre_duree']) : '—' ?>
                                    </p>
                                </div>
                                <div>
                                    <p style="font-size:10.5px;font-weight:700;color:#A1A1AA;letter-spacing:0.05em;margin:0 0 3px;">DÉBUT</p>
                                    <p style="font-size:13px;color:#3F3F46;margin:0;font-weight:500;"><?= $date_debut ?></p>
                                </div>
                            </div>

                            <div style="padding-top:4px;">
                                <a href="offre_detail.php?id=<?= (int)$id_offre ?>"
                                   class="btn-secondary"
                                   style="font-size:12px;padding:6px 14px;width:100%;justify-content:center;">
                                    Voir l'offre complète →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Étudiant -->
                    <div class="card animate-in delay-2" style="padding:22px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                            <div style="width:32px;height:32px;background:#EEF2FF;border-radius:8px;display:flex;align-items:center;justify-content:center;" aria-hidden="true">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#4F46E5" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:#18181B;margin:0;">
                                Étudiant
                            </h3>
                        </div>

                        <!-- Avatar + nom -->
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;background:#FAFAFA;border:1px solid #F4F4F5;border-radius:10px;padding:12px 14px;">
                            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#4F46E5,#818CF8);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:white;flex-shrink:0;" aria-hidden="true">
                                <?= strtoupper(substr($candidature['prenom'] ?? '?', 0, 1)) ?>
                            </div>
                            <div>
                                <p style="font-size:14px;font-weight:600;color:#18181B;margin:0;">
                                    <?= htmlspecialchars($candidature['prenom'] . ' ' . $candidature['etudiant_nom']) ?>
                                </p>
                                <a href="mailto:<?= htmlspecialchars($candidature['etudiant_mail'] ?? '') ?>"
                                   style="font-size:12px;color:#4F46E5;text-decoration:none;">
                                    <?= htmlspecialchars($candidature['etudiant_mail'] ?? '') ?>
                                </a>
                            </div>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <?php
                            $infos_etudiant = [
                                'FILIÈRE' => $candidature['filiere'] ?: '—',
                                'ANNÉE'   => $candidature['annee']   ?: '—',
                                'GROUPE'  => $candidature['groupe']  ?: '—',
                            ];
                            ?>
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                                <?php foreach ($infos_etudiant as $lbl => $val): ?>
                                <div>
                                    <p style="font-size:10.5px;font-weight:700;color:#A1A1AA;letter-spacing:0.05em;margin:0 0 3px;"><?= $lbl ?></p>
                                    <p style="font-size:13px;color:#3F3F46;margin:0;font-weight:500;"><?= htmlspecialchars($val) ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ── Lettre de motivation ────────────────────────────── -->
                <?php if ($candidature['lettre_motivation']): ?>
                <div class="card animate-in delay-3" style="padding:22px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                        <div style="width:32px;height:32px;background:#FFFBEB;border-radius:8px;display:flex;align-items:center;justify-content:center;" aria-hidden="true">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#D97706" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 style="font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:#18181B;margin:0;">
                            Lettre de motivation
                        </h3>
                        <span style="font-size:11px;color:#A1A1AA;margin-left:auto;">
                            <?= mb_strlen($candidature['lettre_motivation']) ?> caractères
                        </span>
                    </div>
                    <div style="background:#FAFAFA;border:1px solid #F4F4F5;border-radius:10px;padding:18px;font-size:13.5px;color:#3F3F46;line-height:1.8;white-space:pre-wrap;max-height:320px;overflow-y:auto;">
                        <?= htmlspecialchars($candidature['lettre_motivation']) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── CV + Action entreprise ──────────────────────────── -->
                <div style="display:grid;grid-template-columns:<?= $role === 'Entreprise' ? '1fr 1fr' : '1fr' ?>;gap:20px;" class="animate-in delay-4">

                    <!-- CV -->
                    <div class="card" style="padding:22px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                            <div style="width:32px;height:32px;background:#F5F3FF;border-radius:8px;display:flex;align-items:center;justify-content:center;" aria-hidden="true">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#7C3AED" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                            </div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:#18181B;margin:0;">CV</h3>
                        </div>

                        <?php if ($cv_path && $candidature['cv_filename']): ?>
                        <div style="display:flex;align-items:center;gap:12px;background:#F5F3FF;border:1px solid #DDD6FE;border-radius:10px;padding:14px 16px;">
                            <div style="width:38px;height:38px;background:#EDE9FE;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;" aria-hidden="true">
                                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#7C3AED" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <p style="font-size:13px;font-weight:600;color:#18181B;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= htmlspecialchars($candidature['document_nom'] ?? $candidature['cv_filename']) ?>
                                </p>
                                <p style="font-size:11.5px;color:#7C3AED;margin:2px 0 0;">
                                    <?= strtoupper(pathinfo($candidature['cv_filename'], PATHINFO_EXTENSION)) ?>
                                </p>
                            </div>
                            <a href="<?= htmlspecialchars($cv_path) ?>"
                               download
                               class="btn-secondary"
                               style="font-size:12px;padding:6px 12px;flex-shrink:0;">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Télécharger
                            </a>
                        </div>
                        <?php else: ?>
                        <div style="text-align:center;padding:24px 0;">
                            <p style="font-size:13px;color:#A1A1AA;font-style:italic;">
                                Aucun CV joint à cette candidature.
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action entreprise -->
                    <?php if ($role === 'Entreprise' && (int)$candidature['idEntreprise'] === $id_metier): ?>
                    <div class="card" style="padding:22px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                            <div style="width:32px;height:32px;background:#ECFDF5;border-radius:8px;display:flex;align-items:center;justify-content:center;" aria-hidden="true">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:#18181B;margin:0;">
                                Votre décision
                            </h3>
                        </div>

                        <p style="font-size:12.5px;color:#71717A;margin:0 0 16px;line-height:1.5;">
                            Décision actuelle :
                            <span style="font-weight:600;color:<?= $badge_statut[$candidature['choixEntreprise'] ?? 'En attente']['color'] ?? '#D97706' ?>;">
                                <?= htmlspecialchars($candidature['choixEntreprise'] ?? 'En attente') ?>
                            </span>
                        </p>

                        <form method="POST"
                              action="candidature_detail.php?doc=<?= $id_document ?>&offre=<?= $id_offre ?>"
                              style="display:flex;flex-direction:column;gap:10px;">

                            <label style="font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;" for="choix_entreprise">
                                MODIFIER LA DÉCISION
                            </label>
                            <select id="choix_entreprise" name="choix_entreprise" class="input-field" style="cursor:pointer;">
                                <?php foreach (['En attente', 'Accepté', 'Refusé'] as $opt): ?>
                                <option value="<?= $opt ?>"
                                    <?= ($candidature['choixEntreprise'] ?? 'En attente') === $opt ? 'selected' : '' ?>>
                                    <?= $opt ?>
                                </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn-primary" style="justify-content:center;padding:9px;">
                                Enregistrer la décision
                            </button>

                        </form>

                        <?php if ($statut === 'Accepté'): ?>
                        <div style="margin-top:14px;background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:12px 14px;">
                            <p style="font-size:12.5px;color:#065F46;margin:0;font-weight:500;">
                                🎉 Stage validé — les deux parties ont accepté.
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- Info lecture seule pour les autres rôles -->
                <?php if (in_array($role, ['Tuteur', 'Jury', 'Administrateur'])): ?>
                <div class="card animate-in delay-4" style="padding:18px 22px;background:#F9F9F9;border-color:#F4F4F5;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span style="font-size:12.5px;color:#71717A;">
                            Vous consultez cette candidature en lecture seule (rôle : <?= htmlspecialchars($role) ?>).
                        </span>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <?php elseif (!$db_error): ?>
            <div style="text-align:center;padding:60px 0;">
                <p style="font-size:14px;color:#A1A1AA;">Candidature introuvable ou accès non autorisé.</p>
                <a href="candidatures.php" class="btn-secondary" style="margin-top:14px;display:inline-flex;">
                    Retour aux candidatures
                </a>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include('../components/footer.php'); ?>

</body>
</html>