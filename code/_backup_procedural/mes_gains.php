<?php
require 'config/config.php';
require 'includes/functions.php';

requireReparateur($pdo);
$reparateurId = currentUserId();

// ---- Chiffres clés ----
// 1. Chiffre d'affaires = somme des tarifs des interventions terminées
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS nb_terminees, COALESCE(SUM(s.tarif), 0) AS ca
     FROM reservation r
     INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
     WHERE r.statut = 'terminee'"
);
$stmt->execute([$reparateurId]);
$statsTerminees = $stmt->fetch();

// 2. Gains encaissés = somme des paiements « valide » sur ses services
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS nb_paiements, COALESCE(SUM(p.montant), 0) AS encaisse
     FROM paiement p
     INNER JOIN reservation r ON r.id = p.reservation_id
     INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
     WHERE p.statut = 'valide'"
);
$stmt->execute([$reparateurId]);
$statsPaiements = $stmt->fetch();

// 3. Demandes en attente de décision
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS nb_attente
     FROM reservation r
     INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
     WHERE r.statut = 'en_attente'"
);
$stmt->execute([$reparateurId]);
$nbAttente = (int)$stmt->fetch()['nb_attente'];

// ---- Historique détaillé (avec statut du paiement si présent) ----
$stmt = $pdo->prepare(
    "SELECT r.id, r.date_intervention, r.statut, r.adresse_intervention,
            s.titre AS service_titre, s.tarif,
            u.prenom AS client_prenom, u.nom AS client_nom,
            p.statut AS paiement_statut, p.montant AS paiement_montant,
            p.moyen_paiement AS paiement_moyen
     FROM reservation r
     INNER JOIN service s ON s.id = r.service_id AND s.reparateur_id = ?
     INNER JOIN utilisateur u ON u.id = r.client_id
     LEFT JOIN paiement p ON p.reservation_id = r.id
     WHERE r.statut IN ('terminee', 'confirmee')
     ORDER BY r.date_intervention DESC
     LIMIT 100"
);
$stmt->execute([$reparateurId]);
$lignes = $stmt->fetchAll();

$pageTitle = "Mes gains";
require 'includes/header.php';
?>

<h1>Mes gains</h1>

<div class="stats">
    <div class="stat-card">
        <div class="valeur"><?= number_format($statsTerminees['ca'], 2) ?> €</div>
        <div class="libelle">Chiffre d'affaires (interventions terminées)</div>
    </div>
    <div class="stat-card">
        <div class="valeur"><?= number_format($statsPaiements['encaisse'], 2) ?> €</div>
        <div class="libelle">Encaissé (paiements validés)</div>
    </div>
    <div class="stat-card">
        <div class="valeur"><?= (int)$statsTerminees['nb_terminees'] ?></div>
        <div class="libelle">Interventions terminées</div>
    </div>
    <div class="stat-card">
        <div class="valeur"><?= $nbAttente ?></div>
        <div class="libelle">Demandes en attente</div>
    </div>
</div>

<h2>Détail des interventions</h2>

<?php if (empty($lignes)): ?>
    <div class="notice">Aucune intervention pour le moment. Dès qu'une réservation est terminée, elle apparaît ici.</div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Intervention</th>
                <th>Client</th>
                <th>Service</th>
                <th>Montant</th>
                <th>Paiement</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $l): ?>
                <tr>
                    <td><?= e((new DateTime($l['date_intervention']))->format('d/m/Y H:i')) ?></td>
                    <td><?= e($l['client_prenom'] . ' ' . $l['client_nom']) ?></td>
                    <td><?= e($l['service_titre']) ?></td>
                    <td><strong><?= number_format($l['tarif'], 2) ?> €</strong></td>
                    <td>
                        <?php if ($l['paiement_statut'] === 'valide'): ?>
                            <span class="badge badge-confirmee">Payé (<?= number_format($l['paiement_montant'], 2) ?> €)</span>
                        <?php elseif ($l['paiement_statut'] === 'en_attente'): ?>
                            <span class="badge badge-pending">En attente</span>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p class="muted">💡 Le paiement réel sera simulé plus tard : actuellement, le montant des interventions terminées représente votre chiffre d'affaires.</p>

<?php require 'includes/footer.php'; ?>