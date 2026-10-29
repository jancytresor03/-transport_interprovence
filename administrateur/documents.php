
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

$message = "";
$typeMessage = "";
$documents = [];

/* =========================
   FONCTIONS UTILITAIRES
========================= */

function echapper($valeur): string
{
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

function tailleLisible($taille): string
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
   ENREGISTREMENT DOCUMENT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nomDocument = trim($_POST["nom_document"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($nomDocument === "") {
        $message = "Veuillez saisir le nom du document.";
        $typeMessage = "erreur";
    } elseif (
        !isset($_FILES["fichier"]) ||
        $_FILES["fichier"]["error"] !== UPLOAD_ERR_OK
    ) {
        $message = "Veuillez sélectionner un fichier valide.";
        $typeMessage = "erreur";
    } else {
        $fichier = $_FILES["fichier"];
        $nomOriginal = basename($fichier["name"]);
        $taille = (int) $fichier["size"];
        $extension = strtolower(
            pathinfo($nomOriginal, PATHINFO_EXTENSION)
        );

        $extensionsAutorisees = [
            "pdf", "doc", "docx",
            "xls", "xlsx", "jpg", "jpeg", "png"
        ];

        $tailleMax = 5 * 1024 * 1024;

        if (!in_array($extension, $extensionsAutorisees, true)) {
            $message = "Format refusé. Utilisez PDF, Word, Excel ou une image JPG/PNG.";
            $typeMessage = "erreur";
        } elseif ($taille <= 0 || $taille > $tailleMax) {
            $message = "Le fichier doit peser au maximum 5 Mo.";
            $typeMessage = "erreur";
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($fichier["tmp_name"]);

            $typesAutorises = [
                "pdf"  => ["application/pdf"],
                "doc"  => ["application/msword", "application/x-ole-storage"],
                "docx" => [
                    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    "application/zip"
                ],
                "xls"  => [
                    "application/vnd.ms-excel",
                    "application/x-ole-storage"
                ],
                "xlsx" => [
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "application/zip"
                ],
                "jpg"  => ["image/jpeg"],
                "jpeg" => ["image/jpeg"],
                "png"  => ["image/png"]
            ];

            if (
                !$mime ||
                !in_array($mime, $typesAutorises[$extension] ?? [], true)
            ) {
                $message = "Le contenu du fichier ne correspond pas au format annoncé.";
                $typeMessage = "erreur";
            } else {
                $dossier = dirname(__DIR__) . DIRECTORY_SEPARATOR . "stockage_documents";

                if (
                    !is_dir($dossier) &&
                    !mkdir($dossier, 0750, true) &&
                    !is_dir($dossier)
                ) {
                    $message = "Impossible de créer le dossier de stockage.";
                    $typeMessage = "erreur";
                } else {
                    $nomStockage = bin2hex(random_bytes(16)) . "." . $extension;
                    $cheminComplet = $dossier . DIRECTORY_SEPARATOR . $nomStockage;

                    if (!move_uploaded_file($fichier["tmp_name"], $cheminComplet)) {
                        $message = "Impossible d'enregistrer le fichier.";
                        $typeMessage = "erreur";
                    } else {
                        
try {
    // 1. Préparer les identifiants
    $documentId = bin2hex(random_bytes(10));
    $versionId = bin2hex(random_bytes(10));
    $userId = $_SESSION["user_id"] ?? null;

    if (!$userId) {
        throw new RuntimeException(
            "Session utilisateur introuvable."
        );
    }

    // 2. Commencer une transaction SQL
    $pdo->beginTransaction();

    // 3. Enregistrer le document principal
    $sql = "
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
    ";

    $requete = $pdo->prepare($sql);

    $requete->execute([
        ":document_id" => $documentId,
        ":user_id" => $userId,
        ":nom_document" => $nomDocument,
        ":nom_fichier" => $nomOriginal,
        ":chemin_fichier" => $cheminComplet,
        ":type_document" => $mime,
        ":extension" => $extension,
        ":taille_fichier" => $taille,
        ":description" => $description !== ""
            ? $description
            : null
    ]);

    // 4. Enregistrer automatiquement la version 1
    $sqlVersion = "
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
    ";

    $requeteVersion = $pdo->prepare($sqlVersion);

    $requeteVersion->execute([
        ":version_id" => $versionId,
        ":document_id" => $documentId,
        ":numero_version" => 1,
        ":nom_fichier" => $nomOriginal,
        ":chemin_fichier" => $cheminComplet,
        ":taille_fichier" => $taille,
        ":commentaire" => $description !== ""
            ? $description
            : "Version initiale",
        ":user_id" => $userId
    ]);

    // 5. Confirmer les deux insertions
    $pdo->commit();

    $message = "Document envoyé avec succès. Version 1 enregistrée.";
    $typeMessage = "succes";

} catch (Throwable $erreur) {

    // Annuler les insertions SQL si une erreur survient
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Supprimer le fichier envoyé si son enregistrement échoue
    if (isset($cheminComplet) && is_file($cheminComplet)) {
        unlink($cheminComplet);
    }

    error_log(
        "Erreur enregistrement document et version : " .
        $erreur->getMessage()
    );

    $message = "Une erreur est survenue pendant l'enregistrement du document ou de sa version.";
    $typeMessage = "erreur";
}

                    }
                }
            }
        }
    }
}

/* =========================
   RECUPERATION DOCUMENTS
========================= */

try {
    $sqlDocuments = "
        SELECT
            document_id,
            nom_document,
            nom_fichier,
            description,
            extension,
            taille_fichier,
            date_creation
        FROM documents
        WHERE statut = 'actif'
        ORDER BY date_creation DESC
    ";

    $requeteDocuments = $pdo->query($sqlDocuments);
    $documents = $requeteDocuments->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $erreur) {
    error_log(
        "Erreur chargement documents : " . $erreur->getMessage()
    );

    $message = "Impossible de charger la liste des documents.";
    $typeMessage = "erreur";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Documents - Burundi Transport</title>

    <link rel="stylesheet" href="theme.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
	
	
/* =========================
   SIDEBAR - DOCUMENTS
========================= */

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
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    padding: 24px 16px;
    background: linear-gradient(180deg, #1e4d6f, #173b56);
    color: #fff;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 4px 0 18px rgba(18, 45, 67, 0.12);
}

.sidebar .logo {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 8px 28px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.sidebar .logo-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #e8b04b;
    color: #173b56;
    border-radius: 13px;
    font-size: 21px;
}

.sidebar .logo h2 {
    margin: 0;
    color: #fff;
    font-size: 21px;
}

.sidebar .logo span {
    display: block;
    margin-top: 3px;
    color: #d0e0ed;
    font-size: 13px;
}

.sidebar .menu {
    display: flex;
    flex-direction: column;
    gap: 7px;
    margin-top: 25px;
    flex: 1;
}

.sidebar .menu a {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 13px 14px;
    color: #d8e5ef;
    text-decoration: none;
    font-size: 14px;
    border-radius: 9px;
    transition: background 0.2s ease, color 0.2s ease;
}

.sidebar .menu a i {
    width: 20px;
    text-align: center;
    font-size: 16px;
}

.sidebar .menu a:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
}

.sidebar .menu a.active {
    background: #e8b04b;
    color: #173b56;
    font-weight: 700;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
}

.sidebar .sidebar-bottom {
    padding-top: 18px;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.sidebar .admin-mini {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 17px;
    padding: 0 5px;
}

.sidebar .admin-avatar {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8b04b;
    color: #173b56;
    border-radius: 50%;
    font-weight: 700;
}

.sidebar .admin-mini strong,
.sidebar .admin-mini span {
    display: block;
}

.sidebar .admin-mini strong {
    color: #fff;
    font-size: 13px;
}

.sidebar .admin-mini span {
    margin-top: 4px;
    color: #c8d8e5;
    font-size: 12px;
}

.sidebar .logout-button {
    width: 100%;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.09);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 9px;
    color: #fff;
    text-decoration: none;
    font-size: 13px;
    transition: background 0.2s ease;
}

.sidebar .logout-button:hover {
    background: rgba(255, 255, 255, 0.18);
}

/* Contenu à droite du menu */

.main-content {
    min-width: 0;
    margin-left: 250px;
    width: calc(100% - 250px);
    box-sizing: border-box;
}

.main-content .top-header {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 20px 28px;
    background: #fff;
    border-bottom: 1px solid #e7edf3;
}

.main-content .header-title {
    flex: 1;
}

.main-content .header-title h1 {
    margin: 0;
    color: #1e2a36;
    font-size: 24px;
}

.main-content .header-title p {
    margin: 5px 0 0;
    color: #718096;
    font-size: 13px;
}

.main-content .header-actions {
    display: flex;
    align-items: center;
    gap: 20px;
}

.main-content .header-admin {
    display: flex;
    align-items: center;
    gap: 10px;
}

.main-content .header-admin strong,
.main-content .header-admin small {
    display: block;
}

.main-content .header-admin strong {
    color: #263746;
    font-size: 13px;
}

.main-content .header-admin small {
    margin-top: 4px;
    color: #718096;
}

.main-content .header-icon {
    position: relative;
    color: #2c6e9b;
    font-size: 19px;
}

.main-content .menu-button {
    display: none;
    padding: 8px 10px;
    border: 0;
    border-radius: 8px;
    background: #edf4f9;
    color: #1e4d6f;
    cursor: pointer;
    font-size: 18px;
}

/* Adaptation mobile */

@media (max-width: 900px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.25s ease;
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .main-content {
        width: 100%;
        margin-left: 0;
    }

    .main-content .menu-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .main-content .top-header {
        padding: 16px;
    }

    .main-content .header-admin {
        display: none;
    }
}

        .main-content {
            min-width: 0;
        }

        .page-content {
            padding: 25px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-heading h2 {
            margin: 8px 0;
        }

        .section-label {
            color: #2c6e9b;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .document-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
        }

        .document-card h3 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #d7e0e8;
            border-radius: 7px;
            font: inherit;
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .btn-primary,
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            cursor: pointer;
            font: inherit;
            font-weight: 600;
        }

        .btn-primary {
            background: #2c6e9b;
            color: #fff;
        }

        .btn-action {
            background: #edf4f9;
            color: #1e4d6f;
            margin: 2px;
        }

        .btn-action.download {
            background: #eaf7ee;
            color: #237343;
        }

        .message {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .message.succes {
            background: #e8f7ed;
            color: #176b36;
        }

        .message.erreur {
            background: #fdecec;
            color: #a42626;
        }

        .table-container {
            overflow-x: auto;
        }

        .documents-table {
            width: 100%;
            border-collapse: collapse;
        }

        .documents-table th,
        .documents-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e8edf1;
            text-align: left;
            vertical-align: middle;
        }

        .documents-table th {
            background: #f5f8fb;
            white-space: nowrap;
        }

        .empty-message {
            padding: 25px;
            text-align: center;
            color: #657585;
        }

        footer {
            padding: 20px 25px;
            text-align: center;
        }

        @media (max-width: 700px) {
            .page-content {
                padding: 15px;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }
        }
		
		
.btn-action.manage-versions {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 4px;
    padding: 8px 12px;
    background: #e8f2f9;
    color: #1e4d6f;
    border: 1px solid #c8dfef;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: background 0.2s ease;
}

.btn-action.manage-versions:hover {
    background: #d5e8f5;
}

    </style>
</head>

<body>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const menuButton = document.getElementById("menuButton");
    const sidebar = document.querySelector(".sidebar");

    if (menuButton && sidebar) {
        menuButton.addEventListener("click", function () {
            sidebar.classList.toggle("open");
        });
    }
});
</script>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">
        <div class="logo-icon">
            <i class="fa-solid fa-bus"></i>
        </div>

        <div>
            <h2>Burundi</h2>
            <span>Transport</span>
        </div>
    </div>

    <nav class="menu">

        <a href="admin-dashboard.php">
            <i class="fa-solid fa-chart-line"></i>
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
            <i class="fa-solid fa-chart-pie"></i>
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

        <a href="documents.php" class="active">
            <i class="fa-solid fa-folder-open"></i>
            <span>Documents</span>
        </a>

        <a href="profil.php">
            <i class="fa-solid fa-user"></i>
            <span>Mon profil</span>
        </a>
		
		 <a href="historique-connexions.php" class="menu-link">
                   <i class="fa-solid fa-shield-halved"></i>
                    <span>Historique connexion</span>
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

