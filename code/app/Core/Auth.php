<?php
namespace App\Core;

use App\Models\Utilisateur;
use App\Models\AuthRemember;

// Gestion de la session : connexion, rôles, garde-fous d'accès.
// Utilisé via les raccourcis globaux de helpers.php (isLoggedIn(), …).
final class Auth
{
    // L'utilisateur est-il connecté ?
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    // Id de l'utilisateur connecté (ou null)
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    // Connecte l'utilisateur (mémorise aussi son rôle principal)
    public static function login(int $userId, string $prenom): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['prenom']  = $prenom;
        $_SESSION['role']    = self::role();
        $_SESSION['role_actif'] = self::roleParDefaut();
    }

    // Déconnexion propre (session + cookie + jeton « se souvenir de moi »)
    public static function logout(): void
    {
        // On oublie aussi l'appareil mémorisé, sinon le cookie
        // continuerait de reconnecter l'utilisateur automatiquement.
        $token = lireCookieMemorise();
        if ($token !== null) {
            AuthRemember::supprimer($token);
            supprimerCookieMemorise();
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ============================================================
    //  « Se souvenir de moi » : reconstruit la session à partir
    //  du cookie. Appelé UNE fois par requête (app/bootstrap.php),
    //  AVANT l'affichage des pages et les gardes d'accès.
    // ============================================================
    public static function restore(): void
    {
        if (self::check()) {
            return;                          // déjà connecté : rien à faire
        }

        $token = lireCookieMemorise();
        if ($token === null) {
            return;                          // pas de cookie : visiteur
        }

        $userId = AuthRemember::utilisateurIdDe($token);
        if ($userId === null) {
            supprimerCookieMemorise();       // jeton inconnu ou périmé
            return;
        }

        $user = Utilisateur::trouver($userId);
        if (!$user) {
            AuthRemember::supprimer($token);
            supprimerCookieMemorise();
            return;
        }

        // Rotation : l'ancien jeton est annulé et remplacé tout de suite,
        // ainsi un cookie copié avant ne sert plus à rien.
        AuthRemember::supprimer($token);
        definirCookieMemorise(AuthRemember::creer($userId));

        self::login($userId, (string)$user['prenom']);
    }

    // Renvoie l'utilisateur complet depuis la base
    public static function user(): ?array
    {
        $id = self::id();
        return $id === null ? null : Utilisateur::trouver($id);
    }

    // Liste des rôles : un compte peut être client ET réparateur ;
    // un modérateur est seul dans son camp.
    public static function roles(): array
    {
        $id = self::id();
        if ($id === null) {
            return [];
        }
        $pdo = Database::get();
        $roles = [];
        foreach (['client', 'reparateur', 'moderateur'] as $role) {
            $stmt = $pdo->prepare("SELECT 1 FROM $role WHERE id_utilisateur = ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()) {
                $roles[] = $role;
            }
        }
        return $roles;
    }

    public static function isClient(): bool
    {
        return in_array('client', self::roles(), true);
    }

    public static function isReparateur(): bool
    {
        return in_array('reparateur', self::roles(), true);
    }

    public static function isModerateur(): bool
    {
        return in_array('moderateur', self::roles(), true);
    }

    // Premier rôle de l'utilisateur, ou null
    public static function role(): ?string
    {
        $roles = self::roles();
        return $roles[0] ?? null;
    }

    // Le mot secret (sécurité RGPD) est-il défini pour le compte ?
    public static function motSecretDefini(): bool
    {
        $id = self::id();
        if ($id === null) {
            return false;
        }
        $stmt = Database::get()->prepare("SELECT mot_secret FROM utilisateur WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user && $user['mot_secret'] !== null;
    }

    // ============================================================
    //  Profil actif (« vue » choisie) — un compte peut être client ET
    //  réparateur : on mémorise dans la session la vue affichée.
    // ============================================================

    // Rôle par défaut quand aucun profil n'est encore choisi :
    // réparateur > modérateur > client (pour rester cohérent avec
    // l'ancienne redirection après connexion).
    public static function roleParDefaut(): ?string
    {
        $roles = self::roles();
        foreach (['reparateur', 'moderateur', 'client'] as $prefere) {
            if (in_array($prefere, $roles, true)) {
                return $prefere;
            }
        }
        return $roles[0] ?? null;
    }

    // Rôle actuellement affiché (profil actif)
    public static function roleActif(): ?string
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }
        $actif = $_SESSION['role_actif'] ?? null;
        if ($actif !== null && in_array($actif, self::roles(), true)) {
            return $actif;
        }
        return self::roleParDefaut();
    }

    // Bascule de profil (client <-> réparateur <-> modérateur)
    public static function setRoleActif(string $role): void
    {
        if (in_array($role, self::roles(), true)) {
            $_SESSION['role_actif'] = $role;
            $_SESSION['role']       = $role;
        }
    }

    public static function estRoleActif(string $role): bool
    {
        return self::roleActif() === $role;
    }

    // Libellé lisible du rôle (« Espace client », « Espace réparateur »…)
    public static function libelleRole(string $role): string
    {
        return [
            'client'     => 'Espace client',
            'reparateur' => 'Espace réparateur',
            'moderateur' => 'Espace modération',
        ][$role] ?? ucfirst($role);
    }

    // Libellé court avec les accents (« Client », « Réparateur », « Modérateur »)
    public static function libelleRoleCourt(string $role): string
    {
        return [
            'client'     => 'Client',
            'reparateur' => 'Réparateur',
            'moderateur' => 'Modérateur',
        ][$role] ?? ucfirst($role);
    }

    // ---- Garde-fous d'accès (pages réservées) ----
    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect(url('connexion'));
        }
    }

    public static function requireReparateur(): void
    {
        if (!self::check()) {
            redirect(url('connexion'));
        }
        if (!self::isReparateur()) {
            setFlash('error', "Cette page est réservée aux réparateurs.");
            redirect(url('accueil'));
        }
    }

    public static function requireModerateur(): void
    {
        if (!self::check()) {
            redirect(url('connexion'));
        }
        if (!self::isModerateur()) {
            setFlash('error', "Cette page est réservée aux modérateurs.");
            redirect(url('accueil'));
        }
    }
}