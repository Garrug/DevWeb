<?php
// pages/mes_offres.php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Entreprise') {
    header('Location: login.php');
    exit();
}

require_once '../includes/db.php';

$id_entreprise = (int)($_SESSION['id_metier'] ?? 0);

if ($id_entreprise === 0) {
    try {
        $stmt = $conn->prepare("SELECT idEntreprise FROM Entreprise WHERE identifiant = ? LIMIT 1");
        $stmt->execute([$_SESSION['user']]);
        $row = $stmt->fetch();
        if ($row) {
            $id_entreprise         = (int)$row['idEntreprise'];
            $_SESSION['id_metier'] = $id_entreprise;
        } else {
            session_destroy();
            header('Location: login.php?error=session');
            exit();
        }
    } catch (PDOException $e) {
        error_log('[StageFlow] mes_offres id lookup : ' . $e->getMessage());
        session_destroy();
        header('Location: login.php?error=session');
        exit();
    }
}

$offres   = [];
$db_error = false;

try {
    $stmt = $conn->prepare(
        "SELECT idOffre, description, niveau, duree, dateDepot, debutStage
         FROM Offre_de_stage
         WHERE idEntreprise = ?
         ORDER BY dateDepot DESC"
    );
    $stmt->execute([$id_entreprise]);
    $offres = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('[StageFlow] mes_offres SELECT : ' . $e->getMessage());
    $db_error = true;
}

$total = count($offres);

// Offre en cours d'édition — vérifiée dans le tableau déjà chargé (pas de 2ème requête)
$edit_offre = null;
$edit_id    = (int)($_GET['edit'] ?? 0);
if ($edit_id > 0) {
    foreach ($offres as $o) {
        if ((int)$o['idOffre'] === $edit_id) { $edit_offre = $o; break; }
    }
}

// ── Feedback via session (cohérence avec le reste du projet) ──────────────
$msg = null;

$messages = [
    'ajoute'   => ['type' => 'success', 'text' => 'Offre publiée avec succès.'],
    'supprime' => ['type' => 'success', 'text' => 'Offre supprimée.'],
    'modifie'  => ['type' => 'success', 'text' => 'Offre modifiée avec succès.'],
    'empty'    => ['type' => 'error',   'text' => 'La description et la date de début sont obligatoires.'],
    'date'     => ['type' => 'error',   'text' => 'Format de date invalide.'],
    'duree'    => ['type' => 'error',   'text' => 'Format de durée invalide (ex : 4:00 pour 4h).'],
    'niveau'   => ['type' => 'error',   'text' => 'Niveau sélectionné invalide.'],
    'forbidden'=> ['type' => 'error',   'text' => 'Action non autorisée sur cette offre.'],
    'server'   => ['type' => 'error',   'text' => 'Erreur serveur. Veuillez réessayer.'],
    'session'  => ['type' => 'error',   'text' => 'Session expirée. Reconnectez-vous.'],
];

if (isset($_SESSION['offre_feedback'])) {
    $key = $_SESSION['offre_feedback'];
    $msg = $messages[$key] ?? null;
    unset($_SESSION['offre_feedback']);
}

$niveau_colors = [
    'Bac+2' => ['bg' => '#F4F4F5', 'color' => '#71717A', 'border' => '#E4E4E7'],
    'Bac+3' => ['bg' => '#EEF2FF', 'color' => '#4F46E5', 'border' => '#C7D2FE'],
    'Bac+4' => ['bg' => '#F5F3FF', 'color' => '#7C3AED', 'border' => '#DDD6FE'],
    'Bac+5' => ['bg' => '#ECFDF5', 'color' => '#059669', 'border' => '#A7F3D0'],
];
$niveau_default = ['bg' => '#F4F4F5', 'color' => '#71717A', 'border' => '#E4E4E7'];
?>
<!DOCTYPE html>
<html lang="fr">

<?php
    $page_title = 'Mes offres';
    include('../components/header.php');
?>

<body>

