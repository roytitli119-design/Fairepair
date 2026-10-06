<h1>Réinitialiser mon mot de passe</h1>

<p class="muted">
    Renseignez votre email, votre <strong>mot secret</strong> (défini dans votre profil)
    puis choisissez un nouveau mot de passe.<br>
    🔒 Sécurité : après <strong>3 tentatives</strong> avec un mot secret incorrect,
    la récupération est bloquée pendant 15 minutes.
</p>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($succes): ?>
    <div class="alert alert-success">
        Votre mot de passe a été modifié avec succès.
        <a href="<?= e(url('connexion')) ?>">Vous pouvez maintenant vous connecter.</a>
    </div>
<?php else: ?>
    <form method="post" class="form">
        <label>Email
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
        </label>
        <label>Mot secret
            <input type="password" name="mot_secret" required>
        </label>
        <label>Nouveau mot de passe (8 caractères min., majuscule, minuscule, chiffre, caractère spécial)
            <input type="password" name="mot_de_passe" required>
        </label>
        <label>Confirmation du nouveau mot de passe
            <input type="password" name="confirmation" required>
        </label>
        <button type="submit">Réinitialiser mon mot de passe</button>
    </form>
<?php endif; ?>

<p><a href="<?= e(url('connexion')) ?>">&larr; Retour à la connexion</a></p>