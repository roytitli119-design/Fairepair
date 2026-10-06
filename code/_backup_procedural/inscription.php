<?php
require 'config/config.php';
require 'includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom       = trim($_POST['prenom'] ?? '');
    $nom          = trim($_POST['nom'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $motDePasse   = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';
    $cguAcceptee  = isset($_POST['cgu']);

    // ---- Vérifications ----
    if ($prenom === '' || $nom === '' || $email === '' || $motDePasse === '') {
        $errors[] = "Merci de renseigner tous les champs.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }
    // Politique de mot de passe (recommandations CNIL / RGPD)
    foreach (validerMotDePasse($motDePasse) as $erreur) {
        $errors[] = $erreur;
    }
    if ($motDePasse !== $confirmation) {
        $errors[] = "Les deux mots de passe ne correspondent pas.";
    }
    // Consentement obligatoire aux CGU (trace conservée en base : date + version)
    if (!$cguAcceptee) {
        $errors[] = "Vous devez accepter les conditions générales d'utilisation pour créer un compte.";
    }

    // Vérifie que l'email n'est pas déjà utilisé
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Un compte existe déjà avec cet email.";
        }
    }

    // ---- Création du compte (rôle client par défaut) ----
    // L'adresse, la ville et le téléphone sont complétés plus tard,
    // depuis la page profil une fois connecté.
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $hash = password_hash($motDePasse, PASSWORD_DEFAULT);

            // On trace l'acceptation des CGU (date + version) : preuve de
            // consentement exigée par le RGPD (article 7).
            $stmt = $pdo->prepare(
                "INSERT INTO utilisateur (prenom, nom, email, mot_de_passe,
                                          cgu_acceptee_at, cgu_version)
                 VALUES (?, ?, ?, ?, NOW(), ?)"
            );
            $stmt->execute([$prenom, $nom, $email, $hash, CGU_VERSION]);

            $userId = $pdo->lastInsertId();

            // Un nouveau compte est toujours créé en tant que client ;
            // le passage réparateur se fait depuis le profil (en donnant
            // les informations de l'entreprise).
            $stmt = $pdo->prepare("INSERT INTO client (id_utilisateur) VALUES (?)");
            $stmt->execute([$userId]);

            $pdo->commit();

            // Connexion automatique + passage OBLIGATOIRE par la création du mot secret
            $_SESSION['user_id'] = $userId;
            $_SESSION['prenom']  = $prenom;
            $_SESSION['role']    = 'client';

            setFlash('success', 'Compte créé avec succès. Définissez maintenant votre mot secret pour sécuriser votre compte.');
            redirect('creer_mot_secret.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Une erreur est survenue lors de la création du compte.";
        }
    }
}

$pageTitle = "Créer un compte";
require 'includes/header.php';
?>

<h1>Créer un compte</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="form">
    <label>Prénom
        <input type="text" name="prenom" value="<?= e($_POST['prenom'] ?? '') ?>" required>
    </label>
    <label>Nom
        <input type="text" name="nom" value="<?= e($_POST['nom'] ?? '') ?>" required>
    </label>
    <label>Email
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
    </label>
    <label>Mot de passe (8 caractères min. : 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial)
        <input type="password" name="mot_de_passe" required>
    </label>
    <label>Confirmation du mot de passe
        <input type="password" name="confirmation" required>
    </label>
    <label class="checkbox">
        <input type="checkbox" name="cgu" value="1" required>
        <span>
            J'accepte les <a href="cgu.php" target="_blank" rel="noopener">conditions générales
            d'utilisation</a> et la <a href="cgu.php#rgpd" target="_blank" rel="noopener">politique
            de confidentialité</a> (obligatoire, le consentement est conservé).
        </span>
    </label>
    <button type="submit">Créer mon compte</button>
</form>

<p class="muted">
    💡 Vous pourrez compléter votre adresse depuis votre profil et,
    si vous êtes réparateur, ajouter les informations de votre entreprise.
</p>

<p>Déjà un compte ? <a href="connexion.php">Se connecter</a></p>

<?php require 'includes/footer.php'; ?>