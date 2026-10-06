<h1>Paiement — <?= e($reservation['titre']) ?></h1>
<p>Montant à régler : <strong><?= number_format($reservation['tarif'], 2) ?> €</strong></p>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($stripeDispo): ?>
    <div class="card">
        <h2>Paiement par carte bancaire</h2>
        <p class="muted">
            Vous allez être redirigé vers la page de paiement sécurisée
            <strong>Stripe Checkout</strong>. Aucune donnée bancaire n'est
            transmise ni stockée par Fair'repair.
        </p>
        <form method="post" class="form">
            <input type="hidden" name="reservation_id" value="<?= (int)$reservationId ?>">
            <input type="hidden" name="stripe" value="1">
            <button type="submit">💳 Payer <?= number_format($reservation['tarif'], 2) ?> € par carte</button>
        </form>
        <p class="muted">Mode test : utilisez la carte fictive <code>4242 4242 4242 4242</code> (exp. une date future, CVC quelconque).</p>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Paiement</h2>
        <p class="muted">
            💡 Mode simulation (aucune clé Stripe configurée). Aucune donnée
            de carte n'est collectée.
        </p>
        <form method="post" class="form">
            <input type="hidden" name="reservation_id" value="<?= (int)$reservationId ?>">
            <label>Moyen de paiement
                <select name="moyen_paiement" required>
                    <option value="carte">Carte bancaire</option>
                    <option value="especes">Espèces</option>
                    <option value="virement">Virement</option>
                </select>
            </label>
            <button type="submit">Payer</button>
        </form>
    </div>
<?php endif; ?>