<h1>Ajouter un service</h1>

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
        <input type="text" name="titre" value="<?= e($_POST['titre'] ?? '') ?>" required maxlength="100">
    </label>
    <label>Description (type de réparation, matériel, etc.)
        <textarea name="description" rows="4" required><?= e($_POST['description'] ?? '') ?></textarea>
    </label>
    <label>Tarif (€)
        <input type="text" name="tarif" value="<?= e($_POST['tarif'] ?? '') ?>" required placeholder="25.50">
    </label>
    <label>Rayon d'action (km)
        <input type="number" name="rayon" min="1" value="<?= e($_POST['rayon'] ?? '') ?>" required>
    </label>
    <label>Photo (JPG, PNG ou WebP, 2 Mo max — facultatif)
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <button type="submit">Publier le service</button>
</form>

<p><a href="<?= e(url('reparateurAccueil')) ?>">← Retour à mon espace réparateur</a></p>