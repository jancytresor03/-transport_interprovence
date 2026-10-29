
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Chat clients - Burundi Transport</title>

    <link rel="stylesheet" href="../chat.css">

    <style>
        .admin-chat-layout {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            min-height: 520px;
        }

        .conversation-list {
            border-right: 1px solid #e5eaf0;
            background: #fff;
        }

        .conversation-list h3 {
            padding: 18px;
            margin: 0;
            border-bottom: 1px solid #e5eaf0;
        }

        .conversation-item {
            display: block;
            width: 100%;
            padding: 16px;
            text-align: left;
            border: none;
            border-bottom: 1px solid #edf0f4;
            background: white;
            cursor: pointer;
            font: inherit;
        }

        .conversation-item:hover,
        .conversation-item.active {
            background: #eaf3f9;
        }

        .conversation-item strong {
            display: block;
            margin-bottom: 6px;
        }

        .conversation-item small {
            color: #647587;
        }

        .admin-chat-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .admin-chat-main .messages {
            flex: 1;
            height: 420px;
        }

        .no-conversation {
            padding: 30px;
            color: #647587;
            text-align: center;
        }

        @media (max-width: 700px) {
            .admin-chat-layout {
                grid-template-columns: 1fr;
            }

            .conversation-list {
                border-right: none;
                border-bottom: 1px solid #e5eaf0;
            }

            .admin-chat-main .messages {
                height: 350px;
            }
        }
    </style>
</head>

<body>

<div class="chat-page">

    <header class="chat-header">
        <div>
            <h1>Burundi Transport</h1>
            <p>Espace d'administration — Service client</p>
        </div>

        <a href="javascript:history.back()" class="back-link">
            Retour
        </a>
    </header>

    <main class="chat-container">

        <div class="chat-title">
            <div>
                <h2>Messagerie des clients</h2>
                <p id="adminChatStatus">Chargement des conversations...</p>
            </div>
        </div>

        <div class="admin-chat-layout">

            <aside class="conversation-list">
                <h3>Conversations</h3>

                <div id="conversationList">
                    <p class="loading-message">
                        Chargement...
                    </p>
                </div>
            </aside>

            <section class="admin-chat-main">

                <div class="chat-title">
                    <div>
                        <h2 id="selectedClient">Sélectionne une conversation</h2>
                        <p id="selectedStatus">
                            Choisis un client dans la liste.
                        </p>
                    </div>
                </div>

                <div id="messages" class="messages">
                    <p class="empty-message">
                        Aucun client sélectionné.
                    </p>
                </div>

                <form id="messageForm" class="message-form">
                    <textarea
                        id="messageInput"
                        name="contenu"
                        placeholder="Écris ta réponse..."
                        maxlength="5000"
                        rows="2"
                        required
                        disabled
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

<span id="documentName">Aucun document choisi</span>

					
				

                    <button type="submit" id="sendButton" disabled>
                        Envoyer
                    </button>
                </form>

                <p id="errorMessage" class="error-message"></p>

            </section>

        </div>

    </main>

</div>

<script>
    const USER_ID = <?= json_encode($_SESSION["user_id"]) ?>;
</script>
<script src="../chat-admin.js"></script>

</body>
</html>
