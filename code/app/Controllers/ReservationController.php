<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Reservation;
use App\Models\Service;

// Contrôleur des réservations côté client.
class ReservationController extends Controller
{
    // Formulaire de création d'une réservation
    public function nouvelle(): void
    {
        Auth::requireLogin();

        $serviceId = (int)($_GET['service_id'] ?? $_POST['service_id'] ?? 0);
        $service = Service::trouver($serviceId);

        if (!$service) {
            $this->flash('error', "Ce service n'existe pas.");
            $this->redirect('accueil');
        }

        $errors = [];

        if ($this->isPost()) {
            $adresse = trim($this->post('adresse_intervention'));
            $date    = trim($this->post('date_intervention'));

            if ($adresse === '' || $date === '') {
                $errors[] = "Merci de renseigner l'adresse et la date d'intervention.";
            }

            if (empty($errors)) {
                Reservation::creer(Auth::id(), $serviceId, $adresse, $date);
                $this->flash('success', 'Votre réservation a bien été enregistrée.');
                $this->redirect('mesReservations');
            }
        }

        $this->render('reservation/nouvelle', [
            'service'   => $service,
            'serviceId' => $serviceId,
            'errors'    => $errors,
        ], 'Réserver - ' . $service['titre'], 'sidebar');
    }

    // Liste de ses réservations
    public function mesReservations(): void
    {
        Auth::requireLogin();

        $this->render('reservation/mes_reservations', [
            'reservations' => Reservation::mesReservations(Auth::id()),
        ], 'Mes réservations', 'sidebar');
    }

    // Annulation d'une réservation (sa propre, si encore en attente)
    public function annuler(): void
    {
        Auth::requireLogin();

        $reservationId = $this->get('id');

        if (Reservation::annuler($reservationId, Auth::id())) {
            $this->flash('success', 'Réservation annulée.');
        } else {
            $this->flash('error', "Impossible d'annuler cette réservation.");
        }
        $this->redirect('mesReservations');
    }
}