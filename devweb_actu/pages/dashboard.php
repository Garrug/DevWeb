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

    <!-- Zone principale -->
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

        <!-- Contenu -->
        <main style="flex:1;padding:28px;overflow-y:auto;">

            <!-- Titre section -->
            <div class="animate-in delay-1" style="margin-bottom:24px;">
                <h2 style="font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:#18181B;margin:0 0 4px;">Vue d'ensemble</h2>
                <p style="font-size:13px;color:#71717A;margin:0;">Résumé de votre activité sur la plateforme</p>
            </div>

            <!-- Cartes statistiques -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px;">

                <?php
                $stats = [
                    [
                        'label'    => 'Offres disponibles',
                        'value'    => '12',
                        'change'   => '+3 cette semaine',
                        'positive' => true,
                        'color'    => '#4F46E5',
                        'bg'       => '#EEF2FF',
                        'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                        'delay'    => 'delay-2',
                    ],
                    [
                        'label'    => 'Candidatures',
                        'value'    => '3',
                        'change'   => '1 en attente',
                        'positive' => null,
                        'color'    => '#D97706',
                        'bg'       => '#FFFBEB',
                        'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                        'delay'    => 'delay-3',
                    ],
                    [
                        'label'    => 'Stages validés',
                        'value'    => '1',
                        'change'   => 'Félicitations !',
                        'positive' => true,
                        'color'    => '#059669',
                        'bg'       => '#ECFDF5',
                        'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                        'delay'    => 'delay-4',
                    ],
                ];
                foreach ($stats as $stat): ?>
                <div class="stat-card animate-in <?= $stat['delay'] ?>">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;">
                        <div style="width:40px;height:40px;border-radius:10px;background:<?= $stat['bg'] ?>;display:flex;align-items:center;justify-content:center;">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="<?= $stat['color'] ?>" stroke-width="1.8">
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

            <!-- Grille inférieure : Offres récentes + Actions rapides -->
            <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;" class="animate-in delay-5">

                <!-- Tableau offres récentes -->
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

                    <?php
                    $offres = [
                        ['poste' => 'Développeur Web Full-Stack', 'entreprise' => 'TechCorp',   'duree' => '6 mois',  'statut' => 'Ouvert',  'niveau' => 'Bac+5'],
                        ['poste' => 'Data Analyst',               'entreprise' => 'DataVision', 'duree' => '4 mois',  'statut' => 'Ouvert',  'niveau' => 'Bac+4'],
                        ['poste' => 'Ingénieur DevOps',           'entreprise' => 'CloudSys',   'duree' => '5 mois',  'statut' => 'Fermé',   'niveau' => 'Bac+5'],
                        ['poste' => 'UX Designer',                'entreprise' => 'CreativeLab','duree' => '3 mois',  'statut' => 'Ouvert',  'niveau' => 'Bac+3'],
                    ];
                    foreach ($offres as $offre):
                        $open = $offre['statut'] === 'Ouvert';
                    ?>
                    <div class="table-row" style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                        <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                            <div style="width:36px;height:36px;border-radius:9px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#4F46E5" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div style="min-width:0;">
                                <p style="font-size:13px;font-weight:600;color:#18181B;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($offre['poste']) ?></p>
                                <p style="font-size:11.5px;color:#A1A1AA;margin:2px 0 0;"><?= htmlspecialchars($offre['entreprise']) ?> · <?= $offre['duree'] ?></p>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                            <span style="font-size:10.5px;font-weight:600;padding:3px 9px;border-radius:20px;background:<?= $open ? '#ECFDF5' : '#F4F4F5' ?>;color:<?= $open ? '#059669' : '#A1A1AA' ?>;">
                                <?= $offre['statut'] ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Actions rapides -->
                <div style="display:flex;flex-direction:column;gap:16px;">

                    <!-- Raccourcis -->
                    <div class="card" style="padding:20px;">
                        <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0 0 16px;">Actions rapides</h3>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <a href="offres.php" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:#F5F5F7;text-decoration:none;transition:background 0.15s;"
                               onmouseover="this.style.background='#EEF2FF'" onmouseout="this.style.background='#F5F5F7'">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#4F46E5" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                                </svg>
                                <span style="font-size:13px;font-weight:500;color:#3F3F46;">Parcourir les offres</span>
                            </a>
                            <a href="candidatures.php" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:#F5F5F7;text-decoration:none;transition:background 0.15s;"
                               onmouseover="this.style.background='#EEF2FF'" onmouseout="this.style.background='#F5F5F7'">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#4F46E5" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <span style="font-size:13px;font-weight:500;color:#3F3F46;">Mes candidatures</span>
                            </a>
                            <a href="profil.php" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:#F5F5F7;text-decoration:none;transition:background 0.15s;"
                               onmouseover="this.style.background='#EEF2FF'" onmouseout="this.style.background='#F5F5F7'">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#4F46E5" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span style="font-size:13px;font-weight:500;color:#3F3F46;">Modifier mon profil</span>
                            </a>
                        </div>
                    </div>

                    <!-- Statut du compte -->
                    <div class="card" style="padding:20px;background:linear-gradient(135deg,#1E1B4B 0%,#312E81 100%);">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:rgba(129,140,248,0.2);display:flex;align-items:center;justify-content:center;">
                                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#818CF8" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <div>
                                <p style="font-size:13px;font-weight:700;color:#E0E7FF;margin:0;">Compte actif</p>
                                <p style="font-size:11px;color:#818CF8;margin:2px 0 0;"><?= htmlspecialchars($role_label) ?></p>
                            </div>
                        </div>
                        <p style="font-size:12px;color:#A5B4FC;margin:0 0 14px;line-height:1.5;">Votre compte est vérifié et actif sur la plateforme StageFlow.</p>
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
