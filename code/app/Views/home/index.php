<section class="hero">
    <div class="hero-contenu">
        <p class="hero-badge">🌿 Plateforme éco-responsable</p>
        <h1>Réparons nos vélos,<br>ensemble et près de chez soi.</h1>
        <p class="hero-sous">
            Trouvez un réparateur de vélos autour de vous, réservez en 2 clics,
            payez en ligne en toute sécurité. La petite réparation qui évite
            le grand remplacement.
        </p>
        <div class="hero-actions">
            <a href="#services" class="btn">Voir les services</a>
            <a href="<?= e(url('carte')) ?>" class="btn btn-secondaire">Voir la carte des ateliers</a>
        </div>
    </div>
    <div class="hero-image">
        <img src="assets/img/hero-accueil.jpg" alt="Cycliste sur une route au soleil couchant">
    </div>
</section>

<section class="eco-points">
    <div class="eco-point">
        <span class="eco-icone">♻️</span>
        <h3>Réparer plutôt que jeter</h3>
        <p>Chaque vélo réparé, c'est moins de déchets et une empreinte carbone allégée.</p>
    </div>
    <div class="eco-point">
        <span class="eco-icone">📍</span>
        <h3>Des ateliers locaux</h3>
        <p>Des réparateurs de proximité, géolocalisés sur la carte, qui encouragent les déplacements doux.</p>
    </div>
    <div class="eco-point">
        <span class="eco-icone">🔒</span>
        <h3>Paiement sécurisé</h3>
        <p>Aucune donnée bancaire stockée : paiement en ligne vérifié ou réglé à l'atelier.</p>
    </div>
</section>

<section class="engagement">
    <div class="engagement-photos">
        <img src="assets/img/velo-reparation.jpg" alt="Réparation d'une roue de vélo en atelier" class="engagement-photo photo-1">
        <img src="assets/img/velo-vert.jpg" alt="Vélo de ville vert avec sacoche en cuir" class="engagement-photo photo-2">
    </div>
    <div class="engagement-texte">
        <h2>Réparer, c'est le geste le plus écologique</h2>
        <p>
            Donner une seconde vie à son vélo, c'est éviter la fabrication d'un
            vélo neuf (énergie, matières premières, transports) et réduire
            les déchets. C'est aussi garder un moyen de déplacement doux
            à portée de main, toute l'année.
        </p>
        <p>
            Chaque intervention est effectuée par un réparateur de proximité,
            chez lui ou à votre domicile — pour un vélo qui roule plus longtemps,
            sans forfait ni pièce imposée.
        </p>
    </div>
</section>

<h1 id="services" class="section-titre">Services disponibles</h1>

<?php if (empty($services)): ?>
    <p>Aucun service disponible pour le moment.</p>
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
                <p class="muted">Par <a href="<?= e(url('reparateurProfil', ['id' => $service['reparateur_id']])) ?>"><?= e($service['rep_prenom'] . ' ' . $service['rep_nom']) ?></a> (<?= e($service['ville']) ?>)</p>
                <a href="<?= e(url('service', ['id' => $service['id']])) ?>" class="btn">Voir le service</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>