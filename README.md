# Fair'repair 🚲

> **Plateforme de réservation de réparation de vélos** — Projet de synthèse **BTS SIO SLAM**
> (Services Informatiques pour l'Organisation, option SLAM — 2ᵉ année)

Fair'repair met en relation des **clients** qui veulent faire réparer leur vélo et des
**réparateurs** de proximité qui proposent leurs services. Un **modérateur** veille à la
qualité (avis clients, signalements). L'ensemble est développé en **POO + MVC**, **sans
framework**, afin de maîtriser chaque couche.

| PHP | Base de données | Front | Cartographie | Paiement |
|---|---|---|---|---|
| 8.5 (POO, sans framework) | MariaDB 11 / MySQL 8 (PDO, requêtes préparées) | HTML5 & CSS3 (thème vert écolo) | Leaflet + OpenStreetMap | Stripe Checkout *(mode test)* |

**Volumétrie :** 57 fichiers PHP · 4 307 lignes de PHP · 656 lignes de CSS · 10 tables · 30 routes · 13 mesures de sécurité

---

## 📑 Sommaire

1. [Fonctionnalités](#-fonctionnalités)
2. [Captures d'écran](#-captures-décran)
3. [Architecture](#-architecture)
4. [Base de données](#-base-de-données)
5. [Installation](#-installation)
6. [Comptes de démonstration](#-comptes-de-démonstration)
7. [Sécurité](#-sécurité)
8. [Structure du projet](#-structure-du-projet)
9. [Versionnement (Git)](#-versionnement-git)
10. [Pistes d'évolution](#-pistes-dévolution)
11. [Crédits](#-crédits)

---

## ✨ Fonctionnalités

### Visiteur (sans compte)
- Page d'accueil presenting les services, avec une identité visuelle « éco-responsable ».
- **Carte des réparateurs** (Leaflet / OpenStreetMap) : un marqueur par atelier géolocalisé et
  **un cercle par service** représentant son rayon d'action.
- **Fiche publique d'un service** : description, tarif, rayon, note moyenne et **avis validés**.
- **Profil public d'un réparateur** : entreprise, description, position, services et avis.

### Client
- Inscription avec **acceptation des CGU tracée en base** (date + version).
- **Mot secret obligatoire** : il permet de récupérer son mot de passe (page de profil).
- Réservation d'un service (adresse + date), annulation tant qu'elle est en attente.
- **Paiement** : Stripe Checkout (mode test) si une clé API est fournie, sinon **simulation**
  (carte / espèces / virement). **Aucune donnée bancaire n'est stockée.**
- **Avis** (note 1-5 + commentaire) sur une réservation terminée — soumis à modération.
- **Signalement** d'un problème sur une intervention.
- Tableau de bord personnel (menu latéral, thème bleu).

### Réparateur
- **Devenir réparateur** depuis le profil (entreprise, SIRET, latitude/longitude).
- Publication / modification / suppression de services, avec **upload de photo sécurisé**.
- Tableau de bord : demandes en attente, rendez-vous à venir, historique.
- Changement du statut d'une réservation : **confirmer / annuler / terminer**.
- **Gains** : chiffre d'affaires, encaissé, nombre d'interventions, détail.
- Tableau de bord dédié (menu latéral, thème vert) — un compte peut être **client + réparateur**.

### Modérateur
- Tableau de bord : compteurs (utilisateurs, services, réservations, signalements, avis).
- **Signalements** : consultation détaillée et suivi du statut
  (`ouvert` → `en_cours` → `resolu` / `rejete`).
- **Modération des avis** : validation (l'avis devient public) ou suppression.
- Tableau de bord dédié (menu latéral, thème violet).

### Transverse
- **3 profils d'interface** avec un **bouton de changement de profil** (client ↔ réparateur).
- **« Se souvenir de moi »** : reconnexion automatique pendant 30 jours.
- Protection anti force brute, politique de mot de passe CNIL/RGPD, thème responsive (mobile).

---

## 📸 Captures d'écran

> *Section à compléter.* Pour ajouter des images :
> 1. lance le site (`php -S 127.0.0.1:8000 -t public` puis `http://127.0.0.1:8000/index.php?route=accueil`) ;
> 2. prends des captures d'écran et place-les dans `docs/captures/` ;
> 3. insère-les ici, par exemple :
>
> ```markdown
> | Accueil | Tableau de bord réparateur | Carte |
> |---|---|---|
> | ![Accueil](docs/captures/accueil.png) | ![Dashboard](docs/captures/dashboard.png) | ![Carte](docs/captures/carte.png) |
> ```
>
> Écrans utiles : accueil (hero + photos), fiche service avec avis, carte Leaflet,
> tableau de bord du réparateur, tableau de bord du modérateur, formulaire de connexion
> (case « se souvenir de moi »).

---

## 🏗 Architecture

Le projet suit le patron **MVC** avec un **front controller** unique : toutes les URLs
passent par `index.php?route=…`.

```
Navigateur : index.php?route=carte
        │
        ▼
public/index.php            ← FRONT CONTROLLER : table des routes (30 routes)
        │  « carte » → HomeController::carte()
        ▼
app/Controllers/            ← CONTRÔLEUR : vérifie, décide, appelle le modèle
        │
        ▼
app/Models/                 ← MODÈLE : SQL (requêtes préparées) — aucun HTML
        │
        ▼
app/Views/                  ← VUE : HTML échappé (e()) — aucun SQL
        │
        ▼
HTML → navigateur
```

**Les 3 rôles du front controller :** charger le noyau, associer chaque URL à une action,
lancer le contrôleur.

### Deux types de mise en page

| Layout | Utilisé pour | Rendu |
|---|---|---|
| `header` | pages publiques | barre de navigation en haut + contenu centré |
| `sidebar` | tous les tableaux de bord | **menu latéral gauche** + contenu, coloré selon le profil |

Le choix se fait via le 4ᵉ paramètre de `Controller::render()` :

```php
$this->render('profil/index', $donnees, 'Mon profil', 'sidebar');
```

### Points techniques notables

- **Autoloader** : `spl_autoload_register()` — `App\Models\Service` charge `app/Models/Service.php`.
- **Singleton PDO** : une seule connexion (`Database::get()`), `ERRMODE_EXCEPTION`, `FETCH_ASSOC`.
- **Profil actif** : `Auth::roleActif()` / `setRoleActif()` — un compte à plusieurs rôles
  change de vue d'un clic, mémorisé en session.
- **Thème par profil** : variables CSS pilotées par `body[data-role]`
  (client = bleu, réparateur = vert, modérateur = violet) ; le site public reste vert.

---

## 🗄 Base de données

10 tables InnoDB, modèle **à spécialisation** (supertype + tables de rôles).

```
utilisateur (identité, mot de passe hashé, mot secret, compteurs de blocage, trace CGU)
   ├── client      ──┐  rôles (id_utilisateur = PK/FK, ON DELETE CASCADE)
   ├── reparateur  ──┤  + entreprise, SIRET, latitude / longitude
   └── moderateur  ──┘
service (reparateur_id) ──► reservation (client_id, service_id)
                                  ├── paiement     (reservation_id UNIQUE → 1 paiement max)
                                  ├── avis         (UNIQUE + CHECK note 1-5, moderateur_id NULL = à modérer)
                                  └── signalement  (statut ouvert / en_cours / resolu / rejete)

auth_remember (« se souvenir de moi » : user_id, token_hash, expires_at)
```

Contraintes notables : `ENUM` pour les statuts, `UNIQUE` sur `email` et sur
`reservation_id` (paiement / avis), `CHECK (note BETWEEN 1 AND 5)`, `DECIMAL(6,2)` pour
les montants, `DECIMAL(10,7)` pour latitude/longitude, `ON DELETE CASCADE` sur toutes les clés
étrangères.

Script complet : [`base-de-donnees/fairepair_bdd.sql`](base-de-donnees/fairepair_bdd.sql)
(**schéma seul — il ne contient aucune donnée**, les comptes se créent via l'application).

---

## 🛠 Installation

### Prérequis
- **PHP ≥ 8.0** (testé sur PHP 8.5) — extension `pdo_mysql` (+ `curl` pour Stripe)
- **MariaDB ≥ 10.x** ou MySQL ≥ 8
- Un navigateur moderne

### 1. Créer la base de données

```bash
mysql -u root -p < base-de-donnees/fairepair_bdd.sql
```

### 2. Configurer l'application

Édite `code/config/config.php` :

```php
return [
    'host'   => '127.0.0.1',   // TCP : fonctionne avec XAMPP et MariaDB système
    'dbname' => 'fairepair',
    'user'   => 'ton_utilisateur_mysql',
    'pass'   => 'ton_mot_de_passe',
    'stripe' => [
        'secret_key' => '',    // clé sk_test_… = vrai Checkout ; vide = paiement simulé
    ],
];
```

> ⚠️ **Ne commitez jamais une clé Stripe réelle** dans Git : laissez `''`.

### 3. Lancer le site

```bash
cd code
php -S 127.0.0.1:8000 -t public
```

Puis ouvre <http://127.0.0.1:8000/index.php?route=accueil>

### 4. Créer un premier compte

1. **« Créer un compte »** → accepter les CGU (obligatoire) → un **mot secret** t'est demandé.
2. **Mon profil → Devenir réparateur** → renseigne entreprise, SIRET, latitude / longitude.

### Option Apache / XAMPP

Le dossier `code/public/` est le **DocumentRoot** recommandé. Si le DocumentRoot doit pointer
sur `code/`, un *shim* (`code/index.php`) renvoie automatiquement vers le front controller, et
le lien symbolique `code/assets` maintient les CSS/images accessibles.

---

## 🧪 Comptes de démonstration

Le script SQL ne contient **aucune donnée** (les comptes ont été créés via le formulaire
d'inscription, pour respecter la maquette et la trace de consentement). Voici les comptes utilisés pour les essais :

| Rôle | Email | Mot de passe | Mot secret |
|---|---|---|---|
| Client | `client@test.fr` | `Client123!` | `soleil` |
| Réparateur (Marc, Paris) | `reparateur@test.fr` | `Reparateur123!` | `velo2026` |
| Réparateur (Thomas, Lyon) | `thomas@test.fr` | `Secret123!` | — |
| Modérateur (Laura) | `moderateur@test.fr` | `Moderateur123!` | `aura2026` |

> `reparateur@test.fr` possède les rôles **client + réparateur** : c'est le compte à utiliser
> pour démontrer le **bouton de changement de profil**.

---

## 🔐 Sécurité

| Risque | Mesure mise en place |
|---|---|
| Injection SQL | **requêtes préparées** partout (`prepare` + `?`) |
| XSS | fonction `e()` sur **toutes** les données affichées |
| Mots de passe | `password_hash()` / `password_verify()` (bcrypt) — jamais en clair |
| Force brute (connexion) | 5 échecs → **blocage 15 min** (`tentatives_connexion`, `blocage_jusqua`) |
| Force brute (mot secret) | **3 échecs** → blocage 15 min (changement **et** récupération) |
| Énumération de comptes | message d'erreur identique (« Email ou mot secret incorrect ») |
| Fichiers piégés | `gererUpload()` : extension + type MIME + `getimagesize()` + 2 Mo + nom regénéré |
| Contrôle d'accès | gardes `requireLogin/Reparateur/Moderateur` **+** propriétaire vérifié dans le SQL |
| Vol de cookie | cookie `HttpOnly` + `SameSite=Lax` (+ `Secure` en HTTPS) |
| Reconnexion automatique | seul le **SHA-256** du jeton est stocké, **rotation** à chaque usage |
| Données bancaires | jamais stockées : la carte est saisie sur la page hébergée par Stripe, ou le paiement est simulé |
| RGPD | CGU acceptées **tracées** (`cgu_acceptee_at`, `cgu_version`) |

---

## 📁 Structure du projet

```
Fair'repair/
├── base-de-donnees/
│   ├── fairepair_bdd.sql        ← schéma complet (10 tables)
│   └── nettoyage_soutenance.sql ← supprime les comptes de démonstration
├── code/
│   ├── index.php                ← shim de compatibilité Apache
│   ├── config/config.php        ← configuration (base + Stripe)
│   ├── app/
│   │   ├── bootstrap.php        ← session, autoloader, helpers, restore()
│   │   ├── Core/                ← Database, Router, Controller, Auth, helpers, StripeGateway
│   │   ├── Models/              ← Utilisateur, Service, Reservation, Paiement, Avis,
│   │   │                          Signalement, Reparateur, AuthRemember
│   │   ├── Controllers/         ← 10 contrôleurs (Auth, Home, Page, Profil, Service,
│   │   │                          Reservation, Paiement, Avis, Signalement, Reparateur,
│   │   │                          Moderateur)
│   │   └── Views/               ← gabarits HTML (layout/, home/, auth/, service/…)
│   ├── public/                  ← DocumentRoot
│   │   ├── index.php            ← ★ front controller (table des routes)
│   │   └── assets/              ← css/style.css, img/, uploads/
│   └── _backup_procedural/      ← version procédurale d'origine (archivée)
├── docs/                        ← cahier des charges, diagrammes
├── CONTEXTE-PROJET.md           ← journal de bord du projet
└── GUIDE-CODE.md                ← explication détaillée de tout le code
```

---

## 🔀 Versionnement (Git)

- Branche : `main` · commits explicites (`feat:`, `fix:`, `docs:`, `refactor:`)
- `.gitignore` : fichiers système, **photos envoyées par les réparateurs**, logs.

Rituel de travail :

```bash
git add -A
git commit -m "feat : ma nouvelle fonctionnalité"
git push
```

> En cas d'erreur : `git log --oneline` puis `git checkout <commit> -- <fichier>` pour récupérer un fichier.

---

## 🚀 Pistes d'évolution

- Envoi d'un e-mail avec un lien de réinitialisation (le flux « mot secret » le remplace aujourd'hui).
- Pagination et tri des avis / signalements.
- Géocodage automatique à partir de l'adresse du réparateur.
- Tests unitaires sur `validerMotDePasse()`, `AuthRemember` et les méthodes de validation.
- Export PDF de la facture / du devis.
- Notifications (e-mail / SMS) lors du passage au statut « confirmée ».

---

## 🙏 Crédits

- **Photos** (licences libres, créditées dans le pied de page du site) :
  « Road cycling – riding a bike on the road » et « Bike workshop – bicycle repair shop »
  © Alextredz — CC BY-SA (Wikimedia Commons) ; « Green city bike & brown leather bag »
  © Jens Rost — CC BY-SA 2.0 (Flickr).
- **Carte** : Leaflet (BSD-2) + tuiles © les contributeurs d'OpenStreetMap (ODbL).
- **Logo** : icône vélo en SVG, dessinée pour le projet.
- **Paiement** : Stripe Checkout — utilisé **uniquement en mode test** (carte fictive
  `4242 4242 4242 4242`).

---

## 📄 Licence

Projet scolaire — **aucune licence publique** (aucun fichier `LICENSE`).
Code écrit de zéro, sans framework externe, dans le cadre d'une évaluation BTS SIO SLAM.
