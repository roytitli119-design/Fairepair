<h1>Modération des avis</h1>

<h2>Avis en attente (<?= count($avisAModerer) ?>)</h2>

<?php if (empty($avisAModerer)): ?>
    <p class="muted">Aucun avis en attente de modération.</p>
<?php else: ?>
    <?php foreach ($avisAModerer as $a): ?>
        <div class="card">
            <p><strong>⭐ <?= (int)$a['note'] ?>/5</strong> — <?= e($a['cli_prenom'] . ' ' . $a['cli_nom']) ?> sur <em><?= e($a['service_titre']) ?></em>
                <span class="muted">(<?= e(date('d/m/Y', strtotime($a['date_avis']))) ?>)</span></p>
            <p><?= nl2br(e($a['commentaire'])) ?></p>
            <div class="actions">
                <form method="post">
                    <input type="hidden" name="avis_id" value="<?= (int)$a['id'] ?>">
                    <input type="hidden" name="action" value="valider">
                    <button type="submit" class="btn btn-small">✅ Valider</button>
                </form>
                <form method="post" onsubmit="return confirm('Supprimer définitivement cet avis ?');">
                    <input type="hidden" name="avis_id" value="<?= (int)$a['id'] ?>">
                    <input type="hidden" name="action" value="supprimer">
                    <button type="submit" class="btn btn-small btn-danger">🗑 Supprimer</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($avisModeres)): ?>
    <h2>Avis déjà modérés (<?= count($avisModeres) ?>)</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Note</th>
                <th>Commentaire</th>
                <th>Service</th>
                <th>Validé par</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($avisModeres as $a): ?>
            <tr>
                <td>⭐ <?= (int)$a['note'] ?>/5</td>
                <td><?= e(mb_strimwidth($a['commentaire'] ?? '', 0, 60, '…')) ?></td>
                <td><?= e($a['service_titre']) ?></td>
                <td><?= e($a['mod_prenom'] . ' ' . $a['mod_nom']) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Supprimer définitivement cet avis ?');">
                        <input type="hidden" name="avis_id" value="<?= (int)$a['id'] ?>">
                        <input type="hidden" name="action" value="supprimer">
                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>