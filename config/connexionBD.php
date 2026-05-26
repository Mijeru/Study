<?php
// ================================================================
// connexionBD.php — Connexion à la base de données
// Ce fichier est inclus dans toutes les pages qui ont besoin de la BD.
// Il crée la variable $pdo qui sera utilisée dans tout le projet.
// ================================================================

// Empêche l'accès direct au fichier via le navigateur (sécurité)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    exit('Accès refusé');
}

// Paramètres de connexion à la base de données
$host   = 'localhost';
$dbname = 'study';
$user   = 'root';
$pass   = '';

// On essaie de se connecter. Si ça échoue, on affiche une erreur et on arrête tout.
try {
    // PDO = PHP Data Objects, une façon sécurisée de parler à MySQL
    // charset=utf8 : pour que les accents fonctionnent correctement
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // On n'affiche pas le vrai message d'erreur pour ne pas exposer les détails de la BD
    die('Erreur connexion base de données');
}
