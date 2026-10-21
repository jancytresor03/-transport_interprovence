
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

// ==========================================
// 1. RÉPONSE JSON
// ==========================================

function repondre($succes, $message = "", $donnees = [])
{
    echo json_encode(
        array_merge([
            "succes" => $succes,
            "message" => $message
        ], $donnees),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// ==========================================
// 2. AJOUTER UNE NOTIFICATION
// ==========================================

function ajouterNotificationUtilisateur(
    PDO $pdo,
    string $adminId,
    string $titre,
    string $message
) {
    $sql = "
        INSERT INTO notifications (
            notification_id,
            user_id,
            type_notification,
            titre,
            message,
            statut
        )
        VALUES (
            '',
            ?,
            'systeme',
            ?,
            ?,
            'non_lue'
        )
    ";

    $requete = $pdo->prepare($sql);

    $requete->execute([
        $adminId,
        $titre,
        $message
    ]);
}

try {

    // ==========================================
    // 3. ADMINISTRATEUR CONNECTÉ
    // ==========================================

    $adminId = $_SESSION["user_id"] ?? "";

    if ($adminId === "") {
        http_response_code(401);
        repondre(false, "Administrateur non connecté.");
    }

    // ==========================================
    // 4. AFFICHER LES UTILISATEURS
    // ==========================================

    if ($_SERVER["REQUEST_METHOD"] === "GET") {

        $sql = "
            SELECT
                u.user_id,
                u.nom,
                u.prenom,
                u.email,
                u.telephone,
                u.statut,
                u.date_creation AS dateInscription,
                r.nom AS nom_role
            FROM users u
            LEFT JOIN roles r ON r.role_id = u.role_id
            ORDER BY u.date_creation DESC
        ";

        $requete = $pdo->query($sql);

        $utilisateurs = $requete->fetchAll(PDO::FETCH_ASSOC);

        foreach ($utilisateurs as &$u) {

            $u["role"] =
                strtolower(trim($u["nom_role"] ?? "")) === "admin"
                ? "admin"
                : "client";

            unset($u["nom_role"]);
        }

        unset($u);

        repondre(true, "Utilisateurs chargés.", [
            "utilisateurs" => $utilisateurs
        ]);
    }

    // ==========================================
    // 5. AUTRES MÉTHODES INTERDITES
    // ==========================================

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        http_response_code(405);

        repondre(false, "Méthode non autorisée.");
    }

    // ==========================================
    // 6. LIRE LES DONNÉES JSON
    // ==========================================

    $contenu = file_get_contents("php://input");

    $donnees = json_decode($contenu, true);

    if (
        json_last_error() !== JSON_ERROR_NONE ||
        !is_array($donnees)
    ) {
        http_response_code(400);

        repondre(false, "Les données JSON envoyées sont invalides.");
    }

    $action = trim((string)($donnees["action"] ?? ""));

    if ($action === "") {
        repondre(false, "L'action demandée est manquante.");
    }

    // Conserver l'identifiant utilisé par les déclencheurs d'audit.
    $pdo->prepare(
        "SET @user_id_connecte = ?"
    )->execute([$adminId]);

    // ==========================================
    // 7. AJOUTER UN UTILISATEUR
    // ==========================================

    if ($action === "ajouter") {

        $nom = trim((string)($donnees["nom"] ?? ""));
        $prenom = trim((string)($donnees["prenom"] ?? ""));
        $email = trim((string)($donnees["email"] ?? ""));
        $telephone = trim((string)($donnees["telephone"] ?? ""));
        $motDePasse = (string)($donnees["password"] ?? "");

        $role = strtolower(
            trim((string)($donnees["role"] ?? "client"))
        );

        // Vérifier les champs obligatoires.
        if (
            $nom === "" ||
            $prenom === "" ||
            $email === "" ||
            $telephone === "" ||
            trim($motDePasse) === ""
        ) {
            repondre(
                false,
                "Veuillez remplir tous les champs obligatoires."
            );
        }

        if (
            mb_strlen($nom) > 100 ||
            mb_strlen($prenom) > 100 ||
            mb_strlen($email) > 150 ||
            mb_strlen($telephone) > 30
        ) {
            repondre(
                false,
                "Un ou plusieurs champs dépassent la longueur autorisée."
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            repondre(false, "L'adresse e-mail n'est pas valide.");
        }

        if (strlen($motDePasse) < 8) {
            repondre(
                false,
                "Le mot de passe doit contenir au moins 8 caractères."
            );
        }

        if (!in_array($role, ["admin", "client"], true)) {
            repondre(false, "Le rôle sélectionné n'est pas valide.");
        }

        // Vérifier l'unicité de l'e-mail et du téléphone.
        $verification = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE email = ? OR telephone = ?
            LIMIT 1
        ");

        $verification->execute([$email, $telephone]);

        if ($verification->fetch(PDO::FETCH_ASSOC)) {
            repondre(
                false,
                "Cet e-mail ou ce numéro de téléphone est déjà utilisé."
            );
        }

        // Rechercher le rôle existant.
        $nomRole = $role === "admin" ? "Admin" : "Client";

        $requeteRole = $pdo->prepare("
            SELECT role_id
            FROM roles
            WHERE LOWER(nom) = LOWER(?)
              AND statut = 'actif'
            LIMIT 1
        ");

        $requeteRole->execute([$nomRole]);

        $roleId = $requeteRole->fetchColumn();

        if (!$roleId) {
            repondre(
                false,
                "Le rôle demandé n'existe pas ou n'est pas actif."
            );
        }

        // Hacher le mot de passe avant l'enregistrement.
        $motDePasseHache = password_hash(
            $motDePasse,
            PASSWORD_DEFAULT
        );

        if ($motDePasseHache === false) {
            repondre(
                false,
                "Impossible de sécuriser le mot de passe."
            );
        }

        // Enregistrer l'utilisateur et sa notification ensemble.
        $pdo->beginTransaction();

        $insertion = $pdo->prepare("
            INSERT INTO users (
                nom,
                prenom,
                email,
                telephone,
                password,
                role_id,
                statut
            )
            VALUES (?, ?, ?, ?, ?, ?, 'actif')
        ");

        $insertion->execute([
            $nom,
            $prenom,
            $email,
            $telephone,
            $motDePasseHache,
            $roleId
        ]);

        ajouterNotificationUtilisateur(
            $pdo,
            $adminId,
            "Nouvel utilisateur ajouté",
            "L'administrateur a ajouté un utilisateur : "
                . $prenom . " " . $nom . "."
        );

        $pdo->commit();

        repondre(true, "Utilisateur ajouté avec succès.");
    }

    // ==========================================
    // 8. MODIFIER UN UTILISATEUR
    // ==========================================

    if ($action === "modifier") {

        $id = trim((string)($donnees["user_id"] ?? ""));
        $nom = trim((string)($donnees["nom"] ?? ""));
        $prenom = trim((string)($donnees["prenom"] ?? ""));
        $email = trim((string)($donnees["email"] ?? ""));
        $telephone = trim((string)($donnees["telephone"] ?? ""));

        if (
            $id === "" ||
            $nom === "" ||
            $prenom === "" ||
            $email === "" ||
            $telephone === ""
        ) {
            repondre(
                false,
                "Tous les champs obligatoires doivent être remplis."
            );
        }

        if (
            mb_strlen($nom) > 100 ||
            mb_strlen($prenom) > 100 ||
            mb_strlen($email) > 150 ||
            mb_strlen($telephone) > 30
        ) {
            repondre(
                false,
                "Un ou plusieurs champs dépassent la longueur autorisée."
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            repondre(false, "L'adresse e-mail n'est pas valide.");
        }

        $existant = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE user_id = ?
            LIMIT 1
        ");

        $existant->execute([$id]);

        if (!$existant->fetchColumn()) {
            repondre(false, "Utilisateur introuvable.");
        }

        // Vérifier les doublons en excluant l'utilisateur modifié.
        $verification = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE (email = ? OR telephone = ?)
              AND user_id <> ?
            LIMIT 1
        ");

        $verification->execute([
            $email,
            $telephone,
            $id
        ]);

        if ($verification->fetch(PDO::FETCH_ASSOC)) {
            repondre(
                false,
                "Cet e-mail ou ce numéro de téléphone est déjà utilisé."
            );
        }

        $pdo->beginTransaction();

        $modification = $pdo->prepare("
            UPDATE users
            SET nom = ?,
                prenom = ?,
                email = ?,
                telephone = ?
            WHERE user_id = ?
        ");

        $modification->execute([
            $nom,
            $prenom,
            $email,
            $telephone,
            $id
        ]);

        ajouterNotificationUtilisateur(
            $pdo,
            $adminId,
            "Utilisateur modifié",
            "L'administrateur a modifié les informations de "
                . $prenom . " " . $nom . "."
        );

        $pdo->commit();

        repondre(true, "Utilisateur modifié avec succès.");
    }

    // ==========================================
    // 9. BLOQUER OU DÉBLOQUER UN UTILISATEUR
    // ==========================================

    if ($action === "statut") {

        $id = trim((string)($donnees["user_id"] ?? ""));

        $statutDemande = trim(
            (string)($donnees["statut"] ?? "")
        );

        if ($id === "") {
            repondre(
                false,
                "L'identifiant de l'utilisateur est manquant."
            );
        }

        // Accepter la valeur "active" envoyée par JavaScript.
        if ($statutDemande === "active") {
            $statutDemande = "actif";
        }

        if (!in_array(
            $statutDemande,
            ["actif", "bloque", "inactif"],
            true
        )) {
            repondre(false, "Le statut demandé n'est pas valide.");
        }

        // Empêcher l'administrateur de bloquer son propre compte.
        if ($id === $adminId && $statutDemande === "bloque") {
            repondre(
                false,
                "Vous ne pouvez pas bloquer votre propre compte."
            );
        }

        // Récupérer le compte et son statut actuel.
        $existant = $pdo->prepare("
            SELECT user_id, nom, prenom, statut
            FROM users
            WHERE user_id = ?
            LIMIT 1
        ");

        $existant->execute([$id]);

        $utilisateur = $existant->fetch(PDO::FETCH_ASSOC);

        if (!$utilisateur) {
            repondre(false, "Utilisateur introuvable.");
        }

        // Éviter de créer une notification inutile.
        if ($utilisateur["statut"] === $statutDemande) {
            repondre(
                true,
                "Le compte possède déjà ce statut."
            );
        }

        $pdo->beginTransaction();

        $modification = $pdo->prepare("
            UPDATE users
            SET statut = ?
            WHERE user_id = ?
        ");

        $modification->execute([
            $statutDemande,
            $id
        ]);

        $nomComplet = $utilisateur["prenom"]
            . " " . $utilisateur["nom"];

        if ($statutDemande === "bloque") {

            $titre = "Utilisateur bloqué";

            $message = "L'administrateur a bloqué le compte de "
                . $nomComplet . ".";

        } elseif ($statutDemande === "actif") {

            $titre = "Utilisateur débloqué";

            $message = "L'administrateur a activé le compte de "
                . $nomComplet . ".";

        } else {

            $titre = "Statut utilisateur modifié";

            $message = "L'administrateur a changé le statut du compte de "
                . $nomComplet . " en inactif.";
        }

        ajouterNotificationUtilisateur(
            $pdo,
            $adminId,
            $titre,
            $message
        );

        $pdo->commit();

        repondre(true, "Statut mis à jour avec succès.");
    }

    // ==========================================
    // 10. ACTION INCONNUE
    // ==========================================

    repondre(false, "Action inconnue.");

} catch (Throwable $erreur) {

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Erreur utilisateurs API : " . $erreur->getMessage()
    );

    http_response_code(500);

    repondre(
        false,
        "Une erreur est survenue pendant l'opération. "
            . "Consultez le journal d'erreurs PHP."
    );
}
