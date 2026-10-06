# Fair'repair — Contexte projet (pour les sessions OpenCode)

> Document de référence chargé automatiquement dans les sessions OpenCode ouvertes dans ce dossier.
> 📖 **Guide du code** : `GUIDE-CODE.md` explique en détail tout le code (MVC, fichiers, SQL, sécurité, parcours).
> Dernière mise à jour : 06/10/2026 (refonte **POO + MVC** + thème vert écolo + **tableau de bord par profil** + **« se souvenir de moi »** + **dépôt Git** + nettoyage du code mort).

## Organisation des fichiers (refonte MVC le 23/09/2026)

```
Fair'repair/
├── docs/                      ← conception : Cahier_des_charges_Fairepair.docx, Fairepair.pdf, usecase_fairepair.drawio
├── base-de-donnees/
│   └── fairepair_bdd.sql      ← script SQL à jour (schéma complet)
├── code/                      ← L'application web (MVC + POO)
│   ├── app/                   ← le code applicatif (hors DocumentRoot)
│   │   ├── Core/              ← Database (singleton PDO), Router, Controller (base), Auth, helpers.php
│   │   ├── Models/            ← Utilisateur, Service, Reservation, Paiement, Avis, Signalement
│   │   ├── Controllers/       ← Home, Auth, Page, Profil, Service, Reservation, Paiement,
│   │   │                        Avis, Signalement, Reparateur, Moderateur (11)
│   │   └── Views/             ← layout/header.php + footer.php + 1 dossier par contrôleur
│   ├── public/                ← DocumentRoot (serveur local) : index.php (front controller) + assets/ (css, uploads)
│   ├── config/config.php      ← retourne un tableau de config (host 127.0.0.1, base fairepair…)
│   ├── index.php              ← shim « compatibilité Apache/XAMPP » → require public/index.php
│   ├── assets -> public/assets (symlink, même but)
│   └── _backup_procedural/    ← ANCIENNES pages PHP procédurales + includes/ (archivées, plus utilisées)
└── CONTEXTE-PROJET.md, CONVERSATION_FAIRREPAIR.md, opencode.jsonc
```

Routeur centralisé : **chaque page = une route** `index.php?route=...` gérée dans
`public/index.php` (tableau de routes → Contrôleur@action) :

| Route | Page |
|---|---|
| `accueil` | liste publique des services |
| `connexion`, `inscription`, `deconnexion`, `motDePasseOublie` (récupération **par mot secret**), `creerMotSecret`, `deconnexion` | authentification |
| `cgu` | CGU + RGPD |
| `profil` | données perso, devenir réparateur, entreprise (lat/lng pour la carte), changement mot secret |
| `service&id=` | fiche publique d'un service (avis validés affichés) |
| `carte` | carte Leaflet/OpenStreetMap des réparateurs géolocalisés (publique) |
| `reparateurProfil&id=` | profil public d'un réparateur (entreprise, services, avis) |
| `reservationNouvelle&service_id=`, `mesReservations`, `reservationAnnuler&id=` | réservations client |
| `paiement&reservation_id=`, `paiementValidation` (retour Stripe), `avis&reservation_id=`, `signalement&reservation_id=` | paiement / avis / signalement |
| `reparateurAccueil`, `ajouterService`, `mesServices`, `editerService&id=`, `mesGains` | espace réparateur |
| `moderateurAccueil`, `signalements`, `signalementDetail&id=`, `avisModeration` | espace modérateur |

Le bootstrap (`app/bootstrap.php`) : sessions, autoloader `spl_autoload_register`
(`App\X` → `app/X.php`), helpers globaux (`e()`, `url()`, `absoluteUrl()`, `config()`, `setFlash()`, `validerMotDePasse()`,
`gererUpload()`, constantes CGU…). Les vues n'appellent plus que des helpers + modèles ;
l'accès PDO passe par `Database::get()` (aucun `$pdo` global).

