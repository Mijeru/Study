<?php
// connexion.php — Page de connexion
session_start();

// Si l'utilisateur est déjà connecté, on l'envoie vers l'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/connexionBD.php';

$erreur = '';

// Quand le formulaire est envoyé
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $mdp   = $_POST['mot_de_passe'];

    // On vérifie que les deux champs sont remplis
    if ($email == '' || $mdp == '') {
        $erreur = 'Remplis tous les champs.';
    } else {
        // On cherche l'utilisateur dans la base de données
        $req = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
        $req->execute([$email]);
        $user = $req->fetch();

        // On vérifie que l'utilisateur existe et que le mot de passe est correct
        if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
            $erreur = 'Email ou mot de passe incorrect.';
        } else {
            // Connexion réussie : on enregistre l'utilisateur en session
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];

            // On met à jour la date de dernière connexion et le compteur
            $req2 = $pdo->prepare('UPDATE utilisateurs SET nb_connexions = nb_connexions + 1, derniere_connexion = NOW() WHERE id = ?');
            $req2->execute([$user['id']]);

            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — Révision Facile</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-boite">

    <h1 class="auth-titre">Révision Facile</h1>
    <h2>Se connecter</h2>

    <?php if ($erreur != ''): ?>
      <p class="msg-erreur"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <form method="POST">
      <input type="email" name="email" placeholder="Ton adresse email" required
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
      <button type="submit">Se connecter</button>
    </form>

    <p class="auth-lien">Pas encore de compte ? <a href="inscription.php">S'inscrire</a></p>
  </div>
</div>
</body>
</html>
