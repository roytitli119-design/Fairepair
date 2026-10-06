<?php
namespace App\Core;

// Routeur : associe une route (?route=…) à un contrôleur + une action.
final class Router
{
    private array $routes = [];

    public function add(string $route, string $controleur, string $action): void
    {
        $this->routes[$route] = [$controleur, $action];
    }

    public function dispatch(string $route): void
    {
        if (!isset($this->routes[$route])) {
            http_response_code(404);
            echo "<h1>404 — Page introuvable</h1>";
            echo '<p><a href="' . e(url('accueil')) . '">Retour à l\'accueil</a></p>';
            return;
        }

        [$controleur, $action] = $this->routes[$route];
        $instance = new $controleur();
        $instance->$action();
    }
}