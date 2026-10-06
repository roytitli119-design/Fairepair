<h1>Mes services</h1>
<p><a href="<?= e(url('ajouterService')) ?>" class="btn">+ Ajouter un service</a></p>

<?php if (empty($services)): ?>
    <div class="notice">Vous n'avez pas encore de service. <a href="<?= e(url('ajouterService')) ?>">Proposez votre premier service</a>.</div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($services as $service): ?>
            <div class="card">
                <?php if (!empty($service['image'])): ?>
                    <img src="<?= e($service['image']) ?>" alt="<?= e($service['titre']) ?>" class="service-img">
                <?php endif; ?>
                <h2><?= e($service['titre']) ?></h2>
                <p><?= e($service['description']) ?></p>
                <p><strong><?= number_format($service['tarif'], 2) ?> €</strong> — rayon <?= (int)$service['rayon'] ?> km</p>
                <p class="muted"><?= (int)$service['nb_reservations'] ?> réservation(s)</p>
                <div class="actions">
                    <a href="<?= e(url('editerService', ['id' => $service['id']])) ?>" class="btn btn-small">Modifier</a>
                    <form method="post" onsubmit="return confirm('Supprimer ce service ? Les réservations liées seront aussi supprimées.');">
                        <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>">
                        <button type="submit" name="action" value="supprimer" class="btn btn-small btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>