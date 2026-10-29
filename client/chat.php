
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Chat - Burundi Transport</title>

    <link rel="stylesheet" href="../chat.css">
</head>
<body>

<div class="chat-page">

    <header class="chat-header">
        <div>
            <h1>Burundi Transport</h1>
            <p>Service d'assistance</p>
        </div>

        <a href="javascript:history.back()" class="back-link">
            Retour
        </a>
    </header>

    <main class="chat-container">

        <div class="chat-title">
            <div>
                <h2>Conversation avec l'administration</h2>
                <p id="chatStatus">Connexion au chat...</p>
            </div>

            <span class="status-indicator"></span>
        </div>

        <div id="messages" class="messages">
            <p class="loading-message">Chargement des messages...</p>
        </div>

        <form id="messageForm" class="message-form">

            <textarea
                id="messageInput"
                name="contenu"
                placeholder="Écris ton message ici..."
                maxlength="5000"
                rows="2"
                required
            ></textarea>
			
			 <label for="documentInput" class="document-button">
                              📎 
                    </label>

                 <input
                   type="file"
                   id="documentInput"
                   name="document"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp"
                   hidden
                 >

                     <span id="documentName">envoyer document</span>

            <button type="submit" id="sendButton">
                Envoyer
            </button>

        </form>

        <p id="errorMessage" class="error-message"></p>

    </main>

</div>

<script>
    const USER_ID = <?= json_encode($_SESSION["user_id"]) ?>;
</script>

<script src="../chat.js"></script>
<script src="deconnexion-auto.js"></script>

</body>
</html>
