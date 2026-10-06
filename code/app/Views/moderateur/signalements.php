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
                <td><a href="<?= e(url('signalementDetail', ['id' => $sg['id']])) ?>">Traiter</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>