## Accès en local (tests)

- **Relance rapide** (port 8000, docroot = `public/`) :
  ```bash
  php -S 127.0.0.1:8000 -t ~/Documents/Fair'repair/code/public
  ```
  → ouvrir `http://127.0.0.1:8000/` (serveur déjà lancé, log `/tmp/opencode/php-serveur.log`).
  Exemple : connexion = `http://127.0.0.1:8000/index.php?route=connexion`.
- **URL (XAMPP Apache, si XAMPP est relancé en root)** : `http://localhost/fairepair/Fair'repair/code/`
  → `/opt/lampp/htdocs/fairepair/Fair'repair` est un **lien symbolique** vers ce dossier.
  Le DocumentRoot Apache mène à `code/` : le shim `index.php` + le symlink `assets` maintiennent
  le routage et les CSS fonctionnels sans modifier Apache ; idéalement, pointer le DocumentRoot
  sur `code/public/`. Pour relancer XAMPP : `sudo systemctl stop apache2 mariadb` puis
  `sudo /opt/lampp/lampp start`.
- **Comptes de test (créés via le formulaire d'inscription réel → CGU acceptée + mdp RGPD + mot secret ; ID réels après resync base du 25/09)** :
  - client `client@test.fr` / `Client123!` — mot secret `soleil` (Rose, **id 2**)
  - réparateur `reparateur@test.fr` / `Reparateur123!` — mot secret `velo2026` (Marc, **id 1**, « Atelier Cycles Durand », Paris 48.8566/2.3522) — **double rôle client + réparateur** (nécessaire pour la démo du bouton « changer de profil »)
  - réparateur `thomas@test.fr` / `Secret123!` — (Thomas, **id 5**, « Répar'Vélos Lyon », Lyon 45.7640/4.8357, créé via SQL pour la carte)
  - modérateur `moderateur@test.fr` / `Moderateur123!` — mot secret `aura2026` (Laura, **id 3**)
  - `alex.durand@test.fr` (**id 4**) : client + réparateur (démos « devenir réparateur »), pas de géoloc → absent de la carte
- ⚠️ `config/config.php` utilise `127.0.0.1` (TCP) pour être compatible PHP XAMPP ET PHP système.
  ⚠️ Le dossier Apache `fairepair/Fair'repair` dépend du droit de traverser `/home` (réglé via `chmod o+x /home/...`).

## Le projet en une phrase

**Fair'repair** est le projet de BTS SIO SLAM (1ʳᵉ année) de Rose : une **plateforme web de mise en relation entre cyclistes et réparateurs de vélos à domicile**, inspirée du cahier des charges d'une startup fictive parisienne.

## Stack technique

- **POO + MVC maison (refonte du 23/09/2026)** : tout le SQL est regroupé dans les modèles,
  les contrôleurs gèrent les entrées utilisateur, les vues ne font que de l'affichage.
  Pas de framework externe.
- **PHP 8 + PDO** (requêtes préparées), `password_hash()`/`password_verify()` (bcrypt).
- **MySQL** (`fairepair`, utf8mb4) — compatible MariaDB système ET XAMPP (`127.0.0.1` TCP).
- Sessions PHP pour l'auth, messages flash, échappement `e()` (anti-XSS), upload sécurisé.
- HTML/CSS simple (`public/assets/css/style.css`).
- L'ancienne version procédurale est archivée dans `code/_backup_procedural/`.

## Modèle de données (script `fairepair_bdd.sql` — à jour)

Spécialisation exclusive : `utilisateur` (id, prenom, nom, adresse NULL, code_postal NULL, ville NULL, email UNIQUE, telephone NULL, mot_de_passe hashé, **mot_secret hashé**, date_creation, **tentatives_connexion**, **blocage_jusqua**, **tentatives_secret**, **blocage_secret_jusqua**, **cgu_acceptee_at**, **cgu_version**) → tables `client`, `reparateur`, `moderateur` (PK = FK vers `utilisateur.id`, ON DELETE CASCADE). **Un compte peut être client ET réparateur (double rôle)**.

| Table | Clés étrangères / contraintes clés |
|---|---|
| `reparateur` | `id_utilisateur` → utilisateur ; infos entreprise : `nom_entreprise`, `siret` (14 chiffres), `description_pro`, **`latitude`/`longitude` DECIMAL(10,7)** (pour la carte) |
| `service` | `reparateur_id` → reparateur ; titre, description, tarif DECIMAL(6,2), rayon (km), image |
| `reservation` | `client_id`, `service_id` ; statut ENUM(en_attente/confirmee/annulee/terminee) ; adresse + date intervention |
| `paiement` | `reservation_id` **UNIQUE** (0,1 par réservation) ; moyen_paiement ENUM(carte/especes/virement) |
| `avis` | `reservation_id` **UNIQUE** ; note 1–5 (CHECK) ; `moderateur_id` NULL tant que non modéré |
| `signalement` | `reservation_id`, `client_id`, `moderateur_id` NULL ; statut ENUM(ouvert/en_cours/resolu/rejete) |

**À retenir pour la soutenance :**
- « 1 paiement / 1 avis maximum par réservation » = contrainte UNIQUE en base.
- L'inscription crée toujours un compte **client** ; l'adresse/la ville/le téléphone sont complétés au profil.
- **Passage client → réparateur** depuis le profil (action `devenir_reparateur`, `nom_entreprise`, `siret`, `description_pro`) : la ligne `client` est conservée (double rôle possible).
- Multi-rôles : `Auth::roles()` / helpers `rolesUtilisateur()`, `estClient()`, `estReparateur()`, `estModerateur()` ; menu adaptatif du header selon les rôles.
- **Récupération du mot de passe par mot secret** (route `motDePasseOublie`) : message d'erreur générique (anti-énumération). Le flux « lien par token email » a été **supprimé le 06/10** (rien ne créait de token : code mort).
- **Mot secret OBLIGATOIRE après l'inscription** : l'inscription connecte et redirige vers `creerMotSecret` ; tant que `mot_secret` est NULL, le header redirige toutes les routes (sauf `creerMotSecret` et `deconnexion`). Testé.
- **Changement du mot secret sécurisé** (profil, action `mot_secret`) : l'**ancien** mot secret est obligatoire (`password_verify`), puis nouveau + confirmation. Testé : mauvais ancien → refus ; bon ancien → changement effectif.
- **Blocage après 5 échecs de connexion** : `tentatives_connexion` + `blocage_jusqua` ; après 5 échecs, blocage 15 min puis remise à zéro ; connexion réussie = reset du compteur.
- **Blocage du mot secret après 3 échecs** (nouveau) : `tentatives_secret` + `blocage_secret_jusqua` ; appliqué au **changement** du mot secret (ancien obligatoire) ET à la **récupération** du mot de passe (`motDePasseOublie`). Après 3 échecs → 15 min de blocage, message « Réessayez après HH:MM » ; succès = reset. Testé en HTTP.
- **Mot de passe CNIL/RGPD** : `validerMotDePasse()` — **UNE seule regex** fait foi (`/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,72}$/`) + messages détaillés (majuscule/minuscule/chiffre/spécial/longueur) ; appliqué à l'inscription ET aux réinitialisations.
- **CGU obligatoires + consentement tracé (art. 7 RGPD)** : page `cgu`, case obligatoire à l'inscription, trace `cgu_acceptee_at` + `cgu_version` ; liens CGU/RGPD dans le footer.
- Mots de passe et mot secret **toujours hachés** ; `ON DELETE CASCADE` pour utilisateur/service ; `SET NULL` pour moderateur_id.

## État d'avancement (23/09/2026)

**✅ Fait**
- Cahier des charges complet (`Cahier_des_charges_Fairepair.docx` + `Fairepair.pdf`) : sections fonctionnelles 2.1 client, 2.2 réparateur, 2.3 modérateur ; MCD + MLD + MPD.
- Use case : `usecase_fairepair.drawio` (3 acteurs).
- Base de données : `fairepair_bdd.sql` complet (mot_secret, compteurs de blocage, cgu_acceptee_at/cgu_version, colonnes reparateur latitude/longitude, table `auth_remember`).
- **Refonte POO + MVC (23/09/2026) — testée de bout en bout :**
  - Routeur `?route=...` (27 routes → 11 contrôleurs), autoloader `App\`, singleton `Database`, `Auth` (session/rôles/gardes), helpers `e`/`url`/`setFlash`/`validerMotDePasse`/`gererUpload` repris.
  - 6 modèles encapsulant tout le SQL (Utilisateur avec blocage 5 tentatives + politique mdp + mot secret + trace CGU ; Service ; Reservation ; Paiement ; Avis ; Signalement).
  - Layout header/footer MVC (garde mot secret par route, flash, nav par rôle).
  - **Tous les parcours validés en HTTP sur `127.0.0.1:8000`** : accueil/CGU/404 ; connexion (bonne + mauvaise) ; inscription sans CGU refusée, avec CGU → création + redirection mot secret ; garde mot secret (accès bloqué avant définition) ; définition mot secret ; changement mot secret (mauvais ancien refusé) ; réinit mdp oublié (mauvais secret refusé) ; réservation → paiement (carte, en base) → signalement → confirmation/terminaison réparateur → avis (uniquement si terminée) → validation par le modérateur ; pages réparateur (accueil, ajouter/modifier/supprimer service, gains) et modérateur (dashboard, signalements, détail, avis) ; gardes de rôle (client refusé sur espace réparateur/modérateur).
  - Anciennes pages procédurales archivées dans `_backup_procedural/` ; assets déplacés dans `public/assets/` (+ symlink et shim `index.php` pour compat Apache).
- Côté client : services, réservation (création/liste/annulation limitée `en_attente`), paiement simulé (1 max), avis (1 max, réservation terminée), signalement, profil (infos + devenir réparateur + mot secret).
- Côté réparateur : accueil RDVs (confirmer/annuler/terminer, garde-fou `reparateur_id`), services (ajouter/modifier/supprimer, upload sécurisé JPG/PNG/WebP/GIF ≤ 2 Mo), gains (CA, encaissé, détail paiements).
- Côté modérateur : tableau de bord, signalements (liste + détail + statuts ouvert/en_cours/resolu/rejete avec attribution `moderateur_id`), modération des avis (valider/supprimer, section « déjà modérés »).

**✅ Ajouts du 23/09/2026 (soir)**
- **Avis publics** : les avis validés par le modérateur sont affichés publiquement sur la **fiche service** (`Avis::publicsPourService`) et sur le **profil réparateur** (`Avis::publicsPourReparateur`). Testé (avis existant « Reparation rapide… » visible).
- **Profil public réparateur** : route `reparateurProfil&id=` → entreprise, position, services, avis validés ; liens depuis l'accueil, la fiche service et les bulles de la carte. Testé (Marc id 1, Thomas id 5, id inconnu → 302).
- **Carte des réparateurs** : route `carte`, Leaflet + fond OpenStreetMap (CDN), un marqueur par réparateur géolocalisé (lat/lng renseignés dans « Mon entreprise », colonnes `latitude`/`longitude` DECIMAL(10,7)) + un **cercle par service** représentant son rayon d'action ; lien profil dans les bulles. Testé (Marc Paris + Thomas Lyon visibles).
- **Paiement Stripe (mode test)** : `StripeGateway` (app/Core, cURL sans SDK), Checkout Session + vérification `payment_status=paid` au retour (route `paiementValidation`). Rien de bancaire n'est stocké. **Clé `stripe.secret_key` vide → repli automatique sur la simulation** (testée) ; clé `sk_test_…` renseignée dans `config/config.php` → vrai tunnel (testé avec clé bidon : page Checkout affichée + erreur propre « Impossible de contacter Stripe »).
- **Mot de passe : politique en UNE regex** (`validerMotDePasse`) + messages détaillés (tests unitaires OK).
- **Mot secret : 3 tentatives max → blocage 15 min** (`tentatives_secret`, `blocage_secret_jusqua`), appliqué au changement (profil) et à la récupération du mdp. Testé : 4ᵉ essai refusé avec « Réessayez après 16:30 », récupération bloque, mdp non modifié, compteurs reset.
- **Nettoyage soutenance** : `base-de-donnees/nettoyage_soutenance.sql` (supprime `*@test.fr` par cascade FK) ; les scripts `creer_test_*.php` ont été retirés.
- **Nettoyage du code mort (06/10)** : suppression de `Auth::requireRoleActif()` (jamais appelée), du **flux « reset par token »** (route `reinitialiserMotDePasse`, vue associée, `Utilisateur::trouverParToken()` / `majMotDePasseParToken()`, colonnes `reset_token` / `reset_expires_at` — rien ne créait de token, la récupération se fait par mot secret) et de 7 classes `.badge-*` non utilisées. Audit automatisé : **79 fonctions/méthodes, 0 morte ; 0 classe CSS orpheline**.
- ⚠️ **Bug corrigé le 06/10** : la réécriture du header avait supprimé le `<main class="container">` → les pages publiques n'étaient plus centrées. Le header ouvre désormais `main.container` pour le layout `header` (et `footer.php` le referme) ; le layout `sidebar` garde son propre `.dashboard`.
- **Dépôt Git (06/10)** : projet versionné et **publié sur GitHub** → **`https://github.com/roytitli119-design/Fairepair`** (dépôt public, branche `main`, remote `origin` en SSH `git@github.com:roytitli119-design/Fairepair.git`). Clé SSH générée sur ce PC (`~/.ssh/id_ed25519`, autorisée sur le compte GitHub `roytitli119-design`). `.gitignore` à la racine (fichiers système, photos envoyées dans `code/public/assets/uploads/`, logs). **Après chaque journée de travail** : `git add -A` puis `git commit -m "feat: …"` puis `git push`. ⚠️ Ne jamais committer une clé Stripe réelle : laisser `'secret_key' => ''`.
- **Thème « vert écolo » + logo (25/09)** : `style.css` entièrement refait (dégradés verts, cartes arrondies, ombres, hover, responsive) ; **logo vélo SVG** inline dans le header + **favicon** (`assets/img/favicon.svg`) ; section **hero** sur l'accueil avec photo `hero-accueil.jpg` + 3 points éco ; section « engagement » avec `velo-reparation.jpg` (atelier) et `velo-vert.jpg` ; **crédits photos au pied de page** (Wikimedia Commons / Flickr, CC BY-SA — toujours vérifier le **sujet réel** via l'API avant de télécharger, un ID deviné s'est révélé être une moto).
- ⚠️ **Resync base du 25/09** : la base live avait été restaurée à un état du 23/09 (14h10) sans lat/lng ni colonnes mot-secret ni CGU. Elle a été **ré-alignée** : `ALTER` `reparateur` (lat/lng), `utilisateur` (`tentatives_secret`, `blocage_secret_jusqua`, `cgu_acceptee_at`, `cgu_version`), mots secrets Marc/Laura réinjectés (hash bcrypt), Thomas ajouté. Si la base est réimportée depuis `fairepair_bdd.sql` (schéma seul, zéro seed), tout est recréé via l'application + le bloc SQL ci-dessus (documenté dans la conversation).
- **Tableau de bord + changement de profil (06/10)** : les pages privées ne sont plus rendues dans le gabarit `header` mais dans le layout **`sidebar`** (menu à gauche). `Controller::render()` a un 4ᵉ paramètre `$layout` ('header' | 'sidebar') ; `Views/layout/sidebar.php` (nouveau) affiche la carte profil, le menu **variable selon le profil actif** et le bouton « Changer de profil ». `Auth::roleActif()` / `setRoleActif()` mémorisent le profil choisi en session (`$_SESSION['role_actif']`, défaut réparateur > modérateur > client) ; routes `dashboard` (redirige vers l'espace du profil actif) et `basculerProfil&role=` (refusée si le rôle n'est pas possédé). Connexion → `dashboard`. `body[data-role]` + CSS : **3 thèmes** (client = bleu `#2563a8`, réparateur = vert `#2f8f5b`, modérateur = violet `#6d4aa8`) qui colorent sidebar, badge, titre actif et pastilles ; le site public reste vert. Testé : 3 profils × leurs pages (200), bascule client↔réparateur, refus d'un rôle non possédé, persistance en session, parcours « devenir réparateur » (bascule auto du profil).
- **« Se souvenir de moi » (06/10)** : case à cocher sur le formulaire de connexion. Table `auth_remember` (`user_id`, **`token_hash` CHAR(64)**, `expires_at`) + modèle `app/Models/AuthRemember.php` (4 méthodes : `creer`, `utilisateurIdDe`, `supprimer`, `supprimerTout`). Un jeton aléatoire de 64 hex (`random_bytes`) part dans le cookie `fairrepair_memoire` (30 j, `HttpOnly`, `SameSite=Lax`, `Secure` si HTTPS) ; **seul son SHA-256 est stocké** (pas de selector/validator : version simplifiée volontaire). `Auth::restore()` (appelé en fin de `app/bootstrap.php`) reconstruit la session, puis **fait tourner le jeton** (ancien supprimé, nouveau émis). Nettoyage à la déconnexion (`Auth::logout()`) et quand la case n'est pas cochée (`AuthRemember::supprimerTout`). Testé : cookie posé, auto-connexion sans `PHPSESSID`, rotation, case décochée → cookie + lignes supprimés, cookie trafiqué rejeté. ⚠️ Piège rencontré : `lireCookieMemorise()` validait l'ancien format `selector:validator` (donc `:`) et rejetait le nouveau jeton — le contrôle de format est désormais `/^[a-f0-9]{64}$/`.

**❌ Pas encore fait (prochaines étapes logiques)**
1. ⚠️ Bascule XAMPP : les ports 80/3306 sont pris par apache2/mariadbd système ; relancer XAMPP nécessite `sudo systemctl stop apache2 mariadb` + `sudo /opt/lampp/lampp start` (shims Apache déjà en place).
2. ⏳ Nettoyage final AVANT soutenance : les scripts `creer_test_*.php` ont été supprimés ; exécuter `base-de-donnees/nettoyage_soutenance.sql` juste avant le jour J (supprime les comptes `*@test.fr`) puis recréer les comptes de démo via la vraie application.
3. 🎨 Idées si le temps le permet : garde « on ne réserve pas son propre service », géocodage auto de la ville, pagination des avis.

## Consignes si tu continues ce projet

- Garde le **style MVC** : les requêtes SQL dans `app/Models/`, les entrées dans `app/Controllers/`, l'affichage dans `app/Views/`, sans `$pdo` global (`Database::get()`).
- Nouvelle page = nouvelle route dans `public/index.php` (`$routeur->add(...)`) → contrôleur → vue dans le layout.
- Liens internes via `url('route', [...])` ; échappement `e()` ; test en local avant de valider.
- Pour l'historique du projet côté client déjà construit : voir `CONVERSATION_FAIRREPAIR.md` et l'historique global dans `~/Documents/OpenCode-Contexte/`.