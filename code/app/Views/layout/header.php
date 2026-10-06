<?php
// 🔐 Sécurité RGPD : le mot secret est OBLIGATOIRE après la création du compte.
// Tant qu'il n'est pas défini, l'utilisateur est redirigé vers sa création
// (sauf sur cette page de création et la déconnexion).
$routeCourante = $GLOBALS['currentRoute'] ?? '';
if (isLoggedIn() && !motSecretDefini()
    && !in_array($routeCourante, ['creerMotSecret', 'deconnexion'], true)) {
    setFlash('error', "Pour sécuriser votre compte, définissez d'abord votre mot secret.");
    redirect(url('creerMotSecret'));
}

// Profil affiché (client / réparateur / modérateur) et rôles réellement possédés
$profilActif  = isLoggedIn() ? roleActif() : null;
$mesRoles     = isLoggedIn() ? rolesUtilisateur() : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Fair'repair — la plateforme éco-responsable pour réparer son vélo près de chez soi. Réservation en ligne, réparation à domicile, paiement sécurisé.">
    <title>Fair'repair<?= isset($pageTitle) ? ' - ' . e($pageTitle) : '' ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-role="<?= e($profilActif ?? 'visiteur') ?>">
    <header class="navbar">
        <a href="<?= e(url('accueil')) ?>" class="logo" aria-label="Fair'repair — accueil">
            <svg class="logo-velo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="5.5" cy="17.5" r="3.5"/>
                <circle cx="18.5" cy="17.5" r="3.5"/>
                <circle cx="15" cy="5" r="1"/>
                <path d="M12 17.5V14l-3-3 4-3 2 3h2"/>
            </svg>
            <span>Fair'repair</span>
        </a>

        <?php if (isLoggedIn()): ?>
            <nav>
                <a href="<?= e(url('accueil')) ?>">Services</a>
                <a href="<?= e(url('carte')) ?>">Carte</a>

                <?php // ---- Bouton de changement de profil (client <-> réparateur <-> modérateur) ---- ?>
                <?php if (count($mesRoles) > 1): ?>
                    <span class="switcher" title="Changer de profil">
                        <span class="switcher-libelle">Mon profil :</span>
                        <?php foreach ($mesRoles as $r): ?>
                            <?php if ($r === $profilActif): ?>
                                <span class="switcher-pill actif switch-<?= e($r) ?>">
                                    <?= e(libelleRoleCourt($r)) ?>
                                </span>
                            <?php else: ?>
                                <a class="switcher-pill switch-<?= e($r) ?>"
                                   href="<?= e(basculerProfilUrl($r)) ?>">
                                    <?= e(libelleRoleCourt($r)) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </span>
                <?php endif; ?>

                <a href="<?= e(url('profil')) ?>">Mon profil</a>
                <a href="<?= e(url('deconnexion')) ?>">Déconnexion</a>
            </nav>
        <?php else: ?>
            <nav>
                <a href="<?= e(url('accueil')) ?>">Services</a>
                <a href="<?= e(url('carte')) ?>">Carte</a>
                <a href="<?= e(url('connexion')) ?>">Se connecter</a>
                <a href="<?= e(url('inscription')) ?>">Créer un compte</a>
            </nav>
        <?php endif; ?>
    </header>

    <?php if ($flash = getFlash()): ?>
        <div class="alert alert-<?= e($flash['type']) ?> flash-global"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php // Layout « header » : le contenu est centré dans .container.
          // Layout « sidebar » : c'est .dashboard (dans sidebar.php) qui gère la mise en page. ?>
    <?php if (empty($GLOBALS['layoutSidebar'])): ?>
        <main class="container">
    <?php endif; ?>