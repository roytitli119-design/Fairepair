<?php
// ============================================================
//  Menu latéral du tableau de bord : le contenu dépend du
//  PROFIL ACTIF (client / réparateur / modérateur).
//  Ouvre <div class="dashboard"> + <main class="dashboard-contenu">
//  (la fermeture se fait dans layout/footer.php)
// ============================================================
$routeCourante = $GLOBALS['currentRoute'] ?? '';
$profilActif   = isLoggedIn() ? roleActif() : null;
$mesRoles      = isLoggedIn() ? rolesUtilisateur() : [];
$moi           = isLoggedIn() ? currentUser() : null;

// Navigation de chaque profil
$menus = [
    'client' => [
        ['route' => 'mesReservations', 'libelle' => 'Mes réservations', 'icone' => '📅'],
        ['route' => 'accueil',         'libelle' => 'Trouver un réparateur', 'icone' => '🔎'],
        ['route' => 'carte',           'libelle' => 'Carte des ateliers',  'icone' => '🗺️'],
        ['route' => 'profil',          'libelle' => 'Mon profil',          'icone' => '👤'],
    ],
    'reparateur' => [
        ['route' => 'reparateurAccueil', 'libelle' => 'Mes rendez-vous',  'icone' => '📅'],
        ['route' => 'mesServices',       'libelle' => 'Mes services',     'icone' => '🛠️'],
        ['route' => 'ajouterService',    'libelle' => 'Ajouter un service', 'icone' => '➕'],
        ['route' => 'mesGains',          'libelle' => 'Mes gains',         'icone' => '💶'],
        ['route' => 'profil',            'libelle' => 'Mon profil',        'icone' => '👤'],
    ],
    'moderateur' => [
        ['route' => 'moderateurAccueil', 'libelle' => 'Tableau de bord',   'icone' => '📊'],
        ['route' => 'signalements',      'libelle' => 'Signalements',      'icone' => '🚩'],
        ['route' => 'avisModeration',    'libelle' => 'Avis à modérer',    'icone' => '⭐'],
        ['route' => 'profil',            'libelle' => 'Mon profil',        'icone' => '👤'],
    ],
];
$menu = $menus[$profilActif] ?? $menus['client'];

// Page d'accueil du profil actif (utilisée par le lien « Tableau de bord »)
$accueils = [
    'client'     => 'mesReservations',
    'reparateur' => 'reparateurAccueil',
    'moderateur' => 'moderateurAccueil',
];
$routeAccueil = $accueils[$profilActif] ?? 'accueil';
$initiales = mb_strtoupper(mb_substr((string)($moi['prenom'] ?? $_SESSION['prenom'] ?? '?'), 0, 1));
?>
<div class="dashboard">

    <aside class="sidebar sidebar-<?= e($profilActif ?? 'client') ?>">
        <div class="sidebar-carte-profil">
            <span class="avatar"><?= e($initiales) ?></span>
            <div>
                <strong><?= e(($moi['prenom'] ?? '') . ' ' . ($moi['nom'] ?? '')) ?></strong>
                <span class="badge badge-role"><?= e(libelleRole((string)$profilActif)) ?></span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <?php foreach ($menu as $item): ?>
                <a href="<?= e(url($item['route'])) ?>"
                   class="<?= $routeCourante === $item['route'] ? 'actif' : '' ?>">
                    <span class="sidebar-icone"><?= e($item['icone']) ?></span>
                    <span><?= e($item['libelle']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (count($mesRoles) > 1): ?>
            <div class="sidebar-switch">
                <p class="sidebar-titre">Changer de profil</p>
                <?php foreach ($mesRoles as $r): ?>
                    <?php if ($r === $profilActif): ?>
                        <span class="switcher-pill actif switch-<?= e($r) ?>">
                            <?= e(libelleRoleCourt($r)) ?> ✔
                        </span>
                    <?php else: ?>
                        <a class="switcher-pill switch-<?= e($r) ?>"
                           href="<?= e(basculerProfilUrl($r)) ?>">
                            Passer en <?= e(libelleRoleCourt($r)) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="<?= e(url('accueil')) ?>" class="sidebar-retour">← Retour au site public</a>
    </aside>

    <main class="dashboard-contenu">