<div style="display:flex;min-height:100vh;">

    <?php include('../components/sidebar.php'); ?>

    <div style="flex:1;display:flex;flex-direction:column;min-width:0;">

        <!-- Topbar -->
        <header class="topbar" style="padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;">
            <div>
                <h1 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#18181B;margin:0;">Mes offres de stage</h1>
                <p style="font-size:12px;color:#A1A1AA;margin:0;">Gérez vos offres publiées sur la plateforme</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="role-badge">Entreprise</span>
                <div class="avatar" aria-hidden="true"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
            </div>
        </header>

        <main style="flex:1;padding:28px;overflow-y:auto;max-width:1100px;width:100%;margin:0 auto;">

            <!-- Feedback -->
            <?php if ($msg): ?>
            <div role="alert" class="animate-in delay-1"
                 style="background:<?= $msg['type'] === 'success' ? '#ECFDF5' : '#FEF2F2' ?>;border:1px solid <?= $msg['type'] === 'success' ? '#A7F3D0' : '#FECACA' ?>;border-radius:12px;padding:13px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24"
                     stroke="<?= $msg['type'] === 'success' ? '#059669' : '#EF4444' ?>" stroke-width="2" aria-hidden="true">
                    <?php if ($msg['type'] === 'success'): ?>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    <?php else: ?>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <?php endif; ?>
                </svg>
                <span style="font-size:13px;font-weight:500;color:<?= $msg['type'] === 'success' ? '#065F46' : '#991B1B' ?>;">
                    <?= htmlspecialchars($msg['text']) ?>
                </span>
            </div>
            <?php endif; ?>

            <?php if ($db_error): ?>
            <div role="alert" style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:13px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <span style="font-size:13px;color:#DC2626;">Impossible de charger vos offres. Vérifiez la connexion à la base de données.</span>
            </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start;">

                <!-- ═══ COLONNE GAUCHE : Formulaire ═══ -->
                <div class="card animate-in delay-1" style="position:sticky;top:80px;">

                    <div style="padding:18px 22px;border-bottom:1px solid #F4F4F5;display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;background:<?= $edit_offre ? '#FFFBEB' : '#EEF2FF' ?>;border-radius:9px;display:flex;align-items:center;justify-content:center;" aria-hidden="true">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
                                 stroke="<?= $edit_offre ? '#D97706' : '#4F46E5' ?>" stroke-width="1.8">
                                <?php if ($edit_offre): ?>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                <?php else: ?>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                <?php endif; ?>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#18181B;margin:0;">
                                <?= $edit_offre ? 'Modifier l\'offre #' . (int)$edit_offre['idOffre'] : 'Publier une offre' ?>
                            </h3>
                            <p style="font-size:11.5px;color:#A1A1AA;margin:2px 0 0;">
                                <?= $edit_offre ? 'Mettez à jour les informations' : 'Créer une nouvelle offre' ?>
                            </p>
                        </div>
                    </div>

                    <form action="../includes/offre_process.php" method="POST"
                          style="padding:22px;display:flex;flex-direction:column;gap:16px;">
                        <input type="hidden" name="action" value="<?= $edit_offre ? 'modifier' : 'ajouter' ?>">
                        <?php if ($edit_offre): ?>
                        <input type="hidden" name="idOffre" value="<?= (int)$edit_offre['idOffre'] ?>">
                        <?php endif; ?>

                        <div>
                            <label for="description" style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;">
                                DESCRIPTION <span style="color:#EF4444;">*</span>
                            </label>
                            <textarea id="description" name="description" rows="5" required
                                      placeholder="Décrivez le poste, les missions, les technologies…"
                                      class="input-field"
                                      style="resize:none;"><?= $edit_offre ? htmlspecialchars($edit_offre['description']) : '' ?></textarea>
                        </div>

                        <div>
                            <label for="niveau" style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;">NIVEAU REQUIS</label>
                            <select id="niveau" name="niveau" class="input-field" style="cursor:pointer;">
                                <?php foreach (['', 'Bac+2', 'Bac+3', 'Bac+4', 'Bac+5'] as $niv):
                                    $sel = ($edit_offre && $edit_offre['niveau'] === $niv) ? 'selected' : '';
                                ?>
                                <option value="<?= $niv ?>" <?= $sel ?>>
                                    <?= $niv === '' ? 'Tous niveaux' : $niv ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="duree" style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;">
                                DURÉE <span style="font-weight:400;color:#A1A1AA;">(ex&nbsp;: 4:00 pour 4h)</span>
                            </label>
                            <input type="text" id="duree" name="duree"
                                   value="<?= $edit_offre ? htmlspecialchars($edit_offre['duree'] ?? '') : '' ?>"
                                   placeholder="4:00"
                                   class="input-field">
                        </div>

                        <div>
                            <label for="debutStage" style="display:block;font-size:11px;font-weight:700;color:#A1A1AA;letter-spacing:0.06em;margin-bottom:6px;">
                                DÉBUT DU STAGE <span style="color:#EF4444;">*</span>
                            </label>
                            <input type="date" id="debutStage" name="debutStage" required
                                   value="<?= $edit_offre ? htmlspecialchars($edit_offre['debutStage'] ?? '') : '' ?>"
                                   class="input-field">
                        </div>

                        <div style="display:flex;gap:8px;margin-top:4px;">
                            <button type="submit" class="btn-primary" style="flex:1;justify-content:center;padding:10px;">
                                <?= $edit_offre ? 'Enregistrer' : 'Publier l\'offre' ?>
                            </button>
                            <?php if ($edit_offre): ?>
                            <a href="mes_offres.php" class="btn-secondary" style="justify-content:center;padding:10px;">
                                Annuler
                            </a>
                            <?php endif; ?>
                        </div>

                    </form>
                </div>

                <!-- ═══ COLONNE DROITE : Liste des offres ═══ -->
                <div class="animate-in delay-2">

                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <p style="font-size:13px;color:#71717A;margin:0;">
                            <strong style="color:#18181B;font-family:'Syne',sans-serif;"><?= $total ?></strong>
                            offre<?= $total > 1 ? 's' : '' ?> publiée<?= $total > 1 ? 's' : '' ?>
                        </p>
                    </div>

                    <?php if (empty($offres) && !$db_error): ?>
                    <div class="card" style="padding:60px 24px;text-align:center;">
                        <div style="width:48px;height:48px;background:#F4F4F5;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;" aria-hidden="true">
                            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p style="font-size:14px;color:#A1A1AA;margin:0 0 4px;">Vous n'avez pas encore publié d'offre.</p>
                        <p style="font-size:12.5px;color:#D4D4D8;">Utilisez le formulaire à gauche pour en créer une.</p>
                    </div>
                    <?php endif; ?>

                    <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach ($offres as $o):
                        $nb         = $niveau_colors[$o['niveau']] ?? $niveau_default;
                        $depot      = $o['dateDepot']  ? date('d/m/Y', strtotime($o['dateDepot']))  : '—';
                        $debut      = $o['debutStage'] ? date('d/m/Y', strtotime($o['debutStage'])) : '—';
                        $is_editing = $edit_offre && (int)$edit_offre['idOffre'] === (int)$o['idOffre'];
                    ?>
                    <article class="card" style="padding:20px;<?= $is_editing ? 'border-color:#818CF8;box-shadow:0 0 0 3px rgba(129,140,248,0.15);' : '' ?>">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">

                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap;">
                                    <?php if ($o['niveau']): ?>
                                    <span style="font-size:11.5px;font-weight:600;padding:3px 9px;border-radius:20px;background:<?= $nb['bg'] ?>;color:<?= $nb['color'] ?>;border:1px solid <?= $nb['border'] ?>;">
                                        <?= htmlspecialchars($o['niveau']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($is_editing): ?>
                                    <span style="font-size:11px;font-weight:600;color:#818CF8;background:#EEF2FF;border:1px solid #C7D2FE;padding:3px 9px;border-radius:20px;">
                                        En cours d'édition
                                    </span>
                                    <?php endif; ?>
                                    <span style="font-size:11.5px;color:#A1A1AA;margin-left:auto;">Offre #<?= (int)$o['idOffre'] ?></span>
                                </div>

                                <p style="font-size:13.5px;color:#3F3F46;line-height:1.55;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    <?= htmlspecialchars($o['description']) ?>
                                </p>

                                <div style="display:flex;flex-wrap:wrap;gap:14px;">
                                    <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717A;">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Début : <?= $debut ?>
                                    </span>
                                    <?php if ($o['duree']): ?>
                                    <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717A;">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#A1A1AA" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <?= htmlspecialchars($o['duree']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <span style="font-size:12px;color:#A1A1AA;">Déposé le <?= $depot ?></span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div style="display:flex;flex-direction:column;gap:7px;flex-shrink:0;">
                                <a href="mes_offres.php?edit=<?= (int)$o['idOffre'] ?>"
                                   class="btn-secondary"
                                   style="font-size:12px;padding:6px 14px;justify-content:center;">
                                    Modifier
                                </a>
                                <form action="../includes/offre_process.php" method="POST"
                                      onsubmit="return confirm('Supprimer cette offre ? Action irréversible.')">
                                    <input type="hidden" name="action"  value="supprimer">
                                    <input type="hidden" name="idOffre" value="<?= (int)$o['idOffre'] ?>">
                                    <button type="submit" class="btn-delete">
                                        Supprimer
                                    </button>
                                </form>
                            </div>

                        </div>
                    </article>
                    <?php endforeach; ?>
                    </div>

                </div>

            </div>

        </main>

    </div>

</div>

<?php include('../components/footer.php'); ?>

<style>
.btn-delete {
    width: 100%;
    font-size: 12px;
    font-weight: 500;
    padding: 6px 14px;
    border-radius: 8px;
    border: 1.5px solid #FECACA;
    color: #DC2626;
    background: white;
    cursor: pointer;
    transition: background 0.15s;
}
.btn-delete:hover { background: #FEF2F2; }
</style>

</body>
</html>