<?php
namespace App\Models;

use App\Core\Database;

// Modèle : réparateurs (profil public, carte géographique).
final class Reparateur
{
    // Profil PUBLIC d'un réparateur : identité + informations entreprise
    // (lien depuis la fiche service, la carte et la page d'accueil).
    public static function profilPublic(int $reparateurId): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT u.id AS user_id, u.prenom, u.nom, u.ville,
                    r.nom_entreprise, r.siret, r.description_pro,
                    r.latitude, r.longitude
             FROM reparateur r
             INNER JOIN utilisateur u ON u.id = r.id_utilisateur
             WHERE r.id_utilisateur = ?"
        );
        $stmt->execute([$reparateurId]);
        return $stmt->fetch() ?: null;
    }

    // Réparateurs géolocalisés (lat/lng renseignés dans « Mon entreprise »),
    // utilisés par la carte Leaflet.
    public static function avecCoordonnees(): array
    {
        return Database::get()->query(
            "SELECT r.id_utilisateur, u.prenom, u.nom, u.ville,
                    r.nom_entreprise, r.description_pro, r.latitude, r.longitude
             FROM reparateur r
             INNER JOIN utilisateur u ON u.id = r.id_utilisateur
             WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL
             ORDER BY u.nom, u.prenom"
        )->fetchAll();
    }
}