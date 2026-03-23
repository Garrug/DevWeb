<?php
// pages/candidature_new.php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: login.php');
    exit();
}

require_once '../includes/db.php';

$id_offre = (int)($_GET['offre'] ?? 0);
if ($id_offre === 0) {
    header('Location: offres.php');
    exit();
}

// ── ID étudiant ───────────────────────────────────────────────────────────
$id_etudiant = (int)($_SESSION['id_metier'] ?? 0);
if ($id_etudiant === 0) {
    try {
        $stmt = $conn->prepare(
            "SELECT idEtudiant FROM Etudiant WHERE identifiant = ? LIMIT 1"
        );
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

// ── Récupération offre ────────────────────────────────────────────────────
$offre    = null;
$db_error = false;
try {
    $stmt = $conn->prepare(
        "SELECT o.idOffre, o.description, o.niveau, o.duree, o.debutStage,
                e.nom AS entreprise_nom
         FROM Offre_de_stage o
         JOIN Entreprise e ON o.idEntreprise = e.idEntreprise
         WHERE o.idOffre = ? LIMIT 1"
    );
    $stmt->execute([$id_offre]);
    $offre = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[StageFlow] candidature_new offre : ' . $e->getMessage());
    $db_error = true;
}
if (!$offre && !$db_error) {
    header('Location: offres.php');
    exit();
}

// ── Vérification doublon ──────────────────────────────────────────────────
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
        error_log('[StageFlow] candidature_new doublon : ' . $e->getMessage());
    }
}

// ── Traitement POST ───────────────────────────────────────────────────────
$success   = false;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $offre && !$deja_candidat) {

    $lettre      = trim($_POST['lettre_motivation'] ?? '');
    $cv_filename = null;          // nom unique stocké en base
    $cv_nom_orig = null;          // nom original du fichier (colonne `nom`)
    $cv_ok       = true;

    // ── Gestion upload CV ─────────────────────────────────────────────────
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] !== UPLOAD_ERR_NO_FILE) {

        $file     = $_FILES['cv'];
        $max_size = 5 * 1024 * 1024; // 5 Mo
        $exts_ok  = ['pdf', 'doc', 'docx'];
        $types_ok = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_msg = 'Erreur lors du téléversement (code ' . $file['error'] . ').';
            $cv_ok     = false;

        } elseif ($file['size'] > $max_size) {
            $error_msg = 'Fichier trop volumineux (5 Mo maximum).';
            $cv_ok     = false;

        } elseif (!in_array($ext, $exts_ok, true) || !in_array($file['type'], $types_ok, true)) {
            $error_msg = 'Format non accepté. PDF, DOC ou DOCX uniquement.';
            $cv_ok     = false;

        } else {
            // Nom original pour l'affichage (colonne `nom` de Document)
            $cv_nom_orig = basename($file['name']);

            // Nom sécurisé et unique pour le stockage
            $cv_filename = 'cv_' . $id_etudiant . '_' . $id_offre . '_' . time() . '.' . $ext;
            $upload_dir  = __DIR__ . '/../uploads/cv/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $upload_dir . $cv_filename)) {
                $error_msg   = 'Impossible de sauvegarder le fichier. Vérifiez les permissions du dossier uploads/cv/.';
                $cv_filename = null;
                $cv_nom_orig = null;
                $cv_ok       = false;
            }
        }
    }

    // ── Insertion en transaction ──────────────────────────────────────────
    if ($cv_ok) {
        try {
            $conn->beginTransaction();

            // 1. Créer le Document
            //    - nom      = nom original du fichier CV (ou "Candidature" si pas de CV)
            //    - dateDepot = aujourd'hui
            //    - lettre_motivation = texte de la lettre
            //    - cv_filename = nom du fichier stocké sur le serveur
            $stmt = $conn->prepare(
                "INSERT INTO Document (nom, dateDepot, lettre_motivation, cv_filename)
                 VALUES (:nom, CURDATE(), :lettre, :cv)"
            );
            $stmt->execute([
                ':nom'    => $cv_nom_orig ?? 'Candidature',
                ':lettre' => $lettre      ?: null,
                ':cv'     => $cv_filename,
            ]);

            $id_document = (int)$conn->lastInsertId();

            // 2. Créer la candidature liée au Document
            $stmt = $conn->prepare(
                "INSERT INTO candidater
                    (idDocument, idOffre, idEtudiant, choixEntreprise, choixEtudiant)
                 VALUES
                    (:idDoc, :idOffre, :idEtudiant, 'En attente', 'Accepté')"
            );
            $stmt->execute([
                ':idDoc'      => $id_document,
                ':idOffre'    => $id_offre,
                ':idEtudiant' => $id_etudiant,
            ]);

            $conn->commit();

            $success       = true;
            $deja_candidat = true;

        } catch (PDOException $e) {
            $conn->rollBack();

            // Supprimer le fichier uploadé si la transaction a échoué
            if ($cv_filename) {
                $path = __DIR__ . '/../uploads/cv/' . $cv_filename;
                if (file_exists($path)) unlink($path);
            }

            $code = (string)$e->getCode();
            if ($code === '23000') {
                $deja_candidat = true;
            } else {
                error_log('[StageFlow] candidature INSERT : '
                    . $e->getMessage()
                    . ' | code=' . $code
                    . ' | idOffre=' . $id_offre
                    . ' | idEtudiant=' . $id_etudiant);

                // Message d'erreur utile selon le code
                if (str_contains($e->getMessage(), 'lettre_motivation')
                    || str_contains($e->getMessage(), 'cv_filename')) {
                    $error_msg = 'La migration SQL n\'a pas été exécutée. '
                               . 'Ajoutez les colonnes lettre_motivation et cv_filename à la table Document '
                               . '(voir migration en haut du fichier).';
                } else {
                    $error_msg = 'Erreur lors de l\'enregistrement (code ' . htmlspecialchars($code) . '). '
                               . 'Consultez les logs PHP.';
                }
            }
        }
    }
}

