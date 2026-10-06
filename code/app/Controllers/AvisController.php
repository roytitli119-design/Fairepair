<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Avis;
use App\Models\Reservation;

// Contrôleur des avis : un client note une réservation terminée (1 seul avis).
class AvisController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);
        $reservation = Reservation::pouvoirAvis($reservationId, Auth::id());

        // Un avis n'est possible que sur SA PROPRE réservation terminée
        if (!$reservation) {
            $this->flash('error', "Cette réservation ne permet pas de laisser un avis.");
            $this->redirect('mesReservations');
        }

        // Vérifie qu'un avis n'a pas déjà été laissé (contrainte UNIQUE en base)
        if (Avis::existePourReservation($reservationId)) {
            $this->flash('error', "Vous avez déjà laissé un avis pour cette réservation.");
            $this->redirect('mesReservations');
        }

        $errors = [];

        if ($this->isPost()) {
            $note        = (int)$this->post('note');
            $commentaire = trim($this->post('commentaire'));

            if ($note < 1 || $note > 5) {
                $errors[] = "La note doit être comprise entre 1 et 5.";
            }

            if (empty($errors)) {
                Avis::creer($note, $commentaire, $reservationId);
                $this->flash('success', 'Merci pour votre avis !');
                $this->redirect('mesReservations');
            }
        }

        $this->render('avis/index', [
            'reservation'   => $reservation,
            'reservationId' => $reservationId,
            'errors'        => $errors,
        ], 'Laisser un avis', 'sidebar');
    }
}