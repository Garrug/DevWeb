<?php
// components/header.php
// $page_title peut être défini dans la page avant l'include pour personnaliser le titre
$page_title = isset($page_title) ? htmlspecialchars($page_title) . ' — StageFlow' : 'StageFlow';
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand:         #4F46E5;
            --brand-light:   #EEF2FF;
            --brand-dark:    #3730A3;
            --sidebar-bg:    #0F0F14;
            --sidebar-text:  #A1A1AA;
            --sidebar-hover: #27272A;
            --sidebar-active:#1E1B4B;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #F5F5F7;
            color: #18181B;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, .font-display {
            font-family: 'Syne', sans-serif;
        }

        .sidebar {
            width: 224px;
            min-height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(255,255,255,0.06);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--sidebar-text);
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .nav-link:hover {
            background: var(--sidebar-hover);
            color: #FAFAFA;
        }
        .nav-link.active {
            background: var(--sidebar-active);
            color: #C7D2FE;
        }
        .nav-link.active svg { color: #818CF8; }

        /* Lien déconnexion — hover rouge géré en CSS, pas en JS inline */
        .nav-link-danger:hover {
            background: rgba(239,68,68,0.1);
            color: #FCA5A5;
        }

        .topbar {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid #E4E4E7;
        }

        .stat-card {
            background: white;
            border: 1px solid #E4E4E7;
            border-radius: 16px;
            padding: 24px;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .stat-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .card {
            background: white;
            border: 1px solid #E4E4E7;
            border-radius: 16px;
            overflow: hidden;
        }

        .offre-card {
            background: white;
            border: 1px solid #E4E4E7;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: all 0.2s ease;
        }
        .offre-card:hover {
            border-color: #A5B4FC;
            box-shadow: 0 4px 20px rgba(79,70,229,0.08);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--brand);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-primary:hover {
            background: var(--brand-dark);
            box-shadow: 0 4px 12px rgba(79,70,229,0.3);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: white;
            border: 1.5px solid #E4E4E7;
            color: #3F3F46;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-secondary:hover {
            border-color: #A5B4FC;
            color: var(--brand);
            background: var(--brand-light);
        }

        .role-badge {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.03em;
            padding: 4px 10px;
            border-radius: 20px;
            background: var(--brand-light);
            color: var(--brand);
            border: 1px solid #C7D2FE;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4F46E5, #818CF8);
            color: white;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 0 2px white, 0 0 0 4px #C7D2FE;
        }

        .input-field {
            width: 100%;
            border: 1.5px solid #E4E4E7;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            color: #18181B;
            background: white;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
            font-family: 'DM Sans', sans-serif;
        }
        .input-field:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }
        .input-field::placeholder { color: #A1A1AA; }

        .table-row {
            border-bottom: 1px solid #F4F4F5;
            transition: background 0.1s;
        }
        .table-row:hover  { background: #FAFAFA; }
        .table-row:last-child { border-bottom: none; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-in             { animation: fadeInUp 0.35s ease forwards; }
        .delay-1                { animation-delay: 0.05s; opacity: 0; }
        .delay-2                { animation-delay: 0.10s; opacity: 0; }
        .delay-3                { animation-delay: 0.15s; opacity: 0; }
        .delay-4                { animation-delay: 0.20s; opacity: 0; }
        .delay-5                { animation-delay: 0.25s; opacity: 0; }

        ::-webkit-scrollbar       { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #D4D4D8; border-radius: 99px; }
    </style>
</head>