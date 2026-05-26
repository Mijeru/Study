<?php
// nav.php — Barre de navigation
// Ce fichier est inclus dans toutes les pages protégées
?>
<header>
  <h1>Révision Facile</h1>
  <nav>
    <a href="index.php">Accueil</a>
    <a href="fiches.php">Fiches</a>
    <a href="cours.php">Cours</a>
    <a href="matieres.php">Matières</a>
  </nav>
  <div class="nav-user">
    <span>👤 <?= htmlspecialchars($_SESSION['user_nom']) ?></span>
    <a href="deconnexion.php" class="btn-deconnexion">Déconnexion</a>
  </div>
</header>
