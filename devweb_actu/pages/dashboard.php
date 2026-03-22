<?php
// pages/dashboard.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];
$role = $_SESSION['role'];

$role_labels = [
    'Administrateur' => 'Administrateur',
    'Etudiant'       => 'Étudiant',
    'Tuteur'         => 'Tuteur',
    'Jury'           => 'Jury',
    'Entreprise'     => 'Entreprise',
];
$role_label = $role_labels[$role] ?? $role;

require_once '../includes/db.php';

$id_metier = (int)($_SESSION['id_metier'] ?? 0);

// =========================================================================
// DONNÉES COMMUNES
// =========================================================================

// Nb offres
$nb_offres = 0;
try {
    if ($role === 'Entreprise') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Offre_de_stage WHERE idEntreprise = ?");
        $stmt->execute([$id_metier]);
    } else {
        $stmt = $conn->query("SELECT COUNT(*) FROM Offre_de_stage");
    }
    $nb_offres = (int)$stmt->fetchColumn();
} catch (PDOException $e) { error_log('[StageFlow] dashboard nb_offres : ' . $e->getMessage()); }

// Nb candidatures
$nb_candidatures = 0;
try {
    if ($role === 'Etudiant') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM candidater WHERE idEtudiant = ?");
        $stmt->execute([$id_metier]);
    } elseif ($role === 'Entreprise') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM candidater c
             JOIN Offre_de_stage o ON c.idOffre = o.idOffre
             WHERE o.idEntreprise = ?"
        );
        $stmt->execute([$id_metier]);
    } else {
        $stmt = $conn->query("SELECT COUNT(*) FROM candidater");
    }
    $nb_candidatures = (int)$stmt->fetchColumn();
} catch (PDOException $e) { error_log('[StageFlow] dashboard nb_candidatures : ' . $e->getMessage()); }

// Nb validés
$nb_valides = 0;
try {
    if ($role === 'Etudiant') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM candidater
             WHERE idEtudiant = ?
               AND LOWER(choixEntreprise) = 'accepté'
               AND LOWER(choixEtudiant)   = 'accepté'"
        );
        $stmt->execute([$id_metier]);
    } elseif ($role === 'Entreprise') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM candidater c
             JOIN Offre_de_stage o ON c.idOffre = o.idOffre
             WHERE o.idEntreprise = ?
               AND LOWER(c.choixEntreprise) = 'accepté'
               AND LOWER(c.choixEtudiant)   = 'accepté'"
        );
        $stmt->execute([$id_metier]);
    } else {
        $stmt = $conn->query(
            "SELECT COUNT(*) FROM candidater
             WHERE LOWER(choixEntreprise) = 'accepté' AND LOWER(choixEtudiant) = 'accepté'"
        );
    }
    $nb_valides = (int)$stmt->fetchColumn();
} catch (PDOException $e) { error_log('[StageFlow] dashboard nb_valides : ' . $e->getMessage()); }

// Nb en attente (étudiant)
$nb_en_attente = 0;
try {
    if ($role === 'Etudiant') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM candidater
             WHERE idEtudiant = ?
               AND LOWER(choixEntreprise) != 'refusé'
               AND LOWER(choixEtudiant)   != 'refusé'
               AND NOT (LOWER(choixEntreprise) = 'accepté' AND LOWER(choixEtudiant) = 'accepté')"
        );
        $stmt->execute([$id_metier]);
        $nb_en_attente = (int)$stmt->fetchColumn();
    }
} catch (PDOException $e) { error_log('[StageFlow] dashboard nb_en_attente : ' . $e->getMessage()); }

// =========================================================================
// DONNÉES SPÉCIFIQUES PAR RÔLE
// =========================================================================

