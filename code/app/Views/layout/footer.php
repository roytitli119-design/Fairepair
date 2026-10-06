<?php if (empty($GLOBALS['layoutSidebar'])): ?>
    </main><!-- /.container -->
<?php else: ?>
    </main><!-- /.dashboard-contenu -->
    </div><!-- /.dashboard -->
<?php endif; ?>

    <footer class="footer">
        <div class="footer-gauche">
            <a href="<?= e(url('accueil')) ?>" class="logo footer-logo">Fair'repair</a>
            <p class="footer-slogan">Réparer, c'est éco-responsable. 🌿</p>
        </div>
        <div class="footer-liens">
            <a href="<?= e(url('cgu')) ?>">Conditions générales d'utilisation</a>
            <a href="<?= e(url('cgu')) ?>#rgpd">Politique de confidentialité (RGPD)</a>
            <a href="<?= e(url('carte')) ?>">Carte des réparateurs</a>
        </div>
        <div class="footer-credits">
            <p>&copy; <?= date('Y') ?> Fair'repair — Projet BTS SIO SLAM</p>
            <p class="muted">Photos&nbsp;: «&nbsp;Road cycling – riding a bike on the road&nbsp;» et «&nbsp;Bike workshop – bicycle repair shop&nbsp;» d'Alextredz, «&nbsp;Green city bike &amp; brown leather bag&nbsp;» de Jens Rost — CC&nbsp;BY-SA (Wikimedia&nbsp;Commons / Flickr).</p>
        </div>
    </footer>
</body>
</html>