<h1>Mon profil</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php $mesRoles = rolesUtilisateur(); ?>
<p>
    <?php foreach ($mesRoles as $r): ?>
        <span class="badge"><?= $r === 'moderateur' ? 'Modérateur' : ucfirst($r) ?></span>
    <?php endforeach; ?>
</p>

<div class="card">
    <h2>Mes informations</h2>
    <p class="muted">Les champs adresse, code postal, ville et téléphone peuvent être complétés maintenant ou plus tard.</p>
    <form method="post" class="form">
        <input type="hidden" name="action" value="profil">
        <label>Prénom
            <input type="text" name="prenom" value="<?= e($user['prenom']) ?>" required>
        </label>
        <label>Nom
            <input type="text" name="nom" value="<?= e($user['nom']) ?>" required>
        </label>
        <label>Adresse
            <input type="text" name="adresse" value="<?= e($user['adresse'] ?? '') ?>">
        </label>
        <label>Code postal
            <input type="text" name="code_postal" value="<?= e($user['code_postal'] ?? '') ?>">
        </label>
        <label>Ville
            <input type="text" name="ville" value="<?= e($user['ville'] ?? '') ?>">
        </label>
        <label>Email (non modifiable)
            <input type="email" value="<?= e($user['email']) ?>" disabled>
        </label>
        <label>Téléphone
            <input type="tel" name="telephone" value="<?= e($user['telephone'] ?? '') ?>">
        </label>
        <button type="submit">Enregistrer</button>
    </form>
</div>

<div class="card">
    <h2>Changer mon mot secret</h2>
    <p class="muted">
        Pour changer votre mot secret, indiquez d'abord l'<strong>ancien</strong>.
        Il sert à sécuriser la récupération de votre mot de passe si vous l'oubliez.<br>
        🔒 Sécurité : après <strong>3 tentatives</strong> incorrectes, le changement
        est bloqué pendant 15 minutes.
    </p>
    <form method="post" class="form">
        <input type="hidden" name="action" value="mot_secret">
        <label>Mot secret actuel
            <input type="password" name="mot_secret_actuel" required autocomplete="off">
        </label>
        <label>Nouveau mot secret (4 caractères minimum)
            <input type="password" name="mot_secret" minlength="4" value="<?= e(isset($_POST['mot_secret']) ? $_POST['mot_secret'] : '') ?>" required>
        </label>
        <label>Confirmation du nouveau mot secret
            <input type="password" name="mot_secret_confirmation" minlength="4" required>
        </label>
        <button type="submit">Changer mon mot secret</button>
    </form>
</div>

<?php if (estReparateur()): ?>
    <div class="card">
        <h2>Mon entreprise</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="infos_reparateur">
            <label>Nom de l'entreprise
                <input type="text" name="nom_entreprise" value="<?= e(isset($_POST['nom_entreprise']) ? $_POST['nom_entreprise'] : ($rep['nom_entreprise'] ?? '')) ?>" required>
            </label>
            <label>SIRET (14 chiffres)
                <input type="text" name="siret" maxlength="14" pattern="[0-9]{14}" value="<?= e(isset($_POST['siret']) ? $_POST['siret'] : ($rep['siret'] ?? '')) ?>" required>
            </label>
            <label>Description de mon activité
                <textarea name="description_pro" rows="4"><?= e(isset($_POST['description_pro']) ? $_POST['description_pro'] : ($rep['description_pro'] ?? '')) ?></textarea>
            </label>
            <label>Latitude (ex. 48.8566 — pour apparaître sur la carte)
                <input type="text" name="latitude" inputmode="decimal" placeholder="ex. 48.8566" value="<?= e(isset($_POST['latitude']) ? $_POST['latitude'] : ($rep['latitude'] ?? '')) ?>">
            </label>
            <label>Longitude (ex. 2.3522)
                <input type="text" name="longitude" inputmode="decimal" placeholder="ex. 2.3522" value="<?= e(isset($_POST['longitude']) ? $_POST['longitude'] : ($rep['longitude'] ?? '')) ?>">
            </label>
            <button type="submit">Enregistrer</button>
        </form>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Devenir réparateur 🔧</h2>
        <p class="muted">
            Vous êtes également réparateur de vélos ? Complétez les informations de votre
            entreprise pour publier vos services et recevoir des demandes de rendez-vous.
        </p>
        <form method="post" class="form">
            <input type="hidden" name="action" value="devenir_reparateur">
            <label>Nom de l'entreprise
                <input type="text" name="nom_entreprise" value="<?= e(isset($_POST['nom_entreprise']) ? $_POST['nom_entreprise'] : '') ?>" required>
            </label>
            <label>SIRET (14 chiffres)
                <input type="text" name="siret" maxlength="14" pattern="[0-9]{14}" value="<?= e(isset($_POST['siret']) ? $_POST['siret'] : '') ?>" required>
            </label>
            <label>Description de mon activité
                <textarea name="description_pro" rows="4" placeholder="Ex : réparateur itinérant, spécialiste des vélos de ville..."><?= e(isset($_POST['description_pro']) ? $_POST['description_pro'] : '') ?></textarea>
            </label>
            <label>Latitude (ex. 48.8566 — pour apparaître sur la carte)
                <input type="text" name="latitude" inputmode="decimal" placeholder="ex. 48.8566" value="<?= e(isset($_POST['latitude']) ? $_POST['latitude'] : '') ?>">
            </label>
            <label>Longitude (ex. 2.3522)
                <input type="text" name="longitude" inputmode="decimal" placeholder="ex. 2.3522" value="<?= e(isset($_POST['longitude']) ? $_POST['longitude'] : '') ?>">
            </label>
            <button type="submit">Devenir réparateur</button>
        </form>
    </div>
<?php endif; ?>