<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Reparateur;
use App\Models\Service;

// Contrôleur de la page d'accueil (liste publique des services) et de la carte.
class HomeController extends Controller
{
    public function index(): void
    {
        $this->render(
            'home/index',
            ['services' => Service::tousAvecReparateur()],
            'Services disponibles'
        );
    }

    // Carte Leaflet des réparateurs géolocalisés (publique)
    public function carte(): void
    {
        $reparateurs = Reparateur::avecCoordonnees();

        // Les services (avec leur rayon) sont rattachés à chaque réparateur
        // pour alimenter les bulles et les cercles de rayon d'action.
        $parReparateur = [];
        foreach (Service::tousAvecReparateur() as $service) {
            $parReparateur[$service['reparateur_id']][] = $service;
        }
        foreach ($reparateurs as &$reparateur) {
            $reparateur['services'] = $parReparateur[$reparateur['id_utilisateur']] ?? [];
        }
        unset($reparateur);

        $this->render('home/carte', ['reparateurs' => $reparateurs], 'Carte des réparateurs');
    }

    // ---- Tableau de bord : redirige vers l'espace du PROFIL ACTIF ----
    public function dashboard(): void
    {
        Auth::requireLogin();

        $accueils = [
            'client'     => 'mesReservations',
            'reparateur' => 'reparateurAccueil',
            'moderateur' => 'moderateurAccueil',
        ];
        $this->redirect($accueils[(string)Auth::roleActif()] ?? 'accueil');
    }

    // ---- Changement de profil (client <-> réparateur <-> modérateur) ----
    public function basculerProfil(): void
    {
        Auth::requireLogin();

        $roleDemande = (string)($_GET['role'] ?? '');
        $roles       = Auth::roles();

        // On ne peut basculer que vers un rôle réellement possédé
        if (!in_array($roleDemande, $roles, true)) {
            $this->flash('error', "Impossible de changer de profil.");
            $this->redirect('dashboard');
        }

        Auth::setRoleActif($roleDemande);
        $this->flash('success', 'Profil actif : ' . Auth::libelleRole($roleDemande) . '.');

        $accueils = [
            'client'     => 'mesReservations',
            'reparateur' => 'reparateurAccueil',
            'moderateur' => 'moderateurAccueil',
        ];
        $this->redirect($accueils[$roleDemande] ?? 'accueil');
    }
}