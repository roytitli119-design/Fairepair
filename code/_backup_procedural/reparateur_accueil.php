<?php
require 'config/config.php';
require 'includes/functions.php';

requireReparateur($pdo);
$reparateurId = currentUserId();

// ---- Actions sur une réservation (confirmée / annulée / terminée) ----
$action        = $_POST['action'] ?? '';
$reservationId = (int)($_POST['reservation_id'] ?? 0);

if ($reservationId > 0 && in_array($action, ['confirmer', 'annuler', 'terminer'], true)) {
    $nouveauStatut = [
        'confirmer' => 'confirmee',
        'annuler'   => 'annulee',
        'terminer'  => 'terminee',
    ][$action];

    // On ne peut agir que sur une réservation d'un de SES services :
    // la jointure sur s.reparateur_id = ? sert de garde-fou.
    $stmt = $pdo->prepare(
        "UPDATE reservation r
         INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
         SET r.statut = ?
         WHERE r.id = ?"
    );
    $stmt->execute([$reparateurId, $nouveauStatut, $reservationId]);

    if ($stmt->rowCount() > 0) {
        $messages = [
            'confirmer' => "Réservation confirmée. Elle apparaît dans vos RDVs à venir.",
            'annuler'   => "Réservation annulée.",
            'terminer'  => "Intervention marquée comme terminée. Elle compte dans vos gains.",
        ];
        setFlash('success', $messages[$action]);
    } else {
        setFlash('error', "Impossible de modifier cette réservation.");
    }
    redirect('reparateur_accueil.php');
}

// ---- Liste des réservations (uniquement liées à ses services) ----
function requeteReservations(PDO $pdo, int $reparateurId, string $where, string $order = 'ASC'): array
{
    $stmt = $pdo->prepare(
        "SELECT r.id, r.adresse_intervention, r.date_intervention, r.date_creation, r.statut,
                s.titre AS service_titre, s.tarif,
                u.prenom AS client_prenom, u.nom AS client_nom, u.telephone AS client_tel
         FROM reservation r
         INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = :rid
         INNER JOIN utilisateur u ON u.id = r.client_id
         WHERE $where
         ORDER BY r.date_intervention $order"
    );
    $stmt->execute([':rid' => $reparateurId]);
    return $stmt->fetchAll();
}

$enAttente  = requeteReservations($pdo, $reparateurId, "r.statut = 'en_attente'");
$aVenir     = requeteReservations($pdo, $reparateurId,
    "r.statut = 'confirmee' AND r.date_intervention >= NOW()");
$historique = requeteReservations($pdo, $reparateurId,
    "r.statut IN ('terminee', 'annulee') OR (r.statut = 'confirmee' AND r.date_intervention < NOW())",
    'DESC');

$pageTitle = "Espace réparateur";
require 'includes/header.php';
?>

<h1>Bonjour <?= e($_SESSION['prenom'] ?? '') ?> 👋</h1>
<p class="muted">Voici les demandes de dépannage arrivées sur vos services.</p>

<h2>Demandes en attente <?= count($enAttente) > 0 ? '(' . count($enAttente) . ')' : '' ?></h2>

<?php if (empty($enAttente)): ?>
    <div class="notice">Aucune demande en attente pour le moment.</div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Client</th>
                <th>Adresse</th>
                <th>Date d'intervention</th>
                <th>Tarif</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enAttente as $r): ?>
                <tr>
                    <td><?= e($r['service_titre']) ?></td>
                    <td><?= e($r['client_prenom'] . ' ' . $r['client_nom']) ?><br>
                        <span class="muted"><?= e($r['client_tel']) ?></span></td>
                    <td><?= e($r['adresse_intervention']) ?></td>
                    <td><?= e((new DateTime($r['date_intervention']))->format('d/m/Y H:i')) ?></td>
                    <td><strong><?= number_format($r['tarif'], 2) ?> €</strong></td>
                    <td class="actions">
                        <form method="post">
                            <input type="hidden" name="reservation_id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" name="action" value="confirmer" class="btn btn-small">Confirmer</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="reservation_id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" name="action" value="annuler" class="btn btn-small btn-danger">Annuler</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>RDVs à venir <?= count($aVenir) > 0 ? '(' . count($aVenir) . ')' : '' ?></h2>

<?php if (empty($aVenir)): ?>
    <div class="notice">Aucun rendez-vous confirmé pour l'instant.</div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Client</th>
                <th>Adresse</th>
                <th>Date d'intervention</th>
                <th>Tarif</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($aVenir as $r): ?>
                <tr>
                    <td><?= e($r['service_titre']) ?></td>
                    <td><?= e($r['client_prenom'] . ' ' . $r['client_nom']) ?><br>
                        <span class="muted"><?= e($r['client_tel']) ?></span></td>
                    <td><?= e($r['adresse_intervention']) ?></td>
                    <td><?= e((new DateTime($r['date_intervention']))->format('d/m/Y H:i')) ?></td>
                    <td><strong><?= number_format($r['tarif'], 2) ?> €</strong></td>
                    <td class="actions">
                        <form method="post">
                            <input type="hidden" name="reservation_id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" name="action" value="terminer" class="btn btn-small">Marquer terminée</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="reservation_id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" name="action" value="annuler" class="btn btn-small btn-danger">Annuler</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Historique</h2>

<?php if (empty($historique)): ?>
    <div class="notice">Pas encore d'historique.</div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Client</th>
                <th>Intervention</th>
                <th>Tarif</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historique as $r): ?>
                <?php
                $classes = [
                    'terminee'   => 'badge-terminee',
                    'annulee'    => 'badge-annulee',
                    'confirmee'  => 'badge-confirmee',
                    'en_attente' => 'badge-pending',
                ];
                $libelles = [
                    'terminee'   => 'Terminée',
                    'annulee'    => 'Annulée',
                    'confirmee'  => 'Confirmée (passée)',
                    'en_attente' => 'En attente',
                ];
                ?>
                <tr>
                    <td><?= e($r['service_titre']) ?></td>
                    <td><?= e($r['client_prenom'] . ' ' . $r['client_nom']) ?></td>
                    <td><?= e((new DateTime($r['date_intervention']))->format('d/m/Y H:i')) ?></td>
                    <td><?= number_format($r['tarif'], 2) ?> €</td>
                    <td><span class="badge <?= $classes[$r['statut']] ?? '' ?>"><?= $libelles[$r['statut']] ?? e($r['statut']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>