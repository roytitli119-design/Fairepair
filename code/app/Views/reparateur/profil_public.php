<a href="<?= e(url('accueil')) ?>">&larr; Retour aux services</a>

<div class="card">
    <h1><?= e($reparateur['nom_entreprise']) ?></h1>
    <p class="muted">
        Par <?= e($reparateur['prenom'] . ' ' . $reparateur['nom']) ?>
        <?php if (!empty($reparateur['ville'])): ?>— <?= e($reparateur['ville']) ?><?php endif; ?>
    </p>
    <?php if (!empty($reparateur['description_pro'])): ?>
        <p><?= nl2br(e($reparateur['description_pro'])) ?></p>
    <?php endif; ?>
    <?php if ($reparateur['latitude'] !== null && $reparateur['longitude'] !== null): ?>
        <p class="muted">
            📍 Position sur la carte :
            <?= e(str_replace('.', ',', (string)$reparateur['latitude'])) ?>,
            <?= e(str_replace('.', ',', (string)$reparateur['longitude'])) ?>
        </p>
    <?php endif; ?>
</div>

<h2>Ses services (<?= count($services) ?>)</h2>
<?php if (empty($services)): ?>
    <p class="muted">Ce réparateur n'a pas encore publié de service.</p>
<?php else: ?>
    <div class="grid">
        <?php foreach ($services as $s): ?>
            <div class="card">
                <?php if (!empty($s['image'])): ?>
                    <img src="<?= e($s['image']) ?>" alt="<?= e($s['titre']) ?>" class="service-img">
                <?php endif; ?>
                <h3><?= e($s['titre']) ?></h3>
                <p><?= e($s['description']) ?></p>
                <p><strong><?= number_format($s['tarif'], 2) ?> €</strong> — rayon d'action <?= (int)$s['rayon'] ?> km</p>
                <a href="<?= e(url('service', ['id' => $s['id']])) ?>" class="btn">Voir le service</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<h2>Avis clients (<?= count($avis) ?>)</h2>
<?php if (empty($avis)): ?>
    <p class="muted">Aucun avis validé pour l'instant.</p>
<?php else: ?>
    <?php foreach ($avis as $a): ?>
        <div class="card avis-item">
            <p>
                <strong><?= str_repeat('⭐', (int)$a['note']) ?></strong>
                <span class="muted">par <?= e($a['cli_prenom']) ?> — <?= e((new DateTime($a['date_avis']))->format('d/m/Y')) ?></span>
            </p>
            <?php if (!empty($a['commentaire'])): ?>
                <p><?= nl2br(e($a['commentaire'])) ?></p>
            <?php endif; ?>
            <p class="muted">Intervention : <?= e($a['service_titre']) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>