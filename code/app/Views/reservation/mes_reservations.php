<h1>Mes réservations</h1>

<?php if (empty($reservations)): ?>
    <p>Vous n'avez pas encore de réservation. <a href="<?= e(url('accueil')) ?>">Voir les services</a></p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Paiement</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($reservations as $r): ?>
            <tr>
                <td><?= e($r['titre']) ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($r['date_intervention']))) ?></td>
                <td><?= e(ucfirst(str_replace('_', ' ', $r['statut']))) ?></td>
                <td>
                    <?php if ($r['paiement_id']): ?>
                        <?= e(ucfirst($r['paiement_statut'])) ?>
                    <?php elseif ($r['statut'] !== 'annulee'): ?>
                        <a href="<?= e(url('paiement', ['reservation_id' => $r['id']])) ?>">Payer</a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <?php if ($r['statut'] === 'en_attente'): ?>
                        <a href="<?= e(url('reservationAnnuler', ['id' => $r['id']])) ?>" onclick="return confirm('Annuler cette réservation ?');">Annuler</a>
                    <?php endif; ?>
                    <?php if ($r['statut'] === 'terminee'): ?>
                        <a href="<?= e(url('signalement', ['reservation_id' => $r['id']])) ?>">Signaler</a>
                        <?php if (!$r['avis_id']): ?>
                            <a href="<?= e(url('avis', ['reservation_id' => $r['id']])) ?>">Laisser un avis</a>
                        <?php else: ?>
                            <span class="muted">Avis déjà laissé</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>