// ── Données affichage ─────────────────────────────────────────────────────
$date_debut = ($offre && $offre['debutStage'])
    ? date('d/m/Y', strtotime($offre['debutStage'])) : '—';

$niveau_styles = [
    'Bac+2' => ['bg'=>'#F4F4F5','color'=>'#71717A','border'=>'#E4E4E7'],
    'Bac+3' => ['bg'=>'#EEF2FF','color'=>'#4F46E5','border'=>'#C7D2FE'],
    'Bac+4' => ['bg'=>'#F5F3FF','color'=>'#7C3AED','border'=>'#DDD6FE'],
    'Bac+5' => ['bg'=>'#ECFDF5','color'=>'#059669','border'=>'#A7F3D0'],
];
$niveau_default = ['bg'=>'#F4F4F5','color'=>'#71717A','border'=>'#E4E4E7'];
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

        <header class="topbar" style="padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;">
            <div style="display:flex;align-items:center;gap:12px;">
                <a href="offre_detail.php?id=<?= $id_offre ?>" class="btn-secondary" style="padding:6px 12px;font-size:12px;">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
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
                        <?= $offre ? ' · ' . htmlspecialchars($offre['entreprise_nom']) : '' ?>
                    </p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="role-badge">Étudiant</span>
                <a href="profil.php" title="Mon profil">
                    <div class="avatar"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
                </a>
            </div>
        </header>

        <main style="flex:1;padding:28px;overflow-y:auto;">

        <?php if ($db_error): ?>
        <div role="alert" style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
            <span style="font-size:13px;color:#DC2626;">Impossible de charger cette offre.</span>
        </div>
        <?php endif; ?>

        <?php if ($offre): ?>
        <div style="max-width:700px;margin:0 auto;display:flex;flex-direction:column;gap:20px;">

            <!-- Résumé offre -->
            <div class="card animate-in delay-1" style="padding:18px 22px;">
                <p style="font-size:10.5px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin:0 0 10px;">OFFRE CONCERNÉE</p>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:14px;font-weight:600;color:#18181B;margin:0 0 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= htmlspecialchars(mb_strimwidth($offre['description'] ?? '', 0, 70, '…')) ?>
                        </p>
                        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:5px;">
                            <span style="font-size:12.5px;color:#71717A;font-weight:600;"><?= htmlspecialchars($offre['entreprise_nom']) ?></span>
                            <?php if ($offre['duree']): ?>
                            <span style="font-size:12px;color:#A1A1AA;">⏱ <?= htmlspecialchars($offre['duree']) ?></span>
                            <?php endif; ?>
                            <span style="font-size:12px;color:#A1A1AA;">📅 Début : <?= $date_debut ?></span>
                        </div>
                    </div>
                    <?php if ($offre['niveau']): ?>
                    <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:<?= $nb['bg'] ?>;color:<?= $nb['color'] ?>;border:1px solid <?= $nb['border'] ?>;flex-shrink:0;">
                        <?= htmlspecialchars($offre['niveau']) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SUCCÈS -->
            <?php if ($success): ?>
            <div role="alert" class="card animate-in delay-1" style="padding:32px;text-align:center;border-color:#A7F3D0;">
                <div style="width:56px;height:56px;border-radius:50%;background:#ECFDF5;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;" aria-hidden="true">
                    <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#18181B;margin:0 0 8px;">
                    Candidature envoyée !
                </h2>
                <p style="font-size:13.5px;color:#71717A;margin:0 0 4px;">
                    Votre dossier a bien été transmis à
                    <strong style="color:#18181B;"><?= htmlspecialchars($offre['entreprise_nom']) ?></strong>.
                </p>
                <p style="font-size:12.5px;color:#A1A1AA;margin:0 0 24px;">
                    L'entreprise vous répondra dans les meilleurs délais.
                </p>
                <div style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap;">
                    <a href="candidatures.php" class="btn-primary" style="padding:9px 20px;">Voir mes candidatures</a>
                    <a href="offres.php" class="btn-secondary" style="padding:9px 18px;">Parcourir les offres</a>
                </div>
            </div>

            <!-- DÉJÀ CANDIDAT -->
            <?php elseif ($deja_candidat): ?>
            <div role="alert" class="card animate-in delay-1" style="padding:28px;text-align:center;border-color:#FDE68A;">
                <div style="width:48px;height:48px;border-radius:50%;background:#FFFBEB;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;" aria-hidden="true">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#D97706" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <h2 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#18181B;margin:0 0 8px;">
                    Vous avez déjà postulé
                </h2>
                <p style="font-size:13px;color:#71717A;margin:0 0 18px;">
                    Votre candidature pour cette offre est déjà enregistrée.
                </p>
                <div style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap;">
                    <a href="candidatures.php" class="btn-primary" style="padding:8px 18px;">Voir mes candidatures</a>
                    <a href="offres.php" class="btn-secondary" style="padding:8px 16px;">Retour aux offres</a>
                </div>
            </div>

            <!-- FORMULAIRE WIDGET -->
            <?php else: ?>

            <?php if ($error_msg): ?>
            <div role="alert" style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:13px 16px;display:flex;align-items:flex-start;gap:10px;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#EF4444" stroke-width="2" style="flex-shrink:0;margin-top:1px;" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span style="font-size:13px;color:#DC2626;"><?= htmlspecialchars($error_msg) ?></span>
            </div>
            <?php endif; ?>

            <!-- Widget -->
            <div class="card animate-in delay-2">

                <!-- Indicateur étapes -->
                <div style="padding:20px 24px;border-bottom:1px solid #F4F4F5;">
                    <div style="display:flex;align-items:center;">
                        <div style="display:flex;align-items:center;gap:8px;flex:1;">
                            <div id="step-circle-1" style="width:28px;height:28px;border-radius:50%;background:#4F46E5;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:white;flex-shrink:0;transition:all 0.3s;">1</div>
                            <span id="step-label-1" style="font-size:12.5px;font-weight:600;color:#4F46E5;transition:color 0.3s;">Lettre de motivation</span>
                        </div>
                        <div style="flex:1;height:2px;background:#E4E4E7;margin:0 12px;border-radius:2px;position:relative;overflow:hidden;">
                            <div id="step-line" style="position:absolute;inset:0;background:#4F46E5;transform:scaleX(0);transform-origin:left;transition:transform 0.4s ease;"></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;flex:1;justify-content:flex-end;">
                            <span id="step-label-2" style="font-size:12.5px;font-weight:500;color:#A1A1AA;transition:color 0.3s;">CV</span>
                            <div id="step-circle-2" style="width:28px;height:28px;border-radius:50%;background:#E4E4E7;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#A1A1AA;flex-shrink:0;transition:all 0.3s;">2</div>
                        </div>
                    </div>
                </div>

                <form method="POST"
                      action="candidature_new.php?offre=<?= $id_offre ?>"
                      enctype="multipart/form-data"
                      id="candidatureForm">

                    <!-- ÉTAPE 1 : Lettre -->
                    <div id="step1" style="padding:24px;">
                        <h3 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#18181B;margin:0 0 4px;">
                            Votre lettre de motivation
                        </h3>
                        <p style="font-size:13px;color:#A1A1AA;margin:0 0 18px;">
                            Présentez-vous et expliquez votre intérêt pour ce poste chez
                            <strong style="color:#3F3F46;"><?= htmlspecialchars($offre['entreprise_nom']) ?></strong>.
                        </p>

                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <label for="lettre_motivation" style="font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;">
                                LETTRE DE MOTIVATION
                            </label>
                            <span id="charCount" style="font-size:11.5px;color:#A1A1AA;">0 / 3000</span>
                        </div>

                        <textarea id="lettre_motivation"
                                  name="lettre_motivation"
                                  maxlength="3000"
                                  rows="11"
                                  placeholder="Madame, Monsieur,&#10;&#10;Je me permets de vous adresser ma candidature pour le stage proposé au sein de votre entreprise...&#10;&#10;Cordialement."
                                  class="input-field"
                                  style="resize:vertical;min-height:220px;line-height:1.7;"></textarea>

                        <div style="margin-top:20px;display:flex;justify-content:flex-end;">
                            <button type="button" id="btnStep2" class="btn-primary" style="padding:10px 22px;">
                                Suivant — Joindre un CV
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- ÉTAPE 2 : CV -->
                    <div id="step2" style="padding:24px;display:none;">
                        <h3 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#18181B;margin:0 0 4px;">
                            Joindre votre CV
                            <span style="font-size:13px;font-weight:400;color:#A1A1AA;">(optionnel)</span>
                        </h3>
                        <p style="font-size:13px;color:#A1A1AA;margin:0 0 20px;">
                            Formats acceptés : PDF, DOC, DOCX · Taille max : 5 Mo
                        </p>

                        <!-- Zone drag & drop -->
                        <div id="dropZone"
                             style="border:2px dashed #E4E4E7;border-radius:14px;padding:40px 24px;text-align:center;transition:all 0.2s;cursor:pointer;position:relative;"
                             onclick="document.getElementById('cv').click()">

                            <div id="dropDefault">
                                <div style="width:52px;height:52px;background:#EEF2FF;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;" aria-hidden="true">
                                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#4F46E5" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </div>
                                <p style="font-size:14.5px;font-weight:600;color:#18181B;margin:0 0 6px;">
                                    Glissez votre CV ici
                                </p>
                                <p style="font-size:13px;color:#A1A1AA;margin:0 0 16px;">
                                    ou cliquez pour parcourir vos fichiers
                                </p>
                                <div style="display:inline-flex;gap:8px;">
                                    <span style="font-size:11.5px;background:#F4F4F5;color:#71717A;padding:4px 10px;border-radius:6px;">PDF</span>
                                    <span style="font-size:11.5px;background:#F4F4F5;color:#71717A;padding:4px 10px;border-radius:6px;">DOC</span>
                                    <span style="font-size:11.5px;background:#F4F4F5;color:#71717A;padding:4px 10px;border-radius:6px;">DOCX</span>
                                </div>
                            </div>

                            <div id="dropSelected" style="display:none;">
                                <div style="width:52px;height:52px;background:#ECFDF5;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;" aria-hidden="true">
                                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <p id="fileName" style="font-size:14px;font-weight:600;color:#18181B;margin:0 0 4px;"></p>
                                <p id="fileSize" style="font-size:12.5px;color:#A1A1AA;margin:0 0 14px;"></p>
                                <button type="button" id="removeFile"
                                        style="font-size:12.5px;color:#DC2626;background:none;border:none;cursor:pointer;text-decoration:underline;font-family:'DM Sans',sans-serif;padding:0;">
                                    Supprimer ce fichier
                                </button>
                            </div>

                            <input type="file" id="cv" name="cv"
                                   accept=".pdf,.doc,.docx"
                                   style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
                        </div>

                        <!-- Récap dossier -->
                        <div style="margin-top:16px;background:#F9F9F9;border:1px solid #F4F4F5;border-radius:10px;padding:14px 16px;">
                            <p style="font-size:10.5px;font-weight:700;color:#A1A1AA;letter-spacing:0.05em;margin:0 0 8px;">
                                RÉCAPITULATIF DU DOSSIER
                            </p>
                            <div style="display:flex;align-items:center;gap:7px;margin-bottom:5px;">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span style="font-size:12.5px;color:#52525B;">
                                    Lettre : <span id="recapLettre" style="font-weight:600;color:#18181B;">—</span>
                                </span>
                            </div>
                            <div style="display:flex;align-items:center;gap:7px;">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" id="recapCVIcon" stroke="#A1A1AA" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                                </svg>
                                <span style="font-size:12.5px;color:#52525B;">
                                    CV : <span id="recapCV" style="color:#A1A1AA;">non joint</span>
                                </span>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div style="margin-top:20px;display:flex;gap:10px;">
                            <button type="button" id="btnBack" class="btn-secondary" style="padding:10px 16px;">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Retour
                            </button>
                            <button type="submit" id="btnSubmit" class="btn-primary"
                                    style="flex:1;justify-content:center;padding:11px;font-size:14px;">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Envoyer ma candidature
                            </button>
                        </div>
                        <p style="font-size:11.5px;color:#A1A1AA;text-align:center;margin:10px 0 0;">
                            Vous ne pouvez postuler qu'une seule fois à cette offre.
                        </p>
                    </div>

                </form>
            </div>

            <?php endif; ?>

        </div>
        <?php endif; ?>
        </main>
    </div>
