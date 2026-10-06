<?php
// ============================================================
//  FAIR'REPAIR — Configuration (chargée par app/bootstrap.php)
// ============================================================
// Host en 127.0.0.1 (TCP) plutôt que 'localhost' : fonctionne avec
// XAMPP comme avec le MariaDB système.
return [
    'host'   => '127.0.0.1',
    'dbname' => 'fairepair',
    'user'   => 'monuser',
    'pass'   => 'monmotdepasse',

    // ---- Paiement Stripe (MODE TEST uniquement) ----
    // Renseignez votre clé SECRÈTE « sk_test_… » (https://dashboard.stripe.com/test/apikeys)
    // pour activer le vrai tunnel Stripe Checkout (carte fictive 4242 4242 4242 4242).
    // Clé VIDE = le paiement reste simulé (carte / espèces / virement), pratique pour les démos.
    'stripe' => [
        'secret_key' => '',
    ],
];