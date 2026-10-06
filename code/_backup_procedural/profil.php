<?php
require 'config/config.php';
require 'includes/functions.php';
requireLogin();

$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id = ?");
$stmt->execute([currentUserId()]);
$user = $stmt->fetch();

// Infos réparateur si le compte en est un (ou le devient)
$stmt = $pdo->prepare("SELECT * FROM reparateur WHERE id_utilisateur = ?");
$stmt->execute([currentUserId()]);
$rep = $stmt->fetch() ?: [];

$errors = [];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'profil') {
        // ---- Mise à jour des informations personnelles ----
        $prenom      = trim($_POST['prenom'] ?? '');
        $nom         = trim($_POST['nom'] ?? '');
        $adresse     = trim($_POST['adresse'] ?? '');
        $codePostal  = trim($_POST['code_postal'] ?? '');
        $ville       = trim($_POST['ville'] ?? '');
        $telephone   = trim($_POST['telephone'] ?? '');

        if ($prenom === '' || $nom === '') {
            $errors[] = "Le prénom et le nom sont obligatoires.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "UPDATE utilisateur
                 SET prenom = ?, nom = ?, adresse = ?, code_postal = ?, ville = ?, telephone = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $prenom,
                $nom,
                $adresse    !== '' ? $adresse    : null,
                $codePostal !== '' ? $codePostal : null,
                $ville      !== '' ? $ville      : null,
                $telephone  !== '' ? $telephone  : null,
                currentUserId(),
            ]);

            $_SESSION['prenom'] = $prenom;
            setFlash('success', 'Profil mis à jour.');
            redirect('profil.php');
        }
    } elseif ($action === 'devenir_reparateur') {
        // ---- Passage client → réparateur (infos entreprise) ----
        $nomEntreprise  = trim($_POST['nom_entreprise'] ?? '');
        $siret          = trim($_POST['siret'] ?? '');
        $descriptionPro = trim($_POST['description_pro'] ?? '');

        if ($nomEntreprise === '') {
            $errors[] = "Le nom de l'entreprise est obligatoire.";
        }
        if ($siret === '' || !preg_match('/^\d{14}$/', $siret)) {
            $errors[] = "Le SIRET doit contenir exactement 14 chiffres.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "INSERT INTO reparateur (id_utilisateur, nom_entreprise, siret, description_pro)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     nom_entreprise   = VALUES(nom_entreprise),
                     siret            = VALUES(siret),
                     description_pro  = VALUES(description_pro)"
            );
            $stmt->execute([currentUserId(), $nomEntreprise, $siret, $descriptionPro]);

            // Actualise le rôle mémorisé en session
            $_SESSION['role'] = currentUserRole($pdo);

            setFlash('success', "Félicitations, vous êtes maintenant réparateur !");
            redirect('profil.php');
        }
    } elseif ($action === 'infos_reparateur') {
        // ---- Modification des informations de l'entreprise ----
        $nomEntreprise  = trim($_POST['nom_entreprise'] ?? '');
        $siret          = trim($_POST['siret'] ?? '');
        $descriptionPro = trim($_POST['description_pro'] ?? '');

        if ($nomEntreprise === '') {
            $errors[] = "Le nom de l'entreprise est obligatoire.";
        }
        if ($siret === '' || !preg_match('/^\d{14}$/', $siret)) {
            $errors[] = "Le SIRET doit contenir exactement 14 chiffres.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "UPDATE reparateur
                 SET nom_entreprise = ?, siret = ?, description_pro = ?
                 WHERE id_utilisateur = ?"
            );
            $stmt->execute([$nomEntreprise, $siret, $descriptionPro, currentUserId()]);
            setFlash('success', "Informations de l'entreprise mises à jour.");
            redirect('profil.php');
        }
    } elseif ($action === 'mot_secret') {
        // ---- Modification du mot secret : il faut d'abord donner l'ANCIEN ----
        $motSecretActuel = $_POST['mot_secret_actuel'] ?? '';
        $motSecret       = $_POST['mot_secret'] ?? '';
        $motSecretConf   = $_POST['mot_secret_confirmation'] ?? '';

        // 1. Vérifier l'ancien mot secret avant toute modification
        $stmt = $pdo->prepare("SELECT mot_secret FROM utilisateur WHERE id = ?");
        $stmt->execute([currentUserId()]);
        $ligne = $stmt->fetch();
        if (!$ligne || $ligne['mot_secret'] === null) {
            $errors[] = "Aucun mot secret n'est défini pour votre compte.";
        } elseif (!password_verify($motSecretActuel, $ligne['mot_secret'])) {
            $errors[] = "L'ancien mot secret est incorrect.";
        }

        // 2. Valider le nouveau mot secret
        if (strlen($motSecret) < 4) {
            $errors[] = "Le nouveau mot secret doit contenir au moins 4 caractères.";
        }
        if ($motSecret !== $motSecretConf) {
            $errors[] = "Les deux nouveaux mots secrets ne correspondent pas.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE utilisateur SET mot_secret = ? WHERE id = ?");
            $stmt->execute([password_hash($motSecret, PASSWORD_DEFAULT), currentUserId()]);
            setFlash('success', 'Mot secret modifié avec succès.');
            redirect('profil.php');
        }
    }
}

$pageTitle = "Mon profil";
require 'includes/header.php';
?>

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

<?php $mesRoles = rolesUtilisateur($pdo); ?>
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
        Il sert à sécuriser la récupération de votre mot de passe si vous l'oubliez.
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

<?php if (estReparateur($pdo)): ?>
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
            <button type="submit">Devenir réparateur</button>
        </form>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>