
<?php
session_start();

// Effacer les données de session
$_SESSION = [];

// Détruire la session
session_destroy();

// Retourner à la page de connexion
header("Location: login.html");
exit;
