(() => {
  const body = document.body;
  const toggleButton = document.querySelector("[data-admin-sidebar-toggle]");
  const closeButtons = document.querySelectorAll("[data-admin-sidebar-close]");

  if (!body || !toggleButton) {
    return;
  }

  const isAdminBody = body.classList.contains("admin-body");
  const isSellerBody = body.classList.contains("seller-body");
  const headerState = (window.__novaHeaderState = window.__novaHeaderState || {
    cartCount: 0,
    notificationCount: 0,
  });

  if (!isAdminBody && !isSellerBody) {
    return;
  }

  const collapsedClass = "admin-sidebar-collapsed";
  const openClass = "admin-sidebar-open";
  const syncingClass = "admin-layout-syncing";
  const storageKey = isSellerBody
    ? "novamarket-seller-sidebar-collapsed"
    : "novamarket-admin-sidebar-collapsed";
  const notificationPollMs = 15000;
  const mobileQuery = window.matchMedia("(max-width: 991.98px)");
  let syncFrame = 0;
  let syncTimeout = 0;
  let notificationPollTimer = 0;

  const setExpandedState = () => {
    const isExpanded = mobileQuery.matches
      ? body.classList.contains(openClass)
      : !body.classList.contains(collapsedClass);

    toggleButton.setAttribute("aria-expanded", isExpanded ? "true" : "false");
  };

  const readCollapsedPreference = () => {
    try {
      return window.localStorage.getItem(storageKey) === "true";
    } catch (error) {
      return false;
    }
  };

  const writeCollapsedPreference = (collapsed) => {
    try {
      window.localStorage.setItem(storageKey, collapsed ? "true" : "false");
    } catch (error) {
      // Ignore storage errors and keep the in-memory UI state.
    }
  };

  const syncLayoutMode = () => {
    if (mobileQuery.matches) {
      body.classList.remove(collapsedClass);
    } else {
      body.classList.remove(openClass);
      body.classList.toggle(collapsedClass, readCollapsedPreference());
    }

    setExpandedState();
  };

  const emitLayoutSync = () => {
    document.dispatchEvent(
      new CustomEvent("novamarket:admin-layout-sync", {
        detail: {
          mobile: mobileQuery.matches,
        },
      }),
    );
  };

  const runViewportSync = () => {
    window.cancelAnimationFrame(syncFrame);
    body.classList.add(syncingClass);
    syncFrame = window.requestAnimationFrame(() => {
      syncFrame = window.requestAnimationFrame(() => {
        syncLayoutMode();
        emitLayoutSync();
        window.requestAnimationFrame(() => {
          body.classList.remove(syncingClass);
        });
      });
    });
  };

  const scheduleViewportSync = () => {
    window.clearTimeout(syncTimeout);
    syncTimeout = window.setTimeout(runViewportSync, 40);
  };

  const updateNotificationCount = (count) => {
    const normalizedCount = Number.isFinite(Number(count)) ? Number(count) : 0;
    headerState.notificationCount = normalizedCount;

    document.querySelectorAll("[data-notification-count]").forEach((node) => {
      node.textContent = String(normalizedCount);
    });

    document.querySelectorAll("[data-notification-badge]").forEach((node) => {
      node.hidden = normalizedCount < 1;
    });
  };

  const syncNotificationCount = async () => {
    try {
      const response = await fetch("/api/session.php", {
        headers: {
          Accept: "application/json",
        },
        credentials: "same-origin",
      });
      const payload = await response.json();

      if (!response.ok || !payload.ok || !payload.logged_in) {
        updateNotificationCount(0);
        return;
      }

      updateNotificationCount(payload.notification_count ?? 0);
    } catch (error) {
      // Keep the last known unread count if a refresh attempt fails.
    }
  };

  const startNotificationPolling = () => {
    if (notificationPollTimer || !document.querySelector("[data-notification-count]")) {
      return;
    }

    syncNotificationCount();
    notificationPollTimer = window.setInterval(syncNotificationCount, notificationPollMs);

    document.addEventListener("visibilitychange", () => {
      if (!document.hidden) {
        syncNotificationCount();
      }
    });

    window.addEventListener("focus", syncNotificationCount);
  };

  toggleButton.addEventListener("click", () => {
    if (mobileQuery.matches) {
      body.classList.toggle(openClass);
    } else {
      const collapsed = body.classList.toggle(collapsedClass);
      writeCollapsedPreference(collapsed);
    }

    setExpandedState();
  });

  closeButtons.forEach((button) => {
    button.addEventListener("click", () => {
      body.classList.remove(openClass);
      setExpandedState();
    });
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && body.classList.contains(openClass)) {
      body.classList.remove(openClass);
      setExpandedState();
    }
  });

  if (typeof mobileQuery.addEventListener === "function") {
    mobileQuery.addEventListener("change", scheduleViewportSync);
  } else if (typeof mobileQuery.addListener === "function") {
    mobileQuery.addListener(scheduleViewportSync);
  }

  window.addEventListener("resize", scheduleViewportSync, { passive: true });
  window.addEventListener("orientationchange", scheduleViewportSync);
  window.addEventListener("pageshow", scheduleViewportSync);

  if (window.visualViewport) {
    window.visualViewport.addEventListener("resize", scheduleViewportSync, {
      passive: true,
    });
  }

  scheduleViewportSync();
  startNotificationPolling();
})();
