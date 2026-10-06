<h1>Réserver : <?= e($service['titre']) ?></h1>
<p><strong><?= number_format($service['tarif'], 2) ?> €</strong></p>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="form">
    <input type="hidden" name="service_id" value="<?= (int)$serviceId ?>">
    <label>Adresse d'intervention
        <input type="text" name="adresse_intervention" value="<?= e($_POST['adresse_intervention'] ?? '') ?>" required>
    </label>
    <label>Date et heure souhaitées
        <input type="datetime-local" name="date_intervention" value="<?= e($_POST['date_intervention'] ?? '') ?>" required>
    </label>
    <button type="submit">Confirmer la réservation</button>
</form>