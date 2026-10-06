<?php
require 'config/config.php';
require 'includes/functions.php';

requireReparateur($pdo);
$reparateurId = currentUserId();

$serviceId = (int)($_GET['id'] ?? 0);

// On ne charge QUE les services appartenant au réparateur connecté
$stmt = $pdo->prepare("SELECT * FROM service WHERE id = ? AND reparateur_id = ?");
$stmt->execute([$serviceId, $reparateurId]);
$service = $stmt->fetch();

if (!$service) {
    setFlash('error', "Ce service n'existe pas ou ne vous appartient pas.");
    redirect('mes_services.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tarif       = str_replace(',', '.', trim($_POST['tarif'] ?? ''));
    $rayon       = (int)($_POST['rayon'] ?? 0);

    if ($titre === '' || $description === '') {
        $errors[] = "Le titre et la description sont obligatoires.";
    }
    if (!is_numeric($tarif) || (float)$tarif <= 0) {
        $errors[] = "Le tarif doit être un montant positif (ex. : 25.50).";
    }
    if ($rayon <= 0) {
        $errors[] = "Le rayon d'action doit être un nombre de kilomètres positif.";
    }

    $imagesupprimer = false;
    $nouvelleImage = null;
    if (empty($errors) && !empty($_FILES['image']['name'])) {
        // Nouvelle image fournie : on l'upload, puis on supprime l'ancienne
        $nouvelleImage = gererUpload('image', $errors);
        $imagesupprimer = true;
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "UPDATE service
             SET titre = ?, description = ?, tarif = ?, rayon = ?,
                 image = COALESCE(?, image)
             WHERE id = ? AND reparateur_id = ?"
        );
        $stmt->execute([$titre, $description, (float)$tarif, $rayon, $nouvelleImage, $serviceId, $reparateurId]);

        // Supprimer l'ancienne image si elle a été remplacée
        if ($imagesupprimer && $service['image']) {
            $chemin = __DIR__ . '/' . $service['image'];
            if (is_file($chemin)) {
                unlink($chemin);
            }
        }

        setFlash('success', "Le service a été mis à jour.");
        redirect('mes_services.php');
    }
}

$pageTitle = "Modifier un service";
require 'includes/header.php';
?>

<h1>Modifier le service</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="form" enctype="multipart/form-data">
    <label>Titre du service
        <input type="text" name="titre" value="<?= e($titre ?? $service['titre']) ?>" required maxlength="100">
    </label>
    <label>Description
        <textarea name="description" rows="4" required><?= e($description ?? $service['description']) ?></textarea>
    </label>
    <label>Tarif (€)
        <input type="text" name="tarif" value="<?= e($tarif ?? $service['tarif']) ?>" required placeholder="25.50">
    </label>
    <label>Rayon d'action (km)
        <input type="number" name="rayon" min="1" value="<?= e($rayon ?? $service['rayon']) ?>" required>
    </label>
    <?php if (!empty($service['image'])): ?>
        <p class="muted">Image actuelle : <img src="<?= e($service['image']) ?>" alt="" class="service-img"></p>
    <?php endif; ?>
    <label>Nouvelle photo (facultatif — remplace l'actuelle)
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <button type="submit">Enregistrer les modifications</button>
</form>

<p><a href="mes_services.php">← Retour à mes services</a></p>

<?php require 'includes/footer.php'; ?>