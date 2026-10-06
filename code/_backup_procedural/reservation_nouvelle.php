<?php
require 'config/config.php';
require 'includes/functions.php';
requireLogin();

$serviceId = (int)($_GET['service_id'] ?? $_POST['service_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM service WHERE id = ?");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    setFlash('error', "Ce service n'existe pas.");
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adresse = trim($_POST['adresse_intervention'] ?? '');
    $date    = trim($_POST['date_intervention'] ?? '');

    if ($adresse === '' || $date === '') {
        $errors[] = "Merci de renseigner l'adresse et la date d'intervention.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO reservation (adresse_intervention, date_intervention, statut, client_id, service_id)
             VALUES (?, ?, 'en_attente', ?, ?)"
        );
        $stmt->execute([$adresse, $date, currentUserId(), $serviceId]);

        setFlash('success', 'Votre réservation a bien été enregistrée.');
        redirect('mes_reservations.php');
    }
}

$pageTitle = "Réserver - " . $service['titre'];
require 'includes/header.php';
?>

<h1>Réserver : <?= e($service['titre']) ?></h1>
<p><strong><?= number_format($service['tarif'], 2) ?> €</strong></p>

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
    <input type="hidden" name="service_id" value="<?= (int)$serviceId ?>">
    <label>Adresse d'intervention
        <input type="text" name="adresse_intervention" value="<?= e($_POST['adresse_intervention'] ?? '') ?>" required>
    </label>
    <label>Date et heure souhaitées
        <input type="datetime-local" name="date_intervention" value="<?= e($_POST['date_intervention'] ?? '') ?>" required>
    </label>
    <button type="submit">Confirmer la réservation</button>
</form>

<?php require 'includes/footer.php'; ?>
