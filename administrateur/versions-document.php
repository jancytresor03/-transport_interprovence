

<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");
require_once __DIR__ . "/../database.php";

/* =========================
   FONCTIONS
========================= */

function echapperVersion($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, "UTF-8");
}

function tailleLisibleVersion($taille): string
{
    $taille = (int) $taille;

    if ($taille >= 1048576) {
        return number_format($taille / 1048576, 2) . " Mo";
    }

    if ($taille >= 1024) {
        return number_format($taille / 1024, 2) . " Ko";
    }

    return $taille . " octets";
}

/* =========================
   VARIABLES
========================= */

$documentId = trim($_GET["id"] ?? "");
$document = null;
$versions = [];
$message = "";
$succes = false;

/* =========================
   CHARGER LE DOCUMENT
========================= */

try {
    if ($documentId === "") {
        throw new RuntimeException(
            "Identifiant du document manquant."
        );
    }

    $requete = $pdo->prepare("
        SELECT
            document_id,
            nom_document,
            nom_fichier,
            description,
            extension,
            taille_fichier,
            date_creation
        FROM documents
        WHERE document_id = :id
          AND statut = 'actif'
        LIMIT 1
    ");

    $requete->execute([
        ":id" => $documentId
    ]);

    $document = $requete->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        throw new RuntimeException(
            "Document introuvable ou indisponible."
        );
    }

} catch (Throwable $e) {
    error_log("Chargement document : " . $e->getMessage());

    $message = $e instanceof PDOException
        ? "Impossible de charger le document."
        : $e->getMessage();
}

