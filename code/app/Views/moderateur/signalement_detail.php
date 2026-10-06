<h1>Signalement #<?= (int)$signalement['id'] ?></h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <h2>🚩 Motif : <?= e($signalement['motif']) ?></h2>
    <p><?= nl2br(e($signalement['description'])) ?></p>
    <p class="muted">
        Signalé le <?= e(date('d/m/Y H:i', strtotime($signalement['date_signalement']))) ?>
        — statut actuel : <strong><?= e(ucfirst(str_replace('_', ' ', $signalement['statut']))) ?></strong>
    </p>
</div>

<h2>Réservation concernée</h2>
<table class="table">
    <tbody>
        <tr><th>Client</th><td><?= e($signalement['cli_prenom'] . ' ' . $signalement['cli_nom']) ?> — <?= e($signalement['cli_email']) ?></td></tr>
        <tr><th>Service</th><td><?= e($signalement['service_titre']) ?></td></tr>
        <tr><th>Réparateur</th><td><?= e($signalement['rep_prenom'] . ' ' . $signalement['rep_nom']) ?></td></tr>
        <tr><th>Adresse d'intervention</th><td><?= e($signalement['adresse_intervention']) ?></td></tr>
        <tr><th>Date d'intervention</th><td><?= e(date('d/m/Y H:i', strtotime($signalement['date_intervention']))) ?></td></tr>
        <tr><th>Statut de la réservation</th><td><?= e(ucfirst(str_replace('_', ' ', $signalement['reservation_statut']))) ?></td></tr>
    </tbody>
</table>

<h2>Prendre en charge</h2>
<form method="post" class="form">
    <input type="hidden" name="id" value="<?= (int)$signalement['id'] ?>">
    <label>Nouveau statut
        <select name="statut" required>
            <?php foreach ($statutsValides as $s): ?>
                <option value="<?= e($s) ?>" <?= $signalement['statut'] === $s ? 'selected' : '' ?>>
                    <?= e(ucfirst(str_replace('_', ' ', $s))) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Enregistrer</button>
</form>

<p><a href="<?= e(url('signalements')) ?>">&larr; Retour à la liste des signalements</a></p>