(() => {
  const body = document.body;
  const toggleButton = document.querySelector("[data-admin-sidebar-toggle]");
  const closeButtons = document.querySelectorAll("[data-admin-sidebar-close]");

  if (!body || !toggleButton) {
    return;
  }

  const isAdminBody = body.classList.contains("admin-body");
  const isSellerBody = body.classList.contains("seller-body");

  if (!isAdminBody && !isSellerBody) {
    return;
  }

  const collapsedClass = "admin-sidebar-collapsed";
  const openClass = "admin-sidebar-open";
  const syncingClass = "admin-layout-syncing";
  const storageKey = isSellerBody
    ? "novamarket-seller-sidebar-collapsed"
    : "novamarket-admin-sidebar-collapsed";
  const mobileQuery = window.matchMedia("(max-width: 991.98px)");
  let syncFrame = 0;
  let syncTimeout = 0;

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
})();
