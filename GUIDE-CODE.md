# Fair'repair — Guide du code (explication détaillée)

> Document pédagogique : il explique **tout le code** de l'application, fichier par fichier,
> pour comprendre ce qui a été écrit et savoir le présenter en soutenance.
> Version du 29/09/2026 — 3 911 lignes de PHP + 520 lignes de CSS + 186 lignes de SQL.

---

## 1. L'application en une phrase

Fair'repair met en relation des **clients** qui veulent faire réparer leur vélo et des
**réparateurs** qui proposent leurs services. Un **modérateur** arbitre (avis et signalements).
C'est une application web PHP en **POO + MVC**, sans framework, avec une base de données MySQL/MariaDB.

---

## 2. Faire tourner le site (à faire avant de tester)

**1. Allumer la base de données** (normalement déjà active au démarrage du PC) :

```bash
sudo systemctl start mariadb      # sinon : vérifiez avec systemctl status mariadb
```

**2. Vérifier la base :**

```bash
mysql --skip-ssl -h 127.0.0.1 -P 3306 -u monuser -pmonmotdepasse fairepair -e "SELECT 1;"
```

**3. Lancer le serveur web** (dans un terminal, depuis le dossier `code/`) :

```bash
php -S 127.0.0.1:8000 -t public
```

**4. Ouvrir** <http://127.0.0.1:8000/index.php?route=accueil>

> L'option `-t public` est importante : elle dit à PHP de servir le dossier `public/`
> (le DocumentRoot), où sont le front controller, le CSS et les images.

**Comptes de démonstration**

| Rôle | Email | Mot de passe | Mot secret |
|---|---|---|---|
| Client | `client@test.fr` | `Client123!` | `soleil` |
| Réparateur (Marc, Paris) | `reparateur@test.fr` | `Reparateur123!` | `velo2026` |
| Réparateur (Thomas, Lyon) | `thomas@test.fr` | `Secret123!` | — |
| Modérateur (Laura) | `moderateur@test.fr` | `Moderateur123!` | `aura2026` |

---

## 3. L'architecture MVC : le principe

**MVC = Modèle – Vue – Contrôleur.** L'idée est de séparer trois responsabilités :

| Couche | Rôle | Dans notre projet | Exemple |
|---|---|---|---|
| **Modèle** (Model) | Parler à la base de données | `app/Models/*.php` | `Service::tousAvecReparateur()` |
| **Vue** (View) | Afficher du HTML | `app/Views/**/*.php` | `home/index.php` |
| **Contrôleur** (Controller) | Répondre à une demande : vérifier, décider, appeler le modèle, choisir la vue | `app/Controllers/*.php` | `HomeController::index()` |

**Le cycle complet d'une requête** (exemple : l'utilisateur clique sur « Carte ») :

```
1. Le navigateur demande : index.php?route=carte
                │
2. public/index.php  ← FRONT CONTROLLER (unique point d'entrée)
   charge app/bootstrap.php (session + autoloader + helpers)
   regarde la table des routes : "carte" → HomeController::carte
                │
3. HomeController::carte()  ← CONTRÔLEUR
   vérifie les droits (page publique : rien à vérifier)
   appelle les modèles : qui sont les réparateurs géolocalisés ?
                │
4. Models\Reparateur + Models\Service  ← MODÈLES
   exécutent du SQL via Database::get() et renvoient des tableaux PHP
                │
5. Contrôleur : $this->render('home/carte', ['reparateurs' => ...], 'Carte')
                │
6. Vue app/Views/home/carte.php  ← VUE
   header.php + carte.php + footer.php
   aucun SQL ici, uniquement du HTML + <?= e(...) ?>
                │
7. Le serveur renvoie du HTML → le navigateur affiche la page
```

**Règle d'or** : une vue ne contient **jamais** de SQL, un modèle ne contient **jamais** de HTML,
un contrôleur ne fait **jamais** de mise en forme.

---

## 4. Arborescence : à quoi sert chaque fichier

```
Fair'repair/
├── base-de-donnees/
│   ├── fairepair_bdd.sql        ← schéma complet de la base (création des 8 tables)
│   └── nettoyage_soutenance.sql ← supprime les comptes de test *@test.fr
└── code/                        ← l'application
    ├── index.php                ← shim Apache : require public/index.php
    ├── config/
    │   └── config.php           ← identifiants BDD + clé Stripe (SEUL fichier de config)
    ├── app/
    │   ├── bootstrap.php        ← constants, session, autoloader, helpers
    │   ├── Core/                ← le moteur de l'application
    │   │   ├── Database.php     ← connexion PDO unique (singleton)
    │   │   ├── Router.php       ← table des routes + dispatch
    │   │   ├── Controller.php   ← classe de base des contrôleurs (render, redirect, post…)
    │   │   ├── Auth.php         ← session : connexion, rôles, gardes d'accès
    │   │   ├── helpers.php      ← fonctions globales (e, url, flash, validerMotDePasse…)
    │   │   └── StripeGateway.php← appel à l'API Stripe (mode test)
    │   ├── Models/              ← 7 modèles = 7 tables métier
    │   ├── Controllers/         ← 10 contrôleurs
    │   └── Views/               ← les gabarits HTML (1 dossier par contrôleur)
    └── public/                  ← DocumentRoot (ce qui est exposé sur le web)
        ├── index.php            ← ★ FRONT CONTROLLER (table des routes)
        └── assets/
            ├── css/style.css    ← thème vert écolo (520 lignes)
            ├── img/             ← logo, favicon, photos
            └── uploads/         ← photos des services envoyées par les réparateurs
```