// ── ÉTUDIANT : ses candidatures récentes ─────────────────────────────────
$candidatures_recentes = [];
if ($role === 'Etudiant') {
    try {
        $stmt = $conn->prepare(
            "SELECT c.idDocument, c.idOffre,
                    c.choixEntreprise, c.choixEtudiant,
                    o.description AS offre_desc,
                    o.debutStage,
                    e.nom         AS entreprise_nom
             FROM candidater c
             JOIN Offre_de_stage o ON c.idOffre      = o.idOffre
             JOIN Entreprise e     ON o.idEntreprise  = e.idEntreprise
             WHERE c.idEtudiant = ?
             ORDER BY o.dateDepot DESC
             LIMIT 4"
        );
        $stmt->execute([$id_metier]);
        $candidatures_recentes = $stmt->fetchAll();
    } catch (PDOException $e) { error_log('[StageFlow] dashboard candidatures_recentes : ' . $e->getMessage()); }
}

// ── ÉTUDIANT : profil complet ? ───────────────────────────────────────────
$profil_incomplet = false;
if ($role === 'Etudiant') {
    try {
        $stmt = $conn->prepare(
            "SELECT filiere, annee, groupe FROM Etudiant WHERE idEtudiant = ? LIMIT 1"
        );
        $stmt->execute([$id_metier]);
        $row = $stmt->fetch();
        if ($row) {
            $profil_incomplet = empty($row['filiere']) || empty($row['annee']) || empty($row['groupe']);
        }
    } catch (PDOException $e) { error_log('[StageFlow] dashboard profil_check : ' . $e->getMessage()); }
}

// ── ENTREPRISE : ses offres avec nb candidatures ──────────────────────────
// ── ENTREPRISE : ses offres avec candidats ────────────────────────────────
$offres_entreprise = [];
if ($role === 'Entreprise') {
    try {
        $stmt = $conn->prepare(
            "SELECT o.idOffre, o.description, o.niveau, o.debutStage,
                    COUNT(c.idDocument) AS nb_candidatures
             FROM Offre_de_stage o
             LEFT JOIN candidater c ON c.idOffre = o.idOffre
             WHERE o.idEntreprise = ?
             GROUP BY o.idOffre, o.description, o.niveau, o.debutStage
             ORDER BY o.dateDepot DESC
             LIMIT 5"
        );
        $stmt->execute([$id_metier]);
        $offres_entreprise = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('[StageFlow] dashboard offres_entreprise : ' . $e->getMessage());
    }

    // Récupérer les candidats pour chaque offre
    foreach ($offres_entreprise as &$offre_item) {
        try {
            $stmt = $conn->prepare(
                "SELECT c.idDocument, c.idOffre,
                        c.choixEntreprise, c.choixEtudiant,
                        et.prenom, et.nom AS etudiant_nom
                 FROM candidater c
                 JOIN Etudiant et ON c.idEtudiant = et.idEtudiant
                 WHERE c.idOffre = ?
                 ORDER BY c.idDocument DESC"
            );
            $stmt->execute([$offre_item['idOffre']]);
            $offre_item['candidats'] = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[StageFlow] dashboard candidats_offre : ' . $e->getMessage());
            $offre_item['candidats'] = [];
        }
    }
    unset($offre_item); // important après foreach par référence
}

// ── ADMIN / TUTEUR / JURY : stats globales ────────────────────────────────
$nb_etudiants   = 0;
$nb_entreprises = 0;
$candidatures_globales = [];

if (in_array($role, ['Administrateur', 'Tuteur', 'Jury'])) {
    try {
        $nb_etudiants   = (int)$conn->query("SELECT COUNT(*) FROM Etudiant")->fetchColumn();
        $nb_entreprises = (int)$conn->query("SELECT COUNT(*) FROM Entreprise")->fetchColumn();
    } catch (PDOException $e) { error_log('[StageFlow] dashboard stats_globales : ' . $e->getMessage()); }

    try {
        $stmt = $conn->query(
            "SELECT c.idDocument, c.idOffre,
                    c.choixEntreprise, c.choixEtudiant,
                    o.description  AS offre_desc,
                    e.nom          AS entreprise_nom,
                    CONCAT(et.prenom, ' ', et.nom) AS etudiant_nom
             FROM candidater c
             JOIN Offre_de_stage o ON c.idOffre      = o.idOffre
             JOIN Entreprise e     ON o.idEntreprise  = e.idEntreprise
             JOIN Etudiant et      ON c.idEtudiant    = et.idEtudiant
             ORDER BY o.dateDepot DESC
             LIMIT 5"
        );
        $candidatures_globales = $stmt->fetchAll();
    } catch (PDOException $e) { error_log('[StageFlow] dashboard candidatures_globales : ' . $e->getMessage()); }
}

