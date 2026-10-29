
const API_CHAT = "/transport_interprovence/chat-api.php";

const messagesContainer = document.getElementById("messages");
const messageForm = document.getElementById("messageForm");
const messageInput = document.getElementById("messageInput");
const sendButton = document.getElementById("sendButton");
const chatStatus = document.getElementById("chatStatus");
const errorMessage = document.getElementById("errorMessage");

// Nouvel élément pour sélectionner un document
const documentInput = document.getElementById("documentInput");

let conversationId = null;
let dernierMessageId = 0;
let envoiEnCours = false;

async function appelerAPI(url, options = {}) {
    const response = await fetch(url, {
        credentials: "same-origin",
        ...options
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.erreur || "Une erreur est survenue.");
    }

    return data;
}


const documentName = document.getElementById("documentName");

if (documentInput && documentName) {
    documentInput.addEventListener("change", function () {
        if (documentInput.files.length > 0) {
            documentName.textContent = documentInput.files[0].name;
        } else {
            documentName.textContent = "Aucun document choisi";
        }
    });
}


// Afficher un message
function afficherMessage(message) {
    const bloc = document.createElement("div");

    bloc.className =
        message.expediteur_id === USER_ID
            ? "message mine"
            : "message other";

    // Nom de l'expéditeur
    const auteur = document.createElement("div");
    auteur.className = "message-author";
    auteur.textContent =
        (message.expediteur_prenom || "") + " " +
        (message.expediteur_nom || "");

    bloc.appendChild(auteur);

    // Texte du message
    if (message.contenu) {
        const contenu = document.createElement("div");
        contenu.className = "message-content";
        contenu.textContent = message.contenu;

        bloc.appendChild(contenu);
    }

    // Documents joints
    if (Array.isArray(message.documents) && message.documents.length > 0) {
        const listeDocuments = document.createElement("div");
        listeDocuments.className = "message-documents";

        message.documents.forEach(function (documentChat) {
            const lien = document.createElement("a");

            lien.className = "document-download";
            lien.href =
                API_CHAT +
                "?action=telecharger&id=" +
                encodeURIComponent(documentChat.document_id);

            lien.textContent =
                "📎 Télécharger : " + documentChat.nom_original;

            lien.setAttribute("download", "");
            lien.setAttribute("aria-label",
                "Télécharger le document " + documentChat.nom_original
            );

            listeDocuments.appendChild(lien);
        });

        bloc.appendChild(listeDocuments);
    }

    // Date du message
    const date = document.createElement("div");
    date.className = "message-date";
    date.textContent = message.date_envoi;

    bloc.appendChild(date);
    messagesContainer.appendChild(bloc);
}

// Charger les messages
async function chargerMessages() {
    if (!conversationId) return;

    const data = await appelerAPI(
        `${API_CHAT}?action=messages&id=${conversationId}`
    );

    messagesContainer.replaceChildren();

    if (data.messages.length === 0) {
        const vide = document.createElement("p");
        vide.className = "empty-message";
        vide.textContent =
            "Aucun message pour le moment. Écris ton premier message !";

        messagesContainer.appendChild(vide);
    } else {
        data.messages.forEach(afficherMessage);

        dernierMessageId = Number(
            data.messages[data.messages.length - 1].message_id
        );
    }

    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Ouvrir la conversation du client
async function ouvrirConversation() {
    const data = await appelerAPI(`${API_CHAT}?action=ouvrir`);

    conversationId = Number(data.conversation.conversation_id);

    chatStatus.textContent = "Conversation ouverte";

    await chargerMessages();
}

// Envoyer un message et éventuellement un document
messageForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const contenu = messageInput.value.trim();
    const documentChoisi =
        documentInput && documentInput.files.length > 0
            ? documentInput.files[0]
            : null;

    // On accepte un texte, un document, ou les deux
    if (!contenu && !documentChoisi) {
        errorMessage.textContent =
            "Écris un message ou sélectionne un document.";

        return;
    }

    if (!conversationId || envoiEnCours) return;

    envoiEnCours = true;
    sendButton.disabled = true;
    errorMessage.textContent = "";

    try {
        // FormData permet d'envoyer un fichier avec le texte
        const donnees = new FormData();

        donnees.append("conversation_id", conversationId);
        donnees.append("contenu", contenu);

        if (documentChoisi) {
            donnees.append("document", documentChoisi);
        }

        await appelerAPI(`${API_CHAT}?action=envoyer`, {
            method: "POST",
            body: donnees
        });

        messageInput.value = "";

        if (documentInput) {
            documentInput.value = "";
        }

        await chargerMessages();

    } catch (erreur) {
        errorMessage.textContent = erreur.message;

    } finally {
        envoiEnCours = false;
        sendButton.disabled = false;
        messageInput.focus();
    }
});

// Actualiser les messages périodiquement
async function actualiserMessages() {
    try {
        if (conversationId) {
            await chargerMessages();
        }
    } catch (erreur) {
        errorMessage.textContent = erreur.message;
    }
}

// Démarrer le chat
async function demarrerChat() {
    try {
        await ouvrirConversation();

        setInterval(actualiserMessages, 3000);

    } catch (erreur) {
        chatStatus.textContent =
            "Impossible de charger la conversation";

        errorMessage.textContent = erreur.message;
    }
}

demarrerChat();
