(() => {
  const root = document.querySelector("[data-chat-app]");

  if (!root) {
    return;
  }

  const role = root.dataset.chatRole || "customer";
  const apiUrl = root.dataset.chatApi || "/api/chat.php";
  const socketUrl = root.dataset.chatWebsocketUrl || "";
  const listEl = root.querySelector("[data-chat-conversation-list]");
  const threadEl = root.querySelector("[data-chat-thread]");
  const statusEl = root.querySelector("[data-chat-status]");
  const unreadTotalEl = root.querySelector("[data-chat-unread-total]");
  const bootstrapEl = document.querySelector("[data-chat-bootstrap]");
  const storefront = window.Storefront || {};
  const headerState = (window.__novaHeaderState = window.__novaHeaderState || {
    cartCount: 0,
    notificationCount: 0,
  });

  let state = parseBootstrap();
  let activeConversationId = Number(state.active_conversation_id || 0) || null;
  let socket = null;
  let reconnectTimer = null;

  render();
  connectSocket();

  root.addEventListener("click", (event) => {
    const conversationButton = event.target.closest("[data-chat-conversation-id]");

    if (conversationButton) {
      const conversationId = Number(conversationButton.dataset.chatConversationId || 0);

      if (conversationId > 0 && conversationId !== activeConversationId) {
        activeConversationId = conversationId;
        refreshChat(activeConversationId);
      }

      return;
    }
  });

  root.addEventListener("submit", async (event) => {
    const form = event.target.closest("[data-chat-form]");

    if (!form) {
      return;
    }

    event.preventDefault();

    const textarea = form.querySelector("[name=\"body\"]");
    const submitButton = form.querySelector("button[type=\"submit\"]");
    const body = textarea ? textarea.value.trim() : "";

    if (!textarea || !submitButton || !activeConversationId || body === "") {
      return;
    }

    submitButton.setAttribute("disabled", "disabled");

    try {
      if (socket && socket.readyState === window.WebSocket.OPEN) {
        socket.send(JSON.stringify({
          type: "send_message",
          conversationId: activeConversationId,
          body,
        }));
        textarea.value = "";
      } else {
        await postChatAction({
          action: "send_message",
          conversation_id: String(activeConversationId),
          body,
        });
        textarea.value = "";
        await refreshChat(activeConversationId, false);
      }
    } catch (error) {
      storefront.flashMessage?.(error.message || "Unable to send your message.", "error");
    } finally {
      submitButton.removeAttribute("disabled");
      textarea.focus();
    }
  });

  async function refreshChat(conversationId = activeConversationId, announce = true) {
    try {
      setStatus("Loading conversations...");
      const search = conversationId ? `?conversation_id=${encodeURIComponent(conversationId)}` : "";
      const response = await fetch(`${apiUrl}${search}`, {
        headers: {
          Accept: "application/json",
        },
        credentials: "same-origin",
      });
      const payload = await response.json();

      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || "Unable to load chat right now.");
      }

      state = payload;
      activeConversationId = Number(payload.active_conversation_id || 0) || null;
      render();
      subscribeToConversations();

      if (announce) {
        storefront.flashMessage?.("Chat updated.");
      }
    } catch (error) {
      setStatus(error.message || "Unable to load chat right now.", true);
    }
  }

  async function postChatAction(fields) {
    const formData = new FormData();
    formData.set("csrf_token", document.querySelector('meta[name="csrf-token"]')?.content || "");

    Object.entries(fields).forEach(([key, value]) => {
      formData.set(key, value);
    });

    const response = await fetch(apiUrl, {
      method: "POST",
      headers: {
        Accept: "application/json",
      },
      credentials: "same-origin",
      body: formData,
    });
    const payload = await response.json();

    if (!response.ok || !payload.ok) {
      throw new Error(payload.message || "Unable to complete that chat action.");
    }

    if (typeof payload.unread_total === "number") {
      headerState.notificationCount = payload.unread_total;
    }

    return payload;
  }

  function connectSocket() {
    if (!socketUrl || !("WebSocket" in window)) {
      setStatus("Live updates unavailable. Messages still work after refresh.");
      return;
    }

    setStatus("Connecting to live chat...");

    try {
      socket = new window.WebSocket(socketUrl);
    } catch (error) {
      setStatus("Live chat could not connect. Refresh to retry.", true);
      return;
    }

    socket.addEventListener("open", () => {
      setStatus("Live updates connected.");
      subscribeToConversations();

      if (activeConversationId) {
        socket.send(JSON.stringify({
          type: "mark_read",
          conversationId: activeConversationId,
        }));
      }
    });

    socket.addEventListener("message", async (event) => {
      const payload = parseJson(event.data);

      if (!payload || !payload.type) {
        return;
      }

      if (payload.type === "hello") {
        if (typeof payload.unreadTotal === "number") {
          headerState.notificationCount = payload.unreadTotal;
        }
        renderHeaderCounts();
        return;
      }

      if (payload.type === "message_created") {
        await refreshChat(activeConversationId || Number(payload.conversation_id || 0), false);
        renderHeaderCounts();
        return;
      }

      if (payload.type === "read_receipt") {
        if (typeof payload.unreadTotal === "number") {
          headerState.notificationCount = payload.unreadTotal;
          renderHeaderCounts();
        }
        return;
      }

      if (payload.type === "error") {
        storefront.flashMessage?.(payload.message || "Chat connection error.", "error");
      }
    });

    socket.addEventListener("close", () => {
      setStatus("Reconnecting to live chat...");
      scheduleReconnect();
    });

    socket.addEventListener("error", () => {
      setStatus("Live chat ran into a connection issue.", true);
    });
  }

  function scheduleReconnect() {
    window.clearTimeout(reconnectTimer);
    reconnectTimer = window.setTimeout(() => {
      connectSocket();
    }, 2500);
  }

  function subscribeToConversations() {
    if (!socket || socket.readyState !== window.WebSocket.OPEN) {
      return;
    }

    const conversationIds = (state.conversations || [])
      .map((conversation) => Number(conversation.id || 0))
      .filter((id) => id > 0);

    if (conversationIds.length > 0) {
      socket.send(JSON.stringify({
        type: "subscribe_many",
        conversationIds,
      }));
    }

    if (activeConversationId) {
      socket.send(JSON.stringify({
        type: "mark_read",
        conversationId: activeConversationId,
      }));
    }
  }

  function render() {
    renderConversationList();
    renderThread();

    if (unreadTotalEl) {
      unreadTotalEl.textContent = String(state.unread_total || 0);
    }

    headerState.notificationCount = Number(state.unread_total || 0);
    renderHeaderCounts();
  }

  function renderConversationList() {
    if (!listEl) {
      return;
    }

    const conversations = state.conversations || [];

    if (conversations.length === 0) {
      listEl.innerHTML = `
        <section class="empty-state empty-state--compact">
          <h3>${escapeHtml(emptyListTitle())}</h3>
          <p>${escapeHtml(emptyListCopy())}</p>
        </section>
      `;
      return;
    }

    listEl.innerHTML = conversations
      .map((conversation) => {
        const isActive = Number(conversation.id) === Number(activeConversationId || 0);
        const product = conversation.product;

        return `
          <button class="chat-conversation-item${isActive ? " is-active" : ""}" type="button" data-chat-conversation-id="${escapeHtml(String(conversation.id))}">
            <div class="chat-conversation-item__header">
              <strong>${escapeHtml(conversation.title || "Conversation")}</strong>
              <span>${escapeHtml(conversation.updated_label || "")}</span>
            </div>
            <p class="chat-conversation-item__subtitle">${escapeHtml(conversation.subtitle || "")}</p>
            <p class="chat-conversation-item__preview">${escapeHtml(conversation.preview || "")}</p>
            <div class="chat-conversation-item__footer">
              ${product ? `<span class="pill-badge pill-badge--soft">${escapeHtml(product.name || "Product")}</span>` : `<span class="pill-badge pill-badge--soft">${escapeHtml(conversation.subject || "Open chat")}</span>`}
              ${conversation.unread_count > 0 ? `<span class="chat-conversation-item__count">${escapeHtml(String(conversation.unread_count))}</span>` : ""}
            </div>
          </button>
        `;
      })
      .join("");
  }

  function renderThread() {
    if (!threadEl) {
      return;
    }

    const conversation = state.active_conversation;
    const messages = state.messages || [];

    if (!conversation) {
      threadEl.innerHTML = `
        <section class="empty-state empty-state--compact chat-thread__empty">
          <h3>${escapeHtml(emptyThreadTitle())}</h3>
          <p>${escapeHtml(emptyThreadCopy())}</p>
        </section>
      `;
      return;
    }

    const product = conversation.product;

    threadEl.innerHTML = `
      <header class="chat-thread__header">
        <div>
          <span class="hero-section__eyebrow">${escapeHtml(conversation.subtitle || "Live conversation")}</span>
          <h2>${escapeHtml(conversation.title || "Conversation")}</h2>
          <p>${escapeHtml(conversation.subject || "")}</p>
          ${product ? `<a class="chat-thread__product-link" href="${escapeHtml(product.url || "#")}">View ${escapeHtml(product.name || "product")}</a>` : ""}
        </div>
        <span class="pill-badge pill-badge--soft">${escapeHtml(conversation.status || "open")}</span>
      </header>
      <div class="chat-thread__messages" data-chat-message-list>
        ${messages.length > 0 ? messages.map(renderMessage).join("") : `
          <section class="empty-state empty-state--compact chat-thread__empty">
            <h3>No messages yet.</h3>
            <p>Send the first message to get this conversation started.</p>
          </section>
        `}
      </div>
      <form class="chat-thread__composer" data-chat-form>
        <textarea class="form-control" name="body" rows="3" maxlength="2000" placeholder="Type your message here..." required></textarea>
        <div class="chat-thread__composer-actions">
          <span>Messages update in real time while this tab stays open.</span>
          <button class="btn btn-brand" type="submit">Send message</button>
        </div>
      </form>
    `;

    const messageList = threadEl.querySelector("[data-chat-message-list]");

    if (messageList) {
      messageList.scrollTop = messageList.scrollHeight;
    }
  }

  function renderMessage(message) {
    return `
      <article class="chat-message${message.is_own ? " is-own" : ""}">
        <div class="chat-message__bubble">
          <div class="chat-message__meta">
            <strong>${escapeHtml(message.is_own ? "You" : message.sender_label || message.sender_name || "NovaMarket")}</strong>
            <span>${escapeHtml(message.created_label || "")}</span>
          </div>
          <p>${escapeHtml(message.body || "").replace(/\n/g, "<br>")}</p>
        </div>
      </article>
    `;
  }

  function renderHeaderCounts() {
    document.querySelectorAll("[data-notification-count]").forEach((node) => {
      node.textContent = String(headerState.notificationCount ?? 0);
    });
  }

  function setStatus(message, isError = false) {
    if (!statusEl) {
      return;
    }

    statusEl.textContent = message;
    statusEl.dataset.tone = isError ? "error" : "info";
  }

  function parseBootstrap() {
    if (!bootstrapEl) {
      return {
        conversations: [],
        active_conversation: null,
        active_conversation_id: null,
        messages: [],
        unread_total: 0,
      };
    }

    return parseJson(bootstrapEl.textContent) || {
      conversations: [],
      active_conversation: null,
      active_conversation_id: null,
      messages: [],
      unread_total: 0,
    };
  }

  function parseJson(value) {
    try {
      return JSON.parse(value);
    } catch (error) {
      return null;
    }
  }

  function emptyListTitle() {
    if (role === "seller") return "No customer conversations yet.";
    if (role === "admin") return "No active conversations yet.";
    return "No chats yet.";
  }

  function emptyListCopy() {
    if (role === "seller") return "Customer chats about your products will appear here.";
    if (role === "admin") return "Customer and seller chats will appear here for support monitoring.";
    return "Start a conversation from any product page to message a seller.";
  }

  function emptyThreadTitle() {
    if (role === "seller") return "Select a customer conversation.";
    if (role === "admin") return "Choose a conversation to monitor.";
    return "Choose a conversation to continue chatting.";
  }

  function emptyThreadCopy() {
    if (role === "seller") return "Open any conversation on the left to reply to customers in real time.";
    if (role === "admin") return "Open a live conversation to read messages and step in with support.";
    return "Pick a seller conversation from the left, or open a product and start chatting.";
  }

  function escapeHtml(value) {
    const div = document.createElement("div");
    div.appendChild(document.createTextNode(String(value ?? "")));
    return div.innerHTML;
  }
})();
