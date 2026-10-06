<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$token = $_GET['token'] ?? '';
$erreur = '';
$succes = '';
$token_valide = false;
$user_id = null;

// Vérifier la validité du token
if (empty($token)) {
    $erreur = "Lien de réinitialisation invalide ou absent.";
} else {
    $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE reset_token = ? AND reset_expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $token_valide = true;
        $user_id = $user['id'];
    } else {
        $erreur = "Ce lien de réinitialisation est invalide ou a expiré.";
    }
}

// Traitement du formulaire
if ($token_valide && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($password) || empty($password_confirm)) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif ($password !== $password_confirm) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (!empty($erreursMdp = validerMotDePasse($password))) {
        $erreur = implode(' ', $erreursMdp);
    } else {
        // Hacher le nouveau mot de passe et effacer le token
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmtUpdate = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
        
        if ($stmtUpdate->execute([$hash, $user_id])) {
            $succes = "Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter.";
            $token_valide = false; // Pour masquer le formulaire
        } else {
            $erreur = "Une erreur est survenue lors de la mise à jour.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouveau mot de passe - Fair'repair</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Créer un nouveau mot de passe</h1>

        <?php if ($erreur): ?><p class="error"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
        <?php if ($succes): ?>
            <p class="success"><?= htmlspecialchars($succes) ?></p>
            <p><a href="connexion.php">Aller à la page de connexion</a></p>
        <?php endif; ?>

        <?php if ($token_valide): ?>
            <form method="POST">
                <label>Nouveau mot de passe :</label>
                <input type="password" name="password" required minlength="8">
                
                <label>Confirmer le mot de passe :</label>
                <input type="password" name="password_confirm" required minlength="8">
                
                <button type="submit">Valider le nouveau mot de passe</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>