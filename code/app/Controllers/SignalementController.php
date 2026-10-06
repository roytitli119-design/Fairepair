<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Reservation;
use App\Models\Signalement;

// Contrôleur des signalements : un client signale un problème
// sur l'une de ses réservations.
class SignalementController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);
        $reservation = Reservation::pouvoirSignalement($reservationId, Auth::id());

        if (!$reservation) {
            $this->flash('error', "Réservation introuvable.");
            $this->redirect('mesReservations');
        }

        $errors = [];

        if ($this->isPost()) {
            $motif       = trim($this->post('motif'));
            $description = trim($this->post('description'));

            if ($motif === '' || $description === '') {
                $errors[] = "Merci de renseigner le motif et une description.";
            }

            if (empty($errors)) {
                Signalement::creer($reservationId, Auth::id(), $motif, $description);
                $this->flash('success', 'Votre signalement a bien été transmis à un modérateur.');
                $this->redirect('mesReservations');
            }
        }

        $this->render('signalement/index', [
            'reservation'   => $reservation,
            'reservationId' => $reservationId,
            'errors'        => $errors,
        ], 'Signaler un problème', 'sidebar');
    }
}