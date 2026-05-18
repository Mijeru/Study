<?php
// fiches.php — Gestion des fiches de révision
require_once 'auth.php';
require_once 'config/connexionBD.php';
require_once 'nav.php';

$uid = $_SESSION['user_id'];

// --- Ajouter une fiche ---
if (isset($_POST['titre'])) {
    // Si aucune matière choisie, on met NULL
    $matiere_id = !empty($_POST['matiere_id']) ? $_POST['matiere_id'] : null;

    $req = $pdo->prepare('INSERT INTO fiches (user_id, matiere_id, titre, contenu, difficulte) VALUES (?, ?, ?, ?, ?)');
    $req->execute([$uid, $matiere_id, $_POST['titre'], $_POST['contenu'], $_POST['difficulte']]);
}

// --- Supprimer une fiche ---
if (isset($_GET['delete'])) {
    $req = $pdo->prepare('DELETE FROM fiches WHERE id = ? AND user_id = ?');
    $req->execute([$_GET['delete'], $uid]);
}

// --- Mettre en favori ---
if (isset($_GET['favori'])) {
    $req = $pdo->prepare('UPDATE fiches SET favori = 1 WHERE id = ? AND user_id = ?');
    $req->execute([$_GET['favori'], $uid]);
}

// --- Retirer des favoris ---
if (isset($_GET['defavori'])) {
    $req = $pdo->prepare('UPDATE fiches SET favori = 0 WHERE id = ? AND user_id = ?');
    $req->execute([$_GET['defavori'], $uid]);
}

// --- Récupérer les matières pour le formulaire ---
$req_matieres = $pdo->prepare('SELECT * FROM matieres WHERE user_id = ? ORDER BY nom');
$req_matieres->execute([$uid]);
$matieresList = $req_matieres->fetchAll();

