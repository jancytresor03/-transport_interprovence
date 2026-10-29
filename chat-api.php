
<?php

require_once __DIR__ . "/verification.php";
require_once __DIR__ . "/database.php";

header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");

function repondreChat(array $donnees, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 1. Vérifier la connexion
    if (empty($_SESSION["user_id"])) {
        repondreChat([
            "success" => false,
            "erreur" => "Vous devez vous connecter."
        ], 401);
    }

    $userId = (string) $_SESSION["user_id"];

    // 2. Vérifier le compte et son rôle
    $requeteUtilisateur = $pdo->prepare("
        SELECT u.user_id, u.role_id, r.nom AS role_nom
        FROM users u
        INNER JOIN roles r ON r.role_id = u.role_id
        WHERE u.user_id = :user_id
          AND u.statut = 'actif'
          AND r.statut = 'actif'
        LIMIT 1
    ");

    $requeteUtilisateur->execute([":user_id" => $userId]);
    $utilisateur = $requeteUtilisateur->fetch(PDO::FETCH_ASSOC);

    if (!$utilisateur) {
        repondreChat([
            "success" => false,
            "erreur" => "Compte introuvable ou inactif."
        ], 403);
    }

    $estAdmin = $utilisateur["role_id"] === "ROLE-001";
    $estClient = $utilisateur["role_id"] === "ROLE-002";

    if (!$estAdmin && !$estClient) {
        repondreChat([
            "success" => false,
            "erreur" => "Votre rôle ne permet pas d'utiliser le chat."
        ], 403);
    }

    $action = $_GET["action"] ?? "";

    // 3. Vérifier l'accès à une conversation
    $verifierConversation = function (int $conversationId) use (
        $pdo, $userId, $estAdmin
    ): array {
        $requete = $pdo->prepare("
            SELECT conversation_id, client_id, statut
            FROM conversations
            WHERE conversation_id = :id
            LIMIT 1
        ");

        $requete->execute([":id" => $conversationId]);
        $conversation = $requete->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            repondreChat([
                "success" => false,
                "erreur" => "Conversation introuvable."
            ], 404);
        }

        if (!$estAdmin && $conversation["client_id"] !== $userId) {
            repondreChat([
                "success" => false,
                "erreur" => "Accès interdit à cette conversation."
            ], 403);
        }

        return $conversation;
    };

    // 4. Ouvrir ou retrouver la conversation du client
    if ($action === "ouvrir") {
        if (!$estClient) {
            repondreChat([
                "success" => false,
                "erreur" => "Seul un client peut ouvrir sa conversation."
            ], 403);
        }

        $requete = $pdo->prepare("
            SELECT conversation_id, statut, date_creation
            FROM conversations
            WHERE client_id = :client_id
              AND statut = 'ouverte'
            ORDER BY conversation_id DESC
            LIMIT 1
        ");

        $requete->execute([":client_id" => $userId]);
        $conversation = $requete->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            $creation = $pdo->prepare("
                INSERT INTO conversations (client_id)
                VALUES (:client_id)
            ");

            $creation->execute([":client_id" => $userId]);

            $conversation = [
                "conversation_id" => (int) $pdo->lastInsertId(),
                "statut" => "ouverte"
            ];
        }

        repondreChat([
            "success" => true,
            "conversation" => $conversation
        ]);
    }

    // 5. Lister les conversations
    if ($action === "conversations") {
        if ($estAdmin) {
            $requete = $pdo->query("
                SELECT
                    c.conversation_id,
                    c.client_id,
                    u.nom,
                    u.prenom,
                    c.sujet,
                    c.statut,
                    c.date_creation,
                    c.date_modification
                FROM conversations c
                INNER JOIN users u ON u.user_id = c.client_id
                ORDER BY c.date_modification DESC
            ");
        } else {
            $requete = $pdo->prepare("
                SELECT
                    conversation_id,
                    client_id,
                    sujet,
                    statut,
                    date_creation,
                    date_modification
                FROM conversations
                WHERE client_id = :client_id
                ORDER BY date_modification DESC
            ");

            $requete->execute([":client_id" => $userId]);
        }

        repondreChat([
            "success" => true,
            "conversations" => $requete->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    // 6. Envoyer un message avec ou sans document
    if ($action === "envoyer") {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            repondreChat([
                "success" => false,
                "erreur" => "Utilisez la méthode POST."
            ], 405);
        }

        $conversationId = filter_var(
            $_POST["conversation_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $contenu = trim((string) ($_POST["contenu"] ?? ""));
        $fichier = $_FILES["document"] ?? null;

        if (!$conversationId || $conversationId < 1) {
            repondreChat([
                "success" => false,
                "erreur" => "Identifiant de conversation invalide."
            ], 400);
        }

        $aUnFichier = $fichier !== null
            && isset($fichier["error"])
            && $fichier["error"] !== UPLOAD_ERR_NO_FILE;

        if ($contenu === "" && !$aUnFichier) {
            repondreChat([
                "success" => false,
                "erreur" => "Écrivez un message ou choisissez un document."
            ], 400);
        }

        if (mb_strlen($contenu, "UTF-8") > 5000) {
            repondreChat([
                "success" => false,
                "erreur" => "Le message ne doit pas dépasser 5000 caractères."
            ], 400);
        }

        $conversation = $verifierConversation((int) $conversationId);

        if ($conversation["statut"] !== "ouverte") {
            repondreChat([
                "success" => false,
                "erreur" => "Cette conversation est fermée."
            ], 403);
        }

        // Valider le fichier avant d'enregistrer le message
        $dossier = __DIR__ . "/uploads/chat";
        $cheminFichier = null;
        $nomStockage = null;
        $nomOriginal = null;
        $typeMime = null;
        $taille = null;

        if ($aUnFichier) {
            if ($fichier["error"] !== UPLOAD_ERR_OK) {
                repondreChat([
                    "success" => false,
                    "erreur" => "Le téléversement du fichier a échoué."
                ], 400);
            }

            if (!is_uploaded_file($fichier["tmp_name"])) {
                repondreChat([
                    "success" => false,
                    "erreur" => "Fichier envoyé non valide."
                ], 400);
            }

            $taille = (int) $fichier["size"];

            if ($taille <= 0 || $taille > 5 * 1024 * 1024) {
                repondreChat([
                    "success" => false,
                    "erreur" => "Le fichier doit peser au maximum 5 Mo."
                ], 400);
            }

            $nomOriginal = basename(
                str_replace("\\", "/", $fichier["name"])
            );

            $extension = strtolower(
                pathinfo($nomOriginal, PATHINFO_EXTENSION)
            );

            $extensionsAutorisees = [
                "pdf", "doc", "docx",
                "xls", "xlsx",
                "jpg", "jpeg", "png", "gif", "webp"
            ];

            if (!in_array($extension, $extensionsAutorisees, true)) {
                repondreChat([
                    "success" => false,
                    "erreur" => "Format interdit. Utilisez PDF, Word, Excel ou une image."
                ], 400);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $typeMime = $finfo->file($fichier["tmp_name"]);

            $mimesAutorises = [
                "pdf"  => ["application/pdf"],
                "doc"  => ["application/msword", "application/x-ole-storage"],
                "docx" => [
                    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    "application/zip",
                    "application/x-zip"
                ],
                "xls"  => [
                    "application/vnd.ms-excel",
                    "application/x-ole-storage"
                ],
                "xlsx" => [
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "application/zip",
                    "application/x-zip"
                ],
                "jpg"  => ["image/jpeg"],
                "jpeg" => ["image/jpeg"],
                "png"  => ["image/png"],
                "gif"  => ["image/gif"],
                "webp" => ["image/webp"]
            ];

            if (
                $typeMime === false ||
                !in_array($typeMime, $mimesAutorises[$extension], true)
            ) {
                repondreChat([
                    "success" => false,
                    "erreur" => "Le contenu réel du fichier ne correspond pas à son extension."
                ], 400);
            }

if (!is_dir($dossier)) {
    if (!mkdir($dossier, 0755, true) && !is_dir($dossier)) {
        repondreChat([
            "success" => false,
            "erreur" => "Impossible de créer le dossier de stockage. Vérifiez les droits du dossier du projet."
        ], 500);
    }
}

if (!is_writable($dossier)) {
    repondreChat([
        "success" => false,
        "erreur" => "Le dossier existe, mais PHP ne peut pas y écrire : " . $dossier
    ], 500);
}

            // Nom aléatoire : le nom original n'est jamais utilisé sur le disque.
            $nomStockage = bin2hex(random_bytes(24)) . "." . $extension;
            $cheminFichier = $dossier . "/" . $nomStockage;
        }

        $fichierDeplace = false;

        try {
            $pdo->beginTransaction();

            $creationMessage = $pdo->prepare("
                INSERT INTO messages
                    (conversation_id, expediteur_id, contenu)
                VALUES (:conversation_id, :expediteur_id, :contenu)
            ");

            $creationMessage->execute([
                ":conversation_id" => $conversationId,
                ":expediteur_id" => $userId,
                ":contenu" => $contenu === "" ? null : $contenu
            ]);

            $messageId = (int) $pdo->lastInsertId();

            if ($aUnFichier) {
                if (!move_uploaded_file(
                    $fichier["tmp_name"],
                    $cheminFichier
                )) {
                    throw new RuntimeException(
                        "Impossible de stocker le document."
                    );
                }

                $fichierDeplace = true;

                $creationDocument = $pdo->prepare("
                    INSERT INTO documents_chat
                        (message_id, nom_original, nom_stockage, type_mime, taille)
                    VALUES
                        (:message_id, :nom_original, :nom_stockage, :type_mime, :taille)
                ");

                $creationDocument->execute([
                    ":message_id" => $messageId,
                    ":nom_original" => $nomOriginal,
                    ":nom_stockage" => $nomStockage,
                    ":type_mime" => $typeMime,
                    ":taille" => $taille
                ]);
            }

            $majConversation = $pdo->prepare("
                UPDATE conversations
                SET date_modification = CURRENT_TIMESTAMP
                WHERE conversation_id = :conversation_id
            ");

            $majConversation->execute([
                ":conversation_id" => $conversationId
            ]);

            $pdo->commit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($fichierDeplace && $cheminFichier !== null) {
                if (is_file($cheminFichier)) {
                    unlink($cheminFichier);
                }
            }

            throw $e;
        }

        repondreChat([
            "success" => true,
            "message" => [
                "message_id" => $messageId,
                "conversation_id" => (int) $conversationId,
                "expediteur_id" => $userId,
                "contenu" => $contenu,
                "date_envoi" => date("Y-m-d H:i:s")
            ]
        ], 201);
    }

    // 7. Télécharger un document après vérification des droits
    if ($action === "telecharger") {
        $documentId = filter_var(
            $_GET["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$documentId || $documentId < 1) {
            repondreChat([
                "success" => false,
                "erreur" => "Identifiant du document invalide."
            ], 400);
        }

        $requete = $pdo->prepare("
            SELECT
                d.document_id,
                d.nom_original,
                d.nom_stockage,
                d.type_mime,
                d.taille,
                c.conversation_id,
                c.client_id
            FROM documents_chat d
            INNER JOIN messages m ON m.message_id = d.message_id
            INNER JOIN conversations c
                ON c.conversation_id = m.conversation_id
            WHERE d.document_id = :document_id
            LIMIT 1
        ");

        $requete->execute([":document_id" => $documentId]);
        $document = $requete->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            repondreChat([
                "success" => false,
                "erreur" => "Document introuvable."
            ], 404);
        }

        // Un client ne peut télécharger que les documents de sa conversation.
        if (!$estAdmin && $document["client_id"] !== $userId) {
            repondreChat([
                "success" => false,
                "erreur" => "Vous n'avez pas accès à ce document."
            ], 403);
        }

        $chemin = __DIR__ . "/uploads/chat/" .
            basename($document["nom_stockage"]);

        if (!is_file($chemin) || !is_readable($chemin)) {
            repondreChat([
                "success" => false,
                "erreur" => "Le fichier est absent du serveur."
            ], 404);
        }

        // Le nom du fichier est encodé pour l'en-tête HTTP.
        $nomEncode = rawurlencode($document["nom_original"]);

        header_remove("Content-Type");
        header("Content-Type: " . $document["type_mime"]);
        header("X-Content-Type-Options: nosniff");
        header("Content-Length: " . filesize($chemin));
        header(
            "Content-Disposition: attachment; filename=\"document\"; filename*=UTF-8''" .
            $nomEncode
        );
        header("Cache-Control: private, no-store, max-age=0");

        readfile($chemin);
        exit;
    }

    // 8. Récupérer les messages et leurs documents
    if ($action === "messages") {
        $conversationId = filter_var(
            $_GET["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$conversationId || $conversationId < 1) {
            repondreChat([
                "success" => false,
                "erreur" => "Identifiant de conversation invalide."
            ], 400);
        }

        $verifierConversation((int) $conversationId);

        $requete = $pdo->prepare("
            SELECT
                m.message_id,
                m.conversation_id,
                m.expediteur_id,
                u.nom AS expediteur_nom,
                u.prenom AS expediteur_prenom,
                r.nom AS expediteur_role,
                m.contenu,
                m.date_envoi
            FROM messages m
            INNER JOIN users u ON u.user_id = m.expediteur_id
            LEFT JOIN roles r ON r.role_id = u.role_id
            WHERE m.conversation_id = :conversation_id
            ORDER BY m.message_id ASC
        ");

        $requete->execute([
            ":conversation_id" => $conversationId
        ]);

        $messages = $requete->fetchAll(PDO::FETCH_ASSOC);

        $requeteDocuments = $pdo->prepare("
            SELECT
                document_id,
                nom_original,
                type_mime,
                taille
            FROM documents_chat
            WHERE message_id = :message_id
            ORDER BY document_id ASC
        ");

        foreach ($messages as &$message) {
            $requeteDocuments->execute([
                ":message_id" => $message["message_id"]
            ]);

            $message["documents"] = $requeteDocuments->fetchAll(
                PDO::FETCH_ASSOC
            );
        }

        unset($message);

        repondreChat([
            "success" => true,
            "messages" => $messages
        ]);
    }

    // 9. Action inconnue
    repondreChat([
        "success" => false,
        "erreur" => "Action inconnue."
    ], 400);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Erreur chat-api.php : " . $e->getMessage());

    repondreChat([
        "success" => false,
        "erreur" => "Une erreur interne est survenue dans le chat."
    ], 500);
}
