
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$adminId = $_SESSION["user_id"] ?? "";
$message = "";
$typeMessage = "";

if (empty($_SESSION["csrf_partages"])) {
    $_SESSION["csrf_partages"] = bin2hex(random_bytes(32));
}

function h($valeur): string
{
    return htmlspecialchars((string)$valeur, ENT_QUOTES, "UTF-8");
}

function creerId(string $prefixe): string
{
    return $prefixe . bin2hex(random_bytes(8));
}

/* =========================
   INFORMATIONS ADMIN
========================= */

$adminConnecte = [
    "nom" => "Administrateur",
    "prenom" => "",
    "email" => ""
];

try {
    $stmt = $pdo->prepare("
        SELECT nom, prenom, email
        FROM users
        WHERE user_id = :id
        LIMIT 1
    ");
    $stmt->execute(["id" => $adminId]);
    $resultatAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultatAdmin) {
        $adminConnecte = $resultatAdmin;
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
}

/* =========================
   CHARGER LES DESTINATAIRES
========================= */

try {
    $stmt = $pdo->prepare("
        SELECT user_id, nom, prenom, email
        FROM users
        WHERE role_id = 'ROLE-001'
          AND statut = 'actif'
          AND user_id <> :admin_id
        ORDER BY nom, prenom
    ");
    $stmt->execute(["admin_id" => $adminId]);
    $administrateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $administrateurs = [];
    $message = "Impossible de charger les administrateurs.";
    $typeMessage = "erreur";
}

/* =========================
   CHARGER LES DOCUMENTS
========================= */

try {
    $stmt = $pdo->query("
        SELECT document_id, nom_document, extension, taille_fichier
        FROM documents
        WHERE statut = 'actif'
        ORDER BY nom_document
    ");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $documents = [];
}

/* =========================
   TRAITEMENT DU FORMULAIRE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fichierCree = null;

    try {
        $jeton = $_POST["csrf_token"] ?? "";

        if (
            !is_string($jeton) ||
            !hash_equals($_SESSION["csrf_partages"], $jeton)
        ) {
            throw new Exception(
                "Le formulaire a expiré. Rechargez la page."
            );
        }

        $destinataireId = trim(
            (string)($_POST["destinataire"] ?? "")
        );

        if (
            $destinataireId === "" ||
            $destinataireId === $adminId
        ) {
            throw new Exception(
                "Choisissez un autre administrateur."
            );
        }

        // Vérifier le destinataire côté serveur.
        $stmt = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE user_id = :id
              AND role_id = 'ROLE-001'
              AND statut = 'actif'
            LIMIT 1
        ");
        $stmt->execute(["id" => $destinataireId]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception(
                "Le destinataire doit être un administrateur actif."
            );
        }

        $mode = $_POST["mode_partage"] ?? "";
        $documentId = "";

        /* ---------------------------------
           PARTAGE D'UN DOCUMENT EXISTANT
        --------------------------------- */

        if ($mode === "existant") {

            $documentId = trim(
                (string)($_POST["document_id"] ?? "")
            );

            $stmt = $pdo->prepare("
                SELECT document_id
                FROM documents
                WHERE document_id = :id
                  AND statut = 'actif'
                LIMIT 1
            ");
            $stmt->execute(["id" => $documentId]);

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception(
                    "Le document sélectionné n'est pas disponible."
                );
            }

        /* ---------------------------------
           PARTAGE DIRECT DEPUIS L'ORDINATEUR
        --------------------------------- */

        } elseif ($mode === "direct") {

            if (
                !isset($_FILES["fichier_direct"]) ||
                !is_array($_FILES["fichier_direct"])
            ) {
                throw new Exception(
                    "Sélectionnez un fichier à partager."
                );
            }

            $fichier = $_FILES["fichier_direct"];

            if ($fichier["error"] !== UPLOAD_ERR_OK) {
                throw new Exception(
                    "L'envoi du fichier a échoué. Vérifiez sa taille et réessayez."
                );
            }

            if (!is_uploaded_file($fichier["tmp_name"])) {
                throw new Exception("Le fichier envoyé est invalide.");
            }

            // Limite applicative : 5 Mo.
            $tailleMax = 5 * 1024 * 1024;

            if (
                $fichier["size"] <= 0 ||
                $fichier["size"] > $tailleMax
            ) {
                throw new Exception(
                    "Le fichier doit peser entre 1 octet et 5 Mo."
                );
            }

            $nomOriginal = basename(
                str_replace(
                    "\\",
                    "/",
                    (string)$fichier["name"]
                )
            );

            $extension = strtolower(
                pathinfo($nomOriginal, PATHINFO_EXTENSION)
            );

            $extensionsAutorisees = [
                "pdf", "doc", "docx",
                "xls", "xlsx",
                "jpg", "jpeg", "png"
            ];

            if (!in_array(
                $extension,
                $extensionsAutorisees,
                true
            )) {
                throw new Exception(
                    "Formats acceptés : PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG et PNG."
                );
            }

            // Détecter le type MIME réel.
            $typeMime = "application/octet-stream";

            if (function_exists("finfo_open")) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                if ($finfo) {
                    $typeMime = finfo_file(
                        $finfo,
                        $fichier["tmp_name"]
                    ) ?: $typeMime;

                    finfo_close($finfo);
                }
            }

            $typesMimeAutorises = [
                "pdf" => ["application/pdf"],
                "doc" => [
                    "application/msword",
                    "application/octet-stream"
                ],
                "docx" => [
                    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    "application/zip",
                    "application/octet-stream"
                ],
                "xls" => [
                    "application/vnd.ms-excel",
                    "application/octet-stream"
                ],
                "xlsx" => [
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "application/zip",
                    "application/octet-stream"
                ],
                "jpg" => ["image/jpeg"],
                "jpeg" => ["image/jpeg"],
                "png" => ["image/png"]
            ];

            if (
                isset($typesMimeAutorises[$extension]) &&
                !in_array(
                    $typeMime,
                    $typesMimeAutorises[$extension],
                    true
                )
            ) {
                throw new Exception(
                    "Le contenu du fichier ne correspond pas à son extension."
                );
            }

            /*
             * Stockage dans le dossier du projet.
             * Le dossier doit être protégé contre l'accès HTTP direct.
             */

            $dossier = dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . "stockage_documents"
                . DIRECTORY_SEPARATOR
                . "partages_directs";

            if (
                !is_dir($dossier) &&
                !mkdir($dossier, 0700, true)
            ) {
                throw new Exception(
                    "Impossible de créer le dossier de stockage."
                );
            }

            // Empêcher l'accès HTTP direct sur Apache.
            $fichierHtaccess = $dossier
                . DIRECTORY_SEPARATOR
                . ".htaccess";

            if (!is_file($fichierHtaccess)) {
                if (
                    file_put_contents(
                        $fichierHtaccess,
                        "Require all denied\n",
                        LOCK_EX
                    ) === false
                ) {
                    throw new Exception(
                        "Impossible de sécuriser le dossier de stockage."
                    );
                }
            }

            $nomStocke = bin2hex(random_bytes(16))
                . "."
                . $extension;

            $cheminAbsolu = $dossier
                . DIRECTORY_SEPARATOR
                . $nomStocke;

            if (!move_uploaded_file(
                $fichier["tmp_name"],
                $cheminAbsolu
            )) {
                throw new Exception(
                    "Impossible d'enregistrer le fichier sur le serveur."
                );
            }

            $fichierCree = $cheminAbsolu;
            $documentId = creerId("DOC-");

            /*
             * Enregistrer le fichier dans documents pour que
             * le système existant puisse référencer ce document.
             */

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO documents (
                    document_id,
                    user_id,
                    nom_document,
                    nom_fichier,
                    chemin_fichier,
                    type_document,
                    extension,
                    taille_fichier,
                    description,
                    statut
                ) VALUES (
                    :document_id,
                    :user_id,
                    :nom_document,
                    :nom_fichier,
                    :chemin_fichier,
                    :type_document,
                    :extension,
                    :taille_fichier,
                    :description,
                    'actif'
                )
            ");

            $stmt->execute([
                "document_id" => $documentId,
                "user_id" => $adminId,
                "nom_document" => $nomOriginal,
                "nom_fichier" => $nomStocke,
                "chemin_fichier" => $cheminAbsolu,
                "type_document" => $typeMime,
                "extension" => $extension,
                "taille_fichier" => $fichier["size"],
                "description" => "Fichier ajouté lors d'un partage direct"
            ]);

        } else {
            throw new Exception("Mode de partage inconnu.");
        }

        /* ---------------------------------
           ÉVITER UN DOUBLON ACTIF
        --------------------------------- */

        $stmt = $pdo->prepare("
            SELECT partage_id
            FROM partages_documents
            WHERE document_id = :document_id
              AND partage_par = :expediteur
              AND partage_avec = :destinataire
              AND statut = 'actif'
            LIMIT 1
        ");

        $stmt->execute([
            "document_id" => $documentId,
            "expediteur" => $adminId,
            "destinataire" => $destinataireId
        ]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception(
                "Ce document est déjà partagé avec cet administrateur."
            );
        }

        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }

        $stmt = $pdo->prepare("
            INSERT INTO partages_documents (
                partage_id,
                document_id,
                partage_par,
                partage_avec,
                statut
            ) VALUES (
                :partage_id,
                :document_id,
                :expediteur,
                :destinataire,
                'actif'
            )
        ");

        $stmt->execute([
            "partage_id" => creerId("PAR-"),
            "document_id" => $documentId,
            "expediteur" => $adminId,
            "destinataire" => $destinataireId
        ]);

        $pdo->commit();

        $message = "Le fichier a été partagé avec succès.";
        $typeMessage = "succes";

        $_SESSION["csrf_partages"] = bin2hex(random_bytes(32));

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if (
            $fichierCree !== null &&
            is_file($fichierCree)
        ) {
            unlink($fichierCree);
        }

        error_log($e->getMessage());

        $message = $e instanceof Exception
            ? $e->getMessage()
            : "Une erreur technique est survenue.";

        $typeMessage = "erreur";
    }
}

/* =========================
   HISTORIQUE DES PARTAGES
========================= */

try {
    $stmt = $pdo->prepare("
        SELECT
            p.partage_id,
            p.date_partage,
            p.statut,
            d.nom_document,
            u.nom AS destinataire_nom,
            u.prenom AS destinataire_prenom,
            u.email AS destinataire_email
        FROM partages_documents p
        INNER JOIN documents d
            ON d.document_id = p.document_id
        INNER JOIN users u
            ON u.user_id = p.partage_avec
        WHERE p.partage_par = :admin_id
        ORDER BY p.date_partage DESC
    ");

    $stmt->execute(["admin_id" => $adminId]);
    $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log($e->getMessage());
    $historique = [];
}

/* =========================
   LIENS DE NAVIGATION
========================= */

$nomAffiche = trim(
    ($adminConnecte["prenom"] ?? "") . " " .
    ($adminConnecte["nom"] ?? "")
);

if ($nomAffiche === "") {
    $nomAffiche = "Administrateur";
}

$emailAffiche = $adminConnecte["email"] ?? "";

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Partage des documents | Burundi Transport</title>

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
            inset: 0 auto 0 0;
            width: 250px;
            height: 100vh;
            padding: 22px 15px;
            background: linear-gradient(
                180deg,
                #173b57 0%,
                #102d43 100%
            );
            color: white;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            overflow-y: auto;
            transition: transform .25s ease;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px 8px 25px;
            color: white;
            text-decoration: none;
            font-size: 18px;
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
        }

        .logo small {
            display: block;
            margin-top: 3px;
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
            min-height: 44px;
            padding: 11px 13px;
            border-radius: 8px;
            color: #d9e5ed;
            text-decoration: none;
            font-size: 13px;
            transition: background .2s, color .2s;
        }

        .menu a i {
            width: 18px;
            text-align: center;
            font-size: 15px;
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
            border-top: 1px solid rgba(255,255,255,.13);
        }

        .admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #e8b04b;
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
            color: #b9ccda;
            margin-top: 4px;
            font-size: 10px;
        }

        .logout-button {
            display: flex;
            gap: 10px;
            align-items: center;
            width: 100%;
            padding: 11px 13px;
            border-radius: 8px;
            background: transparent;
            color: #f3c4c4;
            text-decoration: none;
            font-size: 13px;
        }

        .logout-button:hover {
            background: rgba(255,255,255,.08);
        }

        /* CONTENU PRINCIPAL */

        .main-content {
            min-height: 100vh;
            margin-left: 250px;
            transition: margin-left .25s ease;
        }

        .top-header {
            position: sticky;
            top: 0;
            z-index: 500;
            min-height: 76px;
            padding: 14px 28px;
            background: rgba(255,255,255,.96);
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
            width: 40px;
            height: 40px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: white;
            color: var(--primary-dark);
            cursor: pointer;
            display: none;
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

        .header-admin .admin-avatar {
            width: 40px;
            height: 40px;
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
            max-width: 1400px;
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
            flex-shrink: 0;
            display: grid;
            place-items: center;
            background: #e3f0f8;
            color: var(--primary);
            border-radius: 14px;
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
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 11px;
            color: var(--primary);
            background: #eaf3f9;
            font-size: 17px;
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 19px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            font-size: 13px;
            font-weight: 700;
        }

        input[type="file"],
        select {
            width: 100%;
            min-height: 44px;
            padding: 11px 12px;
            border: 1px solid #ccd8e1;
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            font: inherit;
            font-size: 13px;
        }

        input[type="file"]:focus,
        select:focus {
            outline: 2px solid #c4e1f4;
            border-color: var(--primary);
        }

        .help {
            margin: 0;
            color: var(--text-muted);
            font-size: 11px;
            line-height: 1.6;
        }

        .primary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 43px;
            padding: 11px 17px;
            border: none;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s;
        }

        .primary-button:hover {
            background: var(--primary-dark);
        }

        .message {
            padding: 14px 16px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-size: 13px;
        }

        .message.succes {
            background: #e7f7ec;
            border: 1px solid #bce7c9;
            color: #176534;
        }

        .message.erreur {
            background: #fff0f0;
            border: 1px solid #f1c5c5;
            color: #9b2222;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #e8edf2;
            text-align: left;
            font-size: 12px;
        }

        th {
            background: #f6f9fb;
            color: var(--primary-dark);
            font-weight: 700;
        }

        td {
            color: #394b59;
        }

        tbody tr:hover {
            background: #fafcfd;
        }

        .document-name {
            display: flex;
            align-items: center;
            gap: 9px;
            font-weight: 700;
            color: var(--text);
        }

        .document-name i {
            color: var(--primary);
            font-size: 17px;
        }

        .recipient-email {
            display: block;
            margin-top: 5px;
            color: var(--text-muted);
            font-size: 11px;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #e6f6eb;
            color: #176534;
            font-size: 11px;
            font-weight: 700;
        }

        .status.revoked {
            background: #fdeaea;
            color: #9b2222;
        }

        .empty-state {
            padding: 28px 15px;
            color: var(--text-muted);
            text-align: center;
        }

        .empty-state i {
            display: block;
            margin-bottom: 12px;
            color: #9bb5c8;
            font-size: 28px;
        }

        .sidebar-overlay {
            display: none;
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
            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }

            .page-heading h1 {
                font-size: 21px;
            }

            .card {
                padding: 17px;
            }

            .header-admin-info {
                display: none;
            }

            .header-title p {
                display: none;
            }
        }
    </style>
