<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Utilisateur;

// Contrôleur du profil : informations personnelles, passage réparateur,
// entreprise, changement du mot secret (ancien obligatoire).
class ProfilController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $user = Utilisateur::trouver(Auth::id());
        $rep  = Utilisateur::infosReparateur(Auth::id()) ?? [];
        $errors = [];
        $action = $this->post('action');

        if ($this->isPost()) {
            if ($action === 'profil') {
                // ---- Mise à jour des informations personnelles ----
                $prenom     = trim($this->post('prenom'));
                $nom        = trim($this->post('nom'));
                $adresse    = trim($this->post('adresse'));
                $codePostal = trim($this->post('code_postal'));
                $ville      = trim($this->post('ville'));
                $telephone  = trim($this->post('telephone'));

                if ($prenom === '' || $nom === '') {
                    $errors[] = "Le prénom et le nom sont obligatoires.";
                }

                if (empty($errors)) {
                    Utilisateur::majProfil(Auth::id(), [
                        'prenom'      => $prenom,
                        'nom'         => $nom,
                        'adresse'     => $adresse,
                        'code_postal' => $codePostal,
                        'ville'       => $ville,
                        'telephone'   => $telephone,
                    ]);
                    $_SESSION['prenom'] = $prenom;
                    $this->flash('success', 'Profil mis à jour.');
                    $this->redirect('profil');
                }
            } elseif ($action === 'devenir_reparateur' || $action === 'infos_reparateur') {
                // ---- Entreprise (création ou modification) ----
                $nomEntreprise  = trim($this->post('nom_entreprise'));
                $siret          = trim($this->post('siret'));
                $descriptionPro = trim($this->post('description_pro'));

                // Position sur la carte (facultative). Accepte 48.8566 comme 48,8566.
                $latitude  = str_replace(',', '.', trim($this->post('latitude')));
                $longitude = str_replace(',', '.', trim($this->post('longitude')));

                if ($nomEntreprise === '') {
                    $errors[] = "Le nom de l'entreprise est obligatoire.";
                }
                if ($siret === '' || !preg_match('/^\d{14}$/', $siret)) {
                    $errors[] = "Le SIRET doit contenir exactement 14 chiffres.";
                }
                if ($latitude !== '' && (!is_numeric($latitude) || (float)$latitude < -90 || (float)$latitude > 90)) {
                    $errors[] = "La latitude doit être un nombre entre -90 et 90 (ex. 48.8566).";
                }
                if ($longitude !== '' && (!is_numeric($longitude) || (float)$longitude < -180 || (float)$longitude > 180)) {
                    $errors[] = "La longitude doit être un nombre entre -180 et 180 (ex. 2.3522).";
                }
                // Vide → null en base (le réparateur ne sera pas affiché sur la carte)
                $latitude  = $latitude  !== '' ? $latitude  : null;
                $longitude = $longitude !== '' ? $longitude : null;

                if (empty($errors)) {
                    if ($action === 'devenir_reparateur') {
                        Utilisateur::devenirReparateur(Auth::id(), $nomEntreprise, $siret, $descriptionPro, $latitude, $longitude);
                        // Le nouveau rôle devient le profil affiché
                        Auth::setRoleActif('reparateur');
                        $this->flash('success', "Félicitations, vous êtes maintenant réparateur !");
                    } else {
                        Utilisateur::majInfosReparateur(Auth::id(), $nomEntreprise, $siret, $descriptionPro, $latitude, $longitude);
                        $this->flash('success', "Informations de l'entreprise mises à jour.");
                    }
                    $this->redirect('profil');
                }
            } elseif ($action === 'mot_secret') {
                // ---- Changement du mot secret : l'ANCIEN est obligatoire ----
                $motSecretActuel = $this->post('mot_secret_actuel');
                $motSecret       = $this->post('mot_secret');
                $motSecretConf   = $this->post('mot_secret_confirmation');

                // 1. Vérifier l'ancien mot secret
                $erreurAncien = Utilisateur::verifierAncienMotSecret(Auth::id(), $motSecretActuel);
                if ($erreurAncien !== null) {
                    $errors[] = $erreurAncien;
                }
                // 2. Valider le nouveau mot secret
                if (strlen($motSecret) < 4) {
                    $errors[] = "Le nouveau mot secret doit contenir au moins 4 caractères.";
                }
                if ($motSecret !== $motSecretConf) {
                    $errors[] = "Les deux nouveaux mots secrets ne correspondent pas.";
                }

                if (empty($errors)) {
                    Utilisateur::definirMotSecret(Auth::id(), $motSecret);
                    $this->flash('success', 'Mot secret modifié avec succès.');
                    $this->redirect('profil');
                }
            }
        }

        $this->render('profil/index', [
            'user'   => $user,
            'rep'    => $rep,
            'errors' => $errors,
        ], 'Mon profil', 'sidebar');
    }
}