<?php
// 🔐 Sécurité RGPD : le mot secret est OBLIGATOIRE après la création du compte.
// Tant qu'il n'est pas défini, l'utilisateur est redirigé vers sa création
// (sauf sur cette page de création et la déconnexion).
$pageCouranteHeader = basename($_SERVER['PHP_SELF'] ?? '');
if (isLoggedIn() && !motSecretDefini($pdo)
    && !in_array($pageCouranteHeader, ['creer_mot_secret.php', 'deconnexion.php'], true)) {
    setFlash('error', 'Pour sécuriser votre compte, définissez d\'abord votre mot secret.');
    redirect('creer_mot_secret.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fair'repair<?= isset($pageTitle) ? ' - ' . e($pageTitle) : '' ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="navbar">
        <a href="index.php" class="logo">Fair'repair</a>
        <?php $roles = isLoggedIn() ? rolesUtilisateur($pdo) : []; ?>
        <nav>
            <a href="index.php">Services</a>
            <?php if (isLoggedIn()): ?>
                <?php if (in_array('reparateur', $roles, true)): ?>
                    <a href="reparateur_accueil.php">Mes RDVs</a>
                    <a href="ajouter_service.php">Ajouter un service</a>
                    <a href="mes_services.php">Mes services</a>
                    <a href="mes_gains.php">Mes gains</a>
                <?php endif; ?>
                <?php if (in_array('client', $roles, true)): ?>
                    <a href="mes_reservations.php">Mes réservations</a>
                <?php endif; ?>
                <?php if (in_array('moderateur', $roles, true)): ?>
                    <a href="moderateur_accueil.php">Tableau de bord</a>
                    <a href="signalements.php">Signalements</a>
                    <a href="avis_moderation.php">Avis à modérer</a>
                <?php endif; ?>
                <a href="profil.php">Mon profil</a>
                <a href="deconnexion.php">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php">Se connecter</a>
                <a href="inscription.php">Créer un compte</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container">
        <?php $flash = getFlash(); ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
