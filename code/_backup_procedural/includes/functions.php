<?php
// Vérifie si un utilisateur est connecté
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

// Bloque l'accès à la page si l'utilisateur n'est pas connecté
function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('connexion.php');
    }
}

// Retourne l'id de l'utilisateur connecté (ou null)
function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

// Redirige vers une autre page et arrête le script
function redirect(string $path): void
{
    header("Location: $path");
    exit;
}

// Échappe une chaîne avant affichage HTML (protection XSS)
function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Message flash (succès/erreur) affiché une seule fois puis effacé
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

// Retourne la liste des rôles de l'utilisateur connecté.
// Un utilisateur peut être client ET réparateur (double rôle) ; un modérateur est seul dans son camp.
// (Le nom des tables est une constante interne, pas une saisie utilisateur.)
function rolesUtilisateur(PDO $pdo): array
{
    $id = currentUserId();
    if ($id === null) {
        return [];
    }
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

function estClient(PDO $pdo): bool
{
    return in_array('client', rolesUtilisateur($pdo), true);
}

function estReparateur(PDO $pdo): bool
{
    return in_array('reparateur', rolesUtilisateur($pdo), true);
}

function estModerateur(PDO $pdo): bool
{
    return in_array('moderateur', rolesUtilisateur($pdo), true);
}

// Retourne le premier rôle de l'utilisateur : 'client', 'reparateur',
// 'moderateur' ou null si non connecté / sans rôle.
function currentUserRole(PDO $pdo): ?string
{
    $roles = rolesUtilisateur($pdo);
    return $roles[0] ?? null;
}

// Réserve une page aux réparateurs : sinon, redirige vers la connexion
// ou vers l'accueil avec un message d'erreur.
function requireReparateur(PDO $pdo): void
{
    if (!isLoggedIn()) {
        redirect('connexion.php');
    }
    if (!estReparateur($pdo)) {
        setFlash('error', "Cette page est réservée aux réparateurs.");
        redirect('index.php');
    }
}

// Réserve une page aux modérateurs : sinon, redirige vers la connexion
// ou vers l'accueil avec un message d'erreur.
function requireModerateur(PDO $pdo): void
{
    if (!isLoggedIn()) {
        redirect('connexion.php');
    }
    if (!estModerateur($pdo)) {
        setFlash('error', "Cette page est réservée aux modérateurs.");
        redirect('index.php');
    }
}

// Vérifie si l'utilisateur connecté a déjà défini son mot secret.
// Rend le mot secret OBLIGATOIRE après la création du compte (sécurité RGPD) :
// tant qu'il n'est pas défini, les pages du site redirigent vers sa création.
function motSecretDefini(PDO $pdo): bool
{
    $id = currentUserId();
    if ($id === null) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT mot_secret FROM utilisateur WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user && $user['mot_secret'] !== null;
}

// Gère l'upload d'une image depuis un formulaire (champ fichier).
// Retourne le chemin relatif à stocker en base, ou null s'il n'y a rien.
// En cas de fichier invalide, ajoute un message d'erreur dans $errors.
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

    // Destination : assets/uploads/ avec un nom unique
    $dossier = __DIR__ . '/../assets/uploads';
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

// Politique de mot de passe (recommandations CNIL / sécurité RGPD) :
// retourne la liste des non-conformités ; tableau vide = mot de passe valide.
function validerMotDePasse(string $motDePasse): array
{
    $erreurs = [];
    if (strlen($motDePasse) < 8) {
        $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }
    if (strlen($motDePasse) > 72) {
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
    return $erreurs;
}

// Version des CGU en vigueur (traçée en base au moment de l'acceptation)
if (!defined('CGU_VERSION')) {
    define('CGU_VERSION', '1.0');
    define('CGU_DATE', '23/09/2026');
}
