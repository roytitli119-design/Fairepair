-- =========================================================
-- Base de données : Fair'repair
-- Projet BTS SIO SLAM
-- =========================================================

CREATE DATABASE IF NOT EXISTS fairepair
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fairepair;

-- =========================================================
-- Table UTILISATEUR (supertype)
-- Attributs communs à client, réparateur et modérateur
-- =========================================================
CREATE TABLE utilisateur (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    prenom         VARCHAR(50)  NOT NULL,
    nom            VARCHAR(50)  NOT NULL,
    -- Adresse/ville/téléphone : optionnels, complétés plus tard depuis le profil
    adresse        VARCHAR(150) NULL,
    code_postal    VARCHAR(10)  NULL,
    ville          VARCHAR(100) NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    telephone      VARCHAR(20)  NULL,
    mot_de_passe   VARCHAR(255) NOT NULL, -- toujours stocker un hash (password_hash en PHP), jamais en clair
    mot_secret     VARCHAR(255) NULL,     -- hash d'une question/réponse secrète pour récupérer le mdp oublié
    date_creation  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tentatives_connexion INT NOT NULL DEFAULT 0, -- nb d'échecs de connexion consécutifs
    blocage_jusqua DATETIME NULL,              -- blocage temporaire après 5 échecs (15 min)
    tentatives_secret INT NOT NULL DEFAULT 0,   -- nb d'échecs sur le mot secret (3 max → blocage)
    blocage_secret_jusqua DATETIME NULL,        -- blocage temporaire du mot secret après 3 échecs (15 min)
    cgu_acceptee_at DATETIME NULL,             -- trace RGPD : date d'acceptation des CGU
    cgu_version     VARCHAR(10) NULL           -- trace RGPD : version des CGU acceptées
) ENGINE=InnoDB;

