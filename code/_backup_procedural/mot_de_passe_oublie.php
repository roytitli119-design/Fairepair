<?php
require 'config/config.php';
require 'includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$succes = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email        = trim($_POST['email'] ?? '');
    $motSecret    = $_POST['mot_secret'] ?? '';
    $motDePasse   = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    // ---- Vérifications de base ----
    if ($email === '' || $motSecret === '' || $motDePasse === '') {
        $errors[] = "Merci de remplir tous les champs.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }
    if (strlen($motDePasse) > 72) {
        $errors[] = "Le mot de passe ne doit pas dépasser 72 caractères.";
    }
    foreach (validerMotDePasse($motDePasse) as $erreur) {
        $errors[] = $erreur;
    }
    if ($motDePasse !== $confirmation) {
        $errors[] = "Les deux mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, mot_secret FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Message identique dans tous les cas (anti-énumération) :
        // on ne révèle pas si le compte existe, ni si le mot secret est bon.
        if (!$user || $user['mot_secret'] === null || !password_verify($motSecret, $user['mot_secret'])) {
            $errors[] = "Email ou mot secret incorrect. Le mot secret se définit dans votre profil.";
        } else {
            // Mot secret correct : on change le mot de passe et on nettoie les tokens
            $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "UPDATE utilisateur
                 SET mot_de_passe = ?, reset_token = NULL, reset_expires_at = NULL,
                     tentatives_connexion = 0, blocage_jusqua = NULL
                 WHERE id = ?"
            );
            $stmt->execute([$hash, $user['id']]);
            $succes = true;
        }
    }
}

$pageTitle = "Mot de passe oublié";
require 'includes/header.php';
?>

<h1>Réinitialiser mon mot de passe</h1>

<p class="muted">
    Renseignez votre email, votre <strong>mot secret</strong> (défini dans votre profil)
    puis choisissez un nouveau mot de passe.
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
        <a href="connexion.php">Vous pouvez maintenant vous connecter.</a>
    </div>
<?php else: ?>
    <form method="post" class="form">
        <label>Email
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
        </label>
        <label>Mot secret
            <input type="password" name="mot_secret" required>
        </label>
        <label>Nouveau mot de passe (8 caractères minimum)
            <input type="password" name="mot_de_passe" required>
        </label>
        <label>Confirmation du nouveau mot de passe
            <input type="password" name="confirmation" required>
        </label>
        <button type="submit">Réinitialiser mon mot de passe</button>
    </form>
<?php endif; ?>

<p><a href="connexion.php">&larr; Retour à la connexion</a></p>

<?php require 'includes/footer.php'; ?>