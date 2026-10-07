<?php
namespace App\Models;

use App\Core\Database;

// Modèle : avis laissés par les clients sur leurs réservations terminées.
final class Avis
{
    public static function existePourReservation(int $reservationId): bool
    {
        $stmt = Database::get()->prepare("SELECT id FROM avis WHERE reservation_id = ?");
        $stmt->execute([$reservationId]);
        return (bool)$stmt->fetch();
    }

    public static function creer(int $note, string $commentaire, int $reservationId): void
    {
        Database::get()->prepare(
            "INSERT INTO avis (note, commentaire, reservation_id) VALUES (?, ?, ?)"
        )->execute([$note, $commentaire, $reservationId]);
    }

    // Avis en attente de modération (moderateur_id NULL)
    public static function enAttente(): array
    {
        return Database::get()->query(
            "SELECT a.id, a.note, a.commentaire, a.date_avis,
                    u.prenom AS cli_prenom, u.nom AS cli_nom,
                    s.titre AS service_titre
             FROM avis a
             INNER JOIN reservation r ON r.id = a.reservation_id
             INNER JOIN service s ON s.id = r.service_id
             INNER JOIN utilisateur u ON u.id = r.client_id
             WHERE a.moderateur_id IS NULL
             ORDER BY a.date_avis ASC"
        )->fetchAll();
    }

    // Avis déjà modérés (espace modérateur)
    public static function moderes(): array 
    {
        return Database::get()->query(
            "SELECT a.id, a.note, a.commentaire, a.date_avis,
                    u.prenom AS cli_prenom, u.nom AS cli_nom,
                    s.titre AS service_titre,
                    um.prenom AS mod_prenom, um.nom AS mod_nom
             FROM avis a
             INNER JOIN reservation r ON r.id = a.reservation_id
             INNER JOIN service s ON s.id = r.service_id
             INNER JOIN utilisateur u ON u.id = r.client_id
             INNER JOIN moderateur m ON m.id_utilisateur = a.moderateur_id
             INNER JOIN utilisateur um ON um.id = m.id_utilisateur
             ORDER BY a.date_avis DESC"
        )->fetchAll();
    }

    // ---- Avis PUBLICS (validés par un modérateur) ----

    // Avis validés d'un service → visibles par tous sur la fiche service
    public static function publicsPourService(int $serviceId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT a.note, a.commentaire, a.date_avis,
                    u.prenom AS cli_prenom
             FROM avis a
             INNER JOIN reservation r ON r.id = a.reservation_id AND r.service_id = ?
             INNER JOIN utilisateur u ON u.id = r.client_id
             WHERE a.moderateur_id IS NOT NULL
             ORDER BY a.date_avis DESC"
        );
        $stmt->execute([$serviceId]);
        return $stmt->fetchAll();
    }

    // Avis validés d'un réparateur (tous ses services) → profil public
    public static function publicsPourReparateur(int $reparateurId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT a.note, a.commentaire, a.date_avis,
                    u.prenom AS cli_prenom, s.titre AS service_titre
             FROM avis a
             INNER JOIN reservation r ON r.id = a.reservation_id
             INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
             INNER JOIN utilisateur u ON u.id = r.client_id
             WHERE a.moderateur_id IS NOT NULL
             ORDER BY a.date_avis DESC"
        );
        $stmt->execute([$reparateurId]);
        return $stmt->fetchAll();
    }

    // Valide un avis (le modérateur connecté en prend la responsabilité)
    public static function valider(int $avisId, int $moderateurId): bool
    {
        $stmt = Database::get()->prepare(
            "UPDATE avis SET moderateur_id = ? WHERE id = ? AND moderateur_id IS NULL"
        );
        $stmt->execute([$moderateurId, $avisId]);
        return $stmt->rowCount() > 0;
    }

    public static function supprimer(int $avisId): void
    {
        Database::get()->prepare("DELETE FROM avis WHERE id = ?")->execute([$avisId]);
    }
}