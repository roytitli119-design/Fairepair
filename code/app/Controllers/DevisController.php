<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Devis;
use App\Models\Reservation;

// Contrôleur des devis :
//   - le réparateur établit un devis pour UNE de ses réservations
//   - le client accepte ou refuse (un refus annule la réservation)
// Sans devis accepté, l'intervention n'a pas lieu.
class DevisController extends Controller
{
    // ---- Réparateur : créer le devis d'une réservation ----
    public function nouveau(): void
    {
        Auth::requireReparateur();
        $reparateurId = Auth::id();

        $reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);

        // Garde-fou : la réservation doit porter sur UN de mes services
        $reservation = Reservation::appartenantA($reservationId, $reparateurId);
        if (!$reservation) {
            $this->flash('error', "Cette réservation n'existe pas ou ne vous appartient pas.");
            $this->redirect('reparateurAccueil');
        }

        // Un seul devis par réservation (contrainte UNIQUE en base)
        if (Devis::existePourReservation($reservationId)) {
            $this->flash('error', "Un devis a déjà été envoyé pour cette réservation.");
            $this->redirect('reparateurAccueil');
        }

        $errors = [];

        if ($this->isPost()) {
            $description = trim($this->post('description_intervention'));
            $mainOeuvre  = str_replace(',', '.', trim($this->post('main_oeuvre')));
            $coutPieces  = str_replace(',', '.', trim($this->post('cout_pieces')));
            $delaiJours  = (int)$this->post('delai_jours');
            $remarque    = trim($this->post('remarque'));
            $remarque    = $remarque !== '' ? $remarque : null;

            // ---- Vérifications ----
            if ($description === '') {
                $errors[] = "Merci de décrire l'intervention prévue.";
            }
            if (!is_numeric($mainOeuvre) || (float)$mainOeuvre < 0) {
                $errors[] = "La main-d'œuvre doit être un montant positif (ex. : 45.00).";
            }
            if (!is_numeric($coutPieces) || (float)$coutPieces < 0) {
                $errors[] = "Le coût des pièces doit être un montant positif (ex. : 20.50).";
            }
            if ($delaiJours < 1) {
                $errors[] = "Le délai doit être d'au moins 1 jour.";
            }

            // ---- Enregistrement ----
            if (empty($errors)) {
                Devis::creer(
                    $reservationId,
                    $description,
                    (float)$mainOeuvre,
                    (float)$coutPieces,
                    $delaiJours,
                    $remarque
                );
                $this->flash('success', "Devis envoyé au client. Il doit l'accepter avant l'intervention.");
                $this->redirect('reparateurAccueil');
            }
        }

        $this->render('devis/nouveau', [
            'reservation'   => $reservation,
            'reservationId' => $reservationId,
            'errors'        => $errors,
        ], 'Nouveau devis', 'sidebar');
    }

    // ---- Client : accepter ou refuser un devis ----
    public function repondre(): void
    {
        Auth::requireLogin();
        $clientId = Auth::id();

        $devisId       = (int)$this->post('devis_id');
        $decision      = $this->post('decision');
        $reservationId = (int)$this->post('reservation_id');

        // 1. Le devis doit exister
        $devis = Devis::trouver($devisId);
        if (!$devis) {
            $this->flash('error', "Devis introuvable.");
            $this->redirect('mesReservations');
        }

        // 2. La réservation doit être LA SIENNE (anti-énumération)
        $reservation = Reservation::pouvoirPayer($reservationId, $clientId);
        if (!$reservation || (int)$devis['reservation_id'] !== (int)$reservationId) {
            $this->flash('error', "Ce devis ne vous concerne pas.");
            $this->redirect('mesReservations');
        }

        // 3. Décision dans une liste blanche
        if (!in_array($decision, ['accepter', 'refuser'], true)) {
            $this->flash('error', "Merci de choisir une réponse valide.");
            $this->redirect('mesReservations');
        }

        // 4. On applique (le modèle interdit une double réponse)
        if ($decision === 'accepter') {
            if (Devis::accepter($devisId)) {
                $this->flash('success', "Devis accepté. L'intervention peut être confirmée par le réparateur.");
            } else {
                $this->flash('error', "Ce devis a déjà reçu une réponse.");
            }
        } else {
            if (Devis::refuser($devisId)) {
                // Un refus annule la réservation (règle métier)
                Reservation::annuler($reservationId, $clientId);
                $this->flash('success', "Devis refusé. La réservation a été annulée.");
            } else {
                $this->flash('error', "Ce devis a déjà reçu une réponse.");
            }
        }

        $this->redirect('mesReservations');
    }
}