</div>

<?php include('../components/footer.php'); ?>

<script>
const step1        = document.getElementById('step1');
const step2        = document.getElementById('step2');
const btnStep2     = document.getElementById('btnStep2');
const btnBack      = document.getElementById('btnBack');
const lettre       = document.getElementById('lettre_motivation');
const charCount    = document.getElementById('charCount');
const qualityBar   = document.getElementById('qualityBar');
const qualityLabel = document.getElementById('qualityLabel');
const dropZone     = document.getElementById('dropZone');
const fileInput    = document.getElementById('cv');
const dropDefault  = document.getElementById('dropDefault');
const dropSelected = document.getElementById('dropSelected');
const removeFile   = document.getElementById('removeFile');
const stepCircle1  = document.getElementById('step-circle-1');
const stepCircle2  = document.getElementById('step-circle-2');
const stepLabel1   = document.getElementById('step-label-1');
const stepLabel2   = document.getElementById('step-label-2');
const stepLine     = document.getElementById('step-line');
const recapLettre  = document.getElementById('recapLettre');
const recapCV      = document.getElementById('recapCV');
const recapCVIcon  = document.getElementById('recapCVIcon');

// ── Compteur + qualité lettre ─────────────────────────────────────────────
lettre.addEventListener('input', () => {
    const len = lettre.value.length;
    charCount.textContent = len + ' / 3000';

    let pct = 0, color = '#E4E4E7', label = '—';
    if      (len === 0)  { pct = 0;   color = '#E4E4E7'; label = '—'; }
    else if (len < 100)  { pct = 15;  color = '#EF4444'; label = 'Trop courte'; }
    else if (len < 300)  { pct = 35;  color = '#D97706'; label = 'Correcte'; }
    else if (len < 700)  { pct = 65;  color = '#4F46E5'; label = 'Bien'; }
    else if (len < 1200) { pct = 85;  color = '#059669'; label = 'Très bien'; }
    else                 { pct = 100; color = '#059669'; label = 'Excellente'; }

    qualityBar.style.width      = pct + '%';
    qualityBar.style.background = color;
    qualityLabel.textContent    = label;
    qualityLabel.style.color    = color;
});

