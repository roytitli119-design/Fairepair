<?php
require 'config/config.php';
require 'includes/functions.php';
requireLogin();

$reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);

// Un avis n'est possible que sur SA PROPRE réservation, et seulement si elle est terminée
$stmt = $pdo->prepare(
    "SELECT r.id, r.statut, s.titre
     FROM reservation r
     INNER JOIN service s ON s.id = r.service_id
     WHERE r.id = ? AND r.client_id = ? AND r.statut = 'terminee'"
);
$stmt->execute([$reservationId, currentUserId()]);
$reservation = $stmt->fetch();

if (!$reservation) {
    setFlash('error', "Cette réservation ne permet pas de laisser un avis.");
    redirect('mes_reservations.php');
}

// Vérifie qu'un avis n'a pas déjà été laissé (contrainte UNIQUE en base également)
$stmt = $pdo->prepare("SELECT id FROM avis WHERE reservation_id = ?");
$stmt->execute([$reservationId]);
if ($stmt->fetch()) {
    setFlash('error', "Vous avez déjà laissé un avis pour cette réservation.");
    redirect('mes_reservations.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note        = (int)($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');

    if ($note < 1 || $note > 5) {
        $errors[] = "La note doit être comprise entre 1 et 5.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO avis (note, commentaire, reservation_id) VALUES (?, ?, ?)"
        );
        $stmt->execute([$note, $commentaire, $reservationId]);

        setFlash('success', 'Merci pour votre avis !');
        redirect('mes_reservations.php');
    }
}

$pageTitle = "Laisser un avis";
require 'includes/header.php';
?>

<h1>Laisser un avis — <?= e($reservation['titre']) ?></h1>

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
    <label>Note (1 à 5)
        <select name="note" required>
            <option value="5">5 - Excellent</option>
            <option value="4">4 - Très bien</option>
            <option value="3">3 - Correct</option>
            <option value="2">2 - Décevant</option>
            <option value="1">1 - Mauvais</option>
        </select>
    </label>
    <label>Commentaire
        <textarea name="commentaire" rows="4"><?= e($_POST['commentaire'] ?? '') ?></textarea>
    </label>
    <button type="submit">Envoyer mon avis</button>
</form>

<?php require 'includes/footer.php'; ?>
