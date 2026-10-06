<?php
// ============================================================
//  FAIR'REPAIR — Bootstrap (chargement de l'application MVC)
//  Appelé uniquement par public/index.php (front controller).
// ============================================================

// Racines du projet
define('APP_ROOT', dirname(__DIR__));                // code/
define('PUBLIC_ROOT', APP_ROOT . '/public');         // code/public

// Session : indispensable pour l'authentification et les messages flash
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoloader PSR-4 simplifié : App\NomDeClasse → app/NomDeClasse.php
spl_autoload_register(function (string $classe): void {
    $prefixe = 'App\\';
    if (str_starts_with($classe, $prefixe)) {
        $relatif = substr($classe, strlen($prefixe));
        $fichier = APP_ROOT . '/app/' . str_replace('\\', '/', $relatif) . '.php';
        if (is_file($fichier)) {
            require $fichier;
        }
    }
});

// Fonctions utilitaires globales : e(), url(), flash, politique mot de passe…
require_once APP_ROOT . '/app/Core/helpers.php';

// « Se souvenir de moi » : on tente de restaurer la session AVANT tout
// le reste, pour qu'un visiteur revenant avec le cookie ne soit pas
// considéré comme un visiteur anonyme.
\App\Core\Auth::restore();