// --- Récupérer les fiches favorites ---
$req_favoris = $pdo->prepare('
    SELECT f.*, m.nom AS matiere_nom
    FROM fiches f
    LEFT JOIN matieres m ON m.id = f.matiere_id
    WHERE f.user_id = ? AND f.favori = 1
    ORDER BY f.created_at DESC
');
$req_favoris->execute([$uid]);
$favoris = $req_favoris->fetchAll();

// --- Récupérer les autres fiches (avec filtres si demandés) ---

// Recherche par titre
if (!empty($_GET['recherche'])) {
    $req = $pdo->prepare('
        SELECT f.*, m.nom AS matiere_nom
        FROM fiches f
        LEFT JOIN matieres m ON m.id = f.matiere_id
        WHERE f.user_id = ? AND f.favori = 0 AND f.titre LIKE ?
        ORDER BY f.created_at DESC
    ');
    $req->execute([$uid, '%' . $_GET['recherche'] . '%']);

// Filtre par matière
} elseif (!empty($_GET['matiere_filtre'])) {
    $req = $pdo->prepare('
        SELECT f.*, m.nom AS matiere_nom
        FROM fiches f
        LEFT JOIN matieres m ON m.id = f.matiere_id
        WHERE f.user_id = ? AND f.favori = 0 AND f.matiere_id = ?
        ORDER BY f.created_at DESC
    ');
    $req->execute([$uid, $_GET['matiere_filtre']]);

// Tri par colonne
} elseif (!empty($_GET['tri'])) {
    // On autorise seulement ces colonnes pour éviter les injections SQL
    $colonnes_ok = ['titre', 'difficulte'];
    $tri = in_array($_GET['tri'], $colonnes_ok) ? $_GET['tri'] : 'created_at';

    $req = $pdo->prepare('
        SELECT f.*, m.nom AS matiere_nom
        FROM fiches f
        LEFT JOIN matieres m ON m.id = f.matiere_id
        WHERE f.user_id = ? AND f.favori = 0
        ORDER BY f.' . $tri
    );
    $req->execute([$uid]);

// Aucun filtre : toutes les fiches, les plus récentes en premier
} else {
    $req = $pdo->prepare('
        SELECT f.*, m.nom AS matiere_nom
        FROM fiches f
        LEFT JOIN matieres m ON m.id = f.matiere_id
        WHERE f.user_id = ? AND f.favori = 0
        ORDER BY f.created_at DESC
    ');
    $req->execute([$uid]);
}

$fiches = $req->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fiches — Révision Facile</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main>

  <!-- Formulaire d'ajout -->
  <h2>Créer une fiche</h2>
  <form method="POST">
    <input type="text" name="titre" placeholder="Titre de la fiche" required>
    <textarea name="contenu" placeholder="Contenu de la fiche..." required></textarea>

    <select name="matiere_id">
      <option value="">-- Choisir une matière --</option>
      <?php foreach ($matieresList as $m): ?>
        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
      <?php endforeach; ?>
    </select>

    <?php if (empty($matieresList)): ?>
      <p><a href="matieres.php">Crée d'abord une matière</a></p>
    <?php endif; ?>

    <select name="difficulte">
      <option value="">-- Difficulté --</option>
      <option value="Facile">Facile</option>
      <option value="Moyen">Moyen</option>
      <option value="Difficile">Difficile</option>
    </select>

    <button type="submit">Enregistrer la fiche</button>
  </form>

  <!-- Fiches favorites -->
  <h2>⭐ Fiches favorites</h2>

  <?php if (empty($favoris)): ?>
    <p>Aucune fiche en favori.</p>
  <?php else: ?>
    <?php foreach ($favoris as $f): ?>
      <div class="card">
        <h3><?= htmlspecialchars($f['titre']) ?></h3>
        <p><?= htmlspecialchars($f['contenu']) ?></p>

        <?php if ($f['matiere_nom'] != ''): ?>
          <p><strong>Matière :</strong> <?= htmlspecialchars($f['matiere_nom']) ?></p>
        <?php endif; ?>

        <p><strong>Difficulté :</strong> <?= htmlspecialchars($f['difficulte'] ?? '—') ?></p>

        <a href="modifier.php?type=fiche&id=<?= $f['id'] ?>">✏️ Modifier</a>
        <a href="?defavori=<?= $f['id'] ?>">★ Retirer</a>
        <a href="?delete=<?= $f['id'] ?>" onclick="return confirm('Supprimer cette fiche ?')">❌ Supprimer</a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Recherche et filtres -->
  <h2>Mes fiches</h2>

  <form method="GET">
    <input type="text" name="recherche" placeholder="Rechercher par titre..."
           value="<?= htmlspecialchars($_GET['recherche'] ?? '') ?>">
    <button type="submit">Rechercher</button>
  </form>

  <form method="GET" style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px">
    <select name="matiere_filtre" style="flex:1">
      <option value="">-- Toutes les matières --</option>
      <?php foreach ($matieresList as $m): ?>
        <option value="<?= $m['id'] ?>" <?= (($_GET['matiere_filtre'] ?? '') == $m['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($m['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="tri" style="flex:1">
      <option value="">-- Trier par --</option>
      <option value="titre"      <?= (($_GET['tri'] ?? '') === 'titre')      ? 'selected' : '' ?>>Titre</option>
      <option value="difficulte" <?= (($_GET['tri'] ?? '') === 'difficulte') ? 'selected' : '' ?>>Difficulté</option>
    </select>

    <button type="submit">Filtrer</button>
  </form>

  <!-- Liste des fiches -->
  <?php if (empty($fiches)): ?>
    <p>Aucune fiche trouvée.</p>
  <?php else: ?>
    <?php foreach ($fiches as $f): ?>
      <div class="card">
        <h3><?= htmlspecialchars($f['titre']) ?></h3>
        <p><?= htmlspecialchars($f['contenu']) ?></p>

        <?php if ($f['matiere_nom'] != ''): ?>
          <p><strong>Matière :</strong> <?= htmlspecialchars($f['matiere_nom']) ?></p>
        <?php endif; ?>

        <p><strong>Difficulté :</strong> <?= htmlspecialchars($f['difficulte'] ?? '—') ?></p>

        <a href="modifier.php?type=fiche&id=<?= $f['id'] ?>">✏️ Modifier</a>
        <a href="?favori=<?= $f['id'] ?>">⭐ Favoris</a>
        <a href="?delete=<?= $f['id'] ?>" onclick="return confirm('Supprimer cette fiche ?')">❌ Supprimer</a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</main>
</body>
</html>
