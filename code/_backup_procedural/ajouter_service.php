<?php
require 'config/config.php';
require 'includes/functions.php';

requireReparateur($pdo);
$reparateurId = currentUserId();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tarif       = str_replace(',', '.', trim($_POST['tarif'] ?? ''));
    $rayon       = (int)($_POST['rayon'] ?? 0);

    // ---- Vérifications ----
    if ($titre === '' || $description === '') {
        $errors[] = "Le titre et la description sont obligatoires.";
    }
    if (!is_numeric($tarif) || (float)$tarif <= 0) {
        $errors[] = "Le tarif doit être un montant positif (ex. : 25.50).";
    }
    if ($rayon <= 0) {
        $errors[] = "Le rayon d'action doit être un nombre de kilomètres positif.";
    }

    // ---- Upload de l'image (facultatif mais sécurisé) ----
    $image = null;
    if (empty($errors) && !empty($_FILES['image']['name'])) {
        $image = gererUpload('image', $errors);
    }

    // ---- Enregistrement ----
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO service (titre, description, tarif, rayon, image, reparateur_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$titre, $description, (float)$tarif, $rayon, $image, $reparateurId]);

        setFlash('success', "Votre service « $titre » a bien été publié.");
        redirect('mes_services.php');
    }
}

$pageTitle = "Ajouter un service";
require 'includes/header.php';
?>

<h1>Ajouter un service</h1>

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
        <input type="text" name="titre" value="<?= e($_POST['titre'] ?? '') ?>" required maxlength="100">
    </label>
    <label>Description (type de réparation, matériel, etc.)
        <textarea name="description" rows="4" required><?= e($_POST['description'] ?? '') ?></textarea>
    </label>
    <label>Tarif (€)
        <input type="text" name="tarif" value="<?= e($_POST['tarif'] ?? '') ?>" required placeholder="25.50">
    </label>
    <label>Rayon d'action (km)
        <input type="number" name="rayon" min="1" value="<?= e($_POST['rayon'] ?? '') ?>" required>
    </label>
    <label>Photo (JPG, PNG ou WebP, 2 Mo max — facultatif)
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
    </label>
    <button type="submit">Publier le service</button>
</form>

<p><a href="reparateur_accueil.php">← Retour à mon espace réparateur</a></p>

<?php require 'includes/footer.php'; ?>