// ── Étape 1 → 2 ──────────────────────────────────────────────────────────
btnStep2.addEventListener('click', () => {
    const len = lettre.value.trim().length;
    recapLettre.textContent = len > 0 ? len + ' caractères rédigés' : 'non rédigée';
    recapLettre.style.color = len > 0 ? '#18181B' : '#A1A1AA';

    step1.style.display = 'none';
    step2.style.display = 'block';

    // Mettre à jour indicateurs
    stepCircle1.style.background = '#059669';
    stepCircle1.innerHTML = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    stepLabel1.style.color = '#059669';

    stepCircle2.style.background = '#4F46E5';
    stepCircle2.style.color      = 'white';
    stepCircle2.textContent      = '2';
    stepLabel2.style.color       = '#4F46E5';
    stepLabel2.style.fontWeight  = '600';
    stepLine.style.transform     = 'scaleX(1)';
});

// ── Étape 2 → 1 ──────────────────────────────────────────────────────────
btnBack.addEventListener('click', () => {
    step2.style.display = 'none';
    step1.style.display = 'block';

    stepCircle1.style.background = '#4F46E5';
    stepCircle1.textContent      = '1';
    stepLabel1.style.color       = '#4F46E5';
    stepCircle2.style.background = '#E4E4E7';
    stepCircle2.style.color      = '#A1A1AA';
    stepCircle2.textContent      = '2';
    stepLabel2.style.color       = '#A1A1AA';
    stepLabel2.style.fontWeight  = '500';
    stepLine.style.transform     = 'scaleX(0)';
});