Le dossier `code/_backup_procedural/` contient l'ancienne version **procédurale** (avant la
refonte MVC) : elle n'est plus utilisée, elle est gardée uniquement comme archive.

---

## 5. Le noyau (app/Core et bootstrap)

### 5.1 `public/index.php` — le front controller (67 lignes)

C'est le **seul** fichier accessible depuis le navigateur. Il contient la **table des routes** :

```php
$routeur = new Router();
$routeur->add('accueil',          HomeController::class,       'index');
$routeur->add('carte',            HomeController::class,       'carte');
$routeur->add('connexion',        AuthController::class,       'connexion');
$routeur->add('profil',           ProfilController::class,     'index');
$routeur->add('paiement',         PaiementController::class,   'index');
…
$route = $_GET['route'] ?? 'accueil';
$GLOBALS['currentRoute'] = $route;   // utile pour le header (garde du mot secret)
$routeur->dispatch($route);
```

Lecture : `?route=carte` → on exécute `HomeController::carte()`.

**Les 3 rôles du front controller :**
1. charger le noyau (`require bootstrap.php`) ;
2. dire quelle page correspond à quelle URL (la table ci-dessus) ;
3. lancer le contrôleur correspondant.

### 5.2 `app/bootstrap.php` (28 lignes) — démarrer l'application

```php
define('APP_ROOT', dirname(__DIR__));      // dossier code/
define('PUBLIC_ROOT', APP_ROOT . '/public');

if (session_status() === PHP_SESSION_NONE) {
    session_start();                        // 1. la session (mémoire de l'utilisateur connecté)
}

spl_autoload_register(function (string $classe): void {   // 2. l'autoloader
    $prefixe = 'App\\';
    if (str_starts_with($classe, $prefixe)) {
        $relatif = substr($classe, strlen($prefixe));
        $fichier = APP_ROOT . '/app/' . str_replace('\\', '/', $relatif) . '.php';
        if (is_file($fichier)) {
            require $fichier;                // App\Models\Service → app/Models/Service.php
        }
    }
});

require_once APP_ROOT . '/app/Core/helpers.php';   // 3. les fonctions utilitaires
```

**L'autoloader** évite d'écrire 40 `require` : quand le code écrit `new ServiceController()`,
PHP cherche tout seul `app/Controllers/ServiceController.php` et le charge.

### 5.3 `app/Core/Database.php` (27 lignes) — la connexion à la base

Pattern **singleton** : une seule connexion PDO pour toute la requête HTTP.

```php
final class Database
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo === null) {                 // première fois seulement
            $config = require APP_ROOT . '/config/config.php';
            self::$pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
                $config['user'], $config['pass'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erreur SQL → exception
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // résultats en tableaux associatifs
                ]
            );
        }
        return self::$pdo;
    }
}
```

