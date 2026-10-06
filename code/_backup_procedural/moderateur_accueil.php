<?php
require 'config/config.php';
require 'includes/functions.php';
requireModerateur($pdo);

// ---- Compteurs pour le tableau de bord ----
$nbUtilisateurs = (int)$pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
$nbClients      = (int)$pdo->query("SELECT COUNT(*) FROM client")->fetchColumn();
$nbReparateurs  = (int)$pdo->query("SELECT COUNT(*) FROM reparateur")->fetchColumn();
$nbServices     = (int)$pdo->query("SELECT COUNT(*) FROM service")->fetchColumn();
$nbReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservation")->fetchColumn();

$nbSignalementsOuverts = (int)$pdo->query(
    "SELECT COUNT(*) FROM signalement WHERE statut IN ('ouvert', 'en_cours')"
)->fetchColumn();

$nbAvisAModerer = (int)$pdo->query(
    "SELECT COUNT(*) FROM avis WHERE moderateur_id IS NULL"
)->fetchColumn();

// ---- Derniers signalements à traiter (aperçu) ----
$stmt = $pdo->query(
    "SELECT sg.id, sg.motif, sg.statut, sg.date_signalement,
            u.prenom AS cli_prenom, u.nom AS cli_nom,
            s.titre AS service_titre
     FROM signalement sg
     INNER JOIN reservation r ON r.id = sg.reservation_id
     INNER JOIN service s ON s.id = r.service_id
     INNER JOIN utilisateur u ON u.id = sg.client_id
     ORDER BY FIELD(sg.statut, 'ouvert', 'en_cours', 'resolu', 'rejete'),
              sg.date_signalement DESC
     LIMIT 5"
);
$derniersSignalements = $stmt->fetchAll();

$pageTitle = "Tableau de bord modérateur";
require 'includes/header.php';
?>

<h1>Tableau de bord modérateur</h1>

<div class="grid">
    <div class="card">
        <h2>👥 Utilisateurs</h2>
        <p><strong><?= $nbUtilisateurs ?></strong> au total
            (<?= $nbClients ?> client<?= $nbClients > 1 ? 's' : '' ?>,
            <?= $nbReparateurs ?> réparateur<?= $nbReparateurs > 1 ? 's' : '' ?>)</p>
    </div>
    <div class="card">
        <h2>🔧 Services</h2>
        <p><strong><?= $nbServices ?></strong> publié<?= $nbServices > 1 ? 's' : '' ?></p>
    </div>
    <div class="card">
        <h2>📅 Réservations</h2>
        <p><strong><?= $nbReservations ?></strong> au total</p>
    </div>
    <div class="card">
        <h2>🚩 Signalements ouverts</h2>
        <p><strong><?= $nbSignalementsOuverts ?></strong> à traiter</p>
        <a href="signalements.php" class="btn btn-small">Tout voir</a>
    </div>
    <div class="card">
        <h2>⭐ Avis à modérer</h2>
        <p><strong><?= $nbAvisAModerer ?></strong> en attente</p>
        <a href="avis_moderation.php" class="btn btn-small">Modérer</a>
    </div>
</div>

<?php if (!empty($derniersSignalements)): ?>
    <h2>Derniers signalements</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Service</th>
                <th>Motif</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($derniersSignalements as $sg): ?>
            <tr>
                <td><?= e($sg['cli_prenom'] . ' ' . $sg['cli_nom']) ?></td>
                <td><?= e($sg['service_titre']) ?></td>
                <td><?= e($sg['motif']) ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($sg['date_signalement']))) ?></td>
                <td><?= e(ucfirst(str_replace('_', ' ', $sg['statut']))) ?></td>
                <td><a href="signalement_detail.php?id=<?= (int)$sg['id'] ?>">Traiter</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="muted">Aucun signalement pour le moment.</p>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>