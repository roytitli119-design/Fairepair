<h1>Carte des réparateurs</h1>
<p class="muted">
    Retrouvez les ateliers de réparation de vélos près de chez vous. Chaque cercle
    représente le rayon d'action de ses services.
</p>

<?php if (empty($reparateurs)): ?>
    <div class="notice">
        Aucun réparateur géolocalisé pour le moment.<br>
        Les réparateurs peuvent renseigner leur position dans
        <strong>Mon profil → Mon entreprise</strong> (latitude / longitude).
    </div>
<?php else: ?>
    <div id="carte" class="map"></div>
<?php endif; ?>

<?php if (!empty($reparateurs)): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            // Données fournies par le contrôleur (json_encode sécurise l'échappement)
            const reparateurs = <?= json_encode($reparateurs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const urlProfil   = <?= json_encode(url('reparateurProfil'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

            const carte = L.map('carte').setView([46.6, 2.4], 6);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(carte);

            const marqueurs = [];
            reparateurs.forEach(function (rep) {
                const lat = parseFloat(String(rep.latitude).replace(',', '.'));
                const lng = parseFloat(String(rep.longitude).replace(',', '.'));
                if (isNaN(lat) || isNaN(lng)) return;

                // Un cercle par service : le rayon d'action du réparateur
                (rep.services || []).forEach(function (s) {
                    const rayon = parseFloat(s.rayon) * 1000; // km → m
                    L.circle([lat, lng], {
                        radius: rayon,
                        color: '#1256cc',
                        weight: 1,
                        fillColor: '#1256cc',
                        fillOpacity: 0.06
                    }).addTo(carte);
                });

                // Contenu de la bulle
                let html = '<strong>' + rep.prenom + ' ' + rep.nom + '</strong>' +
                           '<br>' + rep.nom_entreprise +
                           (rep.ville ? ' (' + rep.ville + ')' : '') +
                           '<br><a href="' + urlProfil + '&id=' + rep.id_utilisateur + '">Voir le profil</a>';

                if ((rep.services || []).length) {
                    html += '<ul style="margin:6px 0 0 18px;padding:0;font-size:0.9rem">';
                    rep.services.forEach(function (s) {
                        const prix = parseFloat(s.tarif).toFixed(2).replace('.', ',');
                        html += '<li>' + s.titre + ' — ' + prix + ' € (' + s.rayon + ' km)</li>';
                    });
                    html += '</ul>';
                }

                marqueurs.push(L.marker([lat, lng]).addTo(carte).bindPopup(html));
            });

            if (marqueurs.length) {
                carte.fitBounds(L.featureGroup(marqueurs).getBounds().pad(0.3), { maxZoom: 13 });
            }
        })();
    </script>
<?php endif; ?>