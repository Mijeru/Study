<?php
// cours.php — Gestion des cours
require_once 'auth.php';
require_once 'config/connexionBD.php';
require_once 'nav.php';

$uid = $_SESSION['user_id'];

// --- Ajouter un cours ---
if (isset($_POST['titre'])) {
    $matiere_id = !empty($_POST['matiere_id']) ? $_POST['matiere_id'] : null;

    $req = $pdo->prepare('INSERT INTO cours (user_id, matiere_id, titre, contenu) VALUES (?, ?, ?, ?)');
    $req->execute([$uid, $matiere_id, $_POST['titre'], $_POST['contenu']]);
}

// --- Supprimer un cours ---
if (isset($_GET['delete'])) {
    $req = $pdo->prepare('DELETE FROM cours WHERE id = ? AND user_id = ?');
    $req->execute([$_GET['delete'], $uid]);
}

// --- Récupérer les matières pour le formulaire ---
$req_matieres = $pdo->prepare('SELECT * FROM matieres WHERE user_id = ? ORDER BY nom');
$req_matieres->execute([$uid]);
$matieresList = $req_matieres->fetchAll();

// --- Récupérer tous les cours ---
$req = $pdo->prepare('
    SELECT c.*, m.nom AS matiere_nom
    FROM cours c
    LEFT JOIN matieres m ON m.id = c.matiere_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
');
$req->execute([$uid]);
$cours = $req->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cours — Révision Facile</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main>

  <!-- Formulaire d'ajout -->
  <h2>Ajouter un cours</h2>
  <form method="POST">
    <input type="text" name="titre" placeholder="Titre du cours" required>
    <textarea name="contenu" placeholder="Contenu du cours..."></textarea>

    <select name="matiere_id">
      <option value="">-- Choisir une matière --</option>
      <?php foreach ($matieresList as $m): ?>
        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
      <?php endforeach; ?>
    </select>

    <?php if (empty($matieresList)): ?>
      <p><a href="matieres.php">Crée d'abord une matière</a></p>
    <?php endif; ?>

    <button type="submit">Ajouter le cours</button>
  </form>

  <!-- Liste des cours -->
  <h2>Mes cours</h2>

  <?php if (empty($cours)): ?>
    <p>Tu n'as pas encore de cours.</p>
  <?php else: ?>
    <?php foreach ($cours as $c): ?>
      <div class="card">
        <h3><?= htmlspecialchars($c['titre']) ?></h3>
        <p><?= htmlspecialchars($c['contenu']) ?></p>

        <?php if ($c['matiere_nom'] != ''): ?>
          <p><strong>Matière :</strong> <?= htmlspecialchars($c['matiere_nom']) ?></p>
        <?php endif; ?>

        <a href="modifier.php?type=cours&id=<?= $c['id'] ?>">✏️ Modifier</a>
        <a href="?delete=<?= $c['id'] ?>" onclick="return confirm('Supprimer ce cours ?')">❌ Supprimer</a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</main>
</body>
</html>
