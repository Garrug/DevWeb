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

// ── Stat 1 : Offres disponibles ───────────────────────────────────────────
$nb_offres = 0;
try {
    if ($role === 'Entreprise') {
        // Une entreprise voit uniquement ses propres offres
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Offre_de_stage WHERE idEntreprise = ?");
        $stmt->execute([(int)$_SESSION['id_metier']]);
    } else {
        $stmt = $conn->query("SELECT COUNT(*) FROM Offre_de_stage");
    }
    $nb_offres = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('[StageFlow] dashboard nb_offres : ' . $e->getMessage());
}

// ── Stat 2 : Candidatures ─────────────────────────────────────────────────
$nb_candidatures = 0;
try {
    if ($role === 'Etudiant') {
        $id_etudiant = (int)($_SESSION['id_metier'] ?? 0);
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM candidater WHERE idEtudiant = ?"
        );
        $stmt->execute([$id_etudiant]);
    } elseif ($role === 'Entreprise') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM candidater c
             JOIN Offre_de_stage o ON c.idOffre = o.idOffre
             WHERE o.idEntreprise = ?"
        );
        $stmt->execute([(int)$_SESSION['id_metier']]);
    } else {
        // Tuteur, Jury, Admin : toutes les candidatures
        $stmt = $conn->query("SELECT COUNT(*) FROM candidater");
    }
    $nb_candidatures = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('[StageFlow] dashboard nb_candidatures : ' . $e->getMessage());
}

// ── Stat 3 : Stages validés (les deux parties ont accepté) ────────────────
$nb_valides = 0;
try {
    if ($role === 'Etudiant') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM candidater
             WHERE idEtudiant = ?
               AND LOWER(choixEntreprise) = 'accepté'
               AND LOWER(choixEtudiant)   = 'accepté'"
        );
        $stmt->execute([(int)$_SESSION['id_metier']]);
    } elseif ($role === 'Entreprise') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM candidater c
             JOIN Offre_de_stage o ON c.idOffre = o.idOffre
             WHERE o.idEntreprise = ?
               AND LOWER(c.choixEntreprise) = 'accepté'
               AND LOWER(c.choixEtudiant)   = 'accepté'"
        );
        $stmt->execute([(int)$_SESSION['id_metier']]);
    } else {
        $stmt = $conn->query(
            "SELECT COUNT(*) FROM candidater
             WHERE LOWER(choixEntreprise) = 'accepté'
               AND LOWER(choixEtudiant)   = 'accepté'"
        );
    }
    $nb_valides = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('[StageFlow] dashboard nb_valides : ' . $e->getMessage());
}

// ── Stat 4 : Candidatures en attente (pour le sous-titre) ────────────────
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
        $stmt->execute([(int)$_SESSION['id_metier']]);
        $nb_en_attente = (int)$stmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log('[StageFlow] dashboard nb_en_attente : ' . $e->getMessage());
}

// ── Dernières offres (4 max) ──────────────────────────────────────────────
$offres_recentes = [];
try {
    if ($role === 'Entreprise') {
        $stmt = $conn->prepare(
            "SELECT o.description AS poste,
                    e.nom         AS entreprise,
                    o.duree,
                    o.debutStage
             FROM Offre_de_stage o
             JOIN Entreprise e ON o.idEntreprise = e.idEntreprise
             WHERE o.idEntreprise = ?
             ORDER BY o.dateDepot DESC
             LIMIT 4"
        );
        $stmt->execute([(int)$_SESSION['id_metier']]);
    } else {
        $stmt = $conn->query(
            "SELECT o.description AS poste,
                    e.nom         AS entreprise,
                    o.duree,
                    o.debutStage
             FROM Offre_de_stage o
             JOIN Entreprise e ON o.idEntreprise = e.idEntreprise
             ORDER BY o.dateDepot DESC
             LIMIT 4"
        );
    }
    $offres_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('[StageFlow] dashboard offres_recentes : ' . $e->getMessage());
}

