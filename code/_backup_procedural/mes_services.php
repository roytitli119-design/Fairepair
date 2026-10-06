<?php
require 'config/config.php';
require 'includes/functions.php';

requireReparateur($pdo);
$reparateurId = currentUserId();

// ---- Suppression d'un service (seulement si c'est le sien) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    $serviceId = (int)($_POST['service_id'] ?? 0);

    $stmt = $pdo->prepare("SELECT image FROM service WHERE id = ? AND reparateur_id = ?");
    $stmt->execute([$serviceId, $reparateurId]);
    $service = $stmt->fetch();

    if ($service) {
        // ON DELETE CASCADE supprime aussi réservations/avis/paiements liés
        $stmt = $pdo->prepare("DELETE FROM service WHERE id = ? AND reparateur_id = ?");
        $stmt->execute([$serviceId, $reparateurId]);

        if ($service['image']) {
            $chemin = __DIR__ . '/' . $service['image'];
            if (is_file($chemin)) {
                unlink($chemin);
            }
        }
        setFlash('success', "Le service a été supprimé.");
    } else {
        setFlash('error', "Impossible de supprimer ce service.");
    }
    redirect('mes_services.php');
}

// ---- Liste de ses services (avec compteur de réservations) ----
$stmt = $pdo->prepare(
    "SELECT s.id, s.titre, s.description, s.tarif, s.rayon, s.image, s.date_creation,
            (SELECT COUNT(*) FROM reservation r WHERE r.service_id = s.id) AS nb_reservations
     FROM service s
     WHERE s.reparateur_id = ?
     ORDER BY s.date_creation DESC"
);
$stmt->execute([$reparateurId]);
$services = $stmt->fetchAll();

$pageTitle = "Mes services";
require 'includes/header.php';
?>

<h1>Mes services</h1>
<p><a href="ajouter_service.php" class="btn">+ Ajouter un service</a></p>

<?php if (empty($services)): ?>
    <div class="notice">Vous n'avez pas encore de service. <a href="ajouter_service.php">Proposez votre premier service</a>.</div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($services as $service): ?>
            <div class="card">
                <?php if (!empty($service['image'])): ?>
                    <img src="<?= e($service['image']) ?>" alt="<?= e($service['titre']) ?>" class="service-img">
                <?php endif; ?>
                <h2><?= e($service['titre']) ?></h2>
                <p><?= e($service['description']) ?></p>
                <p><strong><?= number_format($service['tarif'], 2) ?> €</strong> — rayon <?= (int)$service['rayon'] ?> km</p>
                <p class="muted"><?= (int)$service['nb_reservations'] ?> réservation(s)</p>
                <div class="actions">
                    <a href="editer_service.php?id=<?= (int)$service['id'] ?>" class="btn btn-small">Modifier</a>
                    <form method="post" onsubmit="return confirm('Supprimer ce service ? Les réservations liées seront aussi supprimées.');">
                        <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>">
                        <button type="submit" name="action" value="supprimer" class="btn btn-small btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>