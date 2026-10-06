<?php
namespace App\Models;

use App\Core\Database;

// Modèle : paiements (simulés) des réservations.
final class Paiement
{
    public static function existePourReservation(int $reservationId): bool
    {
        $stmt = Database::get()->prepare("SELECT id FROM paiement WHERE reservation_id = ?");
        $stmt->execute([$reservationId]);
        return (bool)$stmt->fetch();
    }

    // Paiement simulé : aucune donnée bancaire n'est jamais stockée.
    public static function creer(float $montant, string $moyen, int $reservationId): void
    {
        Database::get()->prepare(
            "INSERT INTO paiement (montant, moyen_paiement, statut, reservation_id)
             VALUES (?, ?, 'valide', ?)"
        )->execute([$montant, $moyen, $reservationId]);
    }
}