</head>

<body>

<!-- MENU LATÉRAL -->

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
		
		 <a href="chat.php" class="menu-link">
                   <i class="fa-solid fa-comments"></i>
                    <span>Message</span>
                </a>
				
				 <a href="historique-connexions.php" class="menu-link">
                   <i class="fa-solid fa-shield-halved"></i>
                    <span>Historique connexion</span>
                </a>

        <a href="documents.php">
            <i class="fa-solid fa-folder"></i>
            <span>Documents</span>
        </a>

        <a href="partages-documents.php" class="active">
            <i class="fa-solid fa-share-nodes"></i>
            <span>Partage des documents</span>
        </a>

        <a href="documents-partages.php">
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
		
		<a href="archives.php" class="menu-link">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Archives</span>
                </a>

    </nav>

    <div class="sidebar-bottom">

        <div class="admin-mini">
            <div class="admin-avatar">
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
                <h2>Partage des documents</h2>
                <p>Gestion des fichiers partagés entre administrateurs</p>
            </div>

        </div>

        <div class="header-admin">

            <div class="admin-avatar">
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
                <h1>Partager un fichier</h1>
                <p>
                    Envoyez un document existant ou choisissez un fichier
                    directement depuis votre ordinateur.
                </p>
            </div>

            <div class="heading-icon">
                <i class="fa-solid fa-share-nodes"></i>
            </div>

        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?= h($typeMessage) ?>">
                <i class="fa-solid <?= $typeMessage === "succes"
                    ? "fa-circle-check"
                    : "fa-circle-exclamation" ?>"></i>
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <!-- PARTAGE D'UN DOCUMENT EXISTANT -->

        <section class="card">

            <div class="card-heading">

                <div class="card-heading-icon">
                    <i class="fa-solid fa-folder-open"></i>
                </div>

                <div>
                    <h3>Document déjà enregistré</h3>
                    <p>Sélectionnez un document présent dans la gestion documentaire.</p>
                </div>

            </div>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h($_SESSION["csrf_partages"]) ?>"
                >

                <input
                    type="hidden"
                    name="mode_partage"
                    value="existant"
                >

                <div class="form-grid">

                    <div class="field">
                        <label for="document_id">Document à partager</label>

                        <select
                            name="document_id"
                            id="document_id"
                            required
                        >
                            <option value="">-- Choisir un document --</option>

                            <?php foreach ($documents as $document): ?>
                                <option value="<?= h($document["document_id"]) ?>">
                                    <?= h($document["nom_document"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if (empty($documents)): ?>
                            <p class="help">
                                Aucun document actif n'est disponible.
                                Vous pouvez utiliser le formulaire ci-dessous.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="destinataire_existant">
                            Administrateur destinataire
                        </label>

                        <select
                            name="destinataire"
                            id="destinataire_existant"
                            required
                        >
                            <option value="">-- Choisir un administrateur --</option>

                            <?php foreach ($administrateurs as $admin): ?>
                                <option value="<?= h($admin["user_id"]) ?>">
                                    <?= h($admin["prenom"] . " " . $admin["nom"]) ?>
                                    — <?= h($admin["email"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field full">
                        <button type="submit" class="primary-button">
                            <i class="fa-solid fa-share"></i>
                            Partager le document
                        </button>
                    </div>

                </div>

            </form>

        </section>

        <!-- PARTAGE DIRECT DEPUIS L'ORDINATEUR -->

        <section class="card">

            <div class="card-heading">

                <div class="card-heading-icon">
                    <i class="fa-solid fa-computer"></i>
                </div>

                <div>
                    <h3>Choisir un fichier depuis mon ordinateur</h3>
                    <p>
                        Le fichier sera envoyé au serveur et associé au partage,
                        sans passer par le formulaire habituel de téléversement.
                    </p>
                </div>

            </div>

            <form method="POST" enctype="multipart/form-data">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h($_SESSION["csrf_partages"]) ?>"
                >

                <input
                    type="hidden"
                    name="mode_partage"
                    value="direct"
                >

                <div class="form-grid">

                    <div class="field">
                        <label for="fichier_direct">Fichier à envoyer</label>

                        <input
                            type="file"
                            id="fichier_direct"
                            name="fichier_direct"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                            required
                        >

                        <p class="help">
                            Formats : PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG.
                            Taille maximale : 5 Mo.
                        </p>
                    </div>

                    <div class="field">
                        <label for="destinataire_direct">
                            Administrateur destinataire
                        </label>

                        <select
                            name="destinataire"
                            id="destinataire_direct"
                            required
                        >
                            <option value="">-- Choisir un administrateur --</option>

                            <?php foreach ($administrateurs as $admin): ?>
                                <option value="<?= h($admin["user_id"]) ?>">
                                    <?= h($admin["prenom"] . " " . $admin["nom"]) ?>
                                    — <?= h($admin["email"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field full">
                        <button type="submit" class="primary-button">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            Envoyer et partager le fichier
                        </button>
                    </div>

                </div>

            </form>

        </section>

        <!-- HISTORIQUE -->

        <section class="card">

            <div class="card-heading">

                <div class="card-heading-icon">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>

                <div>
                    <h3>Historique de mes partages</h3>
                    <p>Retrouvez les fichiers que vous avez partagés.</p>
                </div>

            </div>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Destinataire</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($historique)): ?>

                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="fa-solid fa-folder-open"></i>
                                    Aucun partage envoyé pour le moment.
                                </div>
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($historique as $partage): ?>

                            <tr>

                                <td>
                                    <div class="document-name">
                                        <i class="fa-solid fa-file-lines"></i>
                                        <span><?= h($partage["nom_document"]) ?></span>
                                    </div>
                                </td>

                                <td>
                                    <?= h(
                                        $partage["destinataire_prenom"]
                                        . " "
                                        . $partage["destinataire_nom"]
                                    ) ?>

                                    <span class="recipient-email">
                                        <?= h($partage["destinataire_email"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= h($partage["date_partage"]) ?>
                                </td>

                                <td>
                                    <?php if ($partage["statut"] === "actif"): ?>
                                        <span class="status">Actif</span>
                                    <?php else: ?>
                                        <span class="status revoked">Révoqué</span>
                                    <?php endif; ?>
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
</script>

</body>
</html>