// ── Drag & Drop ───────────────────────────────────────────────────────────
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#4F46E5';
    dropZone.style.background  = '#EEF2FF';
});
dropZone.addEventListener('dragleave', () => {
    dropZone.style.borderColor = '#E4E4E7';
    dropZone.style.background  = '';
});
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#E4E4E7';
    dropZone.style.background  = '';
    if (e.dataTransfer.files.length > 0) {
        const transfer = new DataTransfer();
        transfer.items.add(e.dataTransfer.files[0]);
        fileInput.files = transfer.files;
        afficherFichier(e.dataTransfer.files[0]);
    }
});
fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) afficherFichier(fileInput.files[0]);
});

function afficherFichier(file) {
    const ko = (file.size / 1024).toFixed(0);
    const mo = (file.size / 1024 / 1024).toFixed(2);

    dropDefault.style.display  = 'none';
    dropSelected.style.display = 'block';
    dropZone.style.borderColor = '#A7F3D0';
    dropZone.style.borderStyle = 'solid';
    dropZone.style.background  = '#F0FDF4';

    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = ko > 1024 ? mo + ' Mo' : ko + ' Ko';

    recapCV.textContent = file.name;
    recapCV.style.color = '#18181B';
    recapCVIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>';
    recapCVIcon.setAttribute('stroke', '#059669');
}

// ── Supprimer fichier ─────────────────────────────────────────────────────
removeFile.addEventListener('click', (e) => {
    e.stopPropagation();
    fileInput.value            = '';
    dropDefault.style.display  = 'block';
    dropSelected.style.display = 'none';
    dropZone.style.borderColor = '#E4E4E7';
    dropZone.style.borderStyle = 'dashed';
    dropZone.style.background  = '';
    recapCV.textContent = 'non joint';
    recapCV.style.color = '#A1A1AA';
    recapCVIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>';
    recapCVIcon.setAttribute('stroke', '#A1A1AA');
});

// ── Anti double-envoi ─────────────────────────────────────────────────────
document.getElementById('candidatureForm').addEventListener('submit', () => {
    const btn     = document.getElementById('btnSubmit');
    btn.disabled  = true;
    btn.innerHTML = '⏳ Envoi en cours…';
    btn.style.opacity = '0.7';
});
</script>

</body>
</html>