<?php
namespace App\Models;

use App\Core\Database;

// Modèle : signalements de problèmes envoyés par les clients.
final class Signalement
{
    public static function creer(int $reservationId, int $clientId, string $motif, string $description): void
    {
        Database::get()->prepare(
            "INSERT INTO signalement (motif, description, statut, reservation_id, client_id)
             VALUES (?, ?, 'ouvert', ?, ?)"
        )->execute([$motif, $description, $reservationId, $clientId]);
    }

    // Liste complète avec client, service et réparateur concernés
    public static function tousAvecInfos(): array
    {
        return Database::get()->query(
            "SELECT sg.id, sg.motif, sg.description, sg.statut, sg.date_signalement,
                    u.prenom AS cli_prenom, u.nom AS cli_nom, u.email AS cli_email,
                    s.titre AS service_titre,
                    ur.prenom AS rep_prenom, ur.nom AS rep_nom
             FROM signalement sg
             INNER JOIN reservation r ON r.id = sg.reservation_id
             INNER JOIN service s ON s.id = r.service_id
             INNER JOIN utilisateur u ON u.id = sg.client_id
             INNER JOIN reparateur rep ON rep.id_utilisateur = s.reparateur_id
             INNER JOIN utilisateur ur ON ur.id = rep.id_utilisateur
             ORDER BY FIELD(sg.statut, 'ouvert', 'en_cours', 'resolu', 'rejete'),
                      sg.date_signalement DESC"
        )->fetchAll();
    }

    // Aperçu des 5 derniers (tableau de bord modérateur)
    public static function dernierApercu(): array
    {
        return Database::get()->query(
            "SELECT sg.id, sg.motif, sg.statut, sg.date_signalement,
                    u.prenom AS cli_prenom, u.nom AS cli_nom,
                    s.titre AS service_titre
             FROM signalement sg
             INNER JOIN reservation r ON r.id = sg.reservation_id
             INNER JOIN service s ON s.id = r.service_id
             INNER JOIN utilisateur u ON u.id = sg.client_id
             ORDER BY FIELD(sg.statut, 'ouvert', 'en_cours', 'resolu', 'rejete'),
                      sg.date_signalement DESC
             LIMIT 5"
        )->fetchAll();
    }

    // Détail complet d'un signalement
    public static function trouverDetail(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT sg.*,
                    u.prenom AS cli_prenom, u.nom AS cli_nom, u.email AS cli_email, u.telephone AS cli_tel,
                    s.titre AS service_titre, s.tarif,
                    r.adresse_intervention, r.date_intervention, r.statut AS reservation_statut,
                    ur.prenom AS rep_prenom, ur.nom AS rep_nom
             FROM signalement sg
             INNER JOIN reservation r ON r.id = sg.reservation_id
             INNER JOIN service s ON s.id = r.service_id
             INNER JOIN utilisateur u ON u.id = sg.client_id
             INNER JOIN reparateur rep ON rep.id_utilisateur = s.reparateur_id
             INNER JOIN utilisateur ur ON ur.id = rep.id_utilisateur
             WHERE sg.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function changerStatut(int $id, string $statut, int $moderateurId): void
    {
        Database::get()->prepare(
            "UPDATE signalement SET statut = ?, moderateur_id = ? WHERE id = ?"
        )->execute([$statut, $moderateurId, $id]);
    }
}