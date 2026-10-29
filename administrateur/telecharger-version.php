
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

function erreurVersion(int $code, string $message): void
{
    http_response_code($code);
    exit($message);
}

// Vérifier les paramètres
$versionId = trim($_GET["id"] ?? "");
$mode = $_GET["mode"] ?? "telecharger";

if ($versionId === "" || !in_array($mode, ["ouvrir", "telecharger"], true)) {
    erreurVersion(400, "Demande invalide.");
}

try {
    // Récupérer le fichier de la version demandée
    $requete = $pdo->prepare("
        SELECT
            v.nom_fichier,
            v.chemin_fichier,
            v.taille_fichier,
            d.statut
        FROM document_versions AS v
        INNER JOIN documents AS d
            ON d.document_id = v.document_id
        WHERE v.version_id = :version_id
        LIMIT 1
    ");

    $requete->execute([
        ":version_id" => $versionId
    ]);

    $version = $requete->fetch(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log("Erreur lecture version : " . $e->getMessage());
    erreurVersion(500, "Impossible de récupérer cette version.");
}

if (!$version || $version["statut"] !== "actif") {
    erreurVersion(404, "Version introuvable ou indisponible.");
}

// Définir le dossier autorisé pour les fichiers
$dossierStockage = realpath(
    dirname(__DIR__) . DIRECTORY_SEPARATOR . "stockage_documents"
);

if ($dossierStockage === false) {
    erreurVersion(404, "Dossier de stockage introuvable.");
}

// Vérifier le chemin réel du fichier
$cheminFichier = realpath($version["chemin_fichier"]);

if (
    $cheminFichier === false ||
    !is_file($cheminFichier) ||
    !is_readable($cheminFichier)
) {
    erreurVersion(404, "Le fichier de cette version est introuvable.");
}

// Empêcher l'accès à des fichiers hors du dossier de stockage
$prefixeAutorise = $dossierStockage . DIRECTORY_SEPARATOR;

if (strncmp(
    $cheminFichier,
    $prefixeAutorise,
    strlen($prefixeAutorise)
) !== 0) {
    erreurVersion(403, "Accès au fichier refusé.");
}

// Vérifier le type réel du fichier
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($cheminFichier);

$typesAutorisés = [
    "pdf"  => ["application/pdf"],
    "doc"  => ["application/msword", "application/x-ole-storage"],
    "docx" => [
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "application/zip"
    ],
    "xls"  => ["application/vnd.ms-excel", "application/x-ole-storage"],
    "xlsx" => [
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "application/zip"
    ],
    "jpg"  => ["image/jpeg"],
    "jpeg" => ["image/jpeg"],
    "png"  => ["image/png"]
];

$extension = strtolower(
    pathinfo($version["nom_fichier"], PATHINFO_EXTENSION)
);

if (
    !$mime ||
    !in_array($mime, $typesAutorisés[$extension] ?? [], true)
) {
    erreurVersion(415, "Le format de cette version n'est pas autorisé.");
}

// Ouvrir dans le navigateur uniquement les formats adaptés
$peutOuvrir = in_array(
    $extension,
    ["pdf", "jpg", "jpeg", "png"],
    true
);

$disposition = ($mode === "ouvrir" && $peutOuvrir)
    ? "inline"
    : "attachment";

// Éviter les noms de fichiers injectés dans les en-têtes
$nomFichier = basename($version["nom_fichier"]);
$nomAscii = preg_replace(
    '/[^A-Za-z0-9._-]/',
    "_",
    $nomFichier
);

if (!$nomAscii) {
    $nomAscii = "document." . $extension;
}

header("Content-Type: " . $mime);
header("X-Content-Type-Options: nosniff");
header("Content-Length: " . filesize($cheminFichier));
header("Cache-Control: private, no-store");
header(
    'Content-Disposition: ' . $disposition .
    '; filename="' . $nomAscii . '"' .
    "; filename*=UTF-8''" . rawurlencode($nomFichier)
);

// Envoyer le contenu du fichier
readfile($cheminFichier);
exit;
