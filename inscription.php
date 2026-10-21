
<?php
session_start();

require_once __DIR__ . "/database.php";

$message = "";
$typeMessage = "";

// Le formulaire doit être envoyé avec POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Veuillez utiliser le formulaire d'inscription.");
}

// 1. Récupérer les données envoyées par register.html
$nom = trim($_POST["nom"] ?? "");
$prenom = trim($_POST["prenom"] ?? "");
$email = strtolower(trim($_POST["email"] ?? ""));
$telephone = trim($_POST["telephone"] ?? "");
$motDePasse = $_POST["mot_de_passe"] ?? "";
$confirmation = $_POST["confirmation_mot_de_passe"] ?? "";
$adresse = trim($_POST["adresse"] ?? "");

// 2. Vérifier les champs obligatoires
if (
    $nom === "" ||
    $prenom === "" ||
    $email === "" ||
    $telephone === "" ||
    $motDePasse === "" ||
    $confirmation === ""
) {
    $message = "Veuillez remplir tous les champs obligatoires.";
    $typeMessage = "erreur";

} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = "Veuillez saisir une adresse e-mail valide.";
    $typeMessage = "erreur";

} elseif ($motDePasse !== $confirmation) {
    $message = "Les deux mots de passe ne correspondent pas.";
    $typeMessage = "erreur";

} elseif (strlen($motDePasse) < 8) {
    $message = "Le mot de passe doit contenir au moins 8 caractères.";
    $typeMessage = "erreur";

} else {
    try {
        // 3. Vérifier si l'e-mail ou le téléphone existe déjà
        $sql = "SELECT user_id
                FROM users
                WHERE email = :email
                   OR telephone = :telephone
                LIMIT 1";

        $requete = $pdo->prepare($sql);

        $requete->execute([
            "email" => $email,
            "telephone" => $telephone
        ]);

        if ($requete->fetch()) {
            $message = "Cet e-mail ou ce numéro de téléphone est déjà utilisé.";
            $typeMessage = "erreur";

        } else {
            // 4. Récupérer le rôle Client
            $sqlRole = "SELECT role_id
                        FROM roles
                        WHERE LOWER(nom) = LOWER(:nom)
                        LIMIT 1";

            $requeteRole = $pdo->prepare($sqlRole);
            $requeteRole->execute(["nom" => "Client"]);

            $role = $requeteRole->fetch();

            if (!$role) {
                $message = "Le rôle Client est introuvable dans la base de données.";
                $typeMessage = "erreur";

            } else {
                // 5. Hacher le mot de passe
                $motDePasseHash = password_hash(
                    $motDePasse,
                    PASSWORD_DEFAULT
                );

                if ($motDePasseHash === false) {
                    throw new RuntimeException(
                        "Impossible de sécuriser le mot de passe."
                    );
                }

                // 6. Enregistrer le compte
                // Le système existant doit générer user_id.
                $sqlInsert = "INSERT INTO users
                    (
                        nom,
                        prenom,
                        email,
                        telephone,
                        password,
                        role_id,
                        adresse,
                        statut
                    )
                    VALUES
                    (
                        :nom,
                        :prenom,
                        :email,
                        :telephone,
                        :password,
                        :role_id,
                        :adresse,
                        :statut
                    )";

                $requeteInsert = $pdo->prepare($sqlInsert);

                $requeteInsert->execute([
                    "nom" => $nom,
                    "prenom" => $prenom,
                    "email" => $email,
                    "telephone" => $telephone,
                    "password" => $motDePasseHash,
                    "role_id" => $role["role_id"],
                    "adresse" => $adresse !== "" ? $adresse : null,
                    "statut" => "actif"
                ]);

                // 7. Succès
                $message = "Votre compte a été créé avec succès !";
                $typeMessage = "succes";
            }
        }

    } catch (Throwable $erreur) {
        // Conserver les détails dans le journal du serveur
        error_log("Erreur inscription Burundi Transport : "
            . $erreur->getMessage());

        $message = "L'inscription a échoué. Vérifiez la configuration "
            . "de la base de données ou réessayez.";
		 //$message = "Erreur technique : " . $erreur->getMessage();
        $typeMessage = "erreur";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Burundi Transport</title>
</head>
<body>

    <h1>Burundi Transport</h1>

    <p>
        <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
    </p>

    <?php if ($typeMessage === "succes") : ?>
        <a href="login.html">Se connecter</a>
    <?php else : ?>
        <a href="register.html">Retour au formulaire</a>
    <?php endif; ?>

</body>
</html>
