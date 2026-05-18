<?php
// inscription.php — Création d'un compte
session_start();

// Si déjà connecté, on va à l'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/connexionBD.php';

$erreur = '';
$succes = '';

// Quand le formulaire est envoyé
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom    = trim($_POST['nom']);
    $email  = trim($_POST['email']);
    $mdp    = $_POST['mot_de_passe'];
    $confir = $_POST['confirmation'];

    // Vérifications une par une
    if ($nom == '' || $email == '' || $mdp == '' || $confir == '') {
        $erreur = 'Tous les champs sont obligatoires.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";

    } elseif (strlen($mdp) < 8) {
        $erreur = 'Le mot de passe doit faire au moins 8 caractères.';

    } elseif ($mdp !== $confir) {
        $erreur = 'Les mots de passe ne correspondent pas.';

    } else {
        // On vérifie si cet email est déjà utilisé
        $check = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
        $check->execute([$email]);

        if ($check->fetch()) {
            $erreur = 'Cet email est déjà utilisé.';
        } else {
            // On enregistre le mot de passe en hash (jamais en clair)
            $hash = password_hash($mdp, PASSWORD_DEFAULT);

            $req = $pdo->prepare('INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)');
            $req->execute([$nom, $email, $hash]);

            $succes = 'Compte créé ! Tu peux maintenant te connecter.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscription — Révision Facile</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-boite">

    <h1 class="auth-titre">Révision Facile</h1>
    <h2>Créer un compte</h2>

    <?php if ($erreur != ''): ?>
      <p class="msg-erreur"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <?php if ($succes != ''): ?>
      <p class="msg-succes"><?= htmlspecialchars($succes) ?></p>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="nom" placeholder="Ton prénom ou pseudo" required
             value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
      <input type="email" name="email" placeholder="Ton adresse email" required
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <input type="password" name="mot_de_passe" placeholder="Mot de passe (8 min)" required>
      <input type="password" name="confirmation" placeholder="Confirme ton mot de passe" required>
      <button type="submit">Créer mon compte</button>
    </form>

    <p class="auth-lien">Déjà un compte ? <a href="connexion.php">Se connecter</a></p>
  </div>
</div>
</body>
</html>
