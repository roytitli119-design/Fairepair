<h1>Créer un compte</h1>

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
    <label>Prénom
        <input type="text" name="prenom" value="<?= e($_POST['prenom'] ?? '') ?>" required>
    </label>
    <label>Nom
        <input type="text" name="nom" value="<?= e($_POST['nom'] ?? '') ?>" required>
    </label>
    <label>Email
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
    </label>
    <label>Mot de passe (8 caractères min. : 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial)
        <input type="password" name="mot_de_passe" required>
    </label>
    <label>Confirmation du mot de passe
        <input type="password" name="confirmation" required>
    </label>
    <label class="checkbox">
        <input type="checkbox" name="cgu" value="1" required>
        <span>
            J'accepte les <a href="<?= e(url('cgu')) ?>" target="_blank" rel="noopener">conditions générales
            d'utilisation</a> et la <a href="<?= e(url('cgu')) ?>#rgpd" target="_blank" rel="noopener">politique
            de confidentialité</a> (obligatoire, le consentement est conservé).
        </span>
    </label>
    <button type="submit">Créer mon compte</button>
</form>

<p class="muted">
    💡 Vous pourrez compléter votre adresse depuis votre profil et,
    si vous êtes réparateur, ajouter les informations de votre entreprise.
</p>

<p>Déjà un compte ? <a href="<?= e(url('connexion')) ?>">Se connecter</a></p>