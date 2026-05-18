<?php
// emploi_du_temps.php — Planning hebdomadaire
require_once 'auth.php';
require_once 'config/connexionBD.php';
require_once 'nav.php';

$uid        = $_SESSION['user_id'];
$aujourdhui = date('Y-m-d');

// --- Suppression automatique des créneaux passés ---
// À chaque visite, on supprime les créneaux dont la date est déjà passée
$req = $pdo->prepare('DELETE FROM emploi_du_temps WHERE user_id = ? AND date_creneau < ?');
$req->execute([$uid, $aujourdhui]);

// --- Ajouter un créneau ---
if (isset($_POST['jour'])) {
    $matiere_id = !empty($_POST['matiere_id']) ? $_POST['matiere_id'] : null;

    $req = $pdo->prepare('INSERT INTO emploi_du_temps (user_id, matiere_id, jour, date_creneau, heure, description) VALUES (?, ?, ?, ?, ?, ?)');
    $req->execute([$uid, $matiere_id, $_POST['jour'], $_POST['date_creneau'], $_POST['heure'], $_POST['description']]);
}

// --- Supprimer un créneau manuellement ---
if (isset($_GET['delete'])) {
    $req = $pdo->prepare('DELETE FROM emploi_du_temps WHERE id = ? AND user_id = ?');
    $req->execute([$_GET['delete'], $uid]);
}

// --- Récupérer les matières ---
$req_matieres = $pdo->prepare('SELECT * FROM matieres WHERE user_id = ? ORDER BY nom');
$req_matieres->execute([$uid]);
$matieresList = $req_matieres->fetchAll();

// --- Récupérer tous les créneaux à venir ---
$req = $pdo->prepare('
    SELECT e.*, m.nom AS matiere_nom
    FROM emploi_du_temps e
    LEFT JOIN matieres m ON m.id = e.matiere_id
    WHERE e.user_id = ?
    ORDER BY e.date_creneau ASC, e.heure ASC
');
$req->execute([$uid]);
$creneaux = $req->fetchAll();

// --- Organiser les créneaux dans la grille ---
// $grille[jour][heure] = liste des créneaux à ce moment
$grille = [];
foreach ($creneaux as $c) {
    // On extrait seulement le chiffre de l'heure (ex: "09:30:00" → 9)
    $heure = intval(explode(':', $c['heure'])[0]);

    $c['matiere_affichee'] = $c['matiere_nom'] ?? 'Sans matière';
    $grille[$c['jour']][$heure][] = $c;
}

$jours  = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$heures = range(8, 20); // tableau [8, 9, 10, ..., 20]
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Emploi du temps — Révision Facile</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main>

  <!-- Formulaire d'ajout -->
  <h2>Ajouter un créneau</h2>
  <form method="POST">
    <select name="jour" required>
      <option value="">-- Jour --</option>
      <?php foreach ($jours as $j): ?>
        <option value="<?= $j ?>"><?= $j ?></option>
      <?php endforeach; ?>
    </select>

    <!-- min empêche de choisir une date déjà passée -->
    <input type="date" name="date_creneau" required min="<?= $aujourdhui ?>">
    <input type="time" name="heure" required>

    <select name="matiere_id">
      <option value="">-- Matière --</option>
      <?php foreach ($matieresList as $m): ?>
        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
      <?php endforeach; ?>
    </select>

    <input type="text" name="description" placeholder="Description (optionnel)">
    <button type="submit">Ajouter</button>
  </form>

  <!-- Grille hebdomadaire -->
  <h2>Ma semaine</h2>
  <div class="grille-wrapper">
    <table class="grille">
      <thead>
        <tr>
          <th></th>
          <?php foreach ($jours as $jour): ?>
            <th><?= $jour ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($heures as $h): ?>
          <tr>
            <td class="heure-col"><?= $h ?>h</td>

            <?php foreach ($jours as $jour): ?>
              <td>
                <?php if (isset($grille[$jour][$h])): ?>
                  <?php foreach ($grille[$jour][$h] as $c): ?>
                    <div class="fiche-resume"
                         onclick="ouvrirPopup(
                           <?= $c['id'] ?>,
                           '<?= htmlspecialchars($c['jour'],             ENT_QUOTES) ?>',
                           '<?= htmlspecialchars($c['date_creneau'],     ENT_QUOTES) ?>',
                           '<?= htmlspecialchars($c['heure'],            ENT_QUOTES) ?>',
                           '<?= htmlspecialchars($c['matiere_affichee'], ENT_QUOTES) ?>',
                           '<?= htmlspecialchars($c['description'] ?? '', ENT_QUOTES) ?>'
                         )">
                      <?= htmlspecialchars($c['heure']) ?> — <?= htmlspecialchars($c['matiere_affichee']) ?>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Popup de détail d'un créneau -->
  <div class="popup-overlay" id="popup">
    <div class="popup-boite">
      <h3 id="popup-matiere"></h3>
      <p><strong>Jour :</strong>        <span id="popup-jour"></span></p>
      <p><strong>Date :</strong>        <span id="popup-date"></span></p>
      <p><strong>Heure :</strong>       <span id="popup-heure"></span></p>
      <p><strong>Description :</strong> <span id="popup-description"></span></p>
      <div class="popup-actions">
        <a href="#" id="popup-modifier"  class="btn-modifier">✏️ Modifier</a>
        <a href="#" id="popup-supprimer" class="btn-supprimer" onclick="return confirm('Supprimer ce créneau ?')">❌ Supprimer</a>
        <button onclick="fermerPopup()" class="btn-fermer">Fermer</button>
      </div>
    </div>
  </div>

</main>

<script>
  // Ouvre la popup avec les infos du créneau cliqué
  function ouvrirPopup(id, jour, date, heure, matiere, description) {
    document.getElementById('popup-matiere').textContent     = matiere;
    document.getElementById('popup-jour').textContent        = jour;
    document.getElementById('popup-date').textContent        = date;
    document.getElementById('popup-heure').textContent       = heure;
    document.getElementById('popup-description').textContent = description || 'Aucune description';

    document.getElementById('popup-modifier').href           = 'modifier.php?type=emploi&id=' + id;
    document.getElementById('popup-supprimer').href          = '?delete=' + id;

    document.getElementById('popup').classList.add('actif');
  }

  // Ferme la popup
  function fermerPopup() {
    document.getElementById('popup').classList.remove('actif');
  }

  // Clic sur le fond sombre = fermer la popup
  document.getElementById('popup').addEventListener('click', function(e) {
    if (e.target === this) {
      fermerPopup();
    }
  });
</script>
</body>
</html>
