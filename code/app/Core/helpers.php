<?php
// ============================================================
//  Fonctions utilitaires GLOBALES (aucune classe)
//  Chargées par le bootstrap, utilisables dans contrôleurs et vues.
// ============================================================

use App\Core\Auth;

// Échappe une chaîne avant affichage HTML (protection XSS)
function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Construit une URL interne propre : index.php?route=monRoute&param=valeur
function url(string $route, array $params = []): string
{
    $params = array_merge(['route' => $route], $params);
    return 'index.php?' . http_build_query($params);
}

// Redirige vers une URL absolue/relative et arrête le script
function redirect(string $chemin): void
{
    header("Location: $chemin");
    exit;
}

// Lit une valeur de configuration (config/config.php), en mémoire une seule fois.
// Ex. : config('stripe.secret_key'), config('dbname').
function config(string $cle)
{
    static $config = null;
    if ($config === null) {
        $config = require APP_ROOT . '/config/config.php';
    }
    $valeur = $config;
    foreach (explode('.', $cle) as $partie) {
        if (!is_array($valeur) || !array_key_exists($partie, $valeur)) {
            return null;
        }
        $valeur = $valeur[$partie];
    }
    return $valeur;
}

// Transforme un chemin relatif ('index.php?route=…') en URL absolue.
// Requis par Stripe pour les URL de retour (success_url / cancel_url).
function absoluteUrl(string $chemin): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $hote = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https://' : 'http://') . $hote . '/' . ltrim($chemin, '/');
}

// Nom du cookie « se souvenir de moi »
if (!defined('COOKIE_MEMORISE')) {
    define('COOKIE_MEMORISE', 'fairrepair_memoire');
}

// ---- « Se souvenir de moi » : pose le cookie (30 jours) ----
function definirCookieMemorise(string $valeur): void
{
    setcookie(COOKIE_MEMORISE, $valeur, [
        'expires'  => time() + (30 * 86400),
        'path'     => '/',
        'httponly' => true,   // inaccessible au JavaScript (anti-vol par XSS)
        'samesite' => 'Lax',  // pas envoyé depuis un autre site
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    $_COOKIE[COOKIE_MEMORISE] = $valeur;   // utile pour la requête en cours
}

// ---- Lit le cookie s'il est bien formé ----
// Le jeton est un chaîne hexadécimale de 64 caractères
// (tokens64 = 32 octets aléatoires). On refuse tout autre format.
function lireCookieMemorise(): ?string
{
    $valeur = $_COOKIE[COOKIE_MEMORISE] ?? null;
    return (is_string($valeur) && preg_match('/^[a-f0-9]{64}$/', $valeur) === 1) ? $valeur : null;
}

// ---- Supprime le cookie ----
function supprimerCookieMemorise(): void
{
    setcookie(COOKIE_MEMORISE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    unset($_COOKIE[COOKIE_MEMORISE]);
}

// ---- Messages flash (affichés une seule fois puis effacés) ----
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ============================================================
//  Raccourcis d'authentification → délèguent à App\Core\Auth
// ============================================================
function isLoggedIn(): bool          { return Auth::check(); }
function currentUserId(): ?int       { return Auth::id(); }

// Utilisateur connecté relu en base (mis en cache pour la requête)
function currentUser(): ?array
{
    static $cache = null;
    static $cacheId = -1;
    $id = Auth::id();
    if ($id === null) {
        return null;
    }
    if ($cacheId !== $id) {
        $cache   = Auth::user();
        $cacheId = $id;
    }
    return $cache;
}
function rolesUtilisateur(): array   { return Auth::roles(); }
function estClient(): bool           { return Auth::isClient(); }
function estReparateur(): bool       { return Auth::isReparateur(); }
function estModerateur(): bool       { return Auth::isModerateur(); }
function currentUserRole(): ?string  { return Auth::role(); }
function motSecretDefini(): bool     { return Auth::motSecretDefini(); }

// Profil affiché (client / réparateur / modérateur) + bascule
function roleActif(): ?string        { return Auth::roleActif(); }
function estRoleActif(string $r): bool { return Auth::estRoleActif($r); }
function basculerProfilUrl(string $role): string
{
    return url('basculerProfil', ['role' => $role]);
}
function libelleRole(string $role): string { return Auth::libelleRole($role); }
function libelleRoleCourt(string $role): string { return Auth::libelleRoleCourt($role); }

// ============================================================
//  Upload d'image (services) — sécurisé : type MIME réel, taille 2 Mo
// ============================================================
// Retourne le chemin relatif à stocker en base ('assets/uploads/…'),
// ou null s'il n'y a rien. En cas de fichier invalide, ajoute un
// message d'erreur dans $errors.
function gererUpload(string $champ, array &$errors): ?string
{
    if (empty($_FILES[$champ]['name'])) {
        return null;
    }

    $fichier = $_FILES[$champ];
    $extensions = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
    ];
    $ext = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));

    // 1. Extension et type MIME déclaré
    if (!isset($extensions[$ext]) || $fichier['type'] !== $extensions[$ext]) {
        $errors[] = "L'image doit être un fichier JPG, PNG, WebP ou GIF.";
        return null;
    }
    // 2. C'est bien une image (getimagesize lit l'en-tête réel du fichier)
    if (getimagesize($fichier['tmp_name']) === false) {
        $errors[] = "Le fichier envoyé n'est pas une image valide.";
        return null;
    }
    // 3. Taille maximale : 2 Mo
    if ($fichier['size'] > 2 * 1024 * 1024) {
        $errors[] = "L'image est trop lourde (2 Mo maximum).";
        return null;
    }
    // 4. Erreur d'envoi côté navigateur/serveur
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "L'upload de l'image a échoué (erreur n°" . (int)$fichier['error'] . ").";
        return null;
    }

    // Destination : public/assets/uploads/ (avec un nom unique)
    $dossier = PUBLIC_ROOT . '/assets/uploads';
    if (!is_dir($dossier)) {
        mkdir($dossier, 0755, true);
    }
    $nomFichier = 'srv_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($fichier['tmp_name'], $dossier . '/' . $nomFichier)) {
        $errors[] = "Impossible d'enregistrer l'image sur le serveur.";
        return null;
    }

    return 'assets/uploads/' . $nomFichier;
}