<!-- =========================
     CONTENU PRINCIPAL
========================= -->

<main class="main-content">

    <header class="top-header">

        <button id="menuButton" class="menu-button" type="button"
                aria-label="Ouvrir le menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="header-title">
            <h1>Documents</h1>
            <p>Gestion des documents de Burundi Transport</p>
        </div>

        <div class="header-actions">
            <a href="notifications.php" class="header-icon"
               aria-label="Notifications">
                <i class="fa-regular fa-bell"></i>
            </a>

            <div class="header-admin">
                <div class="admin-avatar">A</div>
                <div>
                    <strong>Administrateur</strong>
                    <small>Administrateur</small>
                </div>
            </div>
        </div>

    </header>

    <section class="page-content">

        <div class="page-heading">
            <div>
                <span class="section-label">GESTION DES FICHIERS</span>
                <h2>Documents administratifs</h2>
                <p>Envoyez, consultez et téléchargez vos documents.</p>
            </div>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?= echapper($typeMessage) ?>">
                <?= echapper($message) ?>
            </div>
        <?php endif; ?>

        <!-- FORMULAIRE D'ENVOI -->

        <div class="document-card">

            <h3>
                <i class="fa-solid fa-cloud-arrow-up"></i>
                Envoyer un document
            </h3>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-grid">

                    <div class="form-group">
                        <label for="nom_document">Nom du document *</label>
                        <input
                            type="text"
                            id="nom_document"
                            name="nom_document"
                            maxlength="255"
                            required
                            placeholder="Ex. Rapport mensuel">
                    </div>

                    <div class="form-group">
                        <label for="fichier">Choisir un fichier *</label>
                        <input
                            type="file"
                            id="fichier"
                            name="fichier"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                            required>
                        <small>Formats PDF, Word, Excel, JPG et PNG — 5 Mo maximum.</small>
                    </div>

                    <div class="form-group full">
                        <label for="description">Description (facultative)</label>
                        <textarea
                            id="description"
                            name="description"
                            placeholder="Décrivez brièvement ce document..."></textarea>
                    </div>

                    <div class="form-group full">
                        <div>
                            <button type="submit" class="btn-primary">
                                <i class="fa-solid fa-upload"></i>
                                Envoyer le document
                            </button>
                        </div>
                    </div>

                </div>

            </form>

        </div>

        <!-- LISTE DES DOCUMENTS -->

        <div class="document-card">

            <h3>
                <i class="fa-solid fa-folder-open"></i>
                Documents enregistrés
                (<?= count($documents) ?>)
            </h3>

            <div class="table-container">

                <table class="documents-table">

                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Nom du fichier</th>
                            <th>Description</th>
                            <th>Taille</th>
                            <th>Date d'envoi</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($documents)): ?>

                        <tr>
                            <td colspan="6" class="empty-message">
                                <i class="fa-regular fa-folder-open"></i>
                                Aucun document enregistré pour le moment.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($documents as $document): ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?= echapper($document["nom_document"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= echapper($document["nom_fichier"]) ?>
                                </td>

                                <td>
                                    <?= echapper($document["description"] ?? "") ?>
                                </td>

                                <td>
                                    <?= echapper(tailleLisible($document["taille_fichier"])) ?>
                                </td>

                                <td>
                                    <?= echapper(
                                        date(
                                            "d/m/Y H:i",
                                            strtotime($document["date_creation"])
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <a
                                        class="btn-action"
                                        href="telecharger-document.php?id=<?= urlencode($document["document_id"]) ?>&amp;mode=ouvrir"
                                        target="_blank"
                                        rel="noopener">
                                        <i class="fa-solid fa-eye"></i>
                                        Ouvrir
                                    </a>

                                    <a
                                        class="btn-action download"
                                        href="telecharger-document.php?id=<?= urlencode($document["document_id"]) ?>&amp;mode=telecharger">
                                        <i class="fa-solid fa-download"></i>
                                        Télécharger
                                    </a>
                                </td>
								
								
                            <a
                                 class="btn-action manage-versions"
                                   href="versions-document.php?id=<?= urlencode($document["document_id"]) ?>">
                                 <i class="fa-solid fa-clock-rotate-left"></i>
                                  Gérer les versions
                            </a>

								
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </section>

    <footer>
        <p>© 2026 Burundi Transport. Tous droits réservés.</p>
    </footer>

</main>

<script src="notification-badge.js"></script>
<script src="theme.js"></script>

</body>
</html>
