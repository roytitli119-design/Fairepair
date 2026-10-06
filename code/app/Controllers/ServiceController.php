<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Avis;
use App\Models\Service;

// Contrôleur des services côté visiteur : fiche publique d'un service.
class ServiceController extends Controller
{
    public function show(): void
    {
        $serviceId = $this->get('id');
        $service = Service::trouverAvecReparateur($serviceId);

        if (!$service) {
            $this->flash('error', "Ce service n'existe pas.");
            $this->redirect('accueil');
        }

        $this->render('service/show', [
            'service'   => $service,
            'avisStats' => Service::statsAvis($serviceId),
            'avis'      => Avis::publicsPourService($serviceId),
        ], $service['titre']);
    }
}