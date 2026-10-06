<?php
require 'config/config.php';
require 'includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    if ($email === '' || $motDePasse === '') {
        $errors[] = "Merci de renseigner votre email et votre mot de passe.";
    } else {
        // Le compte peut être un client, un réparateur (ou un modérateur) :
        // on vérifie juste que l'email + mot de passe sont valides.
        $stmt = $pdo->prepare(
            "SELECT id, prenom, mot_de_passe, tentatives_connexion, blocage_jusqua
             FROM utilisateur
             WHERE email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Blocage temporaire : après 5 échecs, 15 minutes d'attente
        $DUREE_BLOCAGE_MINUTES = 15;
        $MAX_TENTATIVES        = 5;
        $bloque = false;

        if ($user && $user['blocage_jusqua'] !== null) {
            $deblocage = strtotime($user['blocage_jusqua']);
            if (time() < $deblocage) {
                // Toujours bloqué
                $bloque = true;
                $errors[] = "Trop de tentatives échouées. Réessayez après " .
                            date('H:i', $deblocage) . ".";
            } else {
                // Blocage expiré : on remet les compteurs à zéro
                $pdo->prepare(
                    "UPDATE utilisateur SET tentatives_connexion = 0, blocage_jusqua = NULL WHERE id = ?"
                )->execute([$user['id']]);
                $user['tentatives_connexion'] = 0;
                $user['blocage_jusqua'] = null;
            }
        }

        if (!$bloque) {
            if (!$user || !password_verify($motDePasse, $user['mot_de_passe'])) {
                // Échec : on compte la tentative pour ce compte (si l'email existe)
                if ($user) {
                    $tentatives = (int)$user['tentatives_connexion'] + 1;
                    if ($tentatives >= $MAX_TENTATIVES) {
                        $pdo->prepare(
                            "UPDATE utilisateur
                             SET tentatives_connexion = 0,
                                 blocage_jusqua = DATE_ADD(NOW(), INTERVAL $DUREE_BLOCAGE_MINUTES MINUTE)
                             WHERE id = ?"
                        )->execute([$user['id']]);
                        $errors[] = "Trop de tentatives échouées : compte bloqué pendant $DUREE_BLOCAGE_MINUTES minutes.";
                    } else {
                        $pdo->prepare(
                            "UPDATE utilisateur SET tentatives_connexion = ? WHERE id = ?"
                        )->execute([$tentatives, $user['id']]);
                        $errors[] = "Email ou mot de passe incorrect.";
                    }
                } else {
                    $errors[] = "Email ou mot de passe incorrect.";
                }
            } else {
                // Connexion réussie : on réinitialise les compteurs
                $pdo->prepare(
                    "UPDATE utilisateur SET tentatives_connexion = 0, blocage_jusqua = NULL WHERE id = ?"
                )->execute([$user['id']]);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['prenom']  = $user['prenom'];

                // On mémorise le rôle pour adapter le menu et la redirection
                $_SESSION['role'] = currentUserRole($pdo);

                // Un réparateur atterrit sur son espace (il l'est aussi en double rôle)
                if (estReparateur($pdo)) {
                    redirect('reparateur_accueil.php');
                }
                // Idem pour un modérateur
                if (estModerateur($pdo)) {
                    redirect('moderateur_accueil.php');
                }
                redirect('index.php');
            }
        }
    }
}

$pageTitle = "Connexion";
require 'includes/header.php';
?>

<h1>Se connecter</h1>

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
    <label>Email
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
    </label>
    <label>Mot de passe
        <input type="password" name="mot_de_passe" required>
    </label>
    <button type="submit">Se connecter</button>
</form>

<p>Pas encore de compte ? <a href="inscription.php">Créer un compte</a></p>
<p><a href="mot_de_passe_oublie.php">Mot de passe oublié ?</a></p>

<?php require 'includes/footer.php'; ?>
