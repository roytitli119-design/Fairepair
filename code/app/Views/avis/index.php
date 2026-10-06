<h1>Laisser un avis — <?= e($reservation['titre']) ?></h1>

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
    <label>Note (1 à 5)
        <select name="note" required>
            <option value="5">5 - Excellent</option>
            <option value="4">4 - Très bien</option>
            <option value="3">3 - Correct</option>
            <option value="2">2 - Décevant</option>
            <option value="1">1 - Mauvais</option>
        </select>
    </label>
    <label>Commentaire
        <textarea name="commentaire" rows="4"><?= e($_POST['commentaire'] ?? '') ?></textarea>
    </label>
    <button type="submit">Envoyer mon avis</button>
</form>