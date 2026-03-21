<?php
session_start();

// Rediriger si déjà connecté
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

$error   = isset($_GET['error']);
$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="fr">

<?php 
    $page_title = 'Connexion';
    include('../components/header.php');
?>

<body style="background:#0F0F14;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;">

<div style="position:fixed;inset:0;background:radial-gradient(ellipse at 30% 20%, rgba(79,70,229,0.12) 0%, transparent 60%), radial-gradient(ellipse at 70% 80%, rgba(129,140,248,0.08) 0%, transparent 50%);pointer-events:none;"></div>

<div style="width:100%;max-width:420px;position:relative;z-index:1;">

    <!-- Logo -->
    <div style="text-align:center;margin-bottom:32px;" class="animate-in delay-1">
        <div style="display:inline-flex;align-items:center;gap:10px;margin-bottom:10px;">
            <div style="width:40px;height:40px;background:linear-gradient(135deg,#4F46E5,#818CF8);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(79,70,229,0.4);">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <span style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:#FAFAFA;">Stage<span style="color:#818CF8;">Flow</span></span>
        </div>
        <p style="font-size:13px;color:#52525B;margin:0;">Plateforme de gestion des stages</p>
    </div>

    <!-- Card connexion -->
    <div class="animate-in delay-2" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:32px;backdrop-filter:blur(12px);">

        <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#FAFAFA;margin:0 0 6px;">Connexion</h2>
        <p style="font-size:13px;color:#71717A;margin:0 0 24px;">Entrez vos identifiants pour accéder à la plateforme.</p>

        <!-- Message succès (inscription réussie) -->
        <?php if ($success): ?>
        <div role="alert" style="background:rgba(5,150,105,0.1);border:1px solid rgba(5,150,105,0.25);border-radius:10px;padding:12px 14px;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#6EE7B7" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span style="font-size:13px;color:#6EE7B7;">Compte créé avec succès. Vous pouvez vous connecter.</span>
        </div>
        <?php endif; ?>

        <!-- Message erreur -->
        <?php if ($error): ?>
        <div role="alert" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:10px;padding:12px 14px;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#FCA5A5" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span style="font-size:13px;color:#FCA5A5;">Identifiant ou mot de passe incorrect.</span>
        </div>
        <?php endif; ?>

        <form action="../includes/login_process.php" method="POST" style="display:flex;flex-direction:column;gap:16px;" novalidate>

            <div>
                <label style="display:block;font-size:12.5px;font-weight:600;color:#D4D4D8;margin-bottom:6px;" for="identifiant">
                    Identifiant
                </label>
                <input
                    type="text"
                    id="identifiant"
                    name="identifiant"
                    placeholder="ex. prenom.nom"
                    autocomplete="username"
                    required
                    class="sf-login-input"
                >
            </div>

            <div>
                <label style="display:block;font-size:12.5px;font-weight:600;color:#D4D4D8;margin-bottom:6px;" for="mdp">
                    Mot de passe
                </label>
                <input
                    type="password"
                    id="mdp"
                    name="mdp"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                    class="sf-login-input"
                >
            </div>

            <button type="submit" class="sf-login-btn" style="margin-top:4px;">
                Se connecter →
            </button>

        </form>

    </div>

    <p style="text-align:center;font-size:13px;color:#52525B;margin-top:20px;" class="animate-in delay-3">
        Pas encore de compte ?
        <a href="register.php" style="color:#818CF8;text-decoration:none;font-weight:600;"
           onmouseover="this.style.textDecoration='underline'"
           onmouseout="this.style.textDecoration='none'">Créer un compte</a>
    </p>

</div>

<!-- Styles spécifiques à cette page (inputs dark + bouton) -->
<style>
.sf-login-input {
    width: 100%;
    background: rgba(255,255,255,0.05);
    border: 1.5px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 11px 14px;
    font-size: 14px;
    color: #FAFAFA;
    outline: none;
    font-family: 'DM Sans', sans-serif;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.sf-login-input:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.2);
}
.sf-login-input::placeholder {
    color: #52525B;
}

.sf-login-btn {
    width: 100%;
    background: linear-gradient(135deg, #4F46E5, #6366F1);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 12px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 14px rgba(79,70,229,0.35);
}
.sf-login-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(79,70,229,0.45);
}
.sf-login-btn:active {
    transform: translateY(0);
}
</style>

</body>
</html>