- `ERRMODE_EXCEPTION` : une erreur SQL lève une exception (donc une page d'erreur) au lieu
  d'afficher un warning discret.
- `FETCH_ASSOC` : `$stmt->fetch()` renvoie `['id' => 1, 'titre' => '…']` (pas `['id', 0, 1]`).
- Tous les modèles font `Database::get()->prepare(...)` : il n'y a **aucune variable `$pdo` globale**.

### 5.4 `app/Core/Router.php` (26 lignes) — l'aiguillage

```php
public function dispatch(string $route): void
{
    if (!isset($this->routes[$route])) {          // route inconnue
        http_response_code(404);
        echo "<h1>404 — Page introuvable</h1>";
        return;
    }
    [$controleur, $action] = $this->routes[$route];
    $instance = new $controleur();                // instanciation dynamique
    $instance->$action();                         // appel dynamique
}
```

### 5.5 `app/Core/Controller.php` (44 lignes) — la classe de base

Tous les contrôleurs héritent de `Controller` et utilisent ses outils :

```php
protected function render(string $vue, array $donnees = [], string $titrePage = ''): void
{
    extract($donnees);                 // ['services' => [...]] devient $services
    $pageTitle = $titrePage;
    require APP_ROOT . '/app/Views/layout/header.php';   // haut de page commun
    require APP_ROOT . '/app/Views/' . $vue . '.php';    // la vue demandée
    require APP_ROOT . '/app/Views/layout/footer.php';   // pied de page commun
}

protected function redirect(string $route, array $params = []): void { redirect(url($route, $params)); }
protected function isPost(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
protected function post(string $champ, string $defaut = ''): string { return (string)($_POST[$champ] ?? $defaut); }
protected function get(string $champ, int $defaut = 0): int { return (int)($_GET[$champ] ?? $defaut); }
```

`extract($donnees)` transforme un tableau en variables : c'est ainsi que le contrôleur transmet
`$services` à la vue. `redirect()` envoie un en-tête HTTP `Location:` puis arrête le script (`exit`).

### 5.6 `app/Core/Auth.php` (131 lignes) — la session et les droits

| Méthode | Rôle |
|---|---|
| `check()` / `id()` | l'utilisateur est-il connecté ? quel id ? (stockés en `$_SESSION`) |
| `login($id, $prenom)` | mémorise l'utilisateur en session |
| `logout()` | vide la session + supprime le cookie + `session_destroy()` |
| `user()` | relit l'utilisateur complet en base |
| `roles()` | **liste** des rôles : interroge `client`, `reparateur`, `moderateur` |
| `isClient()` / `isReparateur()` / `isModerateur()` | tests de rôle |
| `motSecretDefini()` | le compte a-t-il un mot secret ? (obligatoire) |
| `requireLogin()` | garde : redirige vers la connexion si pas connecté |
| `requireReparateur()` / `requireModerateur()` | idem + contrôle du rôle |

Point important : **un compte peut avoir plusieurs rôles** (client *et* réparateur). C'est pourquoi
`roles()` renvoie un tableau et que la navigation du header boucle sur `rolesUtilisateur()`.

### 5.7 `app/Core/helpers.php` (190 lignes) — les fonctions globales

| Fonction | Ce qu'elle fait |
|---|---|
| `e($txt)` | **échappe** le HTML (`htmlspecialchars`) → protection **XSS** |
| `url('service', ['id' => 4])` | construit `index.php?route=service&id=4` |
| `absoluteUrl($chemin)` | `http://…/index.php?route=…` (exigé par Stripe) |
| `redirect($chemin)` | redirection + `exit` |
| `config('stripe.secret_key')` | lit `config/config.php` avec une notation pointée |
| `setFlash()` / `getFlash()` | message affiché **une seule fois** (stocké en session) |
| `isLoggedIn()`, `estClient()`, `rolesUtilisateur()`… | raccourcis vers `Auth` |
| `validerMotDePasse($mdp)` | politique CNIL/RGPD (voir §8) |
| `gererUpload('image', $errors)` | upload d'image sécurisé (voir §8) |
| `definirCookieMemorise()` / `lireCookieMemorise()` / `supprimerCookieMemorise()` | cookie « se souvenir de moi » (voir §5.9) |
| `currentUser()` | utilisateur connecté relu en base (mis en cache) |
| `roleActif()` / `estRoleActif()` / `libelleRole()` | profil affiché et son libellé |

**Le message flash** est un patron très utile : après un `redirect()`, le message « Votre
réservation a bien été enregistrée » survit à la redirection parce qu'il est stocké en session,
puis `header.php` l'affiche et `getFlash()` le supprime.

### 5.8 `app/Core/StripeGateway.php` (77 lignes) — paiement Stripe (mode test)

```php
public static function configuree(): bool                       // une clé sk_test_ est-elle présente ?
public static function creerSessionCheckout(...): ?string        // POST /v1/checkout/sessions
public static function sessionPayee(string $sessionId): bool     // GET  /v1/checkout/sessions/{id}
private static function requete(string $methode, string $chemin, array $params): ?string  // cURL
```

Trois modes de fonctionnement :

| Clé `stripe.secret_key` | Comportement |
|---|---|
| vide `''` | **simulation** : formulaire carte / espèces / virement |
| `sk_test_…` valide | **vrai tunnel Stripe Checkout** (carte de test `4242 4242 4242 4242`) |
| `sk_test_…` invalide | erreur propre : « Impossible de contacter Stripe » |

Le montant est envoyé en **centimes** (`round($montant * 100)`) et le retour du client est vérifié
**côté serveur** (`payment_status === 'paid'`) avant d'enregistrer le paiement : on n'enregistre
jamais un paiement sur la seule foi du navigateur.

### 5.9 « Se souvenir de moi » — le mécanisme

Par défaut, le cookie `PHPSESSID` est supprimé à la fermeture du navigateur. La case à cocher
pose un **second cookie** (`fairrepair_memoire`, 30 jours) qui permet de **reconstruire la session**.

```
1. L'utilisateur coche la case et se connecte
        │
2. AuthController : AuthRemember::creer() tire un jeton aléatoire
   bin2hex(random_bytes(32)) → 64 caractères
        │
3. Le JETON en clair part dans le cookie ; seule son EMPREINTE
   (hash('sha256', $jeton)) est stockée en base (table auth_remember)
        │
4. Au chargement de CHAQUE requête, app/bootstrap.php appelle Auth::restore()
        ├ session déjà ouverte ?         → on ne fait rien
        ├ pas de cookie ?                → visiteur anonyme
        └ cookie présent
             ├ AuthRemember::utilisateurIdDe($jeton) → null ?  → cookie invalide : on le supprime
             └ utilisateur trouvé → Auth::login()  = session reconstruite
                                  → ROTATION : ancien jeton supprimé,
                                    nouveau jeton émis
```

**Pourquoi c'est sûr :**
- la base ne contient que `SHA-256(jeton)` → un vol de base ne donne **aucun** cookie utilisable ;
- la comparaison se fait sur l'empreinte (index unique) ;
- **rotation** à chaque auto-connexion : un cookie recopié avant ne sert plus à rien ;
- cookie `HttpOnly` (inaccessible au JavaScript) + `SameSite=Lax` + `Secure` si HTTPS ;
- le jeton est **supprimé** à la déconnexion, et quand la case n'est pas cochée
  (`AuthRemember::supprimerTout()`).

**4 méthodes du modèle** (`app/Models/AuthRemember.php`) :

| Méthode | Rôle |
|---|---|
| `creer(int $userId): string` | crée un jeton, stocke son empreinte, **renvoie le jeton en clair** |
| `utilisateurIdDe(string $jeton): ?int` | `?int` = jeton valide (et non expiré), `null` sinon |
| `supprimer(string $jeton)` | oublie **un** appareil |
| `supprimerTout(int $userId)` | oublie **tous** les appareils d'un compte |

> ⚠️ Piège rencontré pendant le développement : la fonction de lecture du cookie validait
> l'ancien format `selector:validator` (donc la présence de `:`). Après simplification en un
> jeton unique, elle rejetait tous les cookies → l'auto-connexion ne marchait pas.
> Le contrôle de format est désormais `preg_match('/^[a-f0-9]{64}$/', $valeur)`.

---

## 6. Les modèles (app/Models) — le dialogue avec la base

Tous les modèles suivent le même patron : **méthodes statiques** qui contiennent le SQL.
Aucun modèle ne fait d'affichage.

### 6.1 `Utilisateur.php` (294 lignes) — comptes, rôles, sécurité

| Méthode | Rôle |
|---|---|
| `trouver($id)` / `trouverParEmail($email)` / `emailExiste()` | lectures |
| `creerClient($prenom, $nom, $email, $mdp)` | crée le compte **dans une transaction** : `utilisateur` + `client` + la trace CGU |
| `verifierConnexion($email, $mdp)` | vérifie le hash, compte les échecs, bloque 15 min après **5 échecs** |
| `definirMotSecret($id, $secret)` | enregistre le mot secret **hashé** |
| `verifierAncienMotSecret($id, $ancien)` | obligatoire pour changer le mot secret, **3 tentatives** max |
| `reinitialiserParMotSecret($email, $secret, $nouveauMdp)` | récupération du mot de passe → `'ok' \| 'bloque' \| 'echec'` |
| `majProfil($id, $data)` | prénom, nom, adresse, code postal, ville, téléphone |
| `infosReparateur($id)` | ligne `reparateur` de l'utilisateur |
| `devenirReparateur(...)` | crée le rôle réparateur (et l'entreprise + lat/lng) |
| `majInfosReparateur(...)` | met à jour l'entreprise + géolocalisation |

**Exemple commented (transaction + hash) :**

```php
public static function creerClient(string $prenom, string $nom, string $email, string $motDePasse): int
{
    $pdo = Database::get();
    $pdo->beginTransaction();                       // tout ou rien
    try {
        $hash = password_hash($motDePasse, PASSWORD_DEFAULT);   // JAMAIS en clair
        $pdo->prepare(
            "INSERT INTO utilisateur (prenom, nom, email, mot_de_passe, cgu_acceptee_at, cgu_version)
             VALUES (?, ?, ?, ?, NOW(), ?)"
        )->execute([$prenom, $nom, $email, $hash, CGU_VERSION]);  // preuve RGPD
        $userId = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO client (id_utilisateur) VALUES (?)")->execute([$userId]);
        $pdo->commit();
        return $userId;
    } catch (\Throwable $e) {
        $pdo->rollBack();                           // on annule tout
        throw $e;
    }
}
```

**Anti force brute** (5 tentatives de connexion / 3 pour le mot secret) :

```php
if (!$user || !password_verify($motDePasse, $user['mot_de_passe'])) {
    if ($user) {
        $tentatives = (int)$user['tentatives_connexion'] + 1;
        if ($tentatives >= self::MAX_TENTATIVES) {           // 5
            $pdo->prepare(
                "UPDATE utilisateur
                 SET tentatives_connexion = 0,
                     blocage_jusqua = DATE_ADD(NOW(), INTERVAL " . self::DUREE_BLOCAGE_MINUTES . " MINUTE)
                 WHERE id = ?"
            )->execute([$user['id']]);
            …
        }
        $pdo->prepare("UPDATE utilisateur SET tentatives_connexion = ? WHERE id = ?")->execute([$tentatives, $user['id']]);
    }
    return ['ok' => false, 'message' => "Email ou mot de passe incorrect.", 'user' => null];
}
```

### 6.2 `Service.php` (124 lignes) — les prestations proposées

`tousAvecReparateur()` (accueil), `pourReparateur($id)` (profil public), `trouver($id)`,
`trouverAvecReparateur($id)` (fiche), `statsAvis($id)` (moyenne), `creer(...)`,
`appartenantA($id, $reparateurId)`, `modifier(...)`, `supprimer(...)`, `mesServices($id)`.

**Le modèle porte la sécurité** : `modifier()`, `supprimer()` et `appartenantA()` ont tous
`AND reparateur_id = ?` dans le SQL. Un réparateur ne peut donc **jamais** modifier le service
d'un autre, même en forgeant l'URL.

### 6.3 `Reservation.php` (192 lignes) — le cœur métier

| Méthode | Rôle |
|---|---|
| `creer($clientId, $serviceId, $adresse, $date)` | statut initial `en_attente` |
| `mesReservations($clientId)` | liste du client avec paiement et avis liés (LEFT JOIN) |
| `annuler($id, $clientId)` | `WHERE … client_id = ? AND statut = 'en_attente'` |
| `pouvoirPayer($id, $clientId)` | droit de payer (sa réservation, non annulée) |
| `pouvoirAvis($id, $clientId)` | droit de noter (**terminée** seulement) |
| `pouvoirSignalement($id, $clientId)` | droit de signaler |
| `changerStatut($id, $reparateurId, $statut)` | le réparateur agit **seulement** sur ses services |
| `enAttente()` / `aVenir()` / `historique()` | les 3 tableaux de l'espace réparateur |
| `statsTerminees()` / `statsPaiements()` / `nbEnAttente()` / `detailGains()` | page « Mes gains » |

### 6.4 `Avis.php` (105 lignes) — les avis clients et leur modération

`existePourReservation()` (1 avis max), `creer()`, `enAttente()` (modérateur : `moderateur_id IS NULL`),
`moderes()`, `publicsPourService($serviceId)`, `publicsPourReparateur($id)`, `valider($avisId, $modId)`,
`supprimer()`.

> Les **avis publics** (fiche service, profil public) ne montrent que ceux **validés par un
> modérateur** (`moderateur_id IS NOT NULL`) : c'est ce qui rend la modération utile.

### 6.5 `Signalement.php` (79 lignes) — les problèmes signalés

`creer()`, `tousAvecInfos()`, `dernierApercu()` (5 derniers), `trouverDetail($id)`,
`changerStatut($id, $statut, $modId)`.

### 6.6 `Paiement.php` (23 lignes) — minimaliste

`existePourReservation()` (évite le double paiement) et `creer($montant, $moyen, $reservationId)`.
**Aucun numéro de carte, aucune donnée bancaire** n'est stockée.

### 6.7 `Reparateur.php` (37 lignes) — profil public et carte

`profilPublic($id)` (entreprise + position) et `avecCoordonnees()` (les réparateurs qui ont
renseigné latitude/longitude → base de la carte Leaflet).

---

## 7. Les contrôleurs (app/Controllers)

### 7.1 `AuthController.php` (245 lignes)

- `connexion()` : vérifie → `Auth::login()` → redirige (réparateur vers son espace, modérateur
  vers son tableau de bord, sinon accueil).
- `inscription()` : champs requis, email valide, **politique de mot de passe**, confirmation,
  **acceptation des CGU obligatoire**, email unique → création + redirection vers le mot secret.
- `deconnexion()`, `motDePasseOublie()` (récupération **par mot secret** — le flux par lien token a été supprimé : code mort)
  (par token), `creerMotSecret()`.

### 7.2 `HomeController.php` (37 lignes)

- `index()` : liste publique des services.
- `carte()` : récupère les réparateurs géolocalisés **et** regroupe leurs services
  (`$parReparateur[$id][] = $service`) pour les bulles et les cercles de rayon.

### 7.3 `ProfilController.php` (118 lignes) — un contrôleur, trois formulaires

Un seul `index()` traite trois actions différentes via un champ caché `action` :
- `profil` : informations personnelles ;
- `devenir_reparateur` / `infos_reparateur` : entreprise, SIRET (regex 14 chiffres),
  latitude (−90..90), longitude (−180..180) ;
- `mot_secret` : l'**ancien** est obligatoire avant d'en définir un nouveau.

### 7.4 `ReparateurController.php` (215 lignes)

`profilPublic($id)` (public, sans connexion), `accueil()` (3 tableaux + boutons
confirmer/annuler/terminer), `ajouterService()`, `mesServices()` (avec suppression + suppression
du fichier image associé), `editerService()` (remplace la photo et supprime l'ancienne), `mesGains()`.

### 7.5 `ReservationController.php` / `AvisController.php` / `SignalementController.php`

Le même schéma : vérifier que c'est **sa** réservation (`pouvoirAvis()`…) puis traiter le POST,
enfin rediriger avec un message flash.

### 7.6 `PaiementController.php` (120 lignes) — paiement à deux visages

```php
$stripeDispo = StripeGateway::configuree();
if ($this->isPost()) {
    if ($stripeDispo && $this->post('stripe') === '1') {
        … StripeGateway::creerSessionCheckout(...) → redirect vers Stripe
    } elseif (!$stripeDispo) {
        // simulation : on vérifie le moyen de paiement (liste blanche)
        Paiement::creer((float)$reservation['tarif'], $moyen, $reservationId);
    }
}
```

`validation()` est le **retour** de Stripe : on redemande à l'API si la session est bien payée
avant d'enregistrer quoi que ce soit.

### 7.7 `ModerateurController.php` (108 lignes)

`accueil()` (compteurs), `signalements()`, `signalementDetail()` (statut parmi
`ouvert / en_cours / resolu / rejete`), `avisModeration()` (valider / supprimer).

### 7.8 `PageController.php` (13 lignes)

Juste les CGU/RGPD (page statique).

---

## 8. Les vues (app/Views) — HTML + PHP, sans SQL

### 8.1 L'organisation

`Views/layout/header.php` + la vue + `Views/layout/footer.php` : c'est le **gabarit** commun
(le « layout »), imposé par `Controller::render()`.

**Il existe deux layouts** (4ᵉ paramètre de `render()`) :

| `$layout` | Rendu | Utilisé pour |
|---|---|---|
| `'header'` (défaut) | barre de navigation en haut | pages publiques : accueil, carte, fiche service, profil public, CGU, connexion |
| `'sidebar'` | **menu latéral gauche** + top bar | tous les tableaux de bord : client, réparateur, modérateur |

```php
// dans un contrôleur
$this->render('profil/index', $donnees, 'Mon profil', 'sidebar');
```

Quand `$layout === 'sidebar'`, le contrôleur de base insère `Views/layout/sidebar.php`
qui ouvre `<div class="dashboard"><aside class="sidebar">…</aside><main>`,
et `footer.php` referme ces balises (`$GLOBALS['layoutSidebar']`).

**Le contenu de la sidebar dépend du profil actif** (`Auth::roleActif()`), et le CSS colore
le dashboard selon `body[data-role]` : client = bleu, réparateur = vert, modérateur = violet.
Un compte ayant plusieurs rôles (client **et** réparateur) voit le bloc « Changer de profil »
qui appelle `basculerProfil&role=…` → `Auth::setRoleActif()` → redirection vers l'espace du
profil choisi.

`header.php` fait deux choses :
1. **la garde du mot secret** : si l'utilisateur est connecté sans mot secret, il est redirigé ;
2. **la navigation adaptée au rôle** (liens réparateur / client / modérateur).

### 8.2 Le style d'une vue

```php
<h1><?= e($service['titre']) ?></p>          // e() = échappement
<?php if (!empty($avis)): ?>
    <?php foreach ($avis as $a): ?>          // boucle
        <div class="card avis-item">
            <strong><?= str_repeat('⭐', (int)$a['note']) ?></strong>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
```

Règles des vues :
- toute donnée venant de la base passe par `e()` ;
- les liens passent par `url('route', ['id' => …])` ;
- les formulaires POST **n'ont pas d'attribut `action`** : la requête revient sur la même URL,
  donc la route est conservée automatiquement ;
- les images de service utilisent le chemin stocké en base (`assets/uploads/…`).

### 8.3 La vue `home/carte.php` : la seule vue avec du JavaScript

Elle reçoit `$reparateurs` et injecte du **JSON** sécurisé :

```php
const reparateurs = <?= json_encode($reparateurs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const carte = L.map('carte').setView([46.6, 2.4], 6);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {…}).addTo(carte);
```

Puis, pour chaque réparateur : **un cercle par service** (rayon d'action = `rayon × 1000` mètres)
et un marqueur avec une bulle (nom, ville, services, lien vers le profil public).
Leaflet est chargé par CDN (`unpkg.com`) — c'est la seule dépendance externe du projet.

---

## 9. La base de données (8 tables)

```
utilisateur (supertype : identité + sécurité)
   │  1,1
   ├── client      (rôle)          ──┐
   ├── reparateur  (rôle + entreprise + lat/lng) ─┐
   └── moderateur  (rôle)          ──┐            │
                                     │            │
service (reparateur_id) ─────────────┘            │
   │                                                │
   └── reservation (client_id, service_id) ────────┘
         │                    │
         ├── paiement        └── avis (moderateur_id NULL = à modérer)
         └── signalement (client_id, moderateur_id)
```

| Table | Contenu | Points importants |
|---|---|---|
| `utilisateur` | identité commune + mot de passe **hashé** + mot secret hashé | compteurs anti force brute, `cgu_acceptee_at` / `cgu_version` (preuve RGPD) |
| `client` / `reparateur` / `moderateur` | **spécialisation** du rôle | `id_utilisateur` = PK = FK, `ON DELETE CASCADE` |
| `reparateur` | entreprise, SIRET, description, `latitude` / `longitude` DECIMAL(10,7) | la carte n'affiche que ceux qui ont des coordonnées |
| `service` | titre, description, tarif, rayon, image | FK `reparateur_id` |
| `reservation` | adresse, date, statut `ENUM('en_attente','confirmee','annulee','terminee')` | FK client + service |
| `paiement` | montant, moyen, statut | `reservation_id UNIQUE` → 1 paiement max |
| `avis` | note 1-5 + commentaire | `reservation_id UNIQUE`, `CHECK (note BETWEEN 1 AND 5)`, `moderateur_id NULL` = en attente |
| `signalement` | motif, description, statut | `moderateur_id NULL` = non traité |

**Le « modèle à spécialisation »** (supertype + sous-tables) est un modèle classique de MERISE :
les attributs communs sont_factorisés dans `utilisateur`, chaque rôle a sa table. Cela évite
d'avoir trois tables qui répètent prénom/nom/email/mot de passe. Le « double rôle » est permis
puisqu'un utilisateur peut avoir une ligne dans `client` **et** dans `reparateur`.

---

## 10. La sécurité — les 8 mesures en place

| Risque | Mesure | Où |
|---|---|---|
| **XSS** (script injecté) | `e()` échappe **tout** ce qui vient de la base | `helpers.php` + toutes les vues |
| **Injection SQL** | requêtes **préparées** (`prepare` + `?`) partout | tous les modèles |
| **Mots de passe volés** | `password_hash()` (bcrypt) / `password_verify()` | `Utilisateur` |
| **Force brute** | 5 échecs de connexion → blocage 15 min | `Utilisateur::verifierConnexion` |
| **Force brute (mot secret)** | 3 échecs → blocage 15 min (changement et récupération) | `Utilisateur::verifierAncienMotSecret`, `reinitialiserParMotSecret` |
| **Énumération de comptes** | message identique « Email ou mot secret incorrect » | `AuthController::motDePasseOublie` |
| **Fichiers piégés** | `gererUpload()` : extension **et** type MIME **et** `getimagesize()` **et** taille ≤ 2 Mo, nom regénéré (`srv_<uniqid>.jpg`) | `helpers.php` |
| **Fuite / dépendance** | contrôleurs protégés par `requireLogin/Reparateur/Moderateur` **et** vérification du propriétaire dans le SQL | contrôleurs + modèles |
| **Données bancaires** | jamais stockées : Stripe Checkout ou simulation | `StripeGateway` |

**La politique de mot de passe en une regex** (CNIL/RGPD) :

```php
$regexPolitique = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,72}$/';
//                     minuscule   majuscule   chiffre     caractère spécial    8 à 72
```

Si la regex échoue, on refait des tests simples pour dire **précisément** ce qui manque
(« doit contenir au moins une majuscule », etc.).

---

## 11. Les parcours complets, pas à pas

### 11.1 Inscription (client)

```
1. GET  ?route=inscription            → formulaire
2. POST ?route=inscription            → vérifications :
   ├ champs requis, format email (filter_var)
   ├ validerMotDePasse() (regex)
   ├ confirmation identique
   ├ CGU acceptées (case obligatoire)  → sinon refus
   └ email déjà utilisé ?             → sinon refus
3. Utilisateur::creerClient()         → transaction : utilisateur + client + trace CGU
4. Auth::login()                      → session créée
5. redirect('creerMotSecret')         → le header interdit toute autre page tant que
                                       le mot secret n'est pas défini
```

### 11.2 Réservation → paiement → avis → signalement

```
Fiche service (?route=service&id=4) → « Réserver »
  → reservationNouvelle : adresse + date → statut 'en_attente'
  → mes réservations : bouton « Payer »
  → paiement : simulation OU Stripe Checkout
       (retour) → paiementValidation → on vérifie 'paid' → paiement enregistré
  → le réparateur « Marque terminée » → statut 'terminee'
  → le client : « Signaler » (problème) et/ou « Laisser un avis » (1 seul)
  → le modérateur : modère l'avis (il devient public) et traite le signalement
```

### 11.3 Espace réparateur

```
?route=reparateurAccueil
  ├ Demandes en attente  → Confirmer / Annuler
  ├ RDVs à venir         → Marquer terminée / Annuler
  └ Historique           (terminées, annulées, RDVs passés)
?route=ajouterService   → titre, description, tarif, rayon, photo (upload)
?route=mesServices      → Modifier / Supprimer
?route=mesGains         → CA, encaissé, nb interventions, détail
```

---

## 12. Le CSS (public/assets/css/style.css — 520 lignes)

Le style est construit autour de **variables CSS** (le thème « vert écolo ») :

```css
:root {
    --vert-900: #14352a;   --vert-700: #256c50;   --vert-500: #34a56b;
    --vert-300: #74c69d;   --vert-100: #d8f3dc;   --vert-50:  #f1f8f0;
    --degrade: linear-gradient(135deg, #2f8f5b, #1c5c3a);
    --radius: 14px;  --ombre: 0 6px 24px rgba(20,53,42,.08);
}
```

Classes principales : `.navbar` (collante, verre dépoli), `.hero` (bandeau d'accueil),
`.eco-points`, `.engagement`, `.grid`/`.card`, `.btn` (+ `-secondaire`, `-danger`, `-small`),
`.form`, `.table`, `.badge` (+ variantes de statut), `.alert` (`-error`/`-success`/`-info`),
`.stat-card`, `.avis-item`, `.map`, `.footer`.

Le thème **moderne** vient de : coins arrondis, ombres douces, `hover` avec élévation
(`transform: translateY(-4px)`), dégradés, transitions, et un bloc `@media` pour le mobile.

**Images** : `assets/img/hero-accueil.jpg` (cycliste), `velo-reparation.jpg` (atelier),
`velo-vert.jpg` (vélo de ville), `favicon.svg` (logo vélo). Les crédits photo (licences CC)
figurent dans le pied de page.

---

## 13. « Les 10 choses à retenir » pour la soutenance

1. **MVC** : contrôleur = logique, modèle = SQL, vue = HTML. Le front controller route tout.
2. **Autoloader** : pas de `require` à la main, PHP charge les classes tout seul.
3. **Singleton PDO** : une seule connexion, `Database::get()`.
4. **Requêtes préparées** partout → plus aucune injection SQL possible.
5. **`e()` partout** → plus de XSS.
6. **`password_hash`** → jamais de mot de passe en clair.
7. **Rôles multiples** (client + réparateur) via 3 tables de spécialisation.
8. **Garde-fous** : `requireX()` dans les contrôleurs + propriétaire vérifié dans le SQL.
9. **Mot secret** : récupération du mot de passe + 3 tentatives avant blocage.
10. **Paiement** : Stripe Checkout (mode test) si clé configurée, simulation sinon, et
    **jamais de données bancaires** dans la base.

---

## 14. Glossaire

| Terme | Signification |
|---|---|
| **POO** | Programmation Orientée Objet (classes, objets, héritage) |
| **MVC** | Modèle / Vue / Contrôleur |
| **Front controller** | le point d'entrée unique qui reçoit toutes les requêtes |
| **Route** | le lien qui associe une URL à une action (`?route=carte`) |
| **Requête préparée** | requête SQL avec des `?` remplis par PDO (sûre) |
| **Singleton** | une seule instance pour toute l'application |
| **Session** | mémoire du serveur liée au navigateur (utilisateur connecté, messages flash) |
| **Flash** | message affiché une seule fois après une redirection |
| **Échappement HTML** | `e()` : transformer `<` en `&lt;` (anti XSS) |
| **Anti force brute** | blocage temporaire après plusieurs échecs |
| **Transaction** | suite d'opérations « tout ou rien » (`beginTransaction` / `commit` / `rollBack`) |
| **FK / CASCADE** | clé étrangère ; la suppression du parent supprime les lignes filles |
| **ENUM** | colonne à valeurs autorisées |
| **Modèle à spécialisation** | une table « mère » (utilisateur) + une table par rôle |
| **JSON_HEX_APOS** | option de `json_encode` qui échappe les apostrophes dans du JS |

---

## 15. Comment tester chaque fonctionnalité (chemin de démonstration)

1. **Accueil** : hero, points éco, section engagement, grille de services.
2. **Carte** : deux réparateurs (Paris, Lyon), un cercle par service, clic sur une bulle → profil public.
3. **S'inscrire** : tried un mot de passe faible → message précis de la regex ; refuser les CGU → refus.
4. **Se connecter** : 5 mauvais essais → « Réessayez après HH:MM ».
5. **Mot secret** : enchaîner 3 mauvais anciens secrets → blocage 15 min.
6. **Réserver** un service puis **payer** (mode simulation : carte / espèces / virement).
7. **Espace réparateur** : confirmer le RDV, le marquer terminé, ajouter un service avec photo, voir les gains.
8. **Laisser un avis** + **signaler** un problème.
9. **Espace modérateur** : valider l'avis (il devient public sur la fiche service et le profil
   réparateur), traiter le signalement.
10. **Profil** : devenir réparateur, renseigner latitude/longitude → apparaît sur la carte.

---

*Ce guide est un complément à `CONTEXTE-PROJET.md` (qui décrit l'état d'avancement et les décisions).*
