<h1>Modifier le service</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="form" enctype="multipart/form-data">
    <label>Titre du service
        <input type="text" name="titre" value="<?= e($_POST['titre'] ?? $service['titre']) ?>" required maxlength="100">
    </label>
    <label>Description
        <textarea name="description" rows="4" required><?= e($_POST['description'] ?? $service['description']) ?></textarea>
    </label>
    <label>Tarif (€)
        <input type="text" name="tarif" value="<?= e($_POST['tarif'] ?? $service['tarif']) ?>" required placeholder="25.50">
    </label>
    <label>Rayon d'action (km)
        <input type="number" name="rayon" min="1" value="<?= e($_POST['rayon'] ?? $service['rayon']) ?>" required>
    </label>
    <?php if (!empty($service['image'])): ?>
        <p class="muted">Image actuelle : <img src="<?= e($service['image']) ?>" alt="" class="service-img"></p>
    <?php endif; ?>
    <label>Nouvelle photo (facultatif — remplace l'actuelle)
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <button type="submit">Enregistrer les modifications</button>
</form>

<p><a href="<?= e(url('mesServices')) ?>">← Retour à mes services</a></p>