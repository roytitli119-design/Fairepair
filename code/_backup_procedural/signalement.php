<?php
require 'config/config.php';
require 'includes/functions.php';
requireLogin();

$reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT r.id, s.titre
     FROM reservation r
     INNER JOIN service s ON s.id = r.service_id
     WHERE r.id = ? AND r.client_id = ?"
);
$stmt->execute([$reservationId, currentUserId()]);
$reservation = $stmt->fetch();

if (!$reservation) {
    setFlash('error', "Réservation introuvable.");
    redirect('mes_reservations.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motif       = trim($_POST['motif'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($motif === '' || $description === '') {
        $errors[] = "Merci de renseigner le motif et une description.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO signalement (motif, description, statut, reservation_id, client_id)
             VALUES (?, ?, 'ouvert', ?, ?)"
        );
        $stmt->execute([$motif, $description, $reservationId, currentUserId()]);

        setFlash('success', 'Votre signalement a bien été transmis à un modérateur.');
        redirect('mes_reservations.php');
    }
}

$pageTitle = "Signaler un problème";
require 'includes/header.php';
?>

<h1>Signaler un problème — <?= e($reservation['titre']) ?></h1>

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
    <label>Motif
        <input type="text" name="motif" placeholder="Ex : arnaque, litige, service non rendu..." value="<?= e($_POST['motif'] ?? '') ?>" required>
    </label>
    <label>Description détaillée
        <textarea name="description" rows="5" required><?= e($_POST['description'] ?? '') ?></textarea>
    </label>
    <button type="submit">Envoyer le signalement</button>
</form>

<?php require 'includes/footer.php'; ?>