// ============================================================
//  Politique de mot de passe (recommandations CNIL / RGPD)
// ============================================================
// UNE SEULE expression régulière exprime la règle complète
// (lookaheads) :
//   ^                  début de chaîne
//   (?=.*[a-z])        au moins une minuscule
//   (?=.*[A-Z])        au moins une majuscule
//   (?=.*\d)           au moins un chiffre
//   (?=.*[^A-Za-z0-9]) au moins un caractère spécial
//   .{8,72}$           8 à 72 caractères
// Retourne la liste des non-conformités ; tableau vide = valide.
function validerMotDePasse(string $motDePasse): array
{
    $erreurs = [];
    $regexPolitique = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,72}$/';

    if (preg_match($regexPolitique, $motDePasse) !== 1) {
        // Messages explicites : on redécoupe la règle pour dire à
        // l'utilisateur précisément ce qui manque.
        if (!preg_match('/^.{8,}$/', $motDePasse)) {
            $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères.";
        }
        if (strlen($motDePasse) > 72) {
            // Limite bcrypt : pas de regex ici car c'est un comptage d'octets
            $erreurs[] = "Le mot de passe ne doit pas dépasser 72 caractères (limite bcrypt).";
        }
        if (!preg_match('/[A-Z]/', $motDePasse)) {
            $erreurs[] = "Le mot de passe doit contenir au moins une majuscule.";
        }
        if (!preg_match('/[a-z]/', $motDePasse)) {
            $erreurs[] = "Le mot de passe doit contenir au moins une minuscule.";
        }
        if (!preg_match('/[0-9]/', $motDePasse)) {
            $erreurs[] = "Le mot de passe doit contenir au moins un chiffre.";
        }
        if (!preg_match('/[^A-Za-z0-9]/', $motDePasse)) {
            $erreurs[] = "Le mot de passe doit contenir au moins un caractère spécial (ex. ! @ # $ %).";
        }
    }
    return $erreurs;
}

// ============================================================
//  Version des CGU en vigueur (traçée en base au moment de l'acceptation)
// ============================================================
if (!defined('CGU_VERSION')) {
    define('CGU_VERSION', '1.0');
    define('CGU_DATE', '23/09/2026');
}

