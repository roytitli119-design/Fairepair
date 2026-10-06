<?php
require 'config/config.php';
require 'includes/functions.php';
requireModerateur($pdo);

// ---- Actions POST : valider ou supprimer un avis ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $avisId = (int)($_POST['avis_id'] ?? 0);

    if ($action === 'valider') {
        // Marque l'avis comme modéré (moderateur_id = modérateur connecté)
        $stmt = $pdo->prepare(
            "UPDATE avis SET moderateur_id = ?
             WHERE id = ? AND moderateur_id IS NULL"
        );
        $stmt->execute([currentUserId(), $avisId]);
        setFlash('success', $stmt->rowCount() > 0
            ? 'Avis validé.'
            : "Impossible de valider cet avis (déjà modéré ou introuvable).");
    } elseif ($action === 'supprimer') {
        // Un avis inapproprié est retiré du site
        $stmt = $pdo->prepare("DELETE FROM avis WHERE id = ?");
        $stmt->execute([$avisId]);
        setFlash('success', 'Avis supprimé.');
    }

    redirect('avis_moderation.php');
}

// ---- Avis en attente de modération (moderateur_id NULL) ----
$stmt = $pdo->query(
    "SELECT a.id, a.note, a.commentaire, a.date_avis,
            u.prenom AS cli_prenom, u.nom AS cli_nom,
            s.titre AS service_titre
     FROM avis a
     INNER JOIN reservation r ON r.id = a.reservation_id
     INNER JOIN service s ON s.id = r.service_id
     INNER JOIN utilisateur u ON u.id = r.client_id
     WHERE a.moderateur_id IS NULL
     ORDER BY a.date_avis ASC"
);
$avisAModerer = $stmt->fetchAll();

// ---- Avis déjà modérés ----
$stmt = $pdo->query(
    "SELECT a.id, a.note, a.commentaire, a.date_avis,
            u.prenom AS cli_prenom, u.nom AS cli_nom,
            s.titre AS service_titre,
            um.prenom AS mod_prenom, um.nom AS mod_nom
     FROM avis a
     INNER JOIN reservation r ON r.id = a.reservation_id
     INNER JOIN service s ON s.id = r.service_id
     INNER JOIN utilisateur u ON u.id = r.client_id
     INNER JOIN moderateur m ON m.id_utilisateur = a.moderateur_id
     INNER JOIN utilisateur um ON um.id = m.id_utilisateur
     ORDER BY a.date_avis DESC"
);
$avisModeres = $stmt->fetchAll();

$pageTitle = "Modération des avis";
require 'includes/header.php';
?>

<h1>Modération des avis</h1>

<h2>Avis en attente (<?= count($avisAModerer) ?>)</h2>

<?php if (empty($avisAModerer)): ?>
    <p class="muted">Aucun avis en attente de modération.</p>
<?php else: ?>
    <?php foreach ($avisAModerer as $a): ?>
        <div class="card">
            <p><strong>⭐ <?= (int)$a['note'] ?>/5</strong> — <?= e($a['cli_prenom'] . ' ' . $a['cli_nom']) ?> sur <em><?= e($a['service_titre']) ?></em>
                <span class="muted">(<?= e(date('d/m/Y', strtotime($a['date_avis']))) ?>)</span></p>
            <p><?= nl2br(e($a['commentaire'])) ?></p>
            <div class="actions">
                <form method="post">
                    <input type="hidden" name="avis_id" value="<?= (int)$a['id'] ?>">
                    <input type="hidden" name="action" value="valider">
                    <button type="submit" class="btn btn-small">✅ Valider</button>
                </form>
                <form method="post" onsubmit="return confirm('Supprimer définitivement cet avis ?');">
                    <input type="hidden" name="avis_id" value="<?= (int)$a['id'] ?>">
                    <input type="hidden" name="action" value="supprimer">
                    <button type="submit" class="btn btn-small btn-danger">🗑 Supprimer</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($avisModeres)): ?>
    <h2>Avis déjà modérés (<?= count($avisModeres) ?>)</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Note</th>
                <th>Commentaire</th>
                <th>Service</th>
                <th>Validé par</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($avisModeres as $a): ?>
            <tr>
                <td>⭐ <?= (int)$a['note'] ?>/5</td>
                <td><?= e(mb_strimwidth($a['commentaire'] ?? '', 0, 60, '…')) ?></td>
                <td><?= e($a['service_titre']) ?></td>
                <td><?= e($a['mod_prenom'] . ' ' . $a['mod_nom']) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Supprimer définitivement cet avis ?');">
                        <input type="hidden" name="avis_id" value="<?= (int)$a['id'] ?>">
                        <input type="hidden" name="action" value="supprimer">
                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>