<?php
// deconnexion.php — Déconnexion
session_start();

// On vide la session et on la détruit
session_unset();
session_destroy();

// On redirige vers la connexion
header('Location: connexion.php');
exit;
