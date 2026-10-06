<?php
require 'config/config.php';
require 'includes/functions.php';
requireLogin();

$stmt = $pdo->prepare(
    "SELECT r.id, r.adresse_intervention, r.date_intervention, r.statut,
            s.titre, s.tarif,
            p.id AS paiement_id, p.statut AS paiement_statut,
            a.id AS avis_id
     FROM reservation r
     INNER JOIN service s ON s.id = r.service_id
     LEFT JOIN paiement p ON p.reservation_id = r.id
     LEFT JOIN avis a ON a.reservation_id = r.id
     WHERE r.client_id = ?
     ORDER BY r.date_intervention DESC"
);
$stmt->execute([currentUserId()]);
$reservations = $stmt->fetchAll();

$pageTitle = "Mes réservations";
require 'includes/header.php';
?>

<h1>Mes réservations</h1>

<?php if (empty($reservations)): ?>
    <p>Vous n'avez pas encore de réservation. <a href="index.php">Voir les services</a></p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Paiement</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($reservations as $r): ?>
            <tr>
                <td><?= e($r['titre']) ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($r['date_intervention']))) ?></td>
                <td><?= e(ucfirst(str_replace('_', ' ', $r['statut']))) ?></td>
                <td>
                    <?php if ($r['paiement_id']): ?>
                        <?= e(ucfirst($r['paiement_statut'])) ?>
                    <?php elseif ($r['statut'] !== 'annulee'): ?>
                        <a href="paiement.php?reservation_id=<?= (int)$r['id'] ?>">Payer</a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <?php if ($r['statut'] === 'en_attente'): ?>
                        <a href="reservation_annuler.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Annuler cette réservation ?');">Annuler</a>
                    <?php endif; ?>
                    <?php if ($r['statut'] === 'terminee'): ?>
                        <a href="signalement.php?reservation_id=<?= (int)$r['id'] ?>">Signaler</a>
                        <?php if (!$r['avis_id']): ?>
                            <a href="avis.php?reservation_id=<?= (int)$r['id'] ?>">Laisser un avis</a>
                        <?php else: ?>
                            <span class="muted">Avis déjà laissé</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
