<?php
namespace App\Core;

// Classe de base de tous les contrôleurs :
// rendu de vue (avec gabarit commun), redirections, messages flash.
abstract class Controller
{
    // Affiche une vue dans le gabarit commun.
    // $layout = 'header' : page classique (barre de navigation en haut)
    // $layout = 'sidebar' : tableau de bord (menu latéral gauche, selon le profil actif)
    protected function render(string $vue, array $donnees = [], string $titrePage = '', string $layout = 'header'): void
    {
        extract($donnees);
        $pageTitle = $titrePage;
        $GLOBALS['layoutSidebar'] = ($layout === 'sidebar');
        require APP_ROOT . '/app/Views/layout/header.php';
        if ($layout === 'sidebar') {
            require APP_ROOT . '/app/Views/layout/sidebar.php';
        }
        require APP_ROOT . '/app/Views/' . $vue . '.php';
        require APP_ROOT . '/app/Views/layout/footer.php';
    }

    // Redirige vers une route interne (index.php?route=…)
    protected function redirect(string $route, array $params = []): void
    {
        redirect(url($route, $params));
    }

    protected function flash(string $type, string $message): void
    {
        setFlash($type, $message);
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    // Champ POST (chaîne) avec défaut
    protected function post(string $champ, string $defaut = ''): string
    {
        return (string)($_POST[$champ] ?? $defaut);
    }

    // Champ GET (entier) avec défaut
    protected function get(string $champ, int $defaut = 0): int
    {
        return (int)($_GET[$champ] ?? $defaut);
    }
}