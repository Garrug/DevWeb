<?php
// components/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);

$menu = [
    [
        'label'  => 'Tableau de bord',
        'href'   => 'dashboard.php',
        'icon'   => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'stroke' => false,
    ],
    [
        'label'  => 'Offres de stage',
        'href'   => 'offres.php',
        'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'stroke' => true,
    ],
    [
        'label'  => 'Candidatures',
        'href'   => 'candidatures.php',
        'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
        'stroke' => true,
    ],
    [
        'label'  => 'Entreprises',
        'href'   => 'entreprises.php',
        'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        'stroke' => true,
    ],
    [
        'label'  => 'Mon profil',
        'href'   => 'profil.php',
        'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'stroke' => true,
    ],
];

// Lien "Mes offres" visible uniquement pour les entreprises
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Entreprise') {
    array_splice($menu, 2, 0, [[
        'label'  => 'Mes offres',
        'href'   => 'mes_offres.php',
        'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>',
        'stroke' => true,
    ]]);
}
?>

<aside class="sidebar" role="navigation" aria-label="Navigation principale">

    <!-- Logo -->
    <div style="padding:20px;border-bottom:1px solid rgba(255,255,255,0.06);">
        <a href="dashboard.php" style="display:flex;align-items:center;gap:8px;text-decoration:none;">
            <div style="width:30px;height:30px;background:linear-gradient(135deg,#4F46E5,#818CF8);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:17px;color:#FAFAFA;">
                Stage<span style="color:#818CF8;">Flow</span>
            </span>
        </a>
        <p style="font-size:11px;color:#52525B;margin-top:6px;padding-left:38px;">Gestion des stages</p>
    </div>

    <!-- Navigation -->
    <nav style="flex:1;padding:12px 10px;display:flex;flex-direction:column;gap:2px;">

        <?php foreach ($menu as $item):
            $is_active = ($current_page === $item['href']);
        ?>
        <a href="<?= $item['href'] ?>"
           class="nav-link <?= $is_active ? 'active' : '' ?>"
           aria-current="<?= $is_active ? 'page' : 'false' ?>">
            <svg class="flex-shrink-0" width="16" height="16"
                 fill="<?= $item['stroke'] ? 'none' : 'currentColor' ?>"
                 viewBox="0 0 24 24"
                 <?= $item['stroke'] ? 'stroke="currentColor" stroke-width="1.8"' : '' ?>
                 aria-hidden="true">
                <?= $item['icon'] ?>
            </svg>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>

    </nav>

    <!-- Section compte -->
    <div style="padding:8px 10px 8px;">
        <p style="font-size:10px;font-weight:600;color:#3F3F46;letter-spacing:0.08em;padding:0 12px;margin-bottom:4px;">COMPTE</p>
        <a href="../includes/logout.php" class="nav-link nav-link-danger" style="color:#71717A;">
            <svg class="flex-shrink-0" width="16" height="16" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Déconnexion
        </a>
    </div>

    <!-- User footer -->
    <?php if (isset($_SESSION['user'])): ?>
    <div style="padding:12px 14px;border-top:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;gap:10px;">
        <a href="/pages/profil.php">
            <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#4F46E5,#818CF8);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:white;flex-shrink:0;"
                aria-hidden="true">
                <?= strtoupper(substr($_SESSION['user'], 0, 1)) ?>
            </div>
        </a>
        <div style="overflow:hidden;min-width:0;">
            <p style="font-size:12px;font-weight:600;color:#FAFAFA;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:0;">
                <?= htmlspecialchars($_SESSION['user']) ?>
            </p>
            <p style="font-size:10px;color:#52525B;margin:2px 0 0;">
                <?= htmlspecialchars($_SESSION['role'] ?? '') ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

</aside>