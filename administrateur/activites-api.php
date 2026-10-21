
<?php

// 1. Vérifier que l'utilisateur est connecté et administrateur
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

// 2. Connexion à la base de données
require_once __DIR__ . "/../database.php";

// 3. Indiquer que la réponse sera en JSON
header("Content-Type: application/json; charset=UTF-8");

// Fonction pour envoyer une réponse JSON
function repondre($donnees, $code = 200)
{
    http_response_code($code);

    echo json_encode(
        $donnees,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

try {

    // Récupérer la méthode HTTP utilisée
    $methode = $_SERVER["REQUEST_METHOD"];

    // ==========================================
    // 4. AFFICHER LES ACTIVITÉS
    // ==========================================
    if ($methode === "GET") {

        $sql = "
            SELECT
                a.activite_id,
                a.user_id,
                a.action,
                a.description,
                a.type_activite,
                a.table_concernee,
                a.id_concerne,
                a.adresse_ip,
                a.date_creation,

                u.nom,
                u.prenom,

                r.nom AS nom_role

            FROM activites a

            LEFT JOIN users u
                ON a.user_id = u.user_id

            LEFT JOIN roles r
                ON u.role_id = r.role_id

            ORDER BY a.date_creation DESC
        ";

        $requete = $pdo->prepare($sql);
        $requete->execute();

        $activites = $requete->fetchAll(PDO::FETCH_ASSOC);

        repondre($activites);
    }

    // ==========================================
    // 5. SUPPRIMER UNE ACTIVITÉ OU VIDER LE JOURNAL
    // ==========================================
    if ($methode === "POST") {

        $contenu = file_get_contents("php://input");
        $donnees = json_decode($contenu, true);

        if (!is_array($donnees)) {
            repondre([
                "succes" => false,
                "erreur" => "Les données envoyées sont invalides."
            ], 400);
        }

        $action = $donnees["action"] ?? "";

        // Supprimer une seule activité
        if ($action === "supprimer") {

            $id = $donnees["activite_id"] ?? "";

            if (!is_string($id) || trim($id) === "") {
                repondre([
                    "succes" => false,
                    "erreur" => "Identifiant de l'activité manquant."
                ], 400);
            }

            $sql = "
                DELETE FROM activites
                WHERE activite_id = :id
            ";

            $requete = $pdo->prepare($sql);
            $requete->execute([":id" => $id]);

            if ($requete->rowCount() === 0) {
                repondre([
                    "succes" => false,
                    "erreur" => "Activité introuvable."
                ], 404);
            }

            repondre([
                "succes" => true,
                "message" => "Activité supprimée avec succès."
            ]);
        }

        // Vider tout le journal
        if ($action === "vider") {

            $sql = "DELETE FROM activites";

            $requete = $pdo->prepare($sql);
            $requete->execute();

            repondre([
                "succes" => true,
                "message" => "Le journal a été vidé."
            ]);
        }

        // Action non reconnue
        repondre([
            "succes" => false,
            "erreur" => "Action inconnue."
        ], 400);
    }

    // ==========================================
    // 6. REFUSER LES AUTRES MÉTHODES HTTP
    // ==========================================
    repondre([
        "succes" => false,
        "erreur" => "Méthode non autorisée."
    ], 405);

} catch (PDOException $e) {

    // Enregistrer l'erreur dans le journal du serveur
    error_log("Erreur API activités : " . $e->getMessage());

    repondre([
        "succes" => false,
        "erreur" => "Une erreur est survenue dans la base de données."
    ], 500);
}
?>
