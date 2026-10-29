
const API_CHAT = "/transport_interprovence/chat-api.php";

const conversationList = document.getElementById("conversationList");
const messagesContainer = document.getElementById("messages");
const messageForm = document.getElementById("messageForm");
const messageInput = document.getElementById("messageInput");
const sendButton = document.getElementById("sendButton");
const errorMessage = document.getElementById("errorMessage");
const adminChatStatus = document.getElementById("adminChatStatus");
const selectedClient = document.getElementById("selectedClient");
const selectedStatus = document.getElementById("selectedStatus");

// Sélection de documents
const documentInput = document.getElementById("documentInput");
const documentName = document.getElementById("documentName");

let conversationActive = null;
let envoiEnCours = false;

// Afficher le nom du fichier sélectionné
if (documentInput && documentName) {
    documentInput.addEventListener("change", function () {
        documentName.textContent =
            documentInput.files.length > 0
                ? documentInput.files[0].name
                : "Aucun document choisi";
    });
}

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

// Charger la liste des conversations
async function chargerConversations() {
    const data = await appelerAPI(
        `${API_CHAT}?action=conversations`
    );

    conversationList.replaceChildren();

    if (data.conversations.length === 0) {
        const message = document.createElement("p");
        message.className = "no-conversation";
        message.textContent = "Aucune conversation pour le moment.";
        conversationList.appendChild(message);

        adminChatStatus.textContent = "Aucune conversation";
        return;
    }

    adminChatStatus.textContent =
        data.conversations.length + " conversation(s)";

    data.conversations.forEach(conversation => {
        const bouton = document.createElement("button");

        bouton.type = "button";
        bouton.className = "conversation-item";

        if (
            conversationActive &&
            Number(conversationActive.conversation_id) ===
                Number(conversation.conversation_id)
        ) {
            bouton.classList.add("active");
        }

        const nom = document.createElement("strong");
        nom.textContent =
            (conversation.prenom || "") + " " +
            (conversation.nom || "");

        const details = document.createElement("small");
        details.textContent =
            "Conversation #" + conversation.conversation_id +
            " — " + conversation.statut;

        bouton.append(nom, details);

        bouton.addEventListener("click", () => {
            selectionnerConversation(conversation);
        });

        conversationList.appendChild(bouton);
    });
}

// Sélectionner une conversation
async function selectionnerConversation(conversation) {
    conversationActive = conversation;

    selectedClient.textContent =
        (conversation.prenom || "") + " " +
        (conversation.nom || "");

    selectedStatus.textContent =
        "Conversation #" + conversation.conversation_id +
        " — " + conversation.statut;

    const conversationOuverte = conversation.statut === "ouverte";

    messageInput.disabled = !conversationOuverte;
    sendButton.disabled = !conversationOuverte;

    if (documentInput) {
        documentInput.disabled = !conversationOuverte;
    }

    errorMessage.textContent = "";

    await chargerConversations();
    await chargerMessages();
}

// Afficher un message et ses documents
function afficherMessage(message) {
    const bloc = document.createElement("div");

    bloc.className =
        message.expediteur_id === USER_ID
            ? "message mine"
            : "message other";

    const auteur = document.createElement("div");
    auteur.className = "message-author";
    auteur.textContent =
        (message.expediteur_prenom || "") + " " +
        (message.expediteur_nom || "");

    bloc.appendChild(auteur);

    // Texte du message : certains messages contiennent seulement un fichier
    if (message.contenu) {
        const contenu = document.createElement("div");
        contenu.className = "message-content";
        contenu.textContent = message.contenu;

        bloc.appendChild(contenu);
    }

    // Documents joints au message
    if (Array.isArray(message.documents) && message.documents.length > 0) {
        const listeDocuments = document.createElement("div");
        listeDocuments.className = "message-documents";

        message.documents.forEach(documentChat => {
            const lien = document.createElement("a");

            lien.className = "document-download";
            lien.href =
                API_CHAT +
                "?action=telecharger&id=" +
                encodeURIComponent(documentChat.document_id);

            lien.textContent =
                "📎 Télécharger : " + documentChat.nom_original;

            lien.setAttribute("download", "");

            listeDocuments.appendChild(lien);
        });

        bloc.appendChild(listeDocuments);
    }

    const date = document.createElement("div");
    date.className = "message-date";
    date.textContent = message.date_envoi;

    bloc.appendChild(date);
    messagesContainer.appendChild(bloc);
}

// Charger les messages d'une conversation sélectionnée
async function chargerMessages() {
    if (!conversationActive) return;

    const id = conversationActive.conversation_id;

    const data = await appelerAPI(
        `${API_CHAT}?action=messages&id=${id}`
    );

    // Ne pas afficher les messages d'une autre conversation
    if (
        !conversationActive ||
        Number(conversationActive.conversation_id) !== Number(id)
    ) {
        return;
    }

    messagesContainer.replaceChildren();

    if (data.messages.length === 0) {
        const vide = document.createElement("p");
        vide.className = "empty-message";
        vide.textContent = "Aucun message dans cette conversation.";

        messagesContainer.appendChild(vide);
    } else {
        data.messages.forEach(afficherMessage);
    }

    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Envoyer un message, un document ou les deux
messageForm.addEventListener("submit", async event => {
    event.preventDefault();

    if (!conversationActive || envoiEnCours) return;

    const contenu = messageInput.value.trim();

    const documentChoisi =
        documentInput && documentInput.files.length > 0
            ? documentInput.files[0]
            : null;

    if (!contenu && !documentChoisi) {
        errorMessage.textContent =
            "Écris un message ou choisis un document.";
        return;
    }

    if (conversationActive.statut !== "ouverte") {
        errorMessage.textContent =
            "Cette conversation est fermée.";
        return;
    }

    envoiEnCours = true;
    sendButton.disabled = true;
    errorMessage.textContent = "";

    try {
        const donnees = new FormData();

        donnees.append(
            "conversation_id",
            conversationActive.conversation_id
        );

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

        if (documentName) {
            documentName.textContent = "Aucun document choisi";
        }

        await chargerMessages();
        await chargerConversations();

    } catch (erreur) {
        errorMessage.textContent = erreur.message;

    } finally {
        envoiEnCours = false;

        if (
            conversationActive &&
            conversationActive.statut === "ouverte"
        ) {
            sendButton.disabled = false;
        }
    }
});

// Actualiser les conversations et les messages
async function actualiserChat() {
    try {
        await chargerConversations();

        if (conversationActive) {
            await chargerMessages();
        }
    } catch (erreur) {
        errorMessage.textContent = erreur.message;
    }
}

// Démarrer le chat administrateur
async function demarrerChatAdmin() {
    try {
        await chargerConversations();

        setInterval(actualiserChat, 3000);

    } catch (erreur) {
        adminChatStatus.textContent =
            "Impossible de charger les conversations.";

        errorMessage.textContent = erreur.message;
    }
}

demarrerChatAdmin();
