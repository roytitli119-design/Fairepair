<?php
require 'config/config.php';
require 'includes/functions.php';

$pageTitle = "Conditions générales d'utilisation";
require 'includes/header.php';
?>

<h1>Conditions générales d'utilisation</h1>
<p class="muted">
    Version <?= e(CGU_VERSION) ?> — en vigueur depuis le <?= e(CGU_DATE) ?>.
    Dernière mise à jour du document : <?= e(CGU_DATE) ?>.
</p>

<div class="card">
    <h2>1. Objet</h2>
    <p>
        Les présentes conditions générales d'utilisation (CGU) encadrent l'accès et l'utilisation
        de la plateforme <strong>Fair'repair</strong>, qui met en relation des clients souhaitant
        faire réparer un vélo et des réparateurs intervenant à domicile ou en atelier.
    </p>
    <p>
        En créant un compte sur la plateforme, l'utilisateur reconnaît avoir pris connaissance des
        présentes CGU et de la <a href="cgu.php#rgpd">politique de confidentialité</a>, et les
        accepte sans réserve. Cette acceptation est <strong>traçée et conservée</strong> en base de
        données (date et version des CGU acceptées), conformément à l'article 7 du RGPD.
    </p>

    <h2>2. Description du service</h2>
    <p>
        Fair'repair permet :
    </p>
    <ul>
        <li>aux <strong>clients</strong> de rechercher un service de réparation, de réserver une
            intervention, de payer, de laisser un avis et de signaler un problème ;</li>
        <li>aux <strong>réparateurs</strong> de proposer leurs services (titre, description, tarif,
            rayon d'intervention) et de gérer leurs réservations et gains ;</li>
        <li>aux <strong>modérateurs</strong> de modérer les avis et de traiter les signalements.</li>
    </ul>
    <p>
        La plateforme est un <strong>projet pédagogique</strong> réalisé dans le cadre d'un BTS SIO
        (option SLAM). Aucune transaction financière réelle n'est effectuée ; les paiements sont
        simulés.
    </p>

    <h2>3. Création de compte et consentement</h2>
    <p>
        La création d'un compte nécessite la fourniture d'un prénom, d'un nom, d'une adresse email
        et d'un mot de passe. L'utilisateur doit :
    </p>
    <ul>
        <li>fournir des informations <strong>exactes et à jour</strong> ;</li>
        <li>cocher la case d'acceptation des CGU — <strong>sans cette validation, l'inscription est
            impossible</strong> (la date et la version acceptées sont enregistrées en base) ;</li>
        <li>choisir un <strong>mot de passe robuste</strong> (au moins 8 caractères comprenant une
            majuscule, une minuscule, un chiffre et un caractère spécial) et le conserver
            confidentiellement.</li>
    </ul>
    <p>
        Tout compte est créé avec le rôle <em>client</em>. L'utilisateur peut ensuite, depuis son
        profil, devenir réparateur en renseignant les informations de son entreprise.
    </p>

    <h2>4. Sécurité du compte</h2>
    <p>
        Pour protéger les données personnelles (art. 32 du RGPD) :
    </p>
    <ul>
        <li>les mots de passe et les mots secrets sont <strong>hachés</strong> (fonction
            <code>password_hash</code> / bcrypt) — ils ne sont jamais stockés en clair ;</li>
        <li>en cas d'oubli du mot de passe, la réinitialisation s'effectue grâce au
            <strong>mot secret</strong> défini dans le profil ;</li>
        <li>après <strong>5 échecs de connexion</strong>, le compte est bloqué temporairement
            (15 minutes) pour empêcher les tentatives d'accès automatisées ;</li>
        <li>l'utilisateur est responsable de la confidentialité de son identifiant et de son mot de
            passe.</li>
    </ul>

    <h2>5. Obligations de l'utilisateur</h2>
    <ul>
        <li>utiliser la plateforme de manière <strong>licite</strong> et conforme aux présentes
            CGU ;</li>
        <li>ne pas usurper l'identité d'un tiers ;</li>
        <li>ne pas tenter d'accéder aux comptes d'autres utilisateurs ou d'altérer le
            fonctionnement de la plateforme ;</li>
        <li>respecter les réparateurs et autres utilisateurs (avis et signalements de bonne foi).</li>
    </ul>

    <h2 id="rgpd">6. Données personnelles et confidentialité (RGPD)</h2>
    <h3>6.1. Responsable du traitement</h3>
    <p>
        Le traitement des données est réalisé dans le cadre d'un projet pédagogique de la formation
        BTS SIO (option SLAM). Pour toute question relative à vos données, contactez l'équipe du
        projet à l'adresse indiquée sur la page de connexion de la plateforme.
    </p>
    <h3>6.2. Données collectées et finalités</h3>
    <p>
        Les données traitées sont limitées au strict nécessaire :
    </p>
    <ul>
        <li><strong>données d'identité</strong> (prénom, nom) et de <strong>contact</strong>
            (email, téléphone, adresse si renseignée) : création et gestion du compte ;</li>
        <li><strong>données de réservation</strong> (adresse et date d'intervention, service choisi,
            paiement simulé) : fourniture du service ;</li>
        <li><strong>trace de consentement</strong> (date et version des CGU acceptées) : preuve du
            consentement exigée par le RGPD ;</li>
        <li>données d'entreprise des réparateurs (nom de l'entreprise, SIRET) : présentation de
            l'offre.</li>
    </ul>
    <h3>6.3. Base légale</h3>
    <p>
        Les traitements reposent sur le <strong>consentement</strong> de l'utilisateur (case CGU
        cochée à l'inscription, tracée en base) et sur l'<strong>exécution du contrat</strong> de
        service entre la plateforme et ses utilisateurs.
    </p>
    <h3>6.4. Durée de conservation</h3>
    <p>
        Les données sont conservées le temps de l'utilisation du compte. Elles peuvent être
        supprimées à tout moment sur simple demande (voir les droits ci-dessous).
    </p>
    <h3>6.5. Sécurité</h3>
    <p>
        Les données sont hébergées sur un serveur local. Les mots de passe et mots secrets sont
        hachés, les sessions sont protégées et les échanges font l'objet d'échappement HTML
        (protection XSS). Les données ne sont <strong>jamais revendues</strong> à des tiers.
    </p>
    <h3>6.6. Vos droits</h3>
    <p>
        Conformément au RGPD, vous disposez d'un droit d'<strong>accès</strong>, de
        <strong>rectification</strong>, d'<strong>effacement</strong> (suppression du compte et des
        données associées), de <strong>limitation</strong>, de <strong>portabilité</strong> et
        d'<strong>opposition</strong>. Vous pouvez les exercer depuis votre profil (modification de
        vos informations) ou en adressant une demande à l'équipe du projet.
    </p>

    <h2>7. Responsabilité</h2>
    <p>
        Fair'repair agit en tant que plateforme de mise en relation. La responsabilité du
        bon déroulement de l'intervention incombe au réparateur. La plateforme n'est pas
        responsable des dommages résultant d'une mauvaise utilisation du service ou d'éléments
        non maîtrisables (défaillance technique, indisponibilité temporaire…).
    </p>

    <h2>8. Modification des CGU</h2>
    <p>
        Les présentes CGU peuvent être modifiées à tout moment pour tenir compte des évolutions du
        service ou de la réglementation. Les utilisateurs sont invités à les consulter
        régulièrement. La version applicable est celle affichée en tête de ce document.
    </p>

    <h2>9. Droit applicable</h2>
    <p>
        Les présentes CGU sont soumises au droit français. En cas de litige, les parties
        s'efforceront de trouver une solution amiable avant toute action judiciaire.
    </p>
</div>

<p>
    <a href="inscription.php">&larr; Retour à l'inscription</a> ·
    <a href="connexion.php">Se connecter</a>
</p>

<?php require 'includes/footer.php'; ?>