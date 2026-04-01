(() => {
  const page = document.querySelector("[data-catalog-page]");

  if (!page) {
    return;
  }

  const headerSearchForm = document.getElementById("header-search-form");
  const headerSearchInput = document.getElementById("header-search-input");
  const autocompletePanel = document.querySelector("[data-search-autocomplete]");
  const autocompleteGrid = document.querySelector("[data-search-autocomplete-grid]");
  const recentSection = document.querySelector("[data-search-recent-section]");
  const recentList = document.querySelector("[data-search-recent-list]");
  const suggestionsSection = document.querySelector("[data-search-suggestions-section]");
  const suggestionsList = document.querySelector("[data-search-suggestions-list]");
  const suggestionsTitle = document.querySelector("[data-search-suggestions-title]");
  const clearRecentButton = document.querySelector("[data-search-clear-recent]");
  const sortSelect = document.querySelector("[data-sort-select]");
  const results = document.getElementById("catalog-results");
  const resultsCount = document.querySelector("[data-results-count]");
  const activeFilterCount = document.querySelector("[data-active-filter-count]");
  const shortcutGrid = document.querySelector("[data-shortcut-grid]");
  const featuredSection = document.querySelector("[data-featured-section]");
  const featuredStrip = document.querySelector("[data-featured-strip]");
  const homeSections = document.querySelectorAll("[data-home-discovery]");
  const sidebarHost = document.querySelector("[data-catalog-sidebar-host]");
  const mobileFilterBody = document.querySelector("[data-mobile-filter-body]");
  const mobileFilterButton = document.querySelector("[data-mobile-filter-button]");
  const shell = document.getElementById("catalog-shell");
  const main = document.getElementById("catalog-main");
  const productsShown = document.querySelector("[data-products-shown]");
  const categoriesTotal = document.querySelector("[data-categories-total]");
  const catalogTitle = document.querySelector("[data-catalog-title]");
  const catalogCopy = document.querySelector("[data-catalog-copy]");
  const RECENT_SEARCHES_KEY = "novamarket:recent-searches";
  const MAX_RECENT_SEARCHES = 6;
  const MAX_AUTOCOMPLETE_ITEMS = 7;
  let searchTimer = null;
  let autocompleteItems = [];
  let activeAutocompleteIndex = -1;
  let latestSidebarHtml = "";
  let latestShowSidebarFilters = false;
  let resizeFrame = null;

  const isMobileViewport = () => window.matchMedia("(max-width: 767.98px)").matches;
  const normalizeSearchTerm = (value) => value.trim().replace(/\s+/g, " ");
  const escapeHtml = (value) => String(value).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#39;");

  const readRecentSearches = () => {
    try {
      const stored = JSON.parse(window.localStorage.getItem(RECENT_SEARCHES_KEY) || "[]");

      if (!Array.isArray(stored)) {
        return [];
      }

      return stored
        .map((entry) => normalizeSearchTerm(String(entry || "")))
        .filter(Boolean)
        .slice(0, MAX_RECENT_SEARCHES);
    } catch (error) {
      return [];
    }
  };

  const writeRecentSearches = (entries) => {
    try {
      window.localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(entries));
    } catch (error) {
      // Ignore storage quota or privacy-mode failures.
    }
  };

  const saveRecentSearch = (value) => {
    const normalized = normalizeSearchTerm(value);

    if (!normalized) {
      return;
    }

    const nextEntries = [normalized, ...readRecentSearches().filter((entry) => entry.toLowerCase() !== normalized.toLowerCase())].slice(0, MAX_RECENT_SEARCHES);

    writeRecentSearches(nextEntries);
  };

  const clearRecentSearches = () => {
    try {
      window.localStorage.removeItem(RECENT_SEARCHES_KEY);
    } catch (error) {
      // Ignore storage quota or privacy-mode failures.
    }
  };

  const buildAutocompleteIndex = (payload) => {
    const nextItems = [];
    const seen = new Set();
    const addSuggestion = (label, meta, type, value = label) => {
      const normalizedLabel = normalizeSearchTerm(String(label || ""));
      const normalizedMeta = normalizeSearchTerm(String(meta || ""));
      const normalizedValue = normalizeSearchTerm(String(value || label || ""));

      if (!normalizedLabel || !normalizedValue) {
        return;
      }

      const key = `${type}:${normalizedLabel.toLowerCase()}`;

      if (seen.has(key)) {
        return;
      }

      seen.add(key);
      nextItems.push({
        type,
        label: normalizedLabel,
        meta: normalizedMeta,
        value: normalizedValue,
        metaParts: normalizedMeta ? normalizedMeta.split(" · ").map((part) => part.toLowerCase()) : [],
        searchText: `${normalizedLabel} ${normalizedMeta}`.trim().toLowerCase(),
      });
    };

    (payload.search_suggestions?.products || []).forEach((product) => {
      addSuggestion(product.name, [product.category, product.seller].filter(Boolean).join(" · "), "product");
    });

    (payload.search_suggestions?.categories || []).forEach((category) => {
      const count = Number(category.product_count || 0);
      addSuggestion(category.name, `${count} ${count === 1 ? "product" : "products"}`, "category");
    });

    autocompleteItems = nextItems;
  };

  const buildRelatedSuggestions = (recentEntries) => {
    const exactRecentMatches = recentEntries.map((entry) => autocompleteItems.find((item) => item.label.toLowerCase() === entry.toLowerCase())).filter(Boolean);

    return autocompleteItems
      .map((item) => {
        const score = recentEntries.reduce((total, entry, index) => {
          const normalizedEntry = entry.toLowerCase();

          if (!normalizedEntry) {
            return total;
          }

          let nextTotal = total;

          if (item.searchText.includes(normalizedEntry)) {
            nextTotal += 12 - index;
          }

          normalizedEntry.split(/\s+/).forEach((token) => {
            if (token.length >= 3 && item.searchText.includes(token)) {
              nextTotal += 2;
            }
          });

          return nextTotal;
        }, 0);

        const relatedMetaScore = exactRecentMatches.reduce((total, matchedItem, index) => {
          if (!matchedItem || item.label === matchedItem.label) {
            return total;
          }

          const sharedMeta = item.metaParts.filter((part) => matchedItem.metaParts.includes(part));

          return total + (sharedMeta.length > 0 ? (8 - index) * sharedMeta.length : 0);
        }, 0);

        return { item, score: score + relatedMetaScore };
      })
      .filter((entry) => entry.score > 0)
      .sort((left, right) => right.score - left.score || left.item.label.localeCompare(right.item.label))
      .slice(0, MAX_AUTOCOMPLETE_ITEMS)
      .map((entry) => entry.item);
  };

  const getAutocompleteButtons = () => (autocompletePanel ? [...autocompletePanel.querySelectorAll("[data-search-option]")] : []);

  const updateActiveAutocomplete = () => {
    const buttons = getAutocompleteButtons();

    buttons.forEach((button, index) => {
      button.classList.toggle("is-active", index === activeAutocompleteIndex);
    });

    if (activeAutocompleteIndex >= 0 && buttons[activeAutocompleteIndex]) {
      buttons[activeAutocompleteIndex].scrollIntoView({ block: "nearest" });
    }
  };

  const renderAutocompleteItem = (item, icon, metaOverride = "") => `
    <button
      class="search-autocomplete__item"
      type="button"
      data-search-option
      data-search-value="${escapeHtml(item.value)}"
    >
      <span class="search-autocomplete__icon" aria-hidden="true">${icon}</span>
      <span class="search-autocomplete__item-copy">
        <span class="search-autocomplete__item-label">${escapeHtml(item.label)}</span>
        ${metaOverride || item.meta ? `<span class="search-autocomplete__item-meta">${escapeHtml(metaOverride || item.meta)}</span>` : ""}
      </span>
    </button>
  `;

  const renderAutocomplete = () => {
    if (!autocompletePanel || !autocompleteGrid || !recentSection || !recentList || !suggestionsSection || !suggestionsList || !suggestionsTitle || !clearRecentButton || !headerSearchInput) {
      return;
    }

    const query = normalizeSearchTerm(headerSearchInput.value || "");
    const recentEntries = readRecentSearches();
    const recentItems = recentEntries.map((entry) => ({
      label: entry,
      meta: "",
      value: entry,
      type: "recent",
    }));

    let singleColumn = false;

    if (query === "") {
      const relatedItems = recentItems.length > 0 ? buildRelatedSuggestions(recentEntries) : [];
      const curatedItems = (relatedItems.length > 0 ? relatedItems : autocompleteItems).slice(0, MAX_AUTOCOMPLETE_ITEMS);

      recentSection.hidden = recentItems.length === 0;
      clearRecentButton.hidden = recentItems.length === 0;
      suggestionsSection.hidden = false;
      suggestionsTitle.textContent = recentItems.length > 0 ? "Related to recent searches" : "Suggested for you";
      recentList.innerHTML = recentItems.length > 0 ? recentItems.map((item) => renderAutocompleteItem(item, "&#8634;")).join("") : "";
      suggestionsList.innerHTML = curatedItems.length > 0 ? curatedItems.map((item) => renderAutocompleteItem(item, item.type === "category" ? "#" : "&#8981;")).join("") : '<div class="search-autocomplete__empty">Start typing to explore products and categories.</div>';

      singleColumn = recentItems.length === 0;
    } else {
      const loweredQuery = query.toLowerCase();
      const matchingItems = autocompleteItems.filter((item) => item.searchText.includes(loweredQuery)).slice(0, MAX_AUTOCOMPLETE_ITEMS);

      recentSection.hidden = true;
      clearRecentButton.hidden = true;
      suggestionsSection.hidden = false;
      suggestionsTitle.textContent = "Suggestions";
      suggestionsList.innerHTML = matchingItems.length > 0 ? matchingItems.map((item) => renderAutocompleteItem(item, item.type === "category" ? "#" : "&#8981;")).join("") : `<div class="search-autocomplete__empty">Press Enter to search for "${escapeHtml(query)}".</div>`;

      singleColumn = true;
    }

    autocompleteGrid.classList.toggle("search-autocomplete__grid--single", singleColumn);
    activeAutocompleteIndex = -1;
    updateActiveAutocomplete();
  };

  const showAutocomplete = () => {
    if (!autocompletePanel || !headerSearchInput || !headerSearchForm) {
      return;
    }

    renderAutocomplete();
    autocompletePanel.hidden = false;
    headerSearchForm.classList.add("is-autocomplete-open");
    headerSearchInput.setAttribute("aria-expanded", "true");
  };

  const hideAutocomplete = () => {
    if (!autocompletePanel || !headerSearchInput || !headerSearchForm) {
      return;
    }

    autocompletePanel.hidden = true;
    headerSearchForm.classList.remove("is-autocomplete-open");
    headerSearchInput.setAttribute("aria-expanded", "false");
    activeAutocompleteIndex = -1;
  };

  const hideOffcanvas = (element) => {
    if (!element || !window.bootstrap?.Offcanvas) {
      return;
    }

    const drawer = element.closest(".offcanvas");

    if (!drawer) {
      return;
    }

    window.bootstrap.Offcanvas.getOrCreateInstance(drawer).hide();
  };

  const syncSidebarHosts = () => {
    if (!shell || !main) {
      return;
    }

    const mobileViewport = isMobileViewport();
    const sidebarHtml = latestShowSidebarFilters ? latestSidebarHtml : "";

    if (sidebarHost) {
      sidebarHost.innerHTML = !mobileViewport ? sidebarHtml : "";
    }

    if (mobileFilterBody) {
      mobileFilterBody.innerHTML = mobileViewport ? sidebarHtml : "";
    }

    if (mobileFilterButton) {
      mobileFilterButton.hidden = !latestShowSidebarFilters;
    }

    shell.classList.toggle("catalog-shell--with-sidebar", latestShowSidebarFilters && !mobileViewport);
    shell.classList.toggle("catalog-shell--without-sidebar", !latestShowSidebarFilters || mobileViewport);
    main.classList.toggle("catalog-main--full", !latestShowSidebarFilters || mobileViewport);

    if ((!latestShowSidebarFilters || !mobileViewport) && mobileFilterBody) {
      hideOffcanvas(mobileFilterBody);
    }
  };

  const cleanParams = (params) => {
    [...params.entries()].forEach(([key, value]) => {
      if (value === "") {
        params.delete(key);
      }
    });

    if (params.get("sort") === "featured") {
      params.delete("sort");
    }

    if (params.get("in_stock") !== "1") {
      params.delete("in_stock");
    }

    return params;
  };

  const buildCatalogHref = (params, includeHash = false) => {
    const query = params.toString();
    return `/${query ? `?${query}` : ""}${includeHash ? "#catalog-feed" : ""}`;
  };

  const submitHeaderSearch = async (searchValue) => {
    const params = new URLSearchParams(window.location.search);
    const value = normalizeSearchTerm(searchValue || "");

    if (value !== "") {
      params.set("search", value);
      saveRecentSearch(value);
    } else {
      params.delete("search");
    }

    hideAutocomplete();
    await loadStorefront(params, { updateHistory: true });

    window.requestAnimationFrame(() => {
      const catalogSection = document.getElementById("catalog-feed");

      if (!catalogSection) {
        return;
      }

      catalogSection.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  };

  const loadStorefront = async (params = new URLSearchParams(window.location.search), options = {}) => {
    const query = cleanParams(params).toString();

    results.classList.add("is-loading");

    try {
      const response = await fetch(`/api/products.php${query ? `?${query}` : ""}`, {
        headers: {
          Accept: "application/json",
        },
      });

      const payload = await response.json();

      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || "Unable to load products.");
      }

      if (headerSearchInput) {
        headerSearchInput.value = payload.filters.search || "";
      }

      buildAutocompleteIndex(payload);

      if (document.activeElement === headerSearchInput || (autocompletePanel && !autocompletePanel.hidden)) {
        renderAutocomplete();
      }

      if (sortSelect) {
        sortSelect.value = payload.filters.sort || "featured";
      }

      if (productsShown) {
        productsShown.textContent = String(payload.metrics?.products_shown ?? 0);
      }

      if (categoriesTotal) {
        categoriesTotal.textContent = String(payload.metrics?.categories ?? 0);
      }

      if (shortcutGrid) {
        shortcutGrid.innerHTML = payload.shortcuts_html || "";
      }

      if (featuredSection && featuredStrip) {
        featuredStrip.innerHTML = payload.featured_html || "";
        featuredSection.hidden = Boolean(payload.show_sidebar_filters) || (payload.featured_html || "").trim() === "";
      }

      homeSections.forEach((section) => {
        if (section !== featuredSection) {
          section.hidden = Boolean(payload.show_sidebar_filters);
        }
      });

      latestSidebarHtml = payload.sidebar_html || "";
      latestShowSidebarFilters = Boolean(payload.show_sidebar_filters);
      syncSidebarHosts();

      results.innerHTML = payload.results_html || "";

      if (catalogTitle) {
        if (payload.filters.search) {
          catalogTitle.textContent = `Results for "${payload.filters.search}"`;
        } else if (payload.filters.category) {
          catalogTitle.textContent = "Filtered products";
        } else {
          catalogTitle.textContent = "Popular gifts right now";
        }
      }

      if (catalogCopy) {
        if (payload.filters.search || payload.filters.category) {
          catalogCopy.textContent = "Browse the products that match your search and category filters.";
        } else {
          catalogCopy.textContent = "Fresh picks from independent sellers and trending home finds.";
        }
      }

      if (resultsCount) {
        resultsCount.textContent = payload.summary || "0 products available";
      }

      if (activeFilterCount) {
        activeFilterCount.textContent = String(payload.active_filter_count ?? 0);
      }

      if (options.updateHistory) {
        const nextUrl = new URL(buildCatalogHref(cleanParams(new URLSearchParams(params)), options.includeHash !== false), window.location.origin);
        window.history.pushState({}, "", nextUrl);
      }
    } catch (error) {
      window.Storefront?.flashMessage(error.message || "Unable to load products.", "error");
    } finally {
      results.classList.remove("is-loading");
    }
  };

  const collectSidebarParams = () => {
    const params = new URLSearchParams(window.location.search);
    const form = sidebarHost?.querySelector("#catalog-filter-form") || mobileFilterBody?.querySelector("#catalog-filter-form");

    if (form) {
      const formParams = new URLSearchParams(new FormData(form));
      params.forEach((_, key) => params.delete(key));
      formParams.forEach((value, key) => params.set(key, value));
    }

    if (sortSelect) {
      params.set("sort", sortSelect.value || "featured");
    }

    return cleanParams(params);
  };

  const loadFromHref = (href) => {
    const nextUrl = new URL(href, window.location.origin);
    const params = new URLSearchParams(nextUrl.search);
    loadStorefront(params, { updateHistory: true, includeHash: nextUrl.hash === "#catalog-feed" });
  };

  headerSearchForm?.addEventListener("submit", (event) => {
    event.preventDefault();
    void submitHeaderSearch(headerSearchInput?.value || "");
  });

  headerSearchInput?.addEventListener("focus", showAutocomplete);
  headerSearchInput?.addEventListener("click", showAutocomplete);
  headerSearchInput?.addEventListener("input", renderAutocomplete);
  headerSearchInput?.addEventListener("keydown", (event) => {
    if (!autocompletePanel || autocompletePanel.hidden) {
      return;
    }

    const buttons = getAutocompleteButtons();

    if (event.key === "ArrowDown") {
      event.preventDefault();

      if (buttons.length === 0) {
        return;
      }

      activeAutocompleteIndex = activeAutocompleteIndex < buttons.length - 1 ? activeAutocompleteIndex + 1 : 0;
      updateActiveAutocomplete();
    } else if (event.key === "ArrowUp") {
      event.preventDefault();

      if (buttons.length === 0) {
        return;
      }

      activeAutocompleteIndex = activeAutocompleteIndex > 0 ? activeAutocompleteIndex - 1 : buttons.length - 1;
      updateActiveAutocomplete();
    } else if (event.key === "Enter" && activeAutocompleteIndex >= 0 && buttons[activeAutocompleteIndex]) {
      event.preventDefault();
      buttons[activeAutocompleteIndex].click();
    } else if (event.key === "Escape") {
      hideAutocomplete();
    }
  });

  clearRecentButton?.addEventListener("click", () => {
    clearRecentSearches();
    renderAutocomplete();
  });

  sortSelect?.addEventListener("change", () => {
    loadStorefront(collectSidebarParams(), { updateHistory: true });
  });

  const handleFilterSubmit = (event) => {
    const form = event.target.closest("#catalog-filter-form");

    if (!form) {
      return;
    }

    event.preventDefault();
    loadStorefront(collectSidebarParams(), { updateHistory: true });
    hideOffcanvas(form);
  };

  const handleFilterChange = (event) => {
    const target = event.target;

    if (!(target instanceof HTMLElement) || target.getAttribute("name") === "search") {
      return;
    }

    loadStorefront(collectSidebarParams(), { updateHistory: true });
  };

  const handleFilterInput = (event) => {
    const target = event.target;

    if (!(target instanceof HTMLInputElement) || target.name !== "search") {
      return;
    }

    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
      loadStorefront(collectSidebarParams(), { updateHistory: true });
    }, 280);
  };

  sidebarHost?.addEventListener("submit", handleFilterSubmit);
  mobileFilterBody?.addEventListener("submit", handleFilterSubmit);
  sidebarHost?.addEventListener("change", handleFilterChange);
  mobileFilterBody?.addEventListener("change", handleFilterChange);
  sidebarHost?.addEventListener("input", handleFilterInput);
  mobileFilterBody?.addEventListener("input", handleFilterInput);

  document.addEventListener("click", (event) => {
    const searchOption = event.target.closest("[data-search-option]");

    if (searchOption) {
      const value = normalizeSearchTerm(searchOption.dataset.searchValue || "");

      if (headerSearchInput && value !== "") {
        headerSearchInput.value = value;
        void submitHeaderSearch(value);
      }

      return;
    }

    const link = event.target.closest("[data-category-link], [data-clear-link], [data-shortcut-link]");

    if (!link) {
      return;
    }

    const href = link.getAttribute("href");

    if (!href || !href.startsWith("/")) {
      return;
    }

    event.preventDefault();
    loadFromHref(href);
    hideOffcanvas(link);
  });

  document.addEventListener("click", (event) => {
    if (!headerSearchForm || !autocompletePanel || autocompletePanel.hidden) {
      return;
    }

    if (headerSearchForm.contains(event.target)) {
      return;
    }

    hideAutocomplete();
  });

  document.addEventListener("focusin", (event) => {
    if (!headerSearchForm || !autocompletePanel || autocompletePanel.hidden) {
      return;
    }

    if (headerSearchForm.contains(event.target)) {
      return;
    }

    hideAutocomplete();
  });

  window.addEventListener("popstate", () => {
    loadStorefront(new URLSearchParams(window.location.search));
  });

  window.addEventListener("resize", () => {
    window.cancelAnimationFrame(resizeFrame);
    resizeFrame = window.requestAnimationFrame(syncSidebarHosts);
  });

  document.addEventListener("storefront:cart-updated", (event) => {
    const count = event.detail?.count ?? 0;
    document.querySelectorAll("[data-hero-cart-count]").forEach((node) => {
      node.textContent = String(count);
    });
  });

  const initialParams = new URLSearchParams(window.location.search);
  const shouldResetInitialLanding = window.location.hash === "#catalog-feed" && window.location.search !== "";

  loadStorefront(initialParams).finally(() => {
    if (!shouldResetInitialLanding) {
      return;
    }

    window.scrollTo({ top: 0, left: 0, behavior: "auto" });

    const nextUrl = new URL(window.location.href);
    nextUrl.hash = "";
    window.history.replaceState({}, "", `${nextUrl.pathname}${nextUrl.search}`);
  });
})();
