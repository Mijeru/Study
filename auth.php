<?php
// auth.php — Protection des pages
// On démarre la session pour avoir accès à $_SESSION
session_start();

// Si l'utilisateur n'est pas connecté, on le redirige vers la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}
