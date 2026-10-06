-- =========================================================
-- NETTOYAGE AVANT SOUTENANCE — Fair'repair
-- ---------------------------------------------------------
-- À exécuter UNIQUEMENT juste avant la soutenance
-- (phpMyAdmin > SQL, ou en ligne de commande) :
--   mysql -u ... -p fairepair < nettoyage_soutenance.sql
--
-- Effet : supprime les comptes de DÉMONSTRATION (*@test.fr).
-- Grâce aux clés étrangères ON DELETE CASCADE, les lignes
-- liées (client / réparateur / modérateur, services,
-- réservations, paiements, avis, signalements) sont
-- supprimées automatiquement.
-- =========================================================
USE fairepair;

-- 1. Comptes de démonstration créés pendant les phases de test
DELETE FROM utilisateur WHERE email LIKE '%@test.fr';

-- 2. Réinitialise le compteur AUTO_INCREMENT (optionnel)
ALTER TABLE utilisateur AUTO_INCREMENT = 1;

-- 3. Contrôle : il ne doit plus rester aucun compte de test
SELECT id, prenom, nom, email FROM utilisateur;

-- 4. (Rappel) Après le nettoyage, recréez des comptes de démo
--    EN PASSANT PAR LA VRAIE APPLICATION, le jour de la soutenance :
--      a. Inscription depuis « Créer un compte » (CGU cochée → mot secret)
--      b. « Mon profil → Devenir réparateur » (entreprise + latitude/longitude)
--      c. Publication de services depuis « Ajouter un service »
--    C'est aussi ce parcours complet qu'il faut montrer au jury.