<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\AuthRemember;
use App\Models\Utilisateur;

// Contrôleur d'authentification : inscription, connexion, déconnexion,
// mot de passe oublié, création du mot secret, lien token.
class AuthController extends Controller
{
    // ---- Connexion ----
    public function connexion(): void
    {
        if (Auth::check()) {
            $this->redirect('accueil');
        }

        $errors = [];

        if ($this->isPost()) {
            $email      = trim($this->post('email'));
            $motDePasse = $this->post('mot_de_passe');
            $seSouvenir = isset($_POST['se_souvenir']);   // case « se souvenir de moi »

            if ($email === '' || $motDePasse === '') {
                $errors[] = "Merci de renseigner votre email et votre mot de passe.";
            } else {
                $resultat = Utilisateur::verifierConnexion($email, $motDePasse);

                if ($resultat['ok']) {
                    $user = $resultat['user'];
                    Auth::login((int)$user['id'], $user['prenom']);

                    // Profil affiché au démarrage : réparateur > modérateur > client.
                    // L'utilisateur pourra ensuite basculer avec le bouton
                    // « Changer de profil ».
                    Auth::setRoleActif((string)Auth::roleParDefaut());

                    // ---- « Se souvenir de moi » ----
                    // Case cochée : on dépose un cookie valable 30 jours
                    // (le serveur ne garde que l'empreinte du jeton).
                    // Case non cochée : on oublie tous les appareils mémorisés.
                    if ($seSouvenir) {
                        definirCookieMemorise(AuthRemember::creer((int)$user['id']));
                    } else {
                        AuthRemember::supprimerTout((int)$user['id']);
                        supprimerCookieMemorise();
                    }

                    $this->redirect('dashboard');
                }
                if ($resultat['message'] !== null) {
                    $errors[] = $resultat['message'];
                }
            }
        }

        $this->render('auth/connexion', ['errors' => $errors], 'Connexion');
    }

    // ---- Inscription (CGU + mot de passe RGPD, puis mot secret obligatoire) ----
    public function inscription(): void
    {
        if (Auth::check()) {
            $this->redirect('accueil');
        }

        $errors = [];

        if ($this->isPost()) {
            $prenom       = trim($this->post('prenom'));
            $nom          = trim($this->post('nom'));
            $email        = trim($this->post('email'));
            $motDePasse   = $this->post('mot_de_passe');
            $confirmation = $this->post('confirmation');
            $cguAcceptee  = isset($_POST['cgu']);

            // ---- Vérifications ----
            if ($prenom === '' || $nom === '' || $email === '' || $motDePasse === '') {
                $errors[] = "Merci de renseigner tous les champs.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'adresse email n'est pas valide.";
            }
            // Politique de mot de passe (recommandations CNIL / RGPD)
            foreach (validerMotDePasse($motDePasse) as $erreur) {
                $errors[] = $erreur;
            }
            if ($motDePasse !== $confirmation) {
                $errors[] = "Les deux mots de passe ne correspondent pas.";
            }
            // Consentement obligatoire aux CGU (trace conservée en base)
            if (!$cguAcceptee) {
                $errors[] = "Vous devez accepter les conditions générales d'utilisation pour créer un compte.";
            }

            if (empty($errors) && Utilisateur::emailExiste($email)) {
                $errors[] = "Un compte existe déjà avec cet email.";
            }

            // ---- Création du compte (rôle client par défaut) ----
            if (empty($errors)) {
                try {
                    $userId = Utilisateur::creerClient($prenom, $nom, $email, $motDePasse);

                    // Connexion automatique + passage OBLIGATOIRE par le mot secret
                    Auth::login($userId, $prenom);
                    $this->flash('success', 'Compte créé avec succès. Définissez maintenant votre mot secret pour sécuriser votre compte.');
                    $this->redirect('creerMotSecret');
                } catch (\Throwable $e) {
                    $errors[] = "Une erreur est survenue lors de la création du compte.";
                }
            }
        }

        $this->render('auth/inscription', ['errors' => $errors], 'Créer un compte');
    }

    // ---- Déconnexion ----
    public function deconnexion(): void
    {
        Auth::logout();
        $this->redirect('accueil');
    }

    // ---- Mot de passe oublié (réinitialisation par mot secret) ----
    public function motDePasseOublie(): void
    {
        if (Auth::check()) {
            $this->redirect('accueil');
        }

        $errors = [];
        $succes = false;

        if ($this->isPost()) {
            $email        = trim($this->post('email'));
            $motSecret    = $this->post('mot_secret');
            $motDePasse   = $this->post('mot_de_passe');
            $confirmation = $this->post('confirmation');

            if ($email === '' || $motSecret === '' || $motDePasse === '') {
                $errors[] = "Merci de remplir tous les champs.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'adresse email n'est pas valide.";
            }
            if (strlen($motDePasse) > 72) {
                $errors[] = "Le mot de passe ne doit pas dépasser 72 caractères.";
            }
            foreach (validerMotDePasse($motDePasse) as $erreur) {
                $errors[] = $erreur;
            }
            if ($motDePasse !== $confirmation) {
                $errors[] = "Les deux mots de passe ne correspondent pas.";
            }

            if (empty($errors)) {
                // Message identique dans les cas d'échec (anti-énumération) :
                // on ne révèle pas si le compte existe, ni si le mot secret est bon.
                // Après 3 échecs, le mot secret est bloqué 15 minutes.
                $reinitialise = Utilisateur::reinitialiserParMotSecret($email, $motSecret, $motDePasse);
                if ($reinitialise === 'ok') {
                    $succes = true;
                } elseif ($reinitialise === 'bloque') {
                    $errors[] = "Trop de tentatives échouées sur le mot secret. Compte temporairement bloqué, réessayez dans 15 minutes.";
                } else {
                    $errors[] = "Email ou mot secret incorrect. Le mot secret se définit dans votre profil.";
                }
            }
        }

        $this->render(
            'auth/mot_de_passe_oublie',
            ['errors' => $errors, 'succes' => $succes],
            'Mot de passe oublié'
        );
    }


    // ---- Définition du mot secret après la création du compte ----
    public function creerMotSecret(): void
    {
        Auth::requireLogin();

        $errors = [];

        if ($this->isPost()) {
            $motSecret     = $this->post('mot_secret');
            $motSecretConf = $this->post('mot_secret_confirmation');

            if (strlen($motSecret) < 4) {
                $errors[] = "Le mot secret doit contenir au moins 4 caractères.";
            }
            if ($motSecret !== $motSecretConf) {
                $errors[] = "Les deux mots secrets ne correspondent pas.";
            }

            if (empty($errors)) {
                Utilisateur::definirMotSecret(Auth::id(), $motSecret);
                $this->flash('success', 'Mot secret enregistré. Vous pouvez maintenant utiliser votre compte.');
                $this->redirect('accueil');
            }
        }

        $this->render('auth/creer_mot_secret', ['errors' => $errors], 'Définir mon mot secret');
    }
}