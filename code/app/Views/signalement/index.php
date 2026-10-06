<h1>Signaler un problème — <?= e($reservation['titre']) ?></h1>

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
    <input type="hidden" name="reservation_id" value="<?= (int)$reservationId ?>">
    <label>Motif
        <input type="text" name="motif" placeholder="Ex : arnaque, litige, service non rendu..." value="<?= e($_POST['motif'] ?? '') ?>" required>
    </label>
    <label>Description détaillée
        <textarea name="description" rows="5" required><?= e($_POST['description'] ?? '') ?></textarea>
    </label>
    <button type="submit">Envoyer le signalement</button>
</form>