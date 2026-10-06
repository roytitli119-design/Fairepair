<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Avis;
use App\Models\Reparateur;
use App\Models\Reservation;
use App\Models\Service;

// Contrôleur de l'espace réparateur : accueil (RDVs), services, gains,
// et du profil public d'un réparateur (visibles par tous).
class ReparateurController extends Controller
{
    // Profil PUBLIC d'un réparateur : entreprise, services, avis validés
    // (accessible sans connexion, lien depuis accueil / fiche service / carte).
    public function profilPublic(): void
    {
        $Id = $this->get('id');
        $reparateur = Reparateur::profilPublic($Id);

        if (!$reparateur) {
            $this->flash('error', "Ce réparateur n'existe pas.");
            $this->redirect('accueil');
        }

        $this->render('reparateur/profil_public', [
            'reparateur' => $reparateur,
            'services'   => Service::pourReparateur($Id),
            'avis'       => Avis::publicsPourReparateur($Id),
        ], 'Profil de ' . $reparateur['prenom'] . ' ' . $reparateur['nom']);
    }

    // Accueil : demandes en attente, RDVs à venir, historique
    public function accueil(): void
    {
        Auth::requireReparateur();
        $reparateurId = Auth::id();

        // ---- Actions sur une réservation (confirmée / annulée / terminée) ----
        $action        = $this->post('action');
        $reservationId = (int)$this->post('reservation_id');

        if ($reservationId > 0 && in_array($action, ['confirmer', 'annuler', 'terminer'], true)) {
            $nouveauStatut = [
                'confirmer' => 'confirmee',
                'annuler'   => 'annulee',
                'terminer'  => 'terminee',
            ][$action];

            if (Reservation::changerStatut($reservationId, $reparateurId, $nouveauStatut)) {
                $messages = [
                    'confirmer' => "Réservation confirmée. Elle apparaît dans vos RDVs à venir.",
                    'annuler'   => "Réservation annulée.",
                    'terminer'  => "Intervention marquée comme terminée. Elle compte dans vos gains.",
                ];
                $this->flash('success', $messages[$action]);
            } else {
                $this->flash('error', "Impossible de modifier cette réservation.");
            }
            $this->redirect('reparateurAccueil');
        }

        $this->render('reparateur/accueil', [
            'enAttente'  => Reservation::enAttente($reparateurId),
            'aVenir'     => Reservation::aVenir($reparateurId),
            'historique' => Reservation::historique($reparateurId),
        ], 'Espace réparateur', 'sidebar');
    }

    // Publication d'un nouveau service
    public function ajouterService(): void
    {
        Auth::requireReparateur();
        $reparateurId = Auth::id();
        $errors = [];

        if ($this->isPost()) {
            $titre       = trim($this->post('titre'));
            $description = trim($this->post('description'));
            $tarif       = str_replace(',', '.', trim($this->post('tarif')));
            $rayon       = (int)$this->post('rayon');

            // ---- Vérifications ----
            if ($titre === '' || $description === '') {
                $errors[] = "Le titre et la description sont obligatoires.";
            }
            if (!is_numeric($tarif) || (float)$tarif <= 0) {
                $errors[] = "Le tarif doit être un montant positif (ex. : 25.50).";
            }
            if ($rayon <= 0) {
                $errors[] = "Le rayon d'action doit être un nombre de kilomètres positif.";
            }

            // ---- Upload de l'image (facultatif mais sécurisé) ----
            $image = null;
            if (empty($errors) && !empty($_FILES['image']['name'])) {
                $image = gererUpload('image', $errors);
            }

            // ---- Enregistrement ----
            if (empty($errors)) {
                Service::creer($reparateurId, $titre, $description, (float)$tarif, $rayon, $image);
                $this->flash('success', "Votre service « $titre » a bien été publié.");
                $this->redirect('mesServices');
            }
        }

        $this->render('reparateur/ajouter_service', ['errors' => $errors], 'Ajouter un service', 'sidebar');
    }

    // Liste de ses services (+ suppression)
    public function mesServices(): void
    {
        Auth::requireReparateur();
        $reparateurId = Auth::id();

        // ---- Suppression (seulement si c'est le sien) ----
        if ($this->isPost() && $this->post('action') === 'supprimer') {
            $serviceId = (int)$this->post('service_id');
            $supprime = Service::supprimer($serviceId, $reparateurId);

            if ($supprime) {
                if (!empty($supprime['image'])) {
                    $chemin = PUBLIC_ROOT . '/' . $supprime['image'];
                    if (is_file($chemin)) {
                        unlink($chemin);
                    }
                }
                $this->flash('success', "Le service a été supprimé.");
            } else {
                $this->flash('error', "Impossible de supprimer ce service.");
            }
            $this->redirect('mesServices');
        }

        $this->render('reparateur/mes_services', [
            'services' => Service::mesServices($reparateurId),
        ], 'Mes services', 'sidebar');
    }

    // Modification d'un service (sa photo remplaçable)
    public function editerService(): void
    {
        Auth::requireReparateur();
        $reparateurId = Auth::id();
        $serviceId = $this->get('id');

        // On ne charge QUE les services appartenant au réparateur connecté
        $service = Service::appartenantA($serviceId, $reparateurId);
        if (!$service) {
            $this->flash('error', "Ce service n'existe pas ou ne vous appartient pas.");
            $this->redirect('mesServices');
        }

        $errors = [];

        if ($this->isPost()) {
            $titre       = trim($this->post('titre'));
            $description = trim($this->post('description'));
            $tarif       = str_replace(',', '.', trim($this->post('tarif')));
            $rayon       = (int)$this->post('rayon');

            if ($titre === '' || $description === '') {
                $errors[] = "Le titre et la description sont obligatoires.";
            }
            if (!is_numeric($tarif) || (float)$tarif <= 0) {
                $errors[] = "Le tarif doit être un montant positif (ex. : 25.50).";
            }
            if ($rayon <= 0) {
                $errors[] = "Le rayon d'action doit être un nombre de kilomètres positif.";
            }

            $remplaceImage = false;
            $nouvelleImage = null;
            if (empty($errors) && !empty($_FILES['image']['name'])) {
                // Nouvelle image fournie : on l'upload puis on supprime l'ancienne
                $nouvelleImage = gererUpload('image', $errors);
                $remplaceImage = true;
            }

            if (empty($errors)) {
                Service::modifier($serviceId, $reparateurId, $titre, $description, (float)$tarif, $rayon, $nouvelleImage);

                if ($remplaceImage && !empty($service['image'])) {
                    $chemin = PUBLIC_ROOT . '/' . $service['image'];
                    if (is_file($chemin)) {
                        unlink($chemin);
                    }
                }

                $this->flash('success', "Le service a été mis à jour.");
                $this->redirect('mesServices');
            }
        }

        $this->render('reparateur/editer_service', [
            'service' => $service,
            'errors'  => $errors,
        ], 'Modifier un service', 'sidebar');
    }

    // Mes gains : CA, encaissé, interventions, détail des paiements
    public function mesGains(): void
    {
        Auth::requireReparateur();
        $reparateurId = Auth::id();

        $this->render('reparateur/mes_gains', [
            'statsTerminees' => Reservation::statsTerminees($reparateurId),
            'statsPaiements' => Reservation::statsPaiements($reparateurId),
            'nbAttente'      => Reservation::nbEnAttente($reparateurId),
            'lignes'         => Reservation::detailGains($reparateurId),
        ], 'Mes gains', 'sidebar');
    }
}