<?php
// modifier.php — Modifier une fiche, un cours ou un créneau
// URL attendue : modifier.php?type=fiche&id=X  ou  type=cours  ou  type=emploi
require_once 'auth.php';
require_once 'config/connexionBD.php';
require_once 'nav.php';

$uid  = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';
$id   = $_GET['id']   ?? 0;

// On vérifie que les paramètres sont valides
if (!in_array($type, ['fiche', 'cours', 'emploi']) || !$id) {
    die('Paramètres invalides.');
}

// --- Enregistrer les modifications quand le formulaire est envoyé ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $matiere_id = !empty($_POST['matiere_id']) ? $_POST['matiere_id'] : null;

    if ($type === 'fiche') {
        $req = $pdo->prepare('UPDATE fiches SET titre = ?, contenu = ?, matiere_id = ?, difficulte = ? WHERE id = ? AND user_id = ?');
        $req->execute([$_POST['titre'], $_POST['contenu'], $matiere_id, $_POST['difficulte'], $id, $uid]);
        header('Location: fiches.php');
        exit;
    }

    if ($type === 'cours') {
        $req = $pdo->prepare('UPDATE cours SET titre = ?, contenu = ?, matiere_id = ? WHERE id = ? AND user_id = ?');
        $req->execute([$_POST['titre'], $_POST['contenu'], $matiere_id, $id, $uid]);
        header('Location: cours.php');
        exit;
    }

    if ($type === 'emploi') {
        $req = $pdo->prepare('UPDATE emploi_du_temps SET jour = ?, date_creneau = ?, heure = ?, matiere_id = ?, description = ? WHERE id = ? AND user_id = ?');
        $req->execute([$_POST['jour'], $_POST['date_creneau'], $_POST['heure'], $matiere_id, $_POST['description'], $id, $uid]);
        header('Location: emploi_du_temps.php');
        exit;
    }
}

// --- Charger l'élément à modifier ---
// On associe chaque type au nom de la table correspondante
$tables = [
    'fiche'  => 'fiches',
    'cours'  => 'cours',
    'emploi' => 'emploi_du_temps'
];

$req = $pdo->prepare('SELECT * FROM ' . $tables[$type] . ' WHERE id = ? AND user_id = ?');
$req->execute([$id, $uid]);
$item = $req->fetch();

// Si l'élément n'existe pas ou n'appartient pas à cet utilisateur
if (!$item) {
    die('Élément introuvable ou accès refusé.');
}

// --- Récupérer les matières pour les listes déroulantes ---
$req_matieres = $pdo->prepare('SELECT * FROM matieres WHERE user_id = ? ORDER BY nom');
$req_matieres->execute([$uid]);
$matieresList = $req_matieres->fetchAll();

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modifier — Révision Facile</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main>

  <!-- Formulaire fiche -->
  <?php if ($type === 'fiche'): ?>
  <h2>Modifier la fiche</h2>
  <form method="POST">
    <input type="text" name="titre" value="<?= htmlspecialchars($item['titre']) ?>" required>
    <textarea name="contenu"><?= htmlspecialchars($item['contenu']) ?></textarea>

    <select name="matiere_id">
      <option value="">-- Choisir une matière --</option>
      <?php foreach ($matieresList as $m): ?>
        <option value="<?= $m['id'] ?>" <?= $item['matiere_id'] == $m['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($m['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="difficulte">
      <option value="">-- Difficulté --</option>
      <?php foreach (['Facile', 'Moyen', 'Difficile'] as $d): ?>
        <option value="<?= $d ?>" <?= $item['difficulte'] === $d ? 'selected' : '' ?>><?= $d ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit">Enregistrer</button>
    <a href="fiches.php">Annuler</a>
  </form>
  <?php endif; ?>

  <!-- Formulaire cours -->
  <?php if ($type === 'cours'): ?>
  <h2>Modifier le cours</h2>
  <form method="POST">
    <input type="text" name="titre" value="<?= htmlspecialchars($item['titre']) ?>" required>
    <textarea name="contenu"><?= htmlspecialchars($item['contenu']) ?></textarea>

    <select name="matiere_id">
      <option value="">-- Choisir une matière --</option>
      <?php foreach ($matieresList as $m): ?>
        <option value="<?= $m['id'] ?>" <?= $item['matiere_id'] == $m['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($m['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit">Enregistrer</button>
    <a href="cours.php">Annuler</a>
  </form>
  <?php endif; ?>

  <!-- Formulaire emploi du temps -->
  <?php if ($type === 'emploi'): ?>
  <h2>Modifier le créneau</h2>
  <form method="POST">
    <select name="jour" required>
      <?php foreach ($jours as $j): ?>
        <option value="<?= $j ?>" <?= $item['jour'] === $j ? 'selected' : '' ?>><?= $j ?></option>
      <?php endforeach; ?>
    </select>

    <input type="date" name="date_creneau" value="<?= htmlspecialchars($item['date_creneau']) ?>" required>
    <input type="time" name="heure" value="<?= htmlspecialchars($item['heure']) ?>" required>

    <select name="matiere_id">
      <option value="">-- Choisir une matière --</option>
      <?php foreach ($matieresList as $m): ?>
        <option value="<?= $m['id'] ?>" <?= $item['matiere_id'] == $m['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($m['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <input type="text" name="description" value="<?= htmlspecialchars($item['description'] ?? '') ?>">

    <button type="submit">Enregistrer</button>
    <a href="emploi_du_temps.php">Annuler</a>
  </form>
  <?php endif; ?>

</main>
</body>
</html>
