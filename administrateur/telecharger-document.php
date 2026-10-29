
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

$documentId = $_GET["id"] ?? "";
$mode = $_GET["mode"] ?? "ouvrir";

if ($documentId === "" || !in_array($mode, ["ouvrir", "telecharger"], true)) {
    http_response_code(400);
    exit("Demande invalide.");
}

try {
    $sql = "
        SELECT nom_fichier, chemin_fichier, type_document
        FROM documents
        WHERE document_id = :id
          AND statut = 'actif'
        LIMIT 1
    ";

    $requete = $pdo->prepare($sql);
    $requete->execute([":id" => $documentId]);
    $document = $requete->fetch();

    if (!$document || !is_file($document["chemin_fichier"])) {
        http_response_code(404);
        exit("Document introuvable.");
    }

    $chemin = $document["chemin_fichier"];
    $nomFichier = basename($document["nom_fichier"]);

    // Déterminer le type MIME depuis le fichier stocké
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($chemin);

    if (!$mime) {
        $mime = "application/octet-stream";
    }

    header("Content-Type: " . $mime);
    header("X-Content-Type-Options: nosniff");
    header("Content-Length: " . filesize($chemin));

    if ($mode === "telecharger") {
        header(
            'Content-Disposition: attachment; filename="'
            . str_replace('"', '', $nomFichier)
            . '"'
        );
    } else {
        // Les PDF et images peuvent s'ouvrir dans le navigateur.
        // Les autres formats seront généralement téléchargés.
        if (
            !in_array($mime, [
                "application/pdf",
                "image/jpeg",
                "image/png"
            ], true)
        ) {
            header(
                'Content-Disposition: attachment; filename="'
                . str_replace('"', '', $nomFichier)
                . '"'
            );
        } else {
            header(
                'Content-Disposition: inline; filename="'
                . str_replace('"', '', $nomFichier)
                . '"'
            );
        }
    }

    readfile($chemin);
    exit;

} catch (Throwable $erreur) {
    error_log("Erreur lecture document : " . $erreur->getMessage());
    http_response_code(500);
    exit("Impossible de lire le document.");
}
