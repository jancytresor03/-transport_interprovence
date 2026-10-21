
<?php

require_once __DIR__ . "/database.php";

try {
    $requete = $pdo->query("SELECT VERSION() AS version_mysql");
    $resultat = $requete->fetch();

    echo "Connexion à MySQL réussie !<br>";
    echo "Version de MySQL : "
        . htmlspecialchars($resultat["version_mysql"], ENT_QUOTES, "UTF-8");

} catch (PDOException $erreur) {

    error_log("Erreur du test MySQL : " . $erreur->getMessage());

    http_response_code(500);
    echo "La vérification de la base de données a échoué.";
}
?>