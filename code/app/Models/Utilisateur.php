<?php
namespace App\Models;

use App\Core\Database;

// Modèle : gestion des comptes (inscription, connexion, mot secret,
// profil, passage réparateur). Toutes les requêtes SQL y sont regroupées.
final class Utilisateur
{
    public const MAX_TENTATIVES        = 5;
    public const DUREE_BLOCAGE_MINUTES = 15;

    // Le mot secret (récupération du mot de passe) est lui aussi protégé :
    // 3 échecs → blocage temporaire (15 minutes).
    public const MAX_TENTATIVES_SECRET        = 3;
    public const DUREE_BLOCAGE_MOT_SECRET_MIN = 15;

    public static function trouver(int $id): ?array
    {
        $stmt = Database::get()->prepare("SELECT * FROM utilisateur WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function trouverParEmail(string $email): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT id, prenom, nom, email, mot_de_passe, mot_secret,
                    tentatives_connexion, blocage_jusqua
             FROM utilisateur WHERE email = ?"
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function emailExiste(string $email): bool
    {
        $stmt = Database::get()->prepare("SELECT id FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        return (bool)$stmt->fetch();
    }

    // Crée un compte (rôle client) en traçant l'acceptation des CGU
    // (date + version) : preuve de consentement exigée par le RGPD.
    // Retourne l'id du nouvel utilisateur.
    public static function creerClient(string $prenom, string $nom, string $email, string $motDePasse): int
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
            $pdo->prepare(
                "INSERT INTO utilisateur (prenom, nom, email, mot_de_passe, cgu_acceptee_at, cgu_version)
                 VALUES (?, ?, ?, ?, NOW(), ?)"
            )->execute([$prenom, $nom, $email, $hash, CGU_VERSION]);

            $userId = (int)$pdo->lastInsertId();

            // Tout nouveau compte démarre en client ; le passage réparateur
            // se fait depuis le profil.
            $pdo->prepare("INSERT INTO client (id_utilisateur) VALUES (?)")->execute([$userId]);

            $pdo->commit();
            return $userId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Vérifie email + mot de passe, avec comptage des échecs et blocage
    // (15 minutes après 5 échecs — anti force brute).
    // Retourne ['ok' => bool, 'message' => ?string, 'user' => ?array].
    public static function verifierConnexion(string $email, string $motDePasse): array
    {
        $pdo  = Database::get();
        $user = self::trouverParEmail($email);

        // Blocage temporaire : après 5 échecs, 15 minutes d'attente
        if ($user && $user['blocage_jusqua'] !== null) {
            $deblocage = strtotime($user['blocage_jusqua']);
            if (time() < $deblocage) {
                return [
                    'ok'      => false,
                    'message' => "Trop de tentatives échouées. Réessayez après " . date('H:i', $deblocage) . ".",
                    'user'    => null,
                ];
            }
            // Blocage expiré : on remet les compteurs à zéro
            $pdo->prepare("UPDATE utilisateur SET tentatives_connexion = 0, blocage_jusqua = NULL WHERE id = ?")
                ->execute([$user['id']]);
        }

        if (!$user || !password_verify($motDePasse, $user['mot_de_passe'])) {
            if ($user) {
                $tentatives = (int)$user['tentatives_connexion'] + 1;
                if ($tentatives >= self::MAX_TENTATIVES) {
                    $pdo->prepare(
                        "UPDATE utilisateur
                         SET tentatives_connexion = 0,
                             blocage_jusqua = DATE_ADD(NOW(), INTERVAL " . self::DUREE_BLOCAGE_MINUTES . " MINUTE)
                         WHERE id = ?"
                    )->execute([$user['id']]);
                    return [
                        'ok'      => false,
                        'message' => "Trop de tentatives échouées : compte bloqué pendant " . self::DUREE_BLOCAGE_MINUTES . " minutes.",
                        'user'    => null,
                    ];
                }
                $pdo->prepare("UPDATE utilisateur SET tentatives_connexion = ? WHERE id = ?")
                    ->execute([$tentatives, $user['id']]);
            }
            return ['ok' => false, 'message' => "Email ou mot de passe incorrect.", 'user' => null];
        }

        // Connexion réussie : réinitialisation des compteurs
        $pdo->prepare("UPDATE utilisateur SET tentatives_connexion = 0, blocage_jusqua = NULL WHERE id = ?")
            ->execute([$user['id']]);
        return ['ok' => true, 'message' => null, 'user' => $user];
    }

    // ---- Mot secret (sécurité RGPD) ----
    public static function definirMotSecret(int $id, string $motSecret): void
    {
        Database::get()->prepare("UPDATE utilisateur SET mot_secret = ? WHERE id = ?")
            ->execute([password_hash($motSecret, PASSWORD_DEFAULT), $id]);
    }

    // Vérifie l'ANCIEN mot secret avant modification.
    // Retourne un message d'erreur, ou null si l'ancien secret est correct.
    // 3 échecs → blocage de 15 minutes (compteur tentatives_secret).
    public static function verifierAncienMotSecret(int $id, string $ancien): ?string
    {
        // Compte bloqué ? (3 échecs consécutifs)
        $reste = self::blocageSecretRestant($id);
        if ($reste !== null) {
            return "Trop de tentatives échouées sur le mot secret. Réessayez après $reste.";
        }

        $stmt = Database::get()->prepare("SELECT mot_secret FROM utilisateur WHERE id = ?");
        $stmt->execute([$id]);
        $ligne = $stmt->fetch();

        if (!$ligne || $ligne['mot_secret'] === null) {
            return "Aucun mot secret n'est défini pour votre compte.";
        }
        if (!password_verify($ancien, $ligne['mot_secret'])) {
            self::enregistrerEchecMotSecret($id);
            return "L'ancien mot secret est incorrect.";
        }
        // Réussite : on réinitialise le compteur d'échecs
        self::reinitialiserEchecsSecret($id);
        return null;
    }

    // ---- Anti force brute : 3 échecs de mot secret → blocage 15 min ----

    // Retourne l'heure de déblocage ("14:32") si le mot secret est bloqué,
    // sinon null. Utilisé aussi par la récupération du mot de passe.
    public static function blocageSecretRestant(int $id): ?string
    {
        $stmt = Database::get()->prepare("SELECT blocage_secret_jusqua FROM utilisateur WHERE id = ?");
        $stmt->execute([$id]);
        $blocage = $stmt->fetchColumn();

        if ($blocage === null || $blocage === false) {
            return null;
        }
        $deblocage = strtotime((string)$blocage);
        return time() < $deblocage ? date('H:i', $deblocage) : null;
    }

    // Compte un échec ; à partir du 3e échec, active le blocage 15 minutes.
    private static function enregistrerEchecMotSecret(int $id): void
    {
        $pdo = Database::get();
        $pdo->prepare("UPDATE utilisateur SET tentatives_secret = tentatives_secret + 1 WHERE id = ?")
            ->execute([$id]);
        $pdo->prepare(
            "UPDATE utilisateur
             SET blocage_secret_jusqua = DATE_ADD(NOW(), INTERVAL " . self::DUREE_BLOCAGE_MOT_SECRET_MIN . " MINUTE)
             WHERE id = ? AND tentatives_secret >= " . self::MAX_TENTATIVES_SECRET
        )->execute([$id]);
    }

    // Remet le compteur à zéro après un mot secret correct.
    private static function reinitialiserEchecsSecret(int $id): void
    {
        Database::get()->prepare(
            "UPDATE utilisateur SET tentatives_secret = 0, blocage_secret_jusqua = NULL WHERE id = ?"
        )->execute([$id]);
    }

    // ---- Récupération du mot de passe ----
    // Via le mot secret : protégée par le même blocage anti force brute
    // (3 échecs → 15 min). Retourne : 'ok' si le changement a eu lieu,
    // 'bloque' si trop d'échecs, 'echec' sinon (message identique → anti-énumération).
    public static function reinitialiserParMotSecret(string $email, string $motSecret, string $nouveauMotDePasse): string
    {
        $user = self::trouverParEmail($email);
        if (!$user) {
            return 'echec';
        }
        $id = (int)$user['id'];

        if (self::blocageSecretRestant($id) !== null) {
            return 'bloque';
        }
        if ($user['mot_secret'] === null || !password_verify($motSecret, $user['mot_secret'])) {
            self::enregistrerEchecMotSecret($id);
            return 'echec';
        }

        $hash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
        Database::get()->prepare(
            "UPDATE utilisateur
             SET mot_de_passe = ?,
                 tentatives_connexion = 0, blocage_jusqua = NULL
             WHERE id = ?"
        )->execute([$hash, $id]);
        self::reinitialiserEchecsSecret($id);
        return 'ok';
    }

    // Via token (lien envoyé par email — conservé en parallèle)


    // ---- Profil ----
    public static function majProfil(int $id, array $data): void
    {
        Database::get()->prepare(
            "UPDATE utilisateur
             SET prenom = ?, nom = ?, adresse = ?, code_postal = ?, ville = ?, telephone = ?
             WHERE id = ?"
        )->execute([
            $data['prenom'],
            $data['nom'],
            $data['adresse']     !== '' ? $data['adresse']     : null,
            $data['code_postal'] !== '' ? $data['code_postal'] : null,
            $data['ville']       !== '' ? $data['ville']       : null,
            $data['telephone']   !== '' ? $data['telephone']   : null,
            $id,
        ]);
    }

    // ---- Rôle réparateur ----
    public static function infosReparateur(int $id): ?array
    {
        $stmt = Database::get()->prepare("SELECT * FROM reparateur WHERE id_utilisateur = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Crée le rôle réparateur (ou le met à jour si déjà créé)
    // $latitude / $longitude (décimales ou null) servent à la carte Leaflet.
    public static function devenirReparateur(int $id, string $entreprise, string $siret, string $description, ?string $latitude = null, ?string $longitude = null): void
    {
        Database::get()->prepare(
            "INSERT INTO reparateur (id_utilisateur, nom_entreprise, siret, description_pro, latitude, longitude)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 nom_entreprise  = VALUES(nom_entreprise),
                 siret           = VALUES(siret),
                 description_pro = VALUES(description_pro),
                 latitude        = VALUES(latitude),
                 longitude       = VALUES(longitude)"
        )->execute([$id, $entreprise, $siret, $description, $latitude, $longitude]);
    }

    public static function majInfosReparateur(int $id, string $entreprise, string $siret, string $description, ?string $latitude = null, ?string $longitude = null): void
    {
        Database::get()->prepare(
            "UPDATE reparateur
             SET nom_entreprise = ?, siret = ?, description_pro = ?,
                 latitude = ?, longitude = ?
             WHERE id_utilisateur = ?"
        )->execute([$entreprise, $siret, $description, $latitude, $longitude, $id]);
    }
}