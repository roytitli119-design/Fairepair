<a href="<?= e(url('accueil')) ?>">&larr; Retour aux services</a>

<h1><?= e($service['titre']) ?></h1>
<p><?= nl2br(e($service['description'])) ?></p>
<p><strong><?= number_format($service['tarif'], 2) ?> €</strong> — rayon d'action <?= (int)$service['rayon'] ?> km</p>
<p class="muted">
    Proposé par
    <a href="<?= e(url('reparateurProfil', ['id' => $service['reparateur_id']])) ?>">
        <?= e($service['rep_prenom'] . ' ' . $service['rep_nom']) ?>
    </a>
    (<?= e($service['ville']) ?>)
</p>

<?php if ($avisStats['total'] > 0): ?>
    <p>⭐ <?= number_format($avisStats['moyenne'], 1) ?> / 5 (<?= (int)$avisStats['total'] ?> avis)</p>
<?php else: ?>
    <p class="muted">Aucun avis pour l'instant</p>
<?php endif; ?>

<?php if (isLoggedIn()): ?>
    <a href="<?= e(url('reservationNouvelle', ['service_id' => $service['id']])) ?>" class="btn">Réserver ce service</a>
<?php else: ?>
    <p><a href="<?= e(url('connexion')) ?>">Connectez-vous</a> pour réserver ce service.</p>
<?php endif; ?>

<?php if (!empty($avis)): ?>
    <h2>Avis des clients</h2>
    <?php foreach ($avis as $a): ?>
        <div class="card avis-item">
            <p>
                <strong><?= str_repeat('⭐', (int)$a['note']) ?></strong>
                <span class="muted">par <?= e($a['cli_prenom']) ?> — <?= e((new DateTime($a['date_avis']))->format('d/m/Y')) ?></span>
            </p>
            <?php if (!empty($a['commentaire'])): ?>
                <p><?= nl2br(e($a['commentaire'])) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>