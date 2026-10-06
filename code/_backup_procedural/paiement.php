<?php
require 'config/config.php';
require 'includes/functions.php';
requireLogin();

$reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT r.id, r.statut, s.titre, s.tarif
     FROM reservation r
     INNER JOIN service s ON s.id = r.service_id
     WHERE r.id = ? AND r.client_id = ? AND r.statut <> 'annulee'"
);
$stmt->execute([$reservationId, currentUserId()]);
$reservation = $stmt->fetch();

if (!$reservation) {
    setFlash('error', "Réservation introuvable.");
    redirect('mes_reservations.php');
}

// Empêche de payer deux fois la même réservation
$stmt = $pdo->prepare("SELECT id FROM paiement WHERE reservation_id = ?");
$stmt->execute([$reservationId]);
if ($stmt->fetch()) {
    setFlash('error', "Cette réservation a déjà été payée.");
    redirect('mes_reservations.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moyen = $_POST['moyen_paiement'] ?? '';

    if (!in_array($moyen, ['carte', 'especes', 'virement'], true)) {
        $errors[] = "Merci de choisir un moyen de paiement valide.";
    }

    if (empty($errors)) {
        // NOTE : ceci simule un paiement (pas de vraie intégration bancaire).
        // Aucune donnée de carte n'est jamais stockée, conformément au cahier des charges.
        $stmt = $pdo->prepare(
            "INSERT INTO paiement (montant, moyen_paiement, statut, reservation_id)
             VALUES (?, ?, 'valide', ?)"
        );
        $stmt->execute([$reservation['tarif'], $moyen, $reservationId]);

        setFlash('success', 'Paiement enregistré avec succès.');
        redirect('mes_reservations.php');
    }
}

$pageTitle = "Paiement";
require 'includes/header.php';
?>

<h1>Paiement — <?= e($reservation['titre']) ?></h1>
<p>Montant à régler : <strong><?= number_format($reservation['tarif'], 2) ?> €</strong></p>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="form">
    <input type="hidden" name="reservation_id" value="<?= (int)$reservationId ?>">
    <label>Moyen de paiement
        <select name="moyen_paiement" required>
            <option value="carte">Carte bancaire</option>
            <option value="especes">Espèces</option>
            <option value="virement">Virement</option>
        </select>
    </label>
    <button type="submit">Payer</button>
</form>

<?php require 'includes/footer.php'; ?>
