<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\StripeGateway;
use App\Models\Paiement;
use App\Models\Reservation;

// Contrôleur des paiements : vrai tunnel Stripe (mode test) quand une clé
// est configurée, sinon simulation (carte / espèces / virement).
class PaiementController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);
        $reservation = Reservation::pouvoirPayer($reservationId, Auth::id());

        if (!$reservation) {
            $this->flash('error', "Réservation introuvable.");
            $this->redirect('mesReservations');
        }

        // Empêche de payer deux fois la même réservation
        if (Paiement::existePourReservation($reservationId)) {
            $this->flash('error', "Cette réservation a déjà été payée.");
            $this->redirect('mesReservations');
        }

        // Retour d'annulation depuis la page Stripe : simple message informatif
        if (isset($_GET['annule'])) {
            $this->flash('info', "Paiement annulé. Vous pouvez réessayer quand vous voulez.");
        }

        $stripeDispo = StripeGateway::configuree();
        $errors = [];

        if ($this->isPost()) {
            if ($stripeDispo && $this->post('stripe') === '1') {
                // ---- Vrai paiement : renvoi vers la page Stripe Checkout ----
                // {CHECKOUT_SESSION_ID} est remplacé par Stripe par l'id de la
                // session : on peut ainsi vérifier le paiement au retour.
                $idSession    = '{CHECKOUT_SESSION_ID}';
                $urlSucces    = absoluteUrl('index.php?route=paiementValidation&reservation_id=' . $reservationId . '&session_id=' . $idSession);
                $urlAnnulation = absoluteUrl('index.php?route=paiement&reservation_id=' . $reservationId . '&annule=1');

                $urlStripe = StripeGateway::creerSessionCheckout(
                    $reservation['titre'],
                    (float)$reservation['tarif'],
                    $urlSucces,
                    $urlAnnulation
                );

                if ($urlStripe !== null) {
                    redirect($urlStripe); // redirection vers le site de Stripe
                }
                $errors[] = "Impossible de contacter Stripe pour le moment. Vérifiez la configuration de la clé API.";
            } elseif (!$stripeDispo) {
                // ---- Mode simulé (aucune clé Stripe configurée) ----
                $moyen = $this->post('moyen_paiement');

                if (!in_array($moyen, ['carte', 'especes', 'virement'], true)) {
                    $errors[] = "Merci de choisir un moyen de paiement valide.";
                }

                if (empty($errors)) {
                    // Simulation : aucune donnée de carte n'est jamais traitée ni stockée
                    Paiement::creer((float)$reservation['tarif'], $moyen, $reservationId);
                    $this->flash('success', 'Paiement enregistré avec succès.');
                    $this->redirect('mesReservations');
                }
            } else {
                $errors[] = "Merci de valider le paiement via le bouton « Payer par carte ».";
            }
        }

        $this->render('paiement/index', [
            'reservation'   => $reservation,
            'reservationId' => $reservationId,
            'stripeDispo'   => $stripeDispo,
            'errors'        => $errors,
        ], 'Paiement', 'sidebar');
    }

    // Retour de Stripe (success_url) : on vérifie que la session a bien été payée
    // PUIS on enregistre le paiement. Inutile pour le mode simulé.
    public function validation(): void
    {
        Auth::requireLogin();

        $reservationId = $this->get('reservation_id');
        $sessionId     = trim((string)($_GET['session_id'] ?? ''));

        $reservation = Reservation::pouvoirPayer($reservationId, Auth::id());
        if (!$reservation) {
            $this->flash('error', "Réservation introuvable.");
            $this->redirect('mesReservations');
        }
        if (Paiement::existePourReservation($reservationId)) {
            $this->flash('success', 'Cette réservation est déjà payée.');
            $this->redirect('mesReservations');
        }

        if ($sessionId === '' || !StripeGateway::configuree()) {
            $this->flash('error', "Retour de paiement invalide.");
            $this->redirect('mesReservations');
        }

        // Vérification côté Stripe : paiement réellement encaissé ?
        if (StripeGateway::sessionPayee($sessionId)) {
            // On n'enregistre QUE les paiements confirmés par Stripe
            Paiement::creer((float)$reservation['tarif'], 'carte', $reservationId);
            $this->flash('success', 'Paiement reçu avec succès ! Merci.');
        } else {
            $this->flash('error', "Le paiement n'a pas abouti. Vous pouvez réessayer.");
        }
        $this->redirect('mesReservations');
    }
}