-- =========================================================
-- Tables de spécialisation (client / réparateur / modérateur)
-- Chacune partage sa clé primaire avec UTILISATEUR (id_utilisateur = id)
-- La contrainte "un utilisateur = un seul rôle" (totale exclusive)
-- est gérée côté application : à la création du compte, on insère
-- une ligne dans UTILISATEUR puis UNE SEULE ligne dans la table
-- de spécialisation correspondante.
-- =========================================================
CREATE TABLE client (
    id_utilisateur INT PRIMARY KEY,
    CONSTRAINT fk_client_utilisateur
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reparateur (
    id_utilisateur  INT PRIMARY KEY,
    -- Informations professionnelles (complétées au moment de devenir réparateur)
    nom_entreprise  VARCHAR(150) NULL,
    siret           VARCHAR(14)  NULL, -- 14 chiffres (SIREN + NIC)
    description_pro TEXT         NULL,
    -- Géolocalisation (optionnelle) : sert à l'affichage sur la carte Leaflet
    latitude        DECIMAL(10,7) NULL, -- -90 à 90
    longitude       DECIMAL(10,7) NULL, -- -180 à 180
    CONSTRAINT fk_reparateur_utilisateur
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE moderateur (
    id_utilisateur INT PRIMARY KEY,
    CONSTRAINT fk_moderateur_utilisateur
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- Table SERVICE
-- Proposé par un réparateur
-- =========================================================
CREATE TABLE service (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    titre          VARCHAR(100)   NOT NULL,
    description    TEXT           NOT NULL,
    tarif          DECIMAL(6,2)   NOT NULL,
    rayon          INT            NOT NULL, -- rayon d'action en km
    image          VARCHAR(255),            -- chemin/nom du fichier image
    reparateur_id  INT            NOT NULL,
    date_creation  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_service_reparateur
        FOREIGN KEY (reparateur_id) REFERENCES reparateur(id_utilisateur)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- Table RESERVATION
-- Effectuée par un client, concerne un service
-- =========================================================
CREATE TABLE reservation (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    adresse_intervention VARCHAR(150) NOT NULL,
    date_intervention    DATETIME     NOT NULL,
    statut               ENUM('en_attente', 'confirmee', 'annulee', 'terminee')
                          NOT NULL DEFAULT 'en_attente',
    client_id            INT NOT NULL,
    service_id           INT NOT NULL,
    date_creation        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reservation_client
        FOREIGN KEY (client_id) REFERENCES client(id_utilisateur)
        ON DELETE CASCADE,

    CONSTRAINT fk_reservation_service
        FOREIGN KEY (service_id) REFERENCES service(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- Table PAIEMENT
-- Règle une réservation (0 ou 1 paiement par réservation -> UNIQUE)
-- =========================================================
CREATE TABLE paiement (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    montant        DECIMAL(6,2) NOT NULL,
    moyen_paiement ENUM('carte', 'especes', 'virement') NOT NULL,
    statut         ENUM('en_attente', 'valide', 'echoue', 'rembourse')
                   NOT NULL DEFAULT 'en_attente',
    date_paiement  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reservation_id INT NOT NULL UNIQUE, -- UNIQUE car 0,1 côté réservation

    CONSTRAINT fk_paiement_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservation(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- Table AVIS
-- Laissé par le client sur une réservation terminée,
-- peut être modéré par un modérateur (id_moderateur NULL = non modéré)
-- =========================================================
CREATE TABLE avis (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    note           TINYINT      NOT NULL, -- de 1 à 5, contrainte vérifiée ci-dessous
    commentaire    TEXT,
    date_avis      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reservation_id INT NOT NULL UNIQUE, -- UNIQUE car 0,1 côté réservation
    moderateur_id  INT NULL,            -- NULL tant que l'avis n'a pas été modéré

    CONSTRAINT fk_avis_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservation(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_avis_moderateur
        FOREIGN KEY (moderateur_id) REFERENCES moderateur(id_utilisateur)
        ON DELETE SET NULL,

    CONSTRAINT chk_avis_note CHECK (note BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- =========================================================
-- Table SIGNALEMENT
-- Porte sur une réservation, fait par un client,
-- peut être traité par un modérateur (id_moderateur NULL = non traité)
-- =========================================================
CREATE TABLE signalement (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    motif            VARCHAR(100) NOT NULL,
    description      TEXT         NOT NULL,
    statut           ENUM('ouvert', 'en_cours', 'resolu', 'rejete')
                     NOT NULL DEFAULT 'ouvert',
    date_signalement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reservation_id   INT NOT NULL,
    client_id        INT NOT NULL, -- celui qui signale
    moderateur_id    INT NULL,     -- NULL tant que le signalement n'a pas été traité

    CONSTRAINT fk_signalement_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservation(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_signalement_client
        FOREIGN KEY (client_id) REFERENCES client(id_utilisateur)
        ON DELETE CASCADE,

    CONSTRAINT fk_signalement_moderateur
        FOREIGN KEY (moderateur_id) REFERENCES moderateur(id_utilisateur)
        ON DELETE SET NULL

    
) ENGINE=InnoDB;

-- ======================================================================
-- Table SIGNALEMENT
-- Porte sur une réservation, fait par un client,
-- peut être traité par un modérateur (id_moderateur NULL = non traité)
-- =========================================================
CREATE TABLE signalement (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    motif            VARCHAR(100) NOT NULL,
    description      TEXT         NOT NULL,
    statut           ENUM('ouvert', 'en_cours', 'resolu', 'rejete')
                     NOT NULL DEFAULT 'ouvert',
    date_signalement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reservation_id   INT NOT NULL,
    client_id        INT NOT NULL, -- celui qui signale
    moderateur_id    INT NULL,     -- NULL tant que le signalement n'a pas été traité

    CONSTRAINT fk_signalement_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservation(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_signalement_client
        FOREIGN KEY (client_id) REFERENCES client(id_utilisateur)
        ON DELETE CASCADE,

    CONSTRAINT fk_signalement_moderateur
        FOREIGN KEY (moderateur_id) REFERENCES moderateur(id_utilisateur)
        ON DELETE SET NULL

    
) ENGINE=InnoDB;

-- =========================================================
-- Table AUTH_REMEMBER (« se souvenir de moi »)
-- Une ligne = un appareil connecté. On ne stocke QUE
-- l'empreinte SHA-256 du jeton : si la base est volée,
-- aucun cookie utilisable ne peut être reconstruit.
-- =========================================================
CREATE TABLE auth_remember (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    token_hash    CHAR(64)   NOT NULL,         -- SHA-256 du jeton (jamais le jeton en clair)
    expires_at    DATETIME   NOT NULL,         -- expiration (30 jours)
    date_creation DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_auth_remember_token UNIQUE (token_hash),
    CONSTRAINT fk_auth_remember_utilisateur
        FOREIGN KEY (user_id) REFERENCES utilisateur(id) ON DELETE CASCADE,
    INDEX idx_auth_remember_expires (expires_at)
) ENGINE=InnoDB;

-- Tables devis
CREATE Table devis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL UNIQUE,
    description_intervation TEXT NOT NULL,
    montant DECIMAL(6,2) NOT NULL,
    statut ENUM('en_attente', 'accepte', 'refuse') NOT NULL DEFAULT 'en_attente',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    main_oeuvre DECIMAL(6,2) NOT NULL,
    cout_materiel DECIMAL(6,2) NOT NULL,
    delai_reparation INT NOT NULL, -- en heures, jours
    remarques_facultatives TEXT NULL,
    CONSTRAINT fk_devis_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservation(id)
        ON DELETE CASCADE
)