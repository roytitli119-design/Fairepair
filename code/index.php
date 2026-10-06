<?php
// ============================================================
//  FAIR'REPAIR — Compatibilité Apache / XAMPP
//  Si le DocumentRoot mène au dossier racine de l'appli (et non
//  à public/), ce fichier bascule automatiquement vers le vrai
//  point d'entrée MVC (public/index.php). En local on sert
//  directement public/ avec : php -S 127.0.0.1:8000 -t public
// ============================================================
require __DIR__ . '/public/index.php';