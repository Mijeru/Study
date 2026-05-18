<?php
// matieres.php — Gestion des matières
require_once 'auth.php';
require_once 'config/connexionBD.php';
require_once 'nav.php';

$uid    = $_SESSION['user_id'];
$erreur = '';

// --- Ajouter une matière ---
if (isset($_POST['nom'])) {
    $nom = trim($_POST['nom']);

    if ($nom == '') {
        $erreur = 'Le nom ne peut pas être vide.';
    } else {
        // On vérifie si cette matière existe déjà pour cet utilisateur
        $check = $pdo->prepare('SELECT id FROM matieres WHERE user_id = ? AND nom = ?');
        $check->execute([$uid, $nom]);

        if ($check->fetch()) {
            $erreur = 'Tu as déjà une matière avec ce nom.';
        } else {
            $req = $pdo->prepare('INSERT INTO matieres (user_id, nom) VALUES (?, ?)');
            $req->execute([$uid, $nom]);
        }
    }
}

// --- Supprimer une matière ---
// Les fiches et cours liés restent, leur matiere_id devient juste NULL
if (isset($_GET['delete'])) {
    $req = $pdo->prepare('DELETE FROM matieres WHERE id = ? AND user_id = ?');
    $req->execute([$_GET['delete'], $uid]);
}

// --- Récupérer toutes les matières avec le nombre de fiches et cours liés ---
$req = $pdo->prepare('
    SELECT m.id, m.nom,
           COUNT(DISTINCT f.id) AS nb_fiches,
           COUNT(DISTINCT c.id) AS nb_cours
    FROM matieres m
    LEFT JOIN fiches f ON f.matiere_id = m.id
    LEFT JOIN cours  c ON c.matiere_id = m.id
    WHERE m.user_id = ?
    GROUP BY m.id, m.nom
    ORDER BY m.nom
');
$req->execute([$uid]);
$matieres = $req->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Matières — Révision Facile</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main>

  <h2>Gérer mes matières</h2>

  <?php if ($erreur != ''): ?>
    <p class="msg-erreur"><?= htmlspecialchars($erreur) ?></p>
  <?php endif; ?>

  <!-- Formulaire d'ajout -->
  <form method="POST">
    <input type="text" name="nom" placeholder="Ex : Mathématiques" required>
    <button type="submit">Ajouter</button>
  </form>

  <!-- Liste des matières -->
  <h2>Mes matières</h2>

  <?php if (empty($matieres)): ?>
    <p>Aucune matière créée.</p>
  <?php else: ?>
    <?php foreach ($matieres as $m): ?>
      <div class="card">
        <h3><?= htmlspecialchars($m['nom']) ?></h3>
        <p>
          <?= $m['nb_fiches'] ?> fiche<?= $m['nb_fiches'] > 1 ? 's' : '' ?>
          •
          <?= $m['nb_cours'] ?> cours
        </p>
        <a href="?delete=<?= $m['id'] ?>"
           onclick="return confirm('Supprimer cette matière ? Les fiches et cours liés garderont leur contenu.')">
          ❌ Supprimer
        </a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</main>
</body>
</html>
