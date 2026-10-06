<?php
require 'config/config.php';
require 'includes/functions.php';

requireLogin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motSecret     = $_POST['mot_secret'] ?? '';
    $motSecretConf = $_POST['mot_secret_confirmation'] ?? '';

    if (strlen($motSecret) < 4) {
        $errors[] = "Le mot secret doit contenir au moins 4 caractères.";
    }
    if ($motSecret !== $motSecretConf) {
        $errors[] = "Les deux mots secrets ne correspondent pas.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE utilisateur SET mot_secret = ? WHERE id = ?");
        $stmt->execute([password_hash($motSecret, PASSWORD_DEFAULT), currentUserId()]);
        setFlash('success', 'Mot secret enregistré. Vous pouvez maintenant utiliser votre compte.');
        redirect('index.php');
    }
}

$pageTitle = "Définir mon mot secret";
require 'includes/header.php';
?>

<h1>Définir votre mot secret 🔑</h1>

<p class="muted">
    Pour sécuriser votre compte, choisissez un <strong>mot secret</strong> (un mot ou une courte
    phrase dont vous vous souviendrez). Il vous permettra de <strong>récupérer votre mot de passe</strong>
    si vous l'oubliez un jour. Il est enregistré de manière chiffrée, jamais en clair.
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

<form method="post" class="form">
    <label>Votre mot secret (4 caractères minimum)
        <input type="password" name="mot_secret" minlength="4" required autocomplete="off" autofocus>
    </label>
    <label>Confirmation du mot secret
        <input type="password" name="mot_secret_confirmation" minlength="4" required autocomplete="off">
    </label>
    <button type="submit">Enregistrer et continuer</button>
</form>

<p class="muted">💡 Vous pourrez le modifier plus tard depuis votre profil.</p>

<?php require 'includes/footer.php'; ?>