// ── Dernières offres (tous sauf Entreprise) ───────────────────────────────
$offres_recentes = [];
if ($role !== 'Entreprise') {
    try {
        $stmt = $conn->query(
            "SELECT o.idOffre, o.description AS poste, o.duree, o.debutStage,
                    e.nom AS entreprise
             FROM Offre_de_stage o
             JOIN Entreprise e ON o.idEntreprise = e.idEntreprise
             ORDER BY o.dateDepot DESC
             LIMIT 4"
        );
        $offres_recentes = $stmt->fetchAll();
    } catch (PDOException $e) { error_log('[StageFlow] dashboard offres_recentes : ' . $e->getMessage()); }
}

// ── Config stat cards ─────────────────────────────────────────────────────
$stats = [
    [
        'label'    => $role === 'Entreprise' ? 'Mes offres publiées' : 'Offres disponibles',
        'value'    => $nb_offres,
        'change'   => $nb_offres > 0 ? $nb_offres . ' au total' : 'Aucune offre',
        'positive' => $nb_offres > 0,
        'color'    => '#4F46E5', 'bg' => '#EEF2FF', 'delay' => 'delay-2',
        'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    ],
    [
        'label'    => 'Candidatures',
        'value'    => $nb_candidatures,
        'change'   => $role === 'Etudiant'
            ? ($nb_en_attente > 0 ? $nb_en_attente . ' en attente' : 'Aucune en attente')
            : $nb_candidatures . ' au total',
        'positive' => null,
        'color'    => '#D97706', 'bg' => '#FFFBEB', 'delay' => 'delay-3',
        'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    ],
    [
        'label'    => 'Stages validés',
        'value'    => $nb_valides,
        'change'   => $nb_valides > 0 ? 'Félicitations !' : 'Aucun pour le moment',
        'positive' => $nb_valides > 0 ? true : null,
        'color'    => '#059669', 'bg' => '#ECFDF5', 'delay' => 'delay-4',
        'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    ],
];

// Stats supplémentaires Admin/Tuteur/Jury
if (in_array($role, ['Administrateur', 'Tuteur', 'Jury'])) {
    $stats[] = [
        'label'    => 'Étudiants inscrits',
        'value'    => $nb_etudiants,
        'change'   => $nb_etudiants . ' comptes actifs',
        'positive' => $nb_etudiants > 0 ? true : null,
        'color'    => '#0284C7', 'bg' => '#E0F2FE', 'delay' => 'delay-5',
        'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>',
    ];
}

// Helpers statut
function statut_label(string $ce, string $cet): string {
    $ce  = strtolower(trim($ce));
    $cet = strtolower(trim($cet));
    if ($ce === 'accepté' && $cet === 'accepté') return 'Accepté';
    if ($ce === 'refusé'  || $cet === 'refusé')  return 'Refusé';
    return 'En attente';
}

$badge_statut = [
    'En attente' => 'background:#FFFBEB;color:#D97706;border:1px solid #FDE68A;',
    'Accepté'    => 'background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;',
    'Refusé'     => 'background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;',
];

$niveau_styles = [
    'Bac+2' => ['bg'=>'#F4F4F5','color'=>'#71717A','border'=>'#E4E4E7'],
    'Bac+3' => ['bg'=>'#EEF2FF','color'=>'#4F46E5','border'=>'#C7D2FE'],
    'Bac+4' => ['bg'=>'#F5F3FF','color'=>'#7C3AED','border'=>'#DDD6FE'],
    'Bac+5' => ['bg'=>'#ECFDF5','color'=>'#059669','border'=>'#A7F3D0'],
];
?>
<!DOCTYPE html>
<html lang="fr">

<?php $page_title = 'Tableau de bord'; include('../components/header.php'); ?>

<body>
<div style="display:flex;min-height:100vh;">

    <?php include('../components/sidebar.php'); ?>

    <div style="flex:1;display:flex;flex-direction:column;min-width:0;">

        <!-- Topbar -->
        <header class="topbar" style="padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;">
            <div>
                <h1 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#18181B;margin:0;">Tableau de bord</h1>
                <p style="font-size:12px;color:#A1A1AA;margin:0;">Bienvenue, <?= htmlspecialchars($user) ?> 👋</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="role-badge"><?= htmlspecialchars($role_label) ?></span>
                <a href="profil.php">
                    <div class="avatar" title="Mon profil"><?= strtoupper(substr($user, 0, 1)) ?></div>
                </a>
            </div>
        </header>

        <main style="flex:1;padding:28px;overflow-y:auto;">

            <!-- ── Alerte profil incomplet (Étudiant) ───────────────────── -->
            <?php if ($profil_incomplet): ?>
            <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:13px 16px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;"
                 class="animate-in delay-1">
                <div style="display:flex;align-items:center;gap:10px;">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#D97706" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <span style="font-size:13px;color:#92400E;font-weight:500;">
                        Votre profil est incomplet — ajoutez votre filière, année et groupe pour être visible.
                    </span>
                </div>
                <a href="profil.php" class="btn-secondary" style="font-size:12px;padding:6px 12px;white-space:nowrap;">
                    Compléter mon profil →
                </a>
            </div>
            <?php endif; ?>

            <!-- ── Titre ─────────────────────────────────────────────────── -->
            <div class="animate-in delay-1" style="margin-bottom:24px;">
                <h2 style="font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:#18181B;margin:0 0 4px;">Vue d'ensemble</h2>
                <p style="font-size:13px;color:#71717A;margin:0;">Résumé de votre activité sur la plateforme</p>
            </div>

            <!-- ── Stat cards ─────────────────────────────────────────────── -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:28px;">
                <?php foreach ($stats as $stat): ?>
                <div class="stat-card animate-in <?= $stat['delay'] ?>">
                    <div style="margin-bottom:16px;">
                        <div style="width:40px;height:40px;border-radius:10px;background:<?= $stat['bg'] ?>;display:flex;align-items:center;justify-content:center;">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24"
                                 stroke="<?= $stat['color'] ?>" stroke-width="1.8" aria-hidden="true">
                                <?= $stat['icon'] ?>
                            </svg>
                        </div>
                    </div>
                    <p style="font-family:'Syne',sans-serif;font-size:30px;font-weight:700;color:#18181B;margin:0 0 4px;"><?= $stat['value'] ?></p>
                    <p style="font-size:13px;color:#71717A;margin:0 0 8px;"><?= $stat['label'] ?></p>
                    <p style="font-size:11px;font-weight:500;margin:0;color:<?= $stat['positive'] === true ? '#059669' : ($stat['positive'] === false ? '#DC2626' : '#A1A1AA') ?>;">
                        <?= $stat['change'] ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ================================================================
                 SECTION SPÉCIFIQUE PAR RÔLE
            ================================================================ -->

            <?php if ($role === 'Etudiant'): ?>
            <!-- ── ÉTUDIANT : candidatures récentes + offres ─────────────── -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="animate-in delay-5">

                <!-- Mes candidatures récentes -->
                <div class="card">
                    <div style="padding:16px 20px;border-bottom:1px solid #F4F4F5;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;">Mes candidatures</h3>
                            <p style="font-size:12px;color:#A1A1AA;margin:4px 0 0;">Vos dernières démarches</p>
                        </div>
                        <a href="candidatures.php" class="btn-secondary" style="font-size:12px;padding:5px 10px;">
                            Voir tout <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    <?php if (empty($candidatures_recentes)): ?>
                    <div style="padding:32px 20px;text-align:center;">
                        <p style="font-size:13px;color:#A1A1AA;margin:0 0 12px;">Vous n'avez pas encore postulé.</p>
                        <a href="offres.php" class="btn-primary" style="font-size:12px;padding:7px 14px;">
                            Parcourir les offres
                        </a>
                    </div>
                    <?php else: ?>
                    <?php foreach ($candidatures_recentes as $c):
                        $statut      = statut_label($c['choixEntreprise'] ?? '', $c['choixEtudiant'] ?? '');
                        $badge_style = $badge_statut[$statut] ?? '';
                    ?>
                    <div class="table-row" style="padding:13px 20px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
                        <div style="min-width:0;flex:1;">
                            <p style="font-size:13px;font-weight:600;color:#18181B;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars(mb_strimwidth($c['offre_desc'] ?? '', 0, 40, '…')) ?>
                            </p>
                            <p style="font-size:11.5px;color:#A1A1AA;margin:2px 0 0;">
                                <?= htmlspecialchars($c['entreprise_nom']) ?>
                            </p>
                        </div>
                        <span style="font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;flex-shrink:0;<?= $badge_style ?>">
                            <?= $statut ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Dernières offres -->
                <div class="card">
                    <div style="padding:16px 20px;border-bottom:1px solid #F4F4F5;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;">Offres récentes</h3>
                            <p style="font-size:12px;color:#A1A1AA;margin:4px 0 0;">Nouvelles opportunités</p>
                        </div>
                        <a href="offres.php" class="btn-secondary" style="font-size:12px;padding:5px 10px;">
                            Voir tout <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    <?php if (empty($offres_recentes)): ?>
                    <div style="padding:32px 20px;text-align:center;">
                        <p style="font-size:13px;color:#A1A1AA;">Aucune offre disponible.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($offres_recentes as $o): ?>
                    <div class="table-row" style="padding:13px 20px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
                        <div style="min-width:0;flex:1;">
                            <p style="font-size:13px;font-weight:600;color:#18181B;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars(mb_strimwidth($o['poste'] ?? '', 0, 40, '…')) ?>
                            </p>
                            <p style="font-size:11.5px;color:#A1A1AA;margin:2px 0 0;">
                                <?= htmlspecialchars($o['entreprise']) ?>
                                <?= $o['duree'] ? '· ' . htmlspecialchars($o['duree']) : '' ?>
                            </p>
                        </div>
                        <a href="offre_detail.php?id=<?= (int)$o['idOffre'] ?>"
                           class="btn-secondary" style="font-size:11.5px;padding:4px 10px;flex-shrink:0;">
                            Voir →
                        </a>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

            <?php elseif ($role === 'Entreprise'): ?>
            <!-- ── ENTREPRISE : mes offres + candidatures reçues ─────────── -->
            <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;" class="animate-in delay-5">

                <!-- Mes offres avec nb candidatures -->
                <div class="card">
                    <div style="padding:16px 20px;border-bottom:1px solid #F4F4F5;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;">Mes offres publiées</h3>
                            <p style="font-size:12px;color:#A1A1AA;margin:4px 0 0;">Candidatures reçues par offre</p>
                        </div>
                        <a href="mes_offres.php" class="btn-primary" style="font-size:12px;padding:6px 12px;">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nouvelle offre
                        </a>
                    </div>

                    <?php if (empty($offres_entreprise)): ?>
                    <div style="padding:32px 20px;text-align:center;">
                        <p style="font-size:13px;color:#A1A1AA;margin:0 0 12px;">Vous n'avez pas encore publié d'offre.</p>
                        <a href="mes_offres.php" class="btn-primary" style="font-size:12px;padding:7px 14px;">Publier une offre</a>
                    </div>

                    <?php else: ?>
                    <?php foreach ($offres_entreprise as $o):
                        $nb_style   = $niveau_styles[$o['niveau']] ?? ['bg'=>'#F4F4F5','color'=>'#71717A','border'=>'#E4E4E7'];
                        $date_debut = $o['debutStage'] ? date('d/m/Y', strtotime($o['debutStage'])) : '—';
                        $candidats  = $o['candidats'] ?? [];

                        $badge_statut_mini = [
                            'En attente' => ['bg'=>'#FFFBEB','color'=>'#D97706'],
                            'Accepté'    => ['bg'=>'#ECFDF5','color'=>'#059669'],
                            'Refusé'     => ['bg'=>'#FEF2F2','color'=>'#DC2626'],
                        ];
                    ?>

                    <!-- Offre row + candidats dépliables -->
                    <div style="border-bottom:1px solid #F4F4F5;">

                        <!-- Header offre — cliquable pour déplier -->
                        <div style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer;transition:background 0.15s;"
                            onclick="toggleCandidats(<?= (int)$o['idOffre'] ?>)"
                            onmouseover="this.style.background='#FAFAFA'" onmouseout="this.style.background=''">

                            <div style="min-width:0;flex:1;">
                                <div style="display:flex;align-items:center;gap:7px;margin-bottom:4px;">
                                    <?php if ($o['niveau']): ?>
                                    <span style="font-size:10.5px;font-weight:600;padding:2px 7px;border-radius:20px;
                                                background:<?= $nb_style['bg'] ?>;color:<?= $nb_style['color'] ?>;border:1px solid <?= $nb_style['border'] ?>;">
                                        <?= htmlspecialchars($o['niveau']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <span style="font-size:11px;color:#A1A1AA;">Début : <?= $date_debut ?></span>
                                </div>
                                <p style="font-size:13px;font-weight:500;color:#18181B;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= htmlspecialchars(mb_strimwidth($o['description'] ?? '', 0, 55, '…')) ?>
                                </p>
                            </div>

                            <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
                                <!-- Compteur -->
                                <div style="text-align:right;">
                                    <p style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#4F46E5;margin:0;line-height:1;">
                                        <?= (int)$o['nb_candidatures'] ?>
                                    </p>
                                    <p style="font-size:10.5px;color:#A1A1AA;margin:0;">
                                        candidature<?= $o['nb_candidatures'] > 1 ? 's' : '' ?>
                                    </p>
                                </div>
                                <!-- Chevron -->
                                <?php if ((int)$o['nb_candidatures'] > 0): ?>
                                <svg id="chevron-<?= (int)$o['idOffre'] ?>"
                                    width="16" height="16" fill="none" viewBox="0 0 24 24"
                                    stroke="#A1A1AA" stroke-width="2.5"
                                    style="transition:transform 0.2s;flex-shrink:0;" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                                <?php endif; ?>
                            </div>

                        </div>

                        <!-- Liste des candidats — masquée par défaut -->
                        <?php if (!empty($candidats)): ?>
                        <div id="candidats-<?= (int)$o['idOffre'] ?>"
                            style="display:none;background:#FAFAFA;border-top:1px solid #F4F4F5;">

                            <?php foreach ($candidats as $c):
                                // Calculer le statut de ce candidat
                                $ce  = strtolower(trim($c['choixEntreprise'] ?? ''));
                                $cet = strtolower(trim($c['choixEtudiant']  ?? ''));
                                if ($ce === 'accepté' && $cet === 'accepté')   $statut_c = 'Accepté';
                                elseif ($ce === 'refusé' || $cet === 'refusé') $statut_c = 'Refusé';
                                else                                            $statut_c = 'En attente';

                                $bs_mini = $badge_statut_mini[$statut_c] ?? $badge_statut_mini['En attente'];
                            ?>
                            <a href="candidature_detail.php?doc=<?= (int)$c['idDocument'] ?>&offre=<?= (int)$c['idOffre'] ?>"
                            style="display:flex;align-items:center;justify-content:space-between;gap:10px;
                                    padding:11px 20px 11px 32px;text-decoration:none;
                                    border-bottom:1px solid #F4F4F5;transition:background 0.12s;"
                            onmouseover="this.style.background='#EEF2FF'" onmouseout="this.style.background=''">

                                <!-- Avatar + nom -->
                                <div style="display:flex;align-items:center;gap:9px;min-width:0;">
                                    <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#4F46E5,#818CF8);
                                                display:flex;align-items:center;justify-content:center;
                                                font-size:11px;font-weight:700;color:white;flex-shrink:0;" aria-hidden="true">
                                        <?= strtoupper(substr($c['prenom'] ?? '?', 0, 1)) ?>
                                    </div>
                                    <span style="font-size:13px;font-weight:500;color:#18181B;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?= htmlspecialchars($c['prenom'] . ' ' . $c['etudiant_nom']) ?>
                                    </span>
                                </div>

                                <!-- Statut + flèche -->
                                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                                    <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;
                                                background:<?= $bs_mini['bg'] ?>;color:<?= $bs_mini['color'] ?>;">
                                        <?= $statut_c ?>
                                    </span>
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24"
                                        stroke="#A1A1AA" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>

                            </a>
                            <?php endforeach; ?>

                        </div>
                        <?php endif; ?>

                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- ── ADMIN / TUTEUR / JURY : vue globale ────────────────────── -->
            <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;" class="animate-in delay-5">

                <!-- Candidatures récentes globales -->
                <div class="card">
                    <div style="padding:16px 20px;border-bottom:1px solid #F4F4F5;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;">Candidatures récentes</h3>
                            <p style="font-size:12px;color:#A1A1AA;margin:4px 0 0;">Dernières demandes sur la plateforme</p>
                        </div>
                        <a href="candidatures.php" class="btn-secondary" style="font-size:12px;padding:5px 10px;">
                            Voir tout <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    <?php if (empty($candidatures_globales)): ?>
                    <div style="padding:32px 20px;text-align:center;">
                        <p style="font-size:13px;color:#A1A1AA;">Aucune candidature enregistrée.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($candidatures_globales as $c):
                        $statut      = statut_label($c['choixEntreprise'] ?? '', $c['choixEtudiant'] ?? '');
                        $badge_style = $badge_statut[$statut] ?? '';
                    ?>
                    <div class="table-row" style="padding:13px 20px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
                        <div style="min-width:0;flex:1;">
                            <p style="font-size:13px;font-weight:600;color:#18181B;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars(mb_strimwidth($c['offre_desc'] ?? '', 0, 38, '…')) ?>
                            </p>
                            <p style="font-size:11.5px;color:#A1A1AA;margin:2px 0 0;">
                                <?= htmlspecialchars($c['etudiant_nom']) ?>
                                · <?= htmlspecialchars($c['entreprise_nom']) ?>
                            </p>
                        </div>
                        <span style="font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;flex-shrink:0;<?= $badge_style ?>">
                            <?= $statut ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Stats globales + actions -->
                <div style="display:flex;flex-direction:column;gap:14px;">

                    <!-- Nb entreprises -->
                    <div class="card" style="padding:18px 20px;display:flex;align-items:center;gap:14px;">
                        <div style="width:40px;height:40px;border-radius:10px;background:#E0F2FE;display:flex;align-items:center;justify-content:center;flex-shrink:0;" aria-hidden="true">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#0284C7" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <p style="font-family:'Syne',sans-serif;font-size:24px;font-weight:700;color:#18181B;margin:0;"><?= $nb_entreprises ?></p>
                            <p style="font-size:12px;color:#71717A;margin:0;">Entreprises partenaires</p>
                        </div>
                    </div>

                    <!-- Actions rapides admin -->
                    <div class="card" style="padding:18px 20px;">
                        <h3 style="font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:#18181B;margin:0 0 12px;">Accès rapides</h3>
                        <div style="display:flex;flex-direction:column;gap:7px;">
                            <?php
                            $actions_admin = [
                                ['href'=>'offres.php',       'label'=>'Toutes les offres'],
                                ['href'=>'entreprises.php',  'label'=>'Liste des entreprises'],
                                ['href'=>'candidatures.php', 'label'=>'Toutes les candidatures'],
                            ];
                            foreach ($actions_admin as $a): ?>
                            <a href="<?= $a['href'] ?>"
                               style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border-radius:9px;background:#F5F5F7;text-decoration:none;font-size:12.5px;font-weight:500;color:#3F3F46;transition:background 0.15s;"
                               onmouseover="this.style.background='#EEF2FF';this.style.color='#4F46E5'" onmouseout="this.style.background='#F5F5F7';this.style.color='#3F3F46'">
                                <?= $a['label'] ?>
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

            </div>
            <?php endif; ?>

        </main>
    </div>
</div>
<script>
function toggleCandidats(idOffre) {
    const liste   = document.getElementById('candidats-' + idOffre);
    const chevron = document.getElementById('chevron-'   + idOffre);
    if (!liste) return;

    const isOpen = liste.style.display !== 'none';
    liste.style.display          = isOpen ? 'none'              : 'block';
    if (chevron) chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}
</script>
</body>
</html>