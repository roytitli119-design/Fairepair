<?php
require 'config/config.php';
require 'includes/functions.php';

$serviceId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT s.*, u.prenom AS rep_prenom, u.nom AS rep_nom, u.ville
     FROM service s
     INNER JOIN reparateur r ON r.id_utilisateur = s.reparateur_id
     INNER JOIN utilisateur u ON u.id = r.id_utilisateur
     WHERE s.id = ?"
);
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    setFlash('error', "Ce service n'existe pas.");
    redirect('index.php');
}

// Moyenne des avis pour ce service (via ses réservations)
$stmt = $pdo->prepare(
    "SELECT AVG(a.note) AS moyenne, COUNT(a.id) AS total
     FROM avis a
     INNER JOIN reservation r ON r.id = a.reservation_id
     WHERE r.service_id = ?"
);
$stmt->execute([$serviceId]);
$avisStats = $stmt->fetch();

$pageTitle = $service['titre'];
require 'includes/header.php';
?>

<a href="index.php">&larr; Retour aux services</a>

<h1><?= e($service['titre']) ?></h1>
<p><?= nl2br(e($service['description'])) ?></p>
<p><strong><?= number_format($service['tarif'], 2) ?> €</strong> — rayon d'action <?= (int)$service['rayon'] ?> km</p>
<p class="muted">Proposé par <?= e($service['rep_prenom'] . ' ' . $service['rep_nom']) ?> (<?= e($service['ville']) ?>)</p>

<?php if ($avisStats['total'] > 0): ?>
    <p>⭐ <?= number_format($avisStats['moyenne'], 1) ?> / 5 (<?= (int)$avisStats['total'] ?> avis)</p>
<?php else: ?>
    <p class="muted">Aucun avis pour l'instant</p>
<?php endif; ?>

<?php if (isLoggedIn()): ?>
    <a href="reservation_nouvelle.php?service_id=<?= (int)$service['id'] ?>" class="btn">Réserver ce service</a>
<?php else: ?>
    <p><a href="connexion.php">Connectez-vous</a> pour réserver ce service.</p>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
