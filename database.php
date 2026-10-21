
<?php

// Empêcher l'affichage des erreurs sensibles en production
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Paramètres de connexion à MySQL
$serveur = "localhost";
$nomBase = "burundi_trans";
$utilisateur = "root";
$motDePasse = "";

// Options PDO
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    // Création de la connexion
    $pdo = new PDO(
        "mysql:host=$serveur;dbname=$nomBase;charset=utf8mb4",
        $utilisateur,
        $motDePasse,
        $options
    );

} catch (PDOException $erreur) {

    // Enregistrer l'erreur dans les journaux du serveur
    error_log("Erreur de connexion MySQL : " . $erreur->getMessage());

    // Message sans détails techniques pour l'utilisateur
    http_response_code(500);
    exit("Impossible de se connecter à la base de données.");
}
?>