<?php
require 'config/config.php';
require 'includes/functions.php';
requireModerateur($pdo);

$signalementId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT sg.*,
            u.prenom AS cli_prenom, u.nom AS cli_nom, u.email AS cli_email, u.telephone AS cli_tel,
            s.titre AS service_titre, s.tarif,
            r.adresse_intervention, r.date_intervention, r.statut AS reservation_statut,
            ur.prenom AS rep_prenom, ur.nom AS rep_nom
     FROM signalement sg
     INNER JOIN reservation r ON r.id = sg.reservation_id
     INNER JOIN service s ON s.id = r.service_id
     INNER JOIN utilisateur u ON u.id = sg.client_id
     INNER JOIN reparateur rep ON rep.id_utilisateur = s.reparateur_id
     INNER JOIN utilisateur ur ON ur.id = rep.id_utilisateur
     WHERE sg.id = ?"
);
$stmt->execute([$signalementId]);
$signalement = $stmt->fetch();

if (!$signalement) {
    setFlash('error', "Signalement introuvable.");
    redirect('signalements.php');
}

$statutsValides = ['ouvert', 'en_cours', 'resolu', 'rejete'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveauStatut = $_POST['statut'] ?? '';

    if (!in_array($nouveauStatut, $statutsValides, true)) {
        $errors[] = "Statut invalide.";
    }

    if (empty($errors)) {
        // Le modérateur connecté prend en charge / clôt le signalement
        $stmt = $pdo->prepare(
            "UPDATE signalement
             SET statut = ?, moderateur_id = ?
             WHERE id = ?"
        );
        $stmt->execute([$nouveauStatut, currentUserId(), $signalementId]);

        setFlash('success', "Signalement mis à jour (statut : $nouveauStatut).");
        redirect('signalements.php');
    }
}

$pageTitle = "Signalement #" . $signalement['id'];
require 'includes/header.php';
?>

<h1>Signalement #<?= (int)$signalement['id'] ?></h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <h2>🚩 Motif : <?= e($signalement['motif']) ?></h2>
    <p><?= nl2br(e($signalement['description'])) ?></p>
    <p class="muted">
        Signalé le <?= e(date('d/m/Y H:i', strtotime($signalement['date_signalement']))) ?>
        — statut actuel : <strong><?= e(ucfirst(str_replace('_', ' ', $signalement['statut']))) ?></strong>
    </p>
</div>

<h2>Réservation concernée</h2>
<table class="table">
    <tbody>
        <tr><th>Client</th><td><?= e($signalement['cli_prenom'] . ' ' . $signalement['cli_nom']) ?> — <?= e($signalement['cli_email']) ?></td></tr>
        <tr><th>Service</th><td><?= e($signalement['service_titre']) ?></td></tr>
        <tr><th>Réparateur</th><td><?= e($signalement['rep_prenom'] . ' ' . $signalement['rep_nom']) ?></td></tr>
        <tr><th>Adresse d'intervention</th><td><?= e($signalement['adresse_intervention']) ?></td></tr>
        <tr><th>Date d'intervention</th><td><?= e(date('d/m/Y H:i', strtotime($signalement['date_intervention']))) ?></td></tr>
        <tr><th>Statut de la réservation</th><td><?= e(ucfirst(str_replace('_', ' ', $signalement['reservation_statut']))) ?></td></tr>
    </tbody>
</table>

<h2>Prendre en charge</h2>
<form method="post" class="form">
    <input type="hidden" name="id" value="<?= (int)$signalement['id'] ?>">
    <label>Nouveau statut
        <select name="statut" required>
            <?php foreach ($statutsValides as $s): ?>
                <option value="<?= e($s) ?>" <?= $signalement['statut'] === $s ? 'selected' : '' ?>>
                    <?= e(ucfirst(str_replace('_', ' ', $s))) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Enregistrer</button>
</form>

<p><a href="signalements.php">&larr; Retour à la liste des signalements</a></p>

<?php require 'includes/footer.php'; ?>