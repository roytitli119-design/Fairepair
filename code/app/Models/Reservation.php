<?php
namespace App\Models;

use App\Core\Database;

// Modèle : réservations (création, annulation, listes par rôle, gains).
final class Reservation
{
    // Crée une réservation en attente
    public static function creer(int $clientId, int $serviceId, string $adresse, string $date): void
    {
        Database::get()->prepare(
            "INSERT INTO reservation (adresse_intervention, date_intervention, statut, client_id, service_id)
             VALUES (?, ?, 'en_attente', ?, ?)"
        )->execute([$adresse, $date, $clientId, $serviceId]);
    }

    // Réservations du client, avec paiement et avis liés
    public static function mesReservations(int $clientId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT r.id, r.adresse_intervention, r.date_intervention, r.statut,
                    s.titre, s.tarif,
                    p.id AS paiement_id, p.statut AS paiement_statut,
                    a.id AS avis_id
             FROM reservation r
             INNER JOIN service s ON s.id = r.service_id
             LEFT JOIN paiement p ON p.reservation_id = r.id
             LEFT JOIN avis a ON a.reservation_id = r.id
             WHERE r.client_id = ?
             ORDER BY r.date_intervention DESC"
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    // Annulation : uniquement SA réservation, et seulement si en attente
    public static function annuler(int $id, int $clientId): bool
    {
        $stmt = Database::get()->prepare(
            "UPDATE reservation SET statut = 'annulee'
             WHERE id = ? AND client_id = ? AND statut = 'en_attente'"
        );
        $stmt->execute([$id, $clientId]);
        return $stmt->rowCount() > 0;
    }

    // Réservation pouvant être payée (sa propre, non annulée)
    public static function pouvoirPayer(int $id, int $clientId): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT r.id, r.statut, s.titre, s.tarif
             FROM reservation r
             INNER JOIN service s ON s.id = r.service_id
             WHERE r.id = ? AND r.client_id = ? AND r.statut <> 'annulee'"
        );
        $stmt->execute([$id, $clientId]);
        return $stmt->fetch() ?: null;
    }

    // Réservation permettant de laisser un avis (terminée, à soi)
    public static function pouvoirAvis(int $id, int $clientId): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT r.id, r.statut, s.titre
             FROM reservation r
             INNER JOIN service s ON s.id = r.service_id
             WHERE r.id = ? AND r.client_id = ? AND r.statut = 'terminee'"
        );
        $stmt->execute([$id, $clientId]);
        return $stmt->fetch() ?: null;
    }

    // Réservation permettant de signaler un problème (à soi)
    public static function pouvoirSignalement(int $id, int $clientId): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT r.id, s.titre
             FROM reservation r
             INNER JOIN service s ON s.id = r.service_id
             WHERE r.id = ? AND r.client_id = ?"
        );
        $stmt->execute([$id, $clientId]);
        return $stmt->fetch() ?: null;
    }

    // Changement de statut limité aux réservations des services du réparateur
    // (la jointure sur s.reparateur_id sert de garde-fou).
    public static function changerStatut(int $id, int $reparateurId, string $nouveauStatut): bool
    {
        $stmt = Database::get()->prepare(
            "UPDATE reservation r
             INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
             SET r.statut = ?
             WHERE r.id = ?"
        );
        $stmt->execute([$reparateurId, $nouveauStatut, $id]);
        return $stmt->rowCount() > 0;
    }

    // ---- Liste des réservations liées aux services du réparateur ----
    private static function requete(int $reparateurId, string $where, string $order = 'ASC'): array
    {
        $stmt = Database::get()->prepare(
            "SELECT r.id, r.adresse_intervention, r.date_intervention, r.date_creation, r.statut,
                    s.titre AS service_titre, s.tarif,
                    u.prenom AS client_prenom, u.nom AS client_nom, u.telephone AS client_tel
             FROM reservation r
             INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
             INNER JOIN utilisateur u ON u.id = r.client_id
             WHERE $where
             ORDER BY r.date_intervention $order"
        );
        $stmt->execute([$reparateurId]);
        return $stmt->fetchAll();
    }

    public static function enAttente(int $reparateurId): array
    {
        return self::requete($reparateurId, "r.statut = 'en_attente'");
    }

    public static function aVenir(int $reparateurId): array
    {
        return self::requete($reparateurId, "r.statut = 'confirmee' AND r.date_intervention >= NOW()");
    }

    public static function historique(int $reparateurId): array
    {
        return self::requete(
            $reparateurId,
            "r.statut IN ('terminee', 'annulee') OR (r.statut = 'confirmee' AND r.date_intervention < NOW())",
            'DESC'
        );
    }

    // ---- Gains du réparateur (chiffres clés) ----
    public static function statsTerminees(int $reparateurId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT COUNT(*) AS nb_terminees, COALESCE(SUM(s.tarif), 0) AS ca
             FROM reservation r
             INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
             WHERE r.statut = 'terminee'"
        );
        $stmt->execute([$reparateurId]);
        return $stmt->fetch();
    }

    public static function statsPaiements(int $reparateurId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT COUNT(*) AS nb_paiements, COALESCE(SUM(p.montant), 0) AS encaisse
             FROM paiement p
             INNER JOIN reservation r ON r.id = p.reservation_id
             INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
             WHERE p.statut = 'valide'"
        );
        $stmt->execute([$reparateurId]);
        return $stmt->fetch();
    }

    public static function nbEnAttente(int $reparateurId): int
    {
        $stmt = Database::get()->prepare(
            "SELECT COUNT(*) AS nb FROM reservation r
             INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
             WHERE r.statut = 'en_attente'"
        );
        $stmt->execute([$reparateurId]);
        return (int)$stmt->fetch()['nb'];
    }

    public static function detailGains(int $reparateurId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT r.id, r.date_intervention, r.statut, r.adresse_intervention,
                    s.titre AS service_titre, s.tarif,
                    u.prenom AS client_prenom, u.nom AS client_nom,
                    p.statut AS paiement_statut, p.montant AS paiement_montant,
                    p.moyen_paiement AS paiement_moyen
             FROM reservation r
             INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
             INNER JOIN utilisateur u ON u.id = r.client_id
             LEFT JOIN paiement p ON p.reservation_id = r.id
             WHERE r.statut IN ('terminee', 'confirmee')
             ORDER BY r.date_intervention DESC
             LIMIT 100"
        );
        $stmt->execute([$reparateurId]);
        return $stmt->fetchAll();
    }
}