<h1>Mes gains</h1>

<div class="stats">
    <div class="stat-card">
        <div class="valeur"><?= number_format($statsTerminees['ca'], 2) ?> €</div>
        <div class="libelle">Chiffre d'affaires (interventions terminées)</div>
    </div>
    <div class="stat-card">
        <div class="valeur"><?= number_format($statsPaiements['encaisse'], 2) ?> €</div>
        <div class="libelle">Encaissé (paiements validés)</div>
    </div>
    <div class="stat-card">
        <div class="valeur"><?= (int)$statsTerminees['nb_terminees'] ?></div>
        <div class="libelle">Interventions terminées</div>
    </div>
    <div class="stat-card">
        <div class="valeur"><?= $nbAttente ?></div>
        <div class="libelle">Demandes en attente</div>
    </div>
</div>

<h2>Détail des interventions</h2>

<?php if (empty($lignes)): ?>
    <div class="notice">Aucune intervention pour le moment. Dès qu'une réservation est terminée, elle apparaît ici.</div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Intervention</th>
                <th>Client</th>
                <th>Service</th>
                <th>Montant</th>
                <th>Paiement</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $l): ?>
                <tr>
                    <td><?= e((new DateTime($l['date_intervention']))->format('d/m/Y H:i')) ?></td>
                    <td><?= e($l['client_prenom'] . ' ' . $l['client_nom']) ?></td>
                    <td><?= e($l['service_titre']) ?></td>
                    <td><strong><?= number_format($l['tarif'], 2) ?> €</strong></td>
                    <td>
                        <?php if ($l['paiement_statut'] === 'valide'): ?>
                            <span class="badge badge-confirmee">Payé (<?= number_format($l['paiement_montant'], 2) ?> €)</span>
                        <?php elseif ($l['paiement_statut'] === 'en_attente'): ?>
                            <span class="badge badge-pending">En attente</span>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p class="muted">💡 La plateforme traite les paiements par carte via <strong>Stripe</strong> (mode test) : aucune donnée bancaire n'est collectée ni stockée. Le chiffre d'affaires correspond aux interventions terminées.</p>