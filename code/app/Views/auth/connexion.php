<h1>Se connecter</h1>

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
    <label>Email
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
    </label>
    <label>Mot de passe
        <input type="password" name="mot_de_passe" required>
    </label>
    <label class="checkbox">
        <input type="checkbox" name="se_souvenir" value="1">
        <span>Se souvenir de moi sur cet appareil (30 jours)</span>
    </label>
    <button type="submit">Se connecter</button>
</form>

<p>Pas encore de compte ? <a href="<?= e(url('inscription')) ?>">Créer un compte</a></p>
<p><a href="<?= e(url('motDePasseOublie')) ?>">Mot de passe oublié ?</a></p>