<?php
namespace App\Models;

use App\Core\Database;

// Jetons « se souvenir de moi » : un ligne par appareil connecté.
// On ne stocke JAMAIS le validator en clair, seulement son SHA-256.
final class AuthRemember
{
    public const DUREE_JOURS = 30;

    // Crée un jeton et renvoie la paire "selector:validator"
    public static function creer(int $userId): string
    {
        $selector   = bin2hex(random_bytes(9));   // public
        $validator  = bin2hex(random_bytes(32));  // secret

        Database::get()->prepare(
            "INSERT INTO auth_remember (user_id, selector, validator_hash, expires_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))"
        )->execute([$userId, $selector, hash('sha256', $validator), self::DUREE_JOURS]);

        return $selector . ':' . $validator;
    }

    // Vérifie le jeton : selector retrouve la ligne, validator prouve l'identité
    public static function verifier(string $selector, string $validator): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM auth_remember WHERE selector = ? AND expires_at > NOW()"
        );
        $stmt->execute([$selector]);
        $ligne = $stmt->fetch();

        if (!$ligne) {
            return null;
        }

        // hash_equals() : comparaison à temps constant (anti timing attack)
        if (!hash_equals($ligne['validator_hash'], hash('sha256', $validator))) {
            self::supprimerParSelector($selector);   // selector deviné + mauvais secret
            return null;
        }

        return $ligne; // ['id' => …, 'user_id' => …]
    }

    public static function supprimerParSelector(string $selector): void
    {
        Database::get()->prepare("DELETE FROM auth_remember WHERE selector = ?")
             ->execute([$selector]);
    }

    public static function supprimerToutPourUtilisateur(int $userId): void
    {
        Database::get()->prepare("DELETE FROM auth_remember WHERE user_id = ?")
             ->execute([$userId]);
    }
}