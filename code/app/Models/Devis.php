<?php
namespace App\Models;

use App\Core\Database;

// Modèle : devis (estimation) créés par le réparateur pour une réservation.
// Le client accepte ou refuse ; sans devis accepté, pas d'intervention.
// ⚠️ Le total n'est PAS stocké : il est calculé en SQL
// (main_oeuvre + cout_pieces) pour qu'il ne puisse jamais être incohérent.
final class Devis
{
    // ---- Réparateur ----

    // Crée un devis pour une réservation (statut 'en_attente')
    public static function creer(
        int $reservationId,
        string $description,
        float $mainOeuvre,
        float $coutPieces,
        int $delaiJours,
        ?string $remarque
    ): void {
        Database::get()->prepare(
            "INSERT INTO devis
                (reservation_id, description_intervention, main_oeuvre, cout_pieces, delai_jours, remarque)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$reservationId, $description, $mainOeuvre, $coutPieces, $delaiJours, $remarque]);
    }

    // Le devis d'une réservation (avec le total calculé) ou null
    public static function pourReservation(int $reservationId): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT d.*, (d.main_oeuvre + d.cout_pieces) AS total
             FROM devis d
             WHERE d.reservation_id = ?"
        );
        $stmt->execute([$reservationId]);
        return $stmt->fetch() ?: null;
    }

    // Les devis reçus par le réparateur (jointures sur SES services)
    public static function mesDevis(int $reparateurId): array
    {
        return Database::get()->query(
            "SELECT d.id, d.reservation_id, d.statut, d.description_intervention,
                    d.main_oeuvre, d.cout_pieces, (d.main_oeuvre + d.cout_pieces) AS total,
                    d.delai_jours, d.remarque, d.date_creation,
                    r.adresse_intervention, r.date_intervention, r.statut AS reservation_statut,
                    s.titre AS service_titre, s.tarif,
                    u.prenom AS client_prenom, u.nom AS client_nom, u.telephone AS client_tel
             FROM devis d
             INNER JOIN reservation r ON r.id = d.reservation_id
             INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
             INNER JOIN utilisateur u ON u.id = r.client_id
             ORDER BY d.date_creation DESC"
        )->fetchAll();
    }

    // ---- Client ----

    // Les devis concernant les réservations du client
    public static function listePourClient(int $clientId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT d.id, d.reservation_id, d.statut, d.description_intervention,
                    d.main_oeuvre, d.cout_pieces, (d.main_oeuvre + d.cout_pieces) AS total,
                    d.delai_jours, d.remarque, d.date_creation,
                    r.date_intervention, r.adresse_intervention, r.statut AS reservation_statut,
                    s.titre AS service_titre,
                    u.prenom AS reparateur_prenom, u.nom AS reparateur_nom,
                    rep.nom_entreprise
             FROM devis d
             INNER JOIN reservation r ON r.id = d.reservation_id AND r.client_id = ?
             INNER JOIN service s ON s.id = r.service_id
             INNER JOIN reparateur rep ON rep.id_utilisateur = s.reparateur_id
             INNER JOIN utilisateur u ON u.id = rep.id_utilisateur
             ORDER BY d.date_creation DESC"
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    // Un devis précis (avec le total)
    public static function trouver(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT d.*, (d.main_oeuvre + d.cout_pieces) AS total
             FROM devis d
             WHERE d.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ---- Décision du client ----

    // Le devis est-il déjà accepté pour cette réservation ?
    // (garde-fou : utilisé avant de laisser confirmer une intervention)
    public static function acceptePourReservation(int $reservationId): bool
    {
        $stmt = Database::get()->prepare(
            "SELECT statut FROM devis WHERE reservation_id = ?"
        );
        $stmt->execute([$reservationId]);
        return $stmt->fetchColumn() === 'accepte';
    }

    // Un devis existe-t-il déjà pour cette réservation ?
    public static function existePourReservation(int $reservationId): bool
    {
        $stmt = Database::get()->prepare("SELECT id FROM devis WHERE reservation_id = ?");
        $stmt->execute([$reservationId]);
        return (bool)$stmt->fetch();
    }

    // Transitions de statut : le « WHERE statut = 'en_attente' » garantit
    // qu'on ne répond qu'une seule fois (rowCount = 0 sinon).
    public static function accepter(int $devisId): bool
    {
        $stmt = Database::get()->prepare(
            "UPDATE devis SET statut = 'accepte' WHERE id = ? AND statut = 'en_attente'"
        );
        $stmt->execute([$devisId]);
        return $stmt->rowCount() > 0;
    }

    public static function refuser(int $devisId): bool
    {
        $stmt = Database::get()->prepare(
            "UPDATE devis SET statut = 'refuse' WHERE id = ? AND statut = 'en_attente'"
        );
        $stmt->execute([$devisId]);
        return $stmt->rowCount() > 0;
    }
}
