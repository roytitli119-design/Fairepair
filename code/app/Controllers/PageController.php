<?php
namespace App\Controllers;

use App\Core\Controller;

// Contrôleur des pages simples : conditions générales d'utilisation.
class PageController extends Controller
{
    // CGU + volet RGPD (ancre #rgpd)
    public function cgu(): void
    {
        $this->render('cgu/index', [], "Conditions générales d'utilisation");
    }
}