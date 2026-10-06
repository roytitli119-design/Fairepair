<?php
namespace App\Models;

use App\Core\Database;

// ============================================================
//  « Se souvenir de moi » — jetons de reconnexion
//
//  Principe : on tire un jeton aléatoire, on le met dans un cookie
//  et on ne stocke en base QUE son empreinte (SHA-256).
//  → un cookie volé ne peut pas être Used pour se connecter
//    avec une fausse empreinte, et un vol de la base ne donne
//    aucun cookie utilisable.
// ============================================================
final class AuthRemember
{
    public const DUREE_JOURS = 30;

    // Crée un jeton pour un utilisateur et le renvoie
    // (c'est ce jeton, en clair, qui ira dans le cookie)
    public static function creer(int $userId): string
    {
        $token = bin2hex(random_bytes(32));           // 64 caractères aléatoires

        Database::get()->prepare(
            "INSERT INTO auth_remember (user_id, token_hash, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))"
        )->execute([$userId, hash('sha256', $token), self::DUREE_JOURS]);

        return $token;
    }

    // Jeton valide (non expiré) ? → renvoie l'id de l'utilisateur, sinon null.
    // La comparaison se fait sur l'empreinte, thanks à l'index UNIQUE.
    public static function utilisateurIdDe(string $token): ?int
    {
        $stmt = Database::get()->prepare(
            "SELECT user_id FROM auth_remember
             WHERE token_hash = ? AND expires_at > NOW()"
        );
        $stmt->execute([hash('sha256', $token)]);

        $ligne = $stmt->fetch();
        return $ligne ? (int)$ligne['user_id'] : null;
    }

    // Oublie UN appareil (le token correspondant)
    public static function supprimer(string $token): void
    {
        Database::get()->prepare("DELETE FROM auth_remember WHERE token_hash = ?")
            ->execute([hash('sha256', $token)]);
    }

    // Oublie TOUS les appareils d'un compte (déconexion, case non cochée)
    public static function supprimerTout(int $userId): void
    {
        Database::get()->prepare("DELETE FROM auth_remember WHERE user_id = ?")
            ->execute([$userId]);
    }
}