// ── Config des cartes stat ────────────────────────────────────────────────
$stats = [
    [
        'label'    => $role === 'Entreprise' ? 'Mes offres publiées' : 'Offres disponibles',
        'value'    => $nb_offres,
        'change'   => $nb_offres > 0 ? $nb_offres . ' au total' : 'Aucune offre',
        'positive' => $nb_offres > 0 ? true : null,
        'color'    => '#4F46E5',
        'bg'       => '#EEF2FF',
        'delay'    => 'delay-2',
        'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    ],
    [
        'label'    => 'Candidatures',
        'value'    => $nb_candidatures,
        'change'   => $role === 'Etudiant'
                        ? ($nb_en_attente > 0 ? $nb_en_attente . ' en attente' : 'Aucune en attente')
                        : $nb_candidatures . ' au total',
        'positive' => null,
        'color'    => '#D97706',
        'bg'       => '#FFFBEB',
        'delay'    => 'delay-3',
        'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    ],
    [
        'label'    => 'Stages validés',
        'value'    => $nb_valides,
        'change'   => $nb_valides > 0 ? 'Félicitations !' : 'Aucun pour le moment',
        'positive' => $nb_valides > 0 ? true : null,
        'color'    => '#059669',
        'bg'       => '#ECFDF5',
        'delay'    => 'delay-4',
        'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    ],
];
?>
<!DOCTYPE html>
<html lang="fr">

<?php
$page_title = 'Tableau de bord';
include('../components/header.php');
?>

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
                <div class="avatar"><?= strtoupper(substr($user, 0, 1)) ?></div>
            </div>
        </header>

        <main style="flex:1;padding:28px;overflow-y:auto;">

            <!-- Titre section -->
            <div class="animate-in delay-1" style="margin-bottom:24px;">
                <h2 style="font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:#18181B;margin:0 0 4px;">Vue d'ensemble</h2>
                <p style="font-size:13px;color:#71717A;margin:0;">Résumé de votre activité sur la plateforme</p>
            </div>

            <!-- Cartes statistiques -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px;">
                <?php foreach ($stats as $stat): ?>
                <div class="stat-card animate-in <?= $stat['delay'] ?>">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;">
                        <div style="width:40px;height:40px;border-radius:10px;background:<?= $stat['bg'] ?>;display:flex;align-items:center;justify-content:center;">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24"
                                 stroke="<?= $stat['color'] ?>" stroke-width="1.8" aria-hidden="true">
                                <?= $stat['icon'] ?>
                            </svg>
                        </div>
                    </div>
                    <p style="font-family:'Syne',sans-serif;font-size:30px;font-weight:700;color:#18181B;margin:0 0 4px;">
                        <?= $stat['value'] ?>
                    </p>
                    <p style="font-size:13px;color:#71717A;margin:0 0 8px;"><?= $stat['label'] ?></p>
                    <p style="font-size:11px;font-weight:500;margin:0;color:<?= $stat['positive'] === true ? '#059669' : ($stat['positive'] === false ? '#DC2626' : '#A1A1AA') ?>;">
                        <?= $stat['change'] ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Grille inférieure -->
            <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;" class="animate-in delay-5">

                <!-- Dernières offres (données réelles) -->
                <div class="card">
                    <div style="padding:18px 20px;border-bottom:1px solid #F4F4F5;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;">Dernières offres</h3>
                            <p style="font-size:12px;color:#A1A1AA;margin:4px 0 0;">Offres de stage récemment publiées</p>
                        </div>
                        <a href="offres.php" class="btn-secondary" style="font-size:12px;padding:6px 12px;">
                            Voir tout
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>

                    <?php if (empty($offres_recentes)): ?>
                    <div style="padding:32px 20px;text-align:center;">
                        <p style="font-size:13px;color:#A1A1AA;">Aucune offre disponible pour le moment.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($offres_recentes as $offre): ?>
                    <div class="table-row" style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                        <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                            <div style="width:36px;height:36px;border-radius:9px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;" aria-hidden="true">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#4F46E5" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div style="min-width:0;">
                                <p style="font-size:13px;font-weight:600;color:#18181B;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars(mb_strimwidth($offre['poste'] ?? '', 0, 45, '…')) ?>
                                </p>
                                <p style="font-size:11.5px;color:#A1A1AA;margin:2px 0 0;">
                                    <?= htmlspecialchars($offre['entreprise']) ?>
                                    <?php if ($offre['duree']): ?>
                                    · <?= htmlspecialchars($offre['duree']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php if ($offre['debutStage']): ?>
                        <span style="font-size:10.5px;color:#A1A1AA;flex-shrink:0;white-space:nowrap;">
                            <?= date('d/m/Y', strtotime($offre['debutStage'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Actions rapides -->
                <div style="display:flex;flex-direction:column;gap:16px;">

                    <div class="card" style="padding:20px;">
                        <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0 0 16px;">Actions rapides</h3>
                        <div style="display:flex;flex-direction:column;gap:8px;">

                            <?php
                            $quick_actions = [
                                ['href' => 'offres.php',       'label' => 'Parcourir les offres',
                                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>'],
                                ['href' => 'candidatures.php', 'label' => 'Mes candidatures',
                                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>'],
                                ['href' => 'profil.php',       'label' => 'Modifier mon profil',
                                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'],
                            ];

                            // Ajouter "Mes offres" pour les entreprises
                            if ($role === 'Entreprise') {
                                array_unshift($quick_actions, [
                                    'href'  => 'mes_offres.php',
                                    'label' => 'Gérer mes offres',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>',
                                ]);
                            }
                            ?>

                            <?php foreach ($quick_actions as $qa): ?>
                            <a href="<?= $qa['href'] ?>"
                               style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:#F5F5F7;text-decoration:none;transition:background 0.15s;"
                               onmouseover="this.style.background='#EEF2FF'"
                               onmouseout="this.style.background='#F5F5F7'">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
                                     stroke="#4F46E5" stroke-width="1.8" aria-hidden="true">
                                    <?= $qa['icon'] ?>
                                </svg>
                                <span style="font-size:13px;font-weight:500;color:#3F3F46;">
                                    <?= $qa['label'] ?>
                                </span>
                            </a>
                            <?php endforeach; ?>

                        </div>
                    </div>

                    <!-- Statut du compte -->
                    <div class="card" style="padding:20px;background:linear-gradient(135deg,#1E1B4B 0%,#312E81 100%);">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:rgba(129,140,248,0.2);display:flex;align-items:center;justify-content:center;" aria-hidden="true">
                                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#818CF8" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#E0E7FF;margin:0;">Compte actif</p>
                                <p style="font-size:11px;color:#818CF8;margin:2px 0 0;"><?= htmlspecialchars($role_label) ?></p>
                            </div>
                        </div>
                        <p style="font-size:12px;color:#A5B4FC;margin:0 0 14px;line-height:1.5;">
                            Votre compte est vérifié et actif sur la plateforme StageFlow.
                        </p>
                        <a href="profil.php" style="font-size:12px;font-weight:600;color:#C7D2FE;text-decoration:none;display:flex;align-items:center;gap:4px;">
                            Voir mon profil
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>

                </div>

            </div>

        </main>

    </div>

</div>

</body>
</html>