/* =========================
   AJOUTER UNE NOUVELLE VERSION
========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["ajouter_version"])
    && $document !== null
) {
    $cheminFichier = null;

    try {
        if (
            !isset($_FILES["fichier_version"]) ||
            !is_array($_FILES["fichier_version"])
        ) {
            throw new RuntimeException(
                "Veuillez sélectionner un fichier."
            );
        }

        $fichier = $_FILES["fichier_version"];

        if ($fichier["error"] !== UPLOAD_ERR_OK) {
            $erreursUpload = [
                UPLOAD_ERR_INI_SIZE =>
                    "Le fichier dépasse la limite autorisée par PHP.",
                UPLOAD_ERR_FORM_SIZE =>
                    "Le fichier dépasse la taille autorisée.",
                UPLOAD_ERR_PARTIAL =>
                    "Le fichier a été envoyé partiellement.",
                UPLOAD_ERR_NO_FILE =>
                    "Veuillez sélectionner un fichier.",
                UPLOAD_ERR_NO_TMP_DIR =>
                    "Le dossier temporaire est introuvable.",
                UPLOAD_ERR_CANT_WRITE =>
                    "Impossible d'écrire le fichier sur le disque.",
                UPLOAD_ERR_EXTENSION =>
                    "Une extension PHP a interrompu l'envoi."
            ];

            throw new RuntimeException(
                $erreursUpload[$fichier["error"]]
                    ?? "Erreur lors de l'envoi du fichier."
            );
        }

        if (
            !is_uploaded_file($fichier["tmp_name"]) ||
            $fichier["size"] <= 0
        ) {
            throw new RuntimeException(
                "Le fichier envoyé est vide ou invalide."
            );
        }

        if ($fichier["size"] > 5 * 1024 * 1024) {
            throw new RuntimeException(
                "Le fichier ne doit pas dépasser 5 Mo."
            );
        }

        $nomOriginal = basename($fichier["name"]);
        $extension = strtolower(
            pathinfo($nomOriginal, PATHINFO_EXTENSION)
        );

        $typesAutorises = [
            "pdf" => ["application/pdf"],
            "doc" => [
                "application/msword",
                "application/x-ole-storage"
            ],
            "docx" => [
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "application/zip"
            ],
            "xls" => [
                "application/vnd.ms-excel",
                "application/x-ole-storage"
            ],
            "xlsx" => [
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                "application/zip"
            ],
            "jpg" => ["image/jpeg"],
            "jpeg" => ["image/jpeg"],
            "png" => ["image/png"]
        ];

        if (!isset($typesAutorises[$extension])) {
            throw new RuntimeException(
                "Format non autorisé. Utilisez PDF, Word, Excel ou une image."
            );
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($fichier["tmp_name"]);

        if (!in_array($mime, $typesAutorises[$extension], true)) {
            throw new RuntimeException(
                "Le type réel du fichier ne correspond pas à son extension."
            );
        }

        $dossier = dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . "stockage_documents";

        if (
            !is_dir($dossier) &&
            !mkdir($dossier, 0755, true) &&
            !is_dir($dossier)
        ) {
            throw new RuntimeException(
                "Impossible de créer le dossier de stockage."
            );
        }

        // Nom physique unique pour ne jamais remplacer un ancien fichier.
        $nomStockage = bin2hex(random_bytes(16)) . "." . $extension;
        $cheminFichier = $dossier . DIRECTORY_SEPARATOR . $nomStockage;

        if (!move_uploaded_file(
            $fichier["tmp_name"],
            $cheminFichier
        )) {
            throw new RuntimeException(
                "Impossible d'enregistrer le fichier envoyé."
            );
        }

        $pdo->beginTransaction();

        // Verrouiller le document pendant le calcul du numéro.
        $verrou = $pdo->prepare("
            SELECT document_id
            FROM documents
            WHERE document_id = :id
              AND statut = 'actif'
            FOR UPDATE
        ");

        $verrou->execute([
            ":id" => $documentId
        ]);

        if (!$verrou->fetchColumn()) {
            throw new RuntimeException(
                "Le document n'existe plus ou n'est plus actif."
            );
        }

        // Le prochain numéro est calculé automatiquement.
        $requeteNumero = $pdo->prepare("
            SELECT COALESCE(MAX(numero_version), 0) + 1
            FROM document_versions
            WHERE document_id = :id
        ");

        $requeteNumero->execute([
            ":id" => $documentId
        ]);

        $numeroVersion = (int) $requeteNumero->fetchColumn();

        $versionId = bin2hex(random_bytes(10));
        $commentaire = trim($_POST["commentaire"] ?? "");

        $insertion = $pdo->prepare("
            INSERT INTO document_versions (
                version_id,
                document_id,
                numero_version,
                nom_fichier,
                chemin_fichier,
                taille_fichier,
                commentaire,
                user_id
            ) VALUES (
                :version_id,
                :document_id,
                :numero_version,
                :nom_fichier,
                :chemin_fichier,
                :taille_fichier,
                :commentaire,
                :user_id
            )
        ");

        $insertion->execute([
            ":version_id" => $versionId,
            ":document_id" => $documentId,
            ":numero_version" => $numeroVersion,
            ":nom_fichier" => $nomOriginal,
            ":chemin_fichier" => $cheminFichier,
            ":taille_fichier" => filesize($cheminFichier),
            ":commentaire" => $commentaire !== ""
                ? $commentaire
                : null,
            ":user_id" => $_SESSION["user_id"] ?? null
        ]);

        $pdo->commit();

        // Redirection pour éviter un nouvel envoi au rafraîchissement.
        header(
            "Location: versions-document.php?id="
            . urlencode($documentId)
            . "&success=1"
        );
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($cheminFichier !== null && is_file($cheminFichier)) {
            unlink($cheminFichier);
        }

        error_log("Ajout version : " . $e->getMessage());

        $message = $e instanceof PDOException
            ? "Erreur de base de données lors de l'enregistrement de la version."
            : $e->getMessage();
    }
}

/* =========================
   CHARGER L'HISTORIQUE
========================= */

if ($document !== null) {
    try {
        $requeteVersions = $pdo->prepare("
            SELECT
                version_id,
                numero_version,
                nom_fichier,
                taille_fichier,
                commentaire,
                user_id,
                date_creation
            FROM document_versions
            WHERE document_id = :id
            ORDER BY numero_version DESC
        ");

        $requeteVersions->execute([
            ":id" => $documentId
        ]);

        $versions = $requeteVersions->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        error_log("Chargement versions : " . $e->getMessage());

        $message = "Impossible de charger l'historique des versions.";
    }
}

if (isset($_GET["success"])) {
    $succes = true;
    $message = "Nouvelle version enregistrée avec succès.";
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Versions des documents - Burundi Transport</title>

    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f4f7fb;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: #1e4d6f;
            color: #fff;
            z-index: 1000;
            overflow-y: auto;
            transition: transform .3s ease;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 75px;
            padding: 0 22px;
            border-bottom: 1px solid rgba(255,255,255,.12);
            font-size: 17px;
            font-weight: 700;
            white-space: nowrap;
        }

        .sidebar-header i {
            color: #e8b04b;
            font-size: 25px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 12px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 13px;
            min-height: 46px;
            margin-bottom: 6px;
            padding: 0 14px;
            border-radius: 8px;
            color: #dce8f1;
            text-decoration: none;
            font-size: 14px;
            transition: background .2s ease;
        }

        .sidebar-nav a i {
            width: 20px;
            text-align: center;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,.12);
            color: #fff;
        }

        .sidebar-nav a.active {
            background: #2c6e9b;
            color: #fff;
            font-weight: 700;
            box-shadow: inset 3px 0 #e8b04b;
        }

        .sidebar-bottom {
            padding: 16px;
            border-top: 1px solid rgba(255,255,255,.12);
        }

        .admin-mini {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #e8b04b;
            color: #1e4d6f;
            font-weight: 800;
        }

        .admin-mini strong,
        .admin-mini span {
            display: block;
        }

        .admin-mini strong {
            font-size: 13px;
        }

        .admin-mini span {
            margin-top: 4px;
            color: #c5d7e5;
            font-size: 12px;
        }

        .logout-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 42px;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 8px;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
        }

        .logout-button:hover {
            background: rgba(255,255,255,.12);
        }

        .main-content {
            min-width: 0;
            min-height: 100vh;
            margin-left: 250px;
        }

        .top-header {
            display: flex;
            align-items: center;
            gap: 15px;
            min-height: 85px;
            padding: 15px 25px;
            background: #fff;
            border-bottom: 1px solid #e5eaf0;
        }

        .header-title h1 {
            margin: 0 0 5px;
            color: #1e2a36;
            font-size: 22px;
        }

        .header-title p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }

        .menu-button {
            display: none;
            width: 42px;
            height: 42px;
            border: 0;
            border-radius: 8px;
            background: #e8f2f9;
            color: #1e4d6f;
            font-size: 18px;
            cursor: pointer;
        }

        .page-content {
            padding: 25px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 24px;
        }

        .page-heading h2 {
            margin: 8px 0;
        }

        .section-label {
            color: #2c6e9b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .document-card,
        .versions-card {
            margin-bottom: 24px;
            padding: 24px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,.05);
        }

        .document-card h3,
        .versions-card h3 {
            margin-top: 0;
            margin-bottom: 18px;
            color: #1e2a36;
        }

        .document-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .detail-item {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .detail-item strong {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 13px;
        }

        .detail-item span {
            color: #1e2a36;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input[type="file"],
        .form-group textarea {
            display: block;
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: #fff;
            font: inherit;
        }

        .form-group textarea {
            resize: vertical;
        }

        .btn-primary,
        .btn-retour {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 16px;
            border: 0;
            border-radius: 7px;
            background: #2c6e9b;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary:hover,
        .btn-retour:hover {
            background: #1e4d6f;
        }

        .btn-primary:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .message {
            margin-bottom: 20px;
            padding: 14px 18px;
            border-radius: 8px;
        }

        .message.erreur {
            background: #fee2e2;
            color: #991b1b;
        }

        .message.succes {
            background: #dcfce7;
            color: #166534;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        .versions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .versions-table th,
        .versions-table td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #e5eaf0;
            vertical-align: middle;
        }

        .versions-table th {
            background: #f5f8fb;
            color: #475569;
            font-size: 13px;
            white-space: nowrap;
        }

        .versions-table td {
            color: #334155;
            font-size: 14px;
        }

        .version-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #e8f2f9;
            color: #1e4d6f;
            font-weight: 700;
            white-space: nowrap;
        }

        .current-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 7px;
            border-radius: 5px;
            background: #e4f7eb;
            color: #187344;
            font-size: 10px;
            font-weight: 700;
        }

        .version-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .version-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .version-action.open {
            background: #e8f2f9;
            color: #1e4d6f;
        }

        .version-action.download {
            background: #e4f7eb;
            color: #187344;
        }

        .version-action:hover {
            filter: brightness(.96);
        }

        .empty-message {
            padding: 28px !important;
            text-align: center !important;
            color: #64748b !important;
        }

        footer {
            padding: 15px 25px;
            color: #64748b;
            text-align: center;
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.sidebar-open {
                transform: translateX(0);
                box-shadow: 8px 0 30px rgba(0,0,0,.2);
            }

            .main-content {
                width: 100%;
                margin-left: 0;
            }

            .menu-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
        }

        @media (max-width: 700px) {
            .page-content {
                padding: 15px;
            }

            .document-card,
            .versions-card {
                padding: 16px;
            }

            .document-details {
                grid-template-columns: 1fr;
            }

            .top-header {
                padding: 15px;
            }

            .header-title h1 {
                font-size: 19px;
            }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fa-solid fa-bus"></i>
        <span>Burundi Transport</span>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php">
            <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
        </a>
        <a href="trajets.php">
            <i class="fa-solid fa-route"></i><span>Trajets</span>
        </a>
        <a href="reservations.php">
            <i class="fa-solid fa-ticket"></i><span>Réservations</span>
        </a>
        <a href="utilisateurs.php">
            <i class="fa-solid fa-users"></i><span>Utilisateurs</span>
        </a>
        <a href="statistiques.php">
            <i class="fa-solid fa-chart-pie"></i><span>Statistiques</span>
        </a>
        <a href="notifications.php">
            <i class="fa-solid fa-bell"></i><span>Notifications</span>
        </a>
        <a href="activites.php">
            <i class="fa-solid fa-clock-rotate-left"></i><span>Activités</span>
        </a>
		 <a href="chat.php" class="menu-link">
                   <i class="fa-solid fa-comments"></i>
                    <span>Message</span>
                </a>
        <a href="documents.php" class="active">
            <i class="fa-solid fa-folder-open"></i><span>Documents</span>
        </a>
        <a href="profil.php">
            <i class="fa-solid fa-user"></i><span>Mon profil</span>
        </a>
        <a href="parametres.php">
            <i class="fa-solid fa-gear"></i><span>Paramètres</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="admin-mini">
            <div class="admin-avatar">A</div>
            <div>
                <strong>Administrateur</strong>
                <span>Admin</span>
            </div>
        </div>

        <a href="../logout.php" class="logout-button">
            <i class="fa-solid fa-right-from-bracket"></i>
            Se déconnecter
        </a>
    </div>
</aside>

<main class="main-content">
    <header class="top-header">
        <button id="menuButton"
                class="menu-button"
                type="button"
                aria-label="Ouvrir le menu"
                aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="header-title">
            <h1>Gestion des versions</h1>
            <p>Historique des documents administratifs</p>
        </div>
    </header>

    <section class="page-content">
        <div class="page-heading">
            <div>
                <span class="section-label">GESTION DES DOCUMENTS</span>
                <h2>Historique des versions</h2>
                <p>Ajoutez, ouvrez ou téléchargez les versions du document.</p>
            </div>

            <a href="documents.php" class="btn-retour">
                <i class="fa-solid fa-arrow-left"></i>
                Retour aux documents
            </a>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?= $succes ? 'succes' : 'erreur' ?>">
                <i class="fa-solid <?= $succes ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= echapperVersion($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($document !== null): ?>

            <div class="document-card">
                <h3>
                    <i class="fa-solid fa-file-lines"></i>
                    Informations du document
                </h3>

                <div class="document-details">
                    <div class="detail-item">
                        <strong>Nom du document</strong>
                        <span><?= echapperVersion($document["nom_document"]) ?></span>
                    </div>

                    <div class="detail-item">
                        <strong>Fichier original</strong>
                        <span><?= echapperVersion($document["nom_fichier"]) ?></span>
                    </div>

                    <div class="detail-item">
                        <strong>Extension</strong>
                        <span><?= echapperVersion($document["extension"]) ?></span>
                    </div>

                    <div class="detail-item">
                        <strong>Taille originale</strong>
                        <span><?= echapperVersion(tailleLisibleVersion($document["taille_fichier"])) ?></span>
                    </div>

                    <div class="detail-item">
                        <strong>Date de création</strong>
                        <span><?= echapperVersion(date("d/m/Y H:i", strtotime($document["date_creation"]))) ?></span>
                    </div>

                    <div class="detail-item">
                        <strong>Description</strong>
                        <span><?= echapperVersion($document["description"] ?: "Aucune description") ?></span>
                    </div>
                </div>
            </div>

            <div class="versions-card">
                <h3>
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Ajouter une nouvelle version
                </h3>

                <form method="POST"
                      action="versions-document.php?id=<?= urlencode($documentId) ?>"
                      enctype="multipart/form-data">

                    <div class="form-group">
                        <label for="fichier_version">Choisir le nouveau fichier</label>
                        <input
                            type="file"
                            id="fichier_version"
                            name="fichier_version"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                            required
                        >
                        <small>Formats autorisés : PDF, Word, Excel, JPG, PNG. Maximum 5 Mo.</small>
                    </div>

                    <div class="form-group">
                        <label for="commentaire">Commentaire sur cette version</label>
                        <textarea
                            id="commentaire"
                            name="commentaire"
                            rows="3"
                            maxlength="2000"
                            placeholder="Exemple : correction du document..."
                        ></textarea>
                    </div>

                    <button type="submit"
                            name="ajouter_version"
                            class="btn-primary">
                        <i class="fa-solid fa-upload"></i>
                        Enregistrer la nouvelle version
                    </button>
                </form>
            </div>

            <div class="versions-card">
                <h3>
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Versions enregistrées
                    (<?= count($versions) ?>)
                </h3>

                <div class="table-container">
                    <table class="versions-table">
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Nom du fichier</th>
                                <th>Taille</th>
                                <th>Commentaire</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($versions)): ?>
                            <tr>
                                <td colspan="6" class="empty-message">
                                    Aucune version enregistrée pour ce document.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($versions as $index => $version): ?>
                                <tr>
                                    <td>
                                        <span class="version-badge">
                                            Version <?= (int) $version["numero_version"] ?>
                                        </span>

                                        <?php if ($index === 0): ?>
                                            <br>
                                            <span class="current-badge">PLUS RÉCENTE</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= echapperVersion($version["nom_fichier"]) ?></td>

                                    <td><?= echapperVersion(tailleLisibleVersion($version["taille_fichier"])) ?></td>

                                    <td><?= echapperVersion($version["commentaire"] ?: "Aucun commentaire") ?></td>

                                    <td><?= echapperVersion(date("d/m/Y H:i", strtotime($version["date_creation"]))) ?></td>

                                    <td>
                                        <div class="version-actions">
                                            <a class="version-action open"
                                               href="telecharger-version.php?id=<?= urlencode($version["version_id"]) ?>&amp;mode=ouvrir"
                                               target="_blank"
                                               rel="noopener">
                                                <i class="fa-solid fa-eye"></i>
                                                Ouvrir
                                            </a>

                                            <a class="version-action download"
                                               href="telecharger-version.php?id=<?= urlencode($version["version_id"]) ?>&amp;mode=telecharger">
                                                <i class="fa-solid fa-download"></i>
                                                Télécharger
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>
    </section>

    <footer>
        <p>© 2026 Burundi Transport. Tous droits réservés.</p>
    </footer>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.querySelector(".sidebar");
    const menuButton = document.getElementById("menuButton");

    if (!sidebar || !menuButton) {
        return;
    }

    menuButton.addEventListener("click", function () {
        const ouvert = sidebar.classList.toggle("sidebar-open");

        menuButton.setAttribute("aria-expanded", String(ouvert));
        menuButton.setAttribute(
            "aria-label",
            ouvert ? "Fermer le menu" : "Ouvrir le menu"
        );
    });

    document.addEventListener("click", function (event) {
        if (
            window.innerWidth <= 900 &&
            !sidebar.contains(event.target) &&
            !menuButton.contains(event.target)
        ) {
            sidebar.classList.remove("sidebar-open");
            menuButton.setAttribute("aria-expanded", "false");
        }
    });
});
</script>

<script src="notification-badge.js"></script>
<script src="theme.js"></script>

</body>
</html>

