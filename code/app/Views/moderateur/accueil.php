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
        <a href="<?= e(url('signalements')) ?>" class="btn btn-small">Tout voir</a>
    </div>
    <div class="card">
        <h2>⭐ Avis à modérer</h2>
        <p><strong><?= $nbAvisAModerer ?></strong> en attente</p>
        <a href="<?= e(url('avisModeration')) ?>" class="btn btn-small">Modérer</a>
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
                <td><a href="<?= e(url('signalementDetail', ['id' => $sg['id']])) ?>">Traiter</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="muted">Aucun signalement pour le moment.</p>
<?php endif; ?>