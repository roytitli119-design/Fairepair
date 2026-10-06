<?php
// ============================================================
//  FAIR'REPAIR — Front controller (point d'entrée unique MVC)
//  Toutes les URLs passent ici : index.php?route=…
// ============================================================
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\PageController;
use App\Controllers\ProfilController;
use App\Controllers\ServiceController;
use App\Controllers\ReservationController;
use App\Controllers\PaiementController;
use App\Controllers\AvisController;
use App\Controllers\SignalementController;
use App\Controllers\ReparateurController;
use App\Controllers\ModerateurController;

$routeur = new Router();

// ---- Pages publiques ----
$routeur->add('accueil',                 HomeController::class,        'index');
$routeur->add('',                        HomeController::class,        'index');
$routeur->add('cgu',                     PageController::class,        'cgu');
$routeur->add('service',                 ServiceController::class,     'show');
$routeur->add('carte',                   HomeController::class,        'carte');
$routeur->add('reparateurProfil',        ReparateurController::class,  'profilPublic');

// ---- Tableau de bord + changement de profil ----
$routeur->add('dashboard',               HomeController::class,        'dashboard');
$routeur->add('basculerProfil',          HomeController::class,        'basculerProfil');

// ---- Authentification / compte ----
$routeur->add('connexion',               AuthController::class,        'connexion');
$routeur->add('inscription',             AuthController::class,        'inscription');
$routeur->add('deconnexion',             AuthController::class,        'deconnexion');
$routeur->add('motDePasseOublie',        AuthController::class,        'motDePasseOublie');
$routeur->add('creerMotSecret',          AuthController::class,        'creerMotSecret');

// ---- Profil (client / réparateur / modérateur) ----
$routeur->add('profil',                  ProfilController::class,      'index');

// ---- Client : réservation, paiement, avis, signalement ----
$routeur->add('reservationNouvelle',     ReservationController::class, 'nouvelle');
$routeur->add('mesReservations',         ReservationController::class, 'mesReservations');
$routeur->add('reservationAnnuler',      ReservationController::class, 'annuler');
$routeur->add('paiement',                PaiementController::class,    'index');
$routeur->add('paiementValidation',      PaiementController::class,    'validation');
$routeur->add('avis',                    AvisController::class,        'index');
$routeur->add('signalement',             SignalementController::class, 'index');

// ---- Réparateur ----
$routeur->add('reparateurAccueil',       ReparateurController::class,  'accueil');
$routeur->add('ajouterService',          ReparateurController::class,  'ajouterService');
$routeur->add('mesServices',             ReparateurController::class,  'mesServices');
$routeur->add('editerService',           ReparateurController::class,  'editerService');
$routeur->add('mesGains',                ReparateurController::class,  'mesGains');

// ---- Modérateur ----
$routeur->add('moderateurAccueil',       ModerateurController::class,  'accueil');
$routeur->add('signalements',            ModerateurController::class,  'signalements');
$routeur->add('signalementDetail',       ModerateurController::class,  'signalementDetail');
$routeur->add('avisModeration',          ModerateurController::class,  'avisModeration');

// Route demandée (gardée en mémoire pour le header, ex. garde mot secret)
$route = $_GET['route'] ?? 'accueil';
$GLOBALS['currentRoute'] = $route;

$routeur->dispatch($route);