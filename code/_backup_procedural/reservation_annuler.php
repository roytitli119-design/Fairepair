<?php
require 'config/config.php';
require 'includes/functions.php';
requireLogin();

$reservationId = (int)($_GET['id'] ?? 0);

// On ne peut annuler que SA PROPRE réservation, et seulement si elle est encore en attente
$stmt = $pdo->prepare(
    "UPDATE reservation
     SET statut = 'annulee'
     WHERE id = ? AND client_id = ? AND statut = 'en_attente'"
);
$stmt->execute([$reservationId, currentUserId()]);

if ($stmt->rowCount() > 0) {
    setFlash('success', 'Réservation annulée.');
} else {
    setFlash('error', "Impossible d'annuler cette réservation.");
}

redirect('mes_reservations.php');
