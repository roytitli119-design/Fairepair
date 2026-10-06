<?php
namespace App\Models;

use App\Core\Database;

// Modèle : services proposés par les réparateurs.
final class Service
{
    // Liste publique des services avec le nom/city du réparateur
    public static function tousAvecReparateur(): array
    {
        return Database::get()->query(
            "SELECT s.id, s.titre, s.description, s.tarif, s.rayon, s.image,
                    r.id_utilisateur AS reparateur_id,
                    u.prenom AS rep_prenom, u.nom AS rep_nom, u.ville
             FROM service s
             INNER JOIN reparateur r ON r.id_utilisateur = s.reparateur_id
             INNER JOIN utilisateur u ON u.id = r.id_utilisateur
             ORDER BY s.date_creation DESC"
        )->fetchAll();
    }

    // Services d'un réparateur (page profil public)
    public static function pourReparateur(int $reparateurId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT s.id, s.titre, s.description, s.tarif, s.rayon, s.image
             FROM service s
             WHERE s.reparateur_id = ?
             ORDER BY s.date_creation DESC"
        );
        $stmt->execute([$reparateurId]);
        return $stmt->fetchAll();
    }

    public static function trouver(int $id): ?array
    {
        $stmt = Database::get()->prepare("SELECT * FROM service WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Fiche publique d'un service + infos du réparateur
    public static function trouverAvecReparateur(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT s.*, u.prenom AS rep_prenom, u.nom AS rep_nom, u.ville
             FROM service s
             INNER JOIN reparateur r ON r.id_utilisateur = s.reparateur_id
             INNER JOIN utilisateur u ON u.id = r.id_utilisateur
             WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Moyenne et nombre d'avis pour un service (via ses réservations)
    public static function statsAvis(int $serviceId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT AVG(a.note) AS moyenne, COUNT(a.id) AS total
             FROM avis a
             INNER JOIN reservation r ON r.id = a.reservation_id
             WHERE r.service_id = ?"
        );
        $stmt->execute([$serviceId]);
        return $stmt->fetch();
    }

    public static function creer(int $reparateurId, string $titre, string $description, float $tarif, int $rayon, ?string $image): void
    {
        Database::get()->prepare(
            "INSERT INTO service (titre, description, tarif, rayon, image, reparateur_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$titre, $description, $tarif, $rayon, $image, $reparateurId]);
    }

    // Charge un service SEULEMENT s'il appartient au réparateur donné
    public static function appartenantA(int $id, int $reparateurId): ?array
    {
        $stmt = Database::get()->prepare("SELECT * FROM service WHERE id = ? AND reparateur_id = ?");
        $stmt->execute([$id, $reparateurId]);
        return $stmt->fetch() ?: null;
    }

    public static function modifier(int $id, int $reparateurId, string $titre, string $description, float $tarif, int $rayon, ?string $nouvelleImage): void
    {
        Database::get()->prepare(
            "UPDATE service
             SET titre = ?, description = ?, tarif = ?, rayon = ?, image = COALESCE(?, image)
             WHERE id = ? AND reparateur_id = ?"
        )->execute([$titre, $description, $tarif, $rayon, $nouvelleImage, $id, $reparateurId]);
    }

    // Supprime un service (seulement s'il appartient au réparateur).
    // Retourne la ligne supprimée (pour nettoyer l'image), ou null.
    public static function supprimer(int $id, int $reparateurId): ?array
    {
        $pdo  = Database::get();
        $stmt = $pdo->prepare("SELECT image FROM service WHERE id = ? AND reparateur_id = ?");
        $stmt->execute([$id, $reparateurId]);
        $service = $stmt->fetch();
        if (!$service) {
            return null;
        }
        // ON DELETE CASCADE supprime aussi réservations/avis/paiements liés
        $pdo->prepare("DELETE FROM service WHERE id = ? AND reparateur_id = ?")
            ->execute([$id, $reparateurId]);
        return $service;
    }

    // Ses services, avec le compteur de réservations
    public static function mesServices(int $reparateurId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT s.id, s.titre, s.description, s.tarif, s.rayon, s.image, s.date_creation,
                    (SELECT COUNT(*) FROM reservation r WHERE r.service_id = s.id) AS nb_reservations
             FROM service s
             WHERE s.reparateur_id = ?
             ORDER BY s.date_creation DESC"
        );
        $stmt->execute([$reparateurId]);
        return $stmt->fetchAll();
    }
}