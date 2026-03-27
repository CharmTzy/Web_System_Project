(() => {
  const body = document.body;
  const toggleButton = document.querySelector("[data-admin-sidebar-toggle]");
  const closeButtons = document.querySelectorAll("[data-admin-sidebar-close]");

  if (!body || !toggleButton || !body.classList.contains("admin-body")) {
    return;
  }

  const collapsedClass = "admin-sidebar-collapsed";
  const openClass = "admin-sidebar-open";
  const storageKey = "novamarket-admin-sidebar-collapsed";
  const mobileQuery = window.matchMedia("(max-width: 991.98px)");

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
    mobileQuery.addEventListener("change", syncLayoutMode);
  } else if (typeof mobileQuery.addListener === "function") {
    mobileQuery.addListener(syncLayoutMode);
  }

  syncLayoutMode();
})();
