<?php
// index.php — Page d'accueil
require_once 'auth.php';
require_once 'config/connexionBD.php';
require_once 'nav.php';

$uid = $_SESSION['user_id'];

// --- Les 3 dernières fiches ---
$req_fiches = $pdo->prepare('
    SELECT f.id, f.titre, f.difficulte, f.created_at, m.nom AS matiere_nom
    FROM fiches f
    LEFT JOIN matieres m ON m.id = f.matiere_id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
    LIMIT 3
');
$req_fiches->execute([$uid]);
$dernieres_fiches = $req_fiches->fetchAll();

// --- Les 3 derniers cours ---
$req_cours = $pdo->prepare('
    SELECT c.id, c.titre, c.created_at, m.nom AS matiere_nom
    FROM cours c
    LEFT JOIN matieres m ON m.id = c.matiere_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
    LIMIT 3
');
$req_cours->execute([$uid]);
$derniers_cours = $req_cours->fetchAll();

// --- Compteurs ---
$req1 = $pdo->prepare('SELECT COUNT(*) FROM fiches WHERE user_id = ?');
$req1->execute([$uid]);
$nb_fiches = $req1->fetchColumn();

$req2 = $pdo->prepare('SELECT COUNT(*) FROM cours WHERE user_id = ?');
$req2->execute([$uid]);
$nb_cours = $req2->fetchColumn();

$req3 = $pdo->prepare('SELECT COUNT(*) FROM matieres WHERE user_id = ?');
$req3->execute([$uid]);
$nb_matieres = $req3->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accueil — Révision Facile</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main>

  <h2>Bonjour, <?= htmlspecialchars($_SESSION['user_nom']) ?> 👋</h2>
  <p>Voici un résumé de tes révisions.</p>

  <!-- Compteurs rapides -->
  <div class="accueil-compteurs">
    <div class="compteur-card">
      <div class="compteur-nombre"><?= $nb_fiches ?></div>
      <div class="compteur-label"><a href="fiches.php">📄 Fiches</a></div>
    </div>
    <div class="compteur-card">
      <div class="compteur-nombre"><?= $nb_cours ?></div>
      <div class="compteur-label"><a href="cours.php">📚 Cours</a></div>
    </div>
    <div class="compteur-card">
      <div class="compteur-nombre"><?= $nb_matieres ?></div>
      <div class="compteur-label"><a href="matieres.php">🗂️ Matières</a></div>
    </div>
  </div>

  <!-- Dernières fiches -->
  <h2>📄 Dernières fiches ajoutées</h2>

  <?php if (empty($dernieres_fiches)): ?>
    <p>Tu n'as pas encore de fiches. <a href="fiches.php">Créer une fiche →</a></p>
  <?php else: ?>
    <?php foreach ($dernieres_fiches as $fiche): ?>
      <div class="card">
        <h3><?= htmlspecialchars($fiche['titre']) ?></h3>

        <?php if ($fiche['matiere_nom'] != ''): ?>
          <p><strong>Matière :</strong> <?= htmlspecialchars($fiche['matiere_nom']) ?></p>
        <?php endif; ?>

        <?php if ($fiche['difficulte'] != ''): ?>
          <p><strong>Difficulté :</strong> <?= htmlspecialchars($fiche['difficulte']) ?></p>
        <?php endif; ?>

        <p><strong>Ajoutée le :</strong> <?= date('d/m/Y', strtotime($fiche['created_at'])) ?></p>
        <a href="modifier.php?type=fiche&id=<?= $fiche['id'] ?>">✏️ Modifier</a>
      </div>
    <?php endforeach; ?>
    <p><a href="fiches.php">Voir toutes mes fiches →</a></p>
  <?php endif; ?>

  <!-- Derniers cours -->
  <h2>📚 Derniers cours ajoutés</h2>

  <?php if (empty($derniers_cours)): ?>
    <p>Tu n'as pas encore de cours. <a href="cours.php">Ajouter un cours →</a></p>
  <?php else: ?>
    <?php foreach ($derniers_cours as $cours): ?>
      <div class="card">
        <h3><?= htmlspecialchars($cours['titre']) ?></h3>

        <?php if ($cours['matiere_nom'] != ''): ?>
          <p><strong>Matière :</strong> <?= htmlspecialchars($cours['matiere_nom']) ?></p>
        <?php endif; ?>

        <p><strong>Ajouté le :</strong> <?= date('d/m/Y', strtotime($cours['created_at'])) ?></p>
        <a href="modifier.php?type=cours&id=<?= $cours['id'] ?>">✏️ Modifier</a>
      </div>
    <?php endforeach; ?>
    <p><a href="cours.php">Voir tous mes cours →</a></p>
  <?php endif; ?>

</main>
</body>
</html>
