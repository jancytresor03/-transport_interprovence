<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$adminId = $_SESSION["user_id"] ?? "";
$message = "";
$messageType = "success";

/* =====================================
   FONCTIONS
===================================== */

function h($valeur): string
{
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

function formaterTaille($octets): string
{
    $octets = (int) $octets;

    if ($octets < 1024) {
        return $octets . " octets";
    }

    if ($octets < 1024 * 1024) {
        return number_format($octets / 1024, 1, ",", " ") . " Ko";
    }

    return number_format(
        $octets / (1024 * 1024),
        2,
        ",",
        " "
    ) . " Mo";
}

/* =====================================
   PROTECTION CSRF ET MESSAGE FLASH
===================================== */

if (empty($_SESSION["csrf_documents"])) {
    $_SESSION["csrf_documents"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_documents"];

if (isset($_SESSION["flash_documents"])) {
    $message = $_SESSION["flash_documents"]["message"];
    $messageType = $_SESSION["flash_documents"]["type"];

    unset($_SESSION["flash_documents"]);
}

/* =====================================
   INFORMATIONS DE L'ADMINISTRATEUR
===================================== */

$nomAffiche = "Administrateur";
$emailAffiche = "";

try {
    $stmt = $pdo->prepare("
        SELECT nom, prenom, email
        FROM users
        WHERE user_id = :id
        LIMIT 1
    ");

    $stmt->execute(["id" => $adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $nomAffiche = trim(
            $admin["prenom"] . " " . $admin["nom"]
        );

        $emailAffiche = $admin["email"];
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
}

/* =====================================
   AJOUTER UN COMMENTAIRE
   POST sur cette même page
===================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["action"] ?? "") === "ajouter_commentaire") {

    $documentId = trim((string) ($_POST["document_id"] ?? ""));
    $contenu = trim((string) ($_POST["contenu"] ?? ""));
    $tokenRecu = (string) ($_POST["csrf_token"] ?? "");

    try {
        if (
            $tokenRecu === ""
            || !hash_equals($csrfToken, $tokenRecu)
        ) {
            throw new RuntimeException(
                "La session du formulaire a expiré. Recharge la page et réessaie."
            );
        }

        if ($documentId === "" || strlen($documentId) > 20) {
            throw new RuntimeException("Document invalide.");
        }

        if ($contenu === "") {
            throw new RuntimeException(
                "Écris un commentaire avant de l'envoyer."
            );
        }

        if (mb_strlen($contenu, "UTF-8") > 5000) {
            throw new RuntimeException(
                "Le commentaire ne doit pas dépasser 5 000 caractères."
            );
        }

        /*
         * Vérifier que le document est toujours actif
         * et qu'il est partagé avec l'administrateur connecté.
         */
        $stmt = $pdo->prepare("
            SELECT d.document_id
            FROM documents d
            INNER JOIN partages_documents p
                ON p.document_id = d.document_id
            WHERE d.document_id = :document_id
              AND d.statut = 'actif'
              AND p.partage_avec = :admin_id
              AND p.statut = 'actif'
            LIMIT 1
        ");

        $stmt->execute([
            "document_id" => $documentId,
            "admin_id" => $adminId
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException(
                "Tu n'es pas autorisé à commenter ce document."
            );
        }

        /*
         * Les identifiants de commentaire utilisent
         * 20 caractères hexadécimaux.
         */
        $commentaireId = bin2hex(random_bytes(10));

        $stmt = $pdo->prepare("
            INSERT INTO commentaires_documents (
                commentaire_id,
                document_id,
                user_id,
                contenu,
                date_creation
            ) VALUES (
                :commentaire_id,
                :document_id,
                :user_id,
                :contenu,
                NOW()
            )
        ");

        $stmt->execute([
            "commentaire_id" => $commentaireId,
            "document_id" => $documentId,
            "user_id" => $adminId,
            "contenu" => $contenu
        ]);

        $_SESSION["flash_documents"] = [
            "message" => "Ton commentaire a été ajouté avec succès.",
            "type" => "success"
        ];

        /*
         * Redirection pour éviter de soumettre
         * le même commentaire une deuxième fois.
         */
        header(
            "Location: documents-partages.php#commentaires-"
            . rawurlencode($documentId)
        );
        exit;

    } catch (RuntimeException $e) {
        $_SESSION["flash_documents"] = [
            "message" => $e->getMessage(),
            "type" => "error"
        ];

        $ancre = $documentId !== ""
            ? "#commentaires-" . rawurlencode($documentId)
            : "";

        header("Location: documents-partages.php" . $ancre);
        exit;

    } catch (Throwable $e) {
        error_log($e->getMessage());

        $_SESSION["flash_documents"] = [
            "message" => "Une erreur est survenue pendant l'ajout du commentaire.",
            "type" => "error"
        ];

        header(
            "Location: documents-partages.php#commentaires-"
            . rawurlencode($documentId)
        );
        exit;
    }
}

/* =====================================
   TÉLÉCHARGEMENT SÉCURISÉ
===================================== */

if (isset($_GET["telecharger"])) {
    $documentId = trim((string) $_GET["telecharger"]);

    try {
        $stmt = $pdo->prepare("
            SELECT
                d.document_id,
                d.nom_document,
                d.nom_fichier,
                d.chemin_fichier,
                d.type_document,
                d.extension
            FROM partages_documents p
            INNER JOIN documents d
                ON d.document_id = p.document_id
            WHERE p.document_id = :document_id
              AND p.partage_avec = :admin_id
              AND p.statut = 'actif'
              AND d.statut = 'actif'
            LIMIT 1
        ");

        $stmt->execute([
            "document_id" => $documentId,
            "admin_id" => $adminId
        ]);

        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            http_response_code(403);
            exit("Accès refusé : ce document ne vous est pas partagé.");
        }

        $chemin = $document["chemin_fichier"];

        if (
            empty($chemin)
            || !is_file($chemin)
            || !is_readable($chemin)
        ) {
            http_response_code(404);
            exit("Le fichier est introuvable sur le serveur.");
        }

        /*
         * Le nom transmis au navigateur n'est jamais
         * utilisé comme chemin sur le serveur.
         */
        $nomFichier = basename(
            str_replace(
                "\\",
                "/",
                (string) (
                    $document["nom_fichier"]
                    ?: $document["nom_document"]
                )
            )
        );

        if (
            $nomFichier === ""
            || $nomFichier === "."
            || $nomFichier === ".."
        ) {
            $nomFichier = "document";
        }

        $typeMime = "application/octet-stream";

        if (
            !empty($document["type_document"])
            && preg_match(
                '~^[a-zA-Z0-9!#$&^_.+-]+/[a-zA-Z0-9!#$&^_.+-]+$~',
                $document["type_document"]
            )
        ) {
            $typeMime = $document["type_document"];
        } elseif (function_exists("finfo_open")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo) {
                $mimeDetecte = finfo_file($finfo, $chemin);

                if ($mimeDetecte) {
                    $typeMime = $mimeDetecte;
                }

                finfo_close($finfo);
            }
        }

        session_write_close();

        header("Content-Type: " . $typeMime);
        header("Content-Length: " . filesize($chemin));
        header(
            "Content-Disposition: attachment; filename*=UTF-8''"
            . rawurlencode($nomFichier)
        );
        header("X-Content-Type-Options: nosniff");
        header("Cache-Control: private, no-store, no-cache, must-revalidate");

        readfile($chemin);
        exit;

    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        exit("Une erreur est survenue pendant le téléchargement.");
    }
}

/* =====================================
   CHARGER LES DOCUMENTS REÇUS
===================================== */

$documentsPartages = [];

try {
    $stmt = $pdo->prepare("
        SELECT
            p.partage_id,
            p.date_partage,
            d.document_id,
            d.nom_document,
            d.extension,
            d.taille_fichier,
            d.description,
            u.nom AS expediteur_nom,
            u.prenom AS expediteur_prenom,
            u.email AS expediteur_email
        FROM partages_documents p
        INNER JOIN documents d
            ON d.document_id = p.document_id
        INNER JOIN users u
            ON u.user_id = p.partage_par
        WHERE p.partage_avec = :admin_id
          AND p.statut = 'actif'
          AND d.statut = 'actif'
        ORDER BY p.date_partage DESC
    ");

    $stmt->execute(["admin_id" => $adminId]);

    $documentsPartages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Charger les commentaires de chaque document reçu.
     * On ne charge que les commentaires des documents
     * auxquels le compte connecté a accès.
     */
    $stmtCommentaires = $pdo->prepare("
        SELECT
            c.commentaire_id,
            c.contenu,
            c.date_creation,
            c.date_modification,
            u.user_id,
            u.nom,
            u.prenom
        FROM commentaires_documents c
        INNER JOIN users u
            ON u.user_id = c.user_id
        WHERE c.document_id = :document_id
        ORDER BY c.date_creation ASC, c.commentaire_id ASC
    ");

    foreach ($documentsPartages as &$doc) {
        $stmtCommentaires->execute([
            "document_id" => $doc["document_id"]
        ]);

        $doc["commentaires"] = $stmtCommentaires->fetchAll(
            PDO::FETCH_ASSOC
        );

        $doc["nombre_commentaires"] = count($doc["commentaires"]);
    }

    unset($doc);

} catch (Throwable $e) {
    error_log($e->getMessage());

    $documentsPartages = [];

    $message = "Impossible de charger les documents ou leurs commentaires.";
    $messageType = "error";
}

$expediteurs = [];

foreach ($documentsPartages as $doc) {
    $expediteurs[$doc["expediteur_email"]] = true;
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Documents partagés | Burundi Transport</title>

    <link rel="stylesheet" href="../theme.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        :root {
            --primary: #2c6e9b;
            --primary-light: #4a8db8;
            --primary-dark: #1e4d6f;
            --accent: #e8b04b;
            --text: #1e2a36;
            --text-muted: #5f7080;
            --border: #e1e8ee;
            --page-bg: #f4f7fa;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--page-bg);
            color: var(--text);
            font-family: Inter, Arial, sans-serif;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            padding: 22px 15px;
            background: linear-gradient(180deg, #173b57, #102d43);
            color: white;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 1000;
            transition: transform .25s ease;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px 8px 25px;
            color: white;
            text-decoration: none;
            font-size: 17px;
            font-weight: 800;
        }

        .logo-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: var(--accent);
            color: #173b57;
            font-size: 20px;
            flex-shrink: 0;
        }

        .logo small {
            display: block;
            margin-top: 4px;
            color: #b9ccda;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 1px;
        }

        .menu-label {
            padding: 8px 12px;
            color: #94afc2;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 13px;
            min-height: 43px;
            padding: 11px 13px;
            border-radius: 8px;
            color: #d9e5ed;
            text-decoration: none;
            font-size: 12px;
            transition: background .2s, color .2s;
        }

        .menu a i {
            width: 18px;
            text-align: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .menu a:hover {
            background: rgba(255, 255, 255, .09);
            color: white;
        }

        .menu a.active {
            background: var(--accent);
            color: #173b57;
            font-weight: 700;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 20px;
        }

        .admin-mini {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 8px;
            margin-bottom: 10px;
            border-top: 1px solid rgba(255, 255, 255, .13);
        }

        .avatar {
            width: 39px;
            height: 39px;
            border-radius: 50%;
            background: var(--accent);
            color: #173b57;
            display: grid;
            place-items: center;
            font-weight: 800;
            flex-shrink: 0;
        }

        .admin-mini-info {
            min-width: 0;
        }

        .admin-mini-info strong,
        .admin-mini-info small {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .admin-mini-info strong {
            font-size: 12px;
        }

        .admin-mini-info small {
            margin-top: 4px;
            color: #b9ccda;
            font-size: 10px;
        }

        .logout-button {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 11px 13px;
            border-radius: 8px;
            color: #f3c4c4;
            text-decoration: none;
            font-size: 13px;
        }

        .logout-button:hover {
            background: rgba(255, 255, 255, .08);
        }

        /* CONTENU PRINCIPAL */

        .main-content {
            min-height: 100vh;
            margin-left: 250px;
        }

        .top-header {
            position: sticky;
            top: 0;
            z-index: 500;
            min-height: 76px;
            padding: 14px 28px;
            background: rgba(255, 255, 255, .97);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .menu-button {
            display: none;
            width: 40px;
            height: 40px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: white;
            color: var(--primary-dark);
            cursor: pointer;
        }

        .header-title h2 {
            margin: 0;
            color: var(--primary-dark);
            font-size: 18px;
        }

        .header-title p {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: 12px;
        }

        .header-admin {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-admin strong,
        .header-admin small {
            display: block;
        }

        .header-admin strong {
            font-size: 12px;
        }

        .header-admin small {
            margin-top: 4px;
            color: var(--text-muted);
            font-size: 11px;
        }

        .page-content {
            max-width: 1500px;
            margin: 0 auto;
            padding: 28px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 23px;
        }

        .page-heading h1 {
            margin: 0 0 8px;
            color: var(--primary-dark);
            font-size: 25px;
        }

        .page-heading p {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .heading-icon {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 14px;
            background: #e3f0f8;
            color: var(--primary);
            font-size: 22px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 17px;
            margin-bottom: 23px;
        }

        .summary-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 19px;
            border: 1px solid #e8edf2;
            border-radius: 12px;
            background: white;
            box-shadow: 0 4px 18px rgba(26, 55, 76, .04);
        }

        .summary-icon {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: #eaf3f9;
            color: var(--primary);
            font-size: 18px;
        }

        .summary-card small {
            display: block;
            margin-bottom: 5px;
            color: var(--text-muted);
            font-size: 11px;
        }

        .summary-card strong {
            color: var(--primary-dark);
            font-size: 22px;
        }

        .card {
            padding: 24px;
            margin-bottom: 23px;
            border: 1px solid #e8edf2;
            border-radius: 13px;
            background: white;
            box-shadow: 0 4px 18px rgba(26, 55, 76, .04);
        }

        .card-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .card-heading-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 11px;
            color: var(--primary);
            background: #eaf3f9;
        }

        .card-heading h3 {
            margin: 0;
            color: var(--primary-dark);
            font-size: 17px;
        }

        .card-heading p {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: 12px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #e8edf2;
            text-align: left;
            vertical-align: top;
            font-size: 12px;
        }

        th {
            background: #f6f9fb;
            color: var(--primary-dark);
            font-weight: 700;
        }

        tbody tr:hover {
            background: #fafcfd;
        }

        .document-name {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
            font-weight: 700;
        }

        .file-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 9px;
            background: #eaf3f9;
            color: var(--primary);
            font-size: 17px;
        }

        .document-name small {
            display: block;
            margin-top: 5px;
            color: var(--text-muted);
            font-weight: 400;
            font-size: 11px;
        }

        .sender-email {
            display: block;
            margin-top: 5px;
            color: var(--text-muted);
            font-size: 11px;
        }

        .download-button,
        .comment-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 12px;
            border: 0;
            border-radius: 7px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .download-button:hover,
        .comment-submit:hover {
            background: var(--primary-dark);
        }

        /* COMMENTAIRES */

        .comments-details {
            min-width: 250px;
            margin-top: 10px;
        }

        .comments-details summary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 11px;
            border: 1px solid #d9e7f0;
            border-radius: 7px;
            background: #f1f7fb;
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            list-style: none;
        }

        .comments-details summary::-webkit-details-marker {
            display: none;
        }

        .comments-details[open] summary {
            background: #e3f0f8;
        }

        .comments-panel {
            width: min(540px, 75vw);
            margin-top: 12px;
            padding: 15px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fbfdff;
            white-space: normal;
        }

        .comments-title {
            margin: 0 0 14px;
            color: var(--primary-dark);
            font-size: 14px;
        }

        .comment-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 320px;
            overflow-y: auto;
            margin-bottom: 18px;
        }

        .comment-item {
            padding: 12px;
            border: 1px solid #e6edf2;
            border-radius: 9px;
            background: white;
        }

        .comment-author {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 8px;
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 50%;
            background: #e3f0f8;
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 800;
        }

        .comment-author strong {
            display: block;
            color: var(--primary-dark);
            font-size: 12px;
        }

        .comment-date {
            display: block;
            margin-top: 3px;
            color: var(--text-muted);
            font-size: 10px;
        }

        .comment-content {
            color: var(--text);
            font-size: 12px;
            line-height: 1.7;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .no-comments {
            padding: 17px 10px;
            border: 1px dashed #ccdce7;
            border-radius: 8px;
            color: var(--text-muted);
            text-align: center;
            font-size: 12px;
            line-height: 1.6;
        }

        .comment-form {
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .comment-form label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 700;
        }

        .comment-form textarea {
            width: 100%;
            min-height: 95px;
            padding: 11px;
            border: 1px solid #cfdce5;
            border-radius: 8px;
            outline: none;
            background: white;
            color: var(--text);
            font-family: inherit;
            font-size: 12px;
            line-height: 1.6;
            resize: vertical;
        }

        .comment-form textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 110, 155, .1);
        }

        .comment-form-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 9px;
        }

        .character-count {
            color: var(--text-muted);
            font-size: 10px;
        }

        .empty-state {
            padding: 42px 15px;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state i {
            display: block;
            margin-bottom: 15px;
            color: #9bb5c8;
            font-size: 36px;
        }

        .empty-state h4 {
            margin: 0 0 8px;
            color: var(--primary-dark);
            font-size: 16px;
        }

        .empty-state p {
            margin: 0;
            font-size: 12px;
            line-height: 1.7;
        }

        .alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert.success {
            background: #e9f8ef;
            color: #17663a;
            border: 1px solid #c4ead1;
        }

        .alert.error {
            background: #fff0f0;
            color: #9b2222;
            border: 1px solid #f2cccc;
        }

        .sidebar-overlay {
            display: none;
        }

        @media (max-width: 1100px) {
            .summary {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .menu-button {
                display: grid;
                place-items: center;
            }

            .sidebar-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 900;
                background: rgba(12, 31, 46, .45);
            }

            .top-header {
                padding: 12px 18px;
            }

            .page-content {
                padding: 20px 17px;
            }
        }

        @media (max-width: 600px) {
            .page-heading h1 {
                font-size: 21px;
            }

            .card {
                padding: 17px;
            }

            .header-admin-info,
            .header-title p {
                display: none;
            }

            .comments-panel {
                width: min(420px, 75vw);
            }
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar" id="sidebar">

    <a href="dashboard.php" class="logo">
        <span class="logo-icon">
            <i class="fa-solid fa-bus"></i>
        </span>

        <span>
            Burundi Transport
            <small>ESPACE ADMINISTRATION</small>
        </span>
    </a>

    <div class="menu-label">Menu principal</div>

    <nav class="menu">

        <a href="dashboard.php">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <a href="trajets.php">
            <i class="fa-solid fa-route"></i>
            <span>Trajets</span>
        </a>

        <a href="reservations.php">
            <i class="fa-solid fa-ticket"></i>
            <span>Réservations</span>
        </a>

        <a href="utilisateurs.php">
            <i class="fa-solid fa-users"></i>
            <span>Utilisateurs</span>
        </a>

        <a href="statistiques.php">
            <i class="fa-solid fa-chart-column"></i>
            <span>Statistiques</span>
        </a>

        <a href="notifications.php">
            <i class="fa-solid fa-bell"></i>
            <span>Notifications</span>
        </a>

        <a href="activites.php">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Activités</span>
        </a>

        <a href="documents.php">
            <i class="fa-solid fa-folder"></i>
            <span>Documents</span>
        </a>

        <a href="partages-documents.php">
            <i class="fa-solid fa-share-nodes"></i>
            <span>Partager un document</span>
        </a>

        <a href="documents-partages.php" class="active">
            <i class="fa-solid fa-folder-open"></i>
            <span>Documents partagés</span>
        </a>

        <a href="profil.php">
            <i class="fa-solid fa-user"></i>
            <span>Mon profil</span>
        </a>

        <a href="parametres.php">
            <i class="fa-solid fa-gear"></i>
            <span>Paramètres</span>
        </a>

    </nav>

    <div class="sidebar-bottom">

        <div class="admin-mini">
            <div class="avatar">
                <?= h(strtoupper(substr($nomAffiche, 0, 1))) ?>
            </div>

            <div class="admin-mini-info">
                <strong><?= h($nomAffiche) ?></strong>
                <small><?= h($emailAffiche) ?></small>
            </div>
        </div>

        <a href="../logout.php" class="logout-button">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Déconnexion</span>
        </a>

    </div>

</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- CONTENU PRINCIPAL -->

<div class="main-content">

    <header class="top-header">

        <div class="header-left">

            <button
                type="button"
                class="menu-button"
                id="menuButton"
                aria-label="Ouvrir ou fermer le menu"
                aria-controls="sidebar"
                aria-expanded="false"
            >
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="header-title">
                <h2>Documents partagés</h2>
                <p>Documents reçus et espace de commentaires</p>
            </div>

        </div>

        <div class="header-admin">
            <div class="avatar">
                <?= h(strtoupper(substr($nomAffiche, 0, 1))) ?>
            </div>

            <div class="header-admin-info">
                <strong><?= h($nomAffiche) ?></strong>
                <small>Administrateur</small>
            </div>
        </div>

    </header>

    <main class="page-content">

        <div class="page-heading">

            <div>
                <h1>Mes documents partagés</h1>
                <p>
                    Consultez les fichiers reçus, téléchargez-les et échangez
                    des commentaires avec les administrateurs autorisés.
                </p>
            </div>

            <div class="heading-icon">
                <i class="fa-solid fa-folder-open"></i>
            </div>

        </div>

        <?php if ($message !== ""): ?>
            <div
                class="alert <?= $messageType === "error" ? "error" : "success" ?>"
                role="status"
            >
                <i class="fa-solid <?= $messageType === "error"
                    ? "fa-circle-exclamation"
                    : "fa-circle-check" ?>"></i>
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <!-- RÉSUMÉ -->

        <section class="summary">

            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fa-solid fa-folder"></i>
                </div>

                <div>
                    <small>Documents reçus</small>
                    <strong><?= count($documentsPartages) ?></strong>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fa-solid fa-users"></i>
                </div>

                <div>
                    <small>Expéditeurs</small>
                    <strong><?= count($expediteurs) ?></strong>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fa-solid fa-comments"></i>
                </div>

                <div>
                    <small>Commentaires</small>
                    <strong>
                        <?php
                        $totalCommentaires = 0;

                        foreach ($documentsPartages as $doc) {
                            $totalCommentaires += $doc["nombre_commentaires"];
                        }

                        echo $totalCommentaires;
                        ?>
                    </strong>
                </div>
            </div>

        </section>

        <!-- LISTE DES DOCUMENTS -->

        <section class="card">

            <div class="card-heading">

                <div class="card-heading-icon">
                    <i class="fa-solid fa-file-lines"></i>
                </div>

                <div>
                    <h3>Liste des documents reçus</h3>
                    <p>
                        Chaque document dispose de son propre espace
                        de commentaires.
                    </p>
                </div>

            </div>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Partagé par</th>
                            <th>Date de réception</th>
                            <th>Taille</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($documentsPartages)): ?>

                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-regular fa-folder-open"></i>

                                    <h4>Aucun document partagé</h4>

                                    <p>
                                        Vous n'avez pas encore reçu de fichier.
                                        Lorsqu'un autre administrateur vous
                                        partagera un document, il apparaîtra ici.
                                    </p>
                                </div>
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($documentsPartages as $doc): ?>

                            <?php
                            $extension = strtolower(
                                (string) $doc["extension"]
                            );

                            $icone = "fa-file-lines";

                            if ($extension === "pdf") {
                                $icone = "fa-file-pdf";
                            } elseif (in_array(
                                $extension,
                                ["doc", "docx"],
                                true
                            )) {
                                $icone = "fa-file-word";
                            } elseif (in_array(
                                $extension,
                                ["xls", "xlsx"],
                                true
                            )) {
                                $icone = "fa-file-excel";
                            } elseif (in_array(
                                $extension,
                                ["jpg", "jpeg", "png"],
                                true
                            )) {
                                $icone = "fa-file-image";
                            }

                            $documentIdHtml = h($doc["document_id"]);
                            ?>

                            <tr>

                                <td>
                                    <div class="document-name">

                                        <div class="file-icon">
                                            <i class="fa-solid <?= h($icone) ?>"></i>
                                        </div>

                                        <div>
                                            <?= h($doc["nom_document"]) ?>

                                            <small>
                                                <?= h(strtoupper(
                                                    $extension ?: "FICHIER"
                                                )) ?>
                                            </small>
                                        </div>

                                    </div>
                                </td>

                                <td>
                                    <?= h(
                                        $doc["expediteur_prenom"]
                                        . " "
                                        . $doc["expediteur_nom"]
                                    ) ?>

                                    <span class="sender-email">
                                        <?= h($doc["expediteur_email"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= h($doc["date_partage"]) ?>
                                </td>

                                <td>
                                    <?= h(formaterTaille($doc["taille_fichier"])) ?>
                                </td>

                                <td>

                                    <a
                                        class="download-button"
                                        href="documents-partages.php?telecharger=<?= rawurlencode($doc["document_id"]) ?>"
                                    >
                                        <i class="fa-solid fa-download"></i>
                                        Télécharger
                                    </a>

                                    <!-- COMMENTAIRES DU DOCUMENT -->

                                    <details
                                        class="comments-details"
                                        id="commentaires-<?= $documentIdHtml ?>"
                                    >

                                        <summary>
                                            <i class="fa-solid fa-comments"></i>

                                            Commentaires
                                            (<?= (int) $doc["nombre_commentaires"] ?>)
                                        </summary>

                                        <div class="comments-panel">

                                            <h4 class="comments-title">
                                                Discussion :
                                                <?= h($doc["nom_document"]) ?>
                                            </h4>

                                            <?php if (
                                                empty($doc["commentaires"])
                                            ): ?>

                                                <div class="no-comments">
                                                    <i class="fa-regular fa-comment-dots"></i>
                                                    <br>
                                                    Aucun commentaire pour le moment.
                                                    <br>
                                                    Sois le premier à écrire un message.
                                                </div>

                                            <?php else: ?>

                                                <div class="comment-list">

                                                    <?php foreach (
                                                        $doc["commentaires"] as $commentaire
                                                    ): ?>

                                                        <?php
                                                        $nomAuteur = trim(
                                                            $commentaire["prenom"]
                                                            . " "
                                                            . $commentaire["nom"]
                                                        );
                                                        ?>

                                                        <article class="comment-item">

                                                            <div class="comment-author">

                                                                <div class="comment-avatar">
                                                                    <?= h(
                                                                        strtoupper(
                                                                            substr(
                                                                                $nomAuteur,
                                                                                0,
                                                                                1
                                                                            )
                                                                        )
                                                                    ) ?>
                                                                </div>

                                                                <div>
                                                                    <strong>
                                                                        <?= h($nomAuteur) ?>

                                                                        <?php if (
                                                                            $commentaire["user_id"]
                                                                            === $adminId
                                                                        ): ?>
                                                                            (Vous)
                                                                        <?php endif; ?>
                                                                    </strong>

                                                                    <span class="comment-date">
                                                                        <?= h(
                                                                            $commentaire["date_creation"]
                                                                        ) ?>

                                                                        <?php if (
                                                                            !empty(
                                                                                $commentaire["date_modification"]
                                                                            )
                                                                        ): ?>
                                                                            · Modifié
                                                                        <?php endif; ?>
                                                                    </span>
                                                                </div>

                                                            </div>

                                                            <div class="comment-content"><?= h(
                                                                $commentaire["contenu"]
                                                            ) ?></div>

                                                        </article>

                                                    <?php endforeach; ?>

                                                </div>

                                            <?php endif; ?>

                                            <!-- FORMULAIRE D'AJOUT -->

                                            <form
                                                method="POST"
                                                action="documents-partages.php#commentaires-<?= $documentIdHtml ?>"
                                                class="comment-form"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="ajouter_commentaire"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="document_id"
                                                    value="<?= $documentIdHtml ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= h($csrfToken) ?>"
                                                >

                                                <label for="commentaire-<?= $documentIdHtml ?>">
                                                    Écrire un commentaire
                                                </label>

                                                <textarea
                                                    id="commentaire-<?= $documentIdHtml ?>"
                                                    name="contenu"
                                                    maxlength="5000"
                                                    required
                                                    placeholder="Écris ton commentaire ici..."
                                                ></textarea>

                                                <div class="comment-form-footer">

                                                    <span class="character-count">
                                                        5 000 caractères maximum
                                                    </span>

                                                    <button
                                                        type="submit"
                                                        class="comment-submit"
                                                    >
                                                        <i class="fa-solid fa-paper-plane"></i>
                                                        Envoyer
                                                    </button>

                                                </div>

                                            </form>

                                        </div>

                                    </details>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<script src="../notification-badge.js"></script>
<script src="../theme.js"></script>

<script>
    const sidebar = document.getElementById("sidebar");
    const menuButton = document.getElementById("menuButton");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    function ouvrirMenu() {
        sidebar.classList.add("open");
        sidebarOverlay.classList.add("show");
        menuButton.setAttribute("aria-expanded", "true");
    }

    function fermerMenu() {
        sidebar.classList.remove("open");
        sidebarOverlay.classList.remove("show");
        menuButton.setAttribute("aria-expanded", "false");
    }

    menuButton.addEventListener("click", function () {
        if (sidebar.classList.contains("open")) {
            fermerMenu();
        } else {
            ouvrirMenu();
        }
    });

    sidebarOverlay.addEventListener("click", fermerMenu);

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            fermerMenu();
        }
    });

    window.addEventListener("resize", function () {
        if (window.innerWidth > 900) {
            fermerMenu();
        }
    });

    /*
     * Après l'envoi d'un commentaire, ouvrir automatiquement
     * la discussion du document concerné.
     */
    if (window.location.hash.startsWith("#commentaires-")) {
        const details = document.querySelector(window.location.hash);

        if (details && details.tagName.toLowerCase() === "details") {
            details.open = true;
        }
    }
</script>

</body>
</html>
