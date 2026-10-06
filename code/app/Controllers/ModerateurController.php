<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Avis;
use App\Models\Signalement;

// Contrôleur de l'espace modérateur : tableau de bord, signalements,
// modération des avis.
class ModerateurController extends Controller
{
    // Tableau de bord avec compteurs
    public function accueil(): void
    {
        Auth::requireModerateur();
        $pdo = Database::get();

        $this->render('moderateur/accueil', [
            'nbUtilisateurs'        => (int)$pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn(),
            'nbClients'             => (int)$pdo->query("SELECT COUNT(*) FROM client")->fetchColumn(),
            'nbReparateurs'         => (int)$pdo->query("SELECT COUNT(*) FROM reparateur")->fetchColumn(),
            'nbServices'            => (int)$pdo->query("SELECT COUNT(*) FROM service")->fetchColumn(),
            'nbReservations'        => (int)$pdo->query("SELECT COUNT(*) FROM reservation")->fetchColumn(),
            'nbSignalementsOuverts' => (int)$pdo->query(
                "SELECT COUNT(*) FROM signalement WHERE statut IN ('ouvert', 'en_cours')"
            )->fetchColumn(),
            'nbAvisAModerer'        => (int)$pdo->query(
                "SELECT COUNT(*) FROM avis WHERE moderateur_id IS NULL"
            )->fetchColumn(),
            'derniersSignalements'  => Signalement::dernierApercu(),
        ], 'Tableau de bord modérateur', 'sidebar');
    }

    // Liste complète des signalements
    public function signalements(): void
    {
        Auth::requireModerateur();

        $this->render('moderateur/signalements', [
            'signalements' => Signalement::tousAvecInfos(),
        ], 'Signalements', 'sidebar');
    }

    // Détail d'un signalement + changement de statut
    public function signalementDetail(): void
    {
        Auth::requireModerateur();

        $signalementId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $signalement = Signalement::trouverDetail($signalementId);

        if (!$signalement) {
            $this->flash('error', "Signalement introuvable.");
            $this->redirect('signalements');
        }

        $statutsValides = ['ouvert', 'en_cours', 'resolu', 'rejete'];
        $errors = [];

        if ($this->isPost()) {
            $nouveauStatut = $this->post('statut');

            if (!in_array($nouveauStatut, $statutsValides, true)) {
                $errors[] = "Statut invalide.";
            }

            if (empty($errors)) {
                // Le modérateur connecté prend en charge / clôt le signalement
                Signalement::changerStatut($signalementId, $nouveauStatut, Auth::id());
                $this->flash('success', "Signalement mis à jour (statut : $nouveauStatut).");
                $this->redirect('signalements');
            }
        }

        $this->render('moderateur/signalement_detail', [
            'signalement'    => $signalement,
            'statutsValides' => $statutsValides,
            'errors'         => $errors,
        ], 'Signalement #' . $signalement['id'], 'sidebar');
    }

    // Modération des avis (valider / supprimer)
    public function avisModeration(): void
    {
        Auth::requireModerateur();

        if ($this->isPost()) {
            $action = $this->post('action');
            $avisId = (int)$this->post('avis_id');

            if ($action === 'valider') {
                $this->flash('success', Avis::valider($avisId, Auth::id())
                    ? 'Avis validé.'
                    : "Impossible de valider cet avis (déjà modéré ou introuvable).");
            } elseif ($action === 'supprimer') {
                Avis::supprimer($avisId);
                $this->flash('success', 'Avis supprimé.');
            }
            $this->redirect('avisModeration');
        }

        $this->render('moderateur/avis_moderation', [
            'avisAModerer' => Avis::enAttente(),
            'avisModeres'  => Avis::moderes(),
        ], 'Modération des avis', 'sidebar');
    }
}