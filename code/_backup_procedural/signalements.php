<?php
require 'config/config.php';
require 'includes/functions.php';
requireModerateur($pdo);

$stmt = $pdo->query(
    "SELECT sg.id, sg.motif, sg.description, sg.statut, sg.date_signalement,
            u.prenom AS cli_prenom, u.nom AS cli_nom, u.email AS cli_email,
            s.titre AS service_titre,
            ur.prenom AS rep_prenom, ur.nom AS rep_nom
     FROM signalement sg
     INNER JOIN reservation r ON r.id = sg.reservation_id
     INNER JOIN service s ON s.id = r.service_id
     INNER JOIN utilisateur u ON u.id = sg.client_id
     INNER JOIN reparateur rep ON rep.id_utilisateur = s.reparateur_id
     INNER JOIN utilisateur ur ON ur.id = rep.id_utilisateur
     ORDER BY FIELD(sg.statut, 'ouvert', 'en_cours', 'resolu', 'rejete'),
              sg.date_signalement DESC"
);
$signalements = $stmt->fetchAll();

$pageTitle = "Signalements";
require 'includes/header.php';
?>

<h1>Signalements</h1>

<?php if (empty($signalements)): ?>
    <p>Aucun signalement pour le moment.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Statut</th>
                <th>Client</th>
                <th>Service</th>
                <th>Réparateur</th>
                <th>Motif</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($signalements as $sg): ?>
            <tr>
                <td><?= e(ucfirst(str_replace('_', ' ', $sg['statut']))) ?></td>
                <td><?= e($sg['cli_prenom'] . ' ' . $sg['cli_nom']) ?></td>
                <td><?= e($sg['service_titre']) ?></td>
                <td><?= e($sg['rep_prenom'] . ' ' . $sg['rep_nom']) ?></td>
                <td><?= e($sg['motif']) ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($sg['date_signalement']))) ?></td>
                <td><a href="signalement_detail.php?id=<?= (int)$sg['id'] ?>">Traiter</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>