(() => {
  const page = document.querySelector('[data-catalog-page]');

  if (!page) {
    return;
  }

  const headerSearchForm = document.getElementById('header-search-form');
  const headerSearchInput = document.getElementById('header-search-input');
  const sortSelect = document.querySelector('[data-sort-select]');
  const results = document.getElementById('catalog-results');
  const resultsCount = document.querySelector('[data-results-count]');
  const activeFilterCount = document.querySelector('[data-active-filter-count]');
  const shortcutGrid = document.querySelector('[data-shortcut-grid]');
  const featuredSection = document.querySelector('[data-featured-section]');
  const featuredStrip = document.querySelector('[data-featured-strip]');
  const sidebarHost = document.querySelector('[data-catalog-sidebar-host]');
  const shell = document.getElementById('catalog-shell');
  const main = document.getElementById('catalog-main');
  const productsShown = document.querySelector('[data-products-shown]');
  const categoriesTotal = document.querySelector('[data-categories-total]');
  let searchTimer = null;

  const cleanParams = (params) => {
    [...params.entries()].forEach(([key, value]) => {
      if (value === '') {
        params.delete(key);
      }
    });

    if (params.get('sort') === 'featured') {
      params.delete('sort');
    }

    if (params.get('in_stock') !== '1') {
      params.delete('in_stock');
    }

    return params;
  };

  const buildCatalogHref = (params, includeHash = true) => {
    const query = params.toString();
    return `/index.html${query ? `?${query}` : ''}${includeHash ? '#catalog-feed' : ''}`;
  };

  const loadStorefront = async (params = new URLSearchParams(window.location.search), options = {}) => {
    const query = cleanParams(params).toString();

    results.classList.add('is-loading');

    try {
      const response = await fetch(`/api/products.php${query ? `?${query}` : ''}`, {
        headers: {
          Accept: 'application/json',
        },
      });

      const payload = await response.json();

      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || 'Unable to load products.');
      }

      if (headerSearchInput) {
        headerSearchInput.value = payload.filters.search || '';
      }

      if (sortSelect) {
        sortSelect.value = payload.filters.sort || 'featured';
      }

      if (productsShown) {
        productsShown.textContent = String(payload.metrics?.products_shown ?? 0);
      }

      if (categoriesTotal) {
        categoriesTotal.textContent = String(payload.metrics?.categories ?? 0);
      }

      if (shortcutGrid) {
        shortcutGrid.innerHTML = payload.shortcuts_html || '';
      }

      if (featuredSection && featuredStrip) {
        featuredStrip.innerHTML = payload.featured_html || '';
        featuredSection.hidden = (payload.featured_html || '').trim() === '';
      }

      if (sidebarHost && shell && main) {
        sidebarHost.innerHTML = payload.sidebar_html || '';
        shell.classList.toggle('catalog-shell--with-sidebar', Boolean(payload.show_sidebar_filters));
        shell.classList.toggle('catalog-shell--without-sidebar', !payload.show_sidebar_filters);
        main.classList.toggle('catalog-main--full', !payload.show_sidebar_filters);
      }

      results.innerHTML = payload.results_html || '';

      if (resultsCount) {
        resultsCount.textContent = payload.summary || '0 products available';
      }

      if (activeFilterCount) {
        activeFilterCount.textContent = String(payload.active_filter_count ?? 0);
      }

      if (options.updateHistory) {
        const nextUrl = new URL(buildCatalogHref(cleanParams(new URLSearchParams(params)), options.includeHash !== false), window.location.origin);
        window.history.pushState({}, '', nextUrl);
      }
    } catch (error) {
      window.Storefront?.flashMessage(error.message || 'Unable to load products.', 'error');
    } finally {
      results.classList.remove('is-loading');
    }
  };

  const collectSidebarParams = () => {
    const params = new URLSearchParams(window.location.search);
    const form = sidebarHost?.querySelector('#catalog-filter-form');

    if (form) {
      const formParams = new URLSearchParams(new FormData(form));
      params.forEach((_, key) => params.delete(key));
      formParams.forEach((value, key) => params.set(key, value));
    }

    if (sortSelect) {
      params.set('sort', sortSelect.value || 'featured');
    }

    return cleanParams(params);
  };

  const loadFromHref = (href) => {
    const nextUrl = new URL(href, window.location.origin);
    const params = new URLSearchParams(nextUrl.search);
    loadStorefront(params, { updateHistory: true, includeHash: nextUrl.hash === '#catalog-feed' });
  };

  headerSearchForm?.addEventListener('submit', (event) => {
    event.preventDefault();

    const params = new URLSearchParams(window.location.search);
    const value = headerSearchInput?.value.trim() || '';

    if (value !== '') {
      params.set('search', value);
    } else {
      params.delete('search');
    }

    loadStorefront(params, { updateHistory: true });

    document.getElementById('catalog-feed')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  sortSelect?.addEventListener('change', () => {
    loadStorefront(collectSidebarParams(), { updateHistory: true });
  });

  sidebarHost?.addEventListener('submit', (event) => {
    const form = event.target.closest('#catalog-filter-form');

    if (!form) {
      return;
    }

    event.preventDefault();
    loadStorefront(collectSidebarParams(), { updateHistory: true });
  });

  sidebarHost?.addEventListener('change', (event) => {
    const target = event.target;

    if (!(target instanceof HTMLElement) || target.getAttribute('name') === 'search') {
      return;
    }

    loadStorefront(collectSidebarParams(), { updateHistory: true });
  });

  sidebarHost?.addEventListener('input', (event) => {
    const target = event.target;

    if (!(target instanceof HTMLInputElement) || target.name !== 'search') {
      return;
    }

    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
      loadStorefront(collectSidebarParams(), { updateHistory: true });
    }, 280);
  });

  document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-category-link], [data-clear-link], [data-shortcut-link]');

    if (!link) {
      return;
    }

    const href = link.getAttribute('href');

    if (!href || !href.startsWith('/index.html')) {
      return;
    }

    event.preventDefault();
    loadFromHref(href);
  });

  window.addEventListener('popstate', () => {
    loadStorefront(new URLSearchParams(window.location.search));
  });

  document.addEventListener('storefront:cart-updated', (event) => {
    const count = event.detail?.count ?? 0;
    document.querySelectorAll('[data-hero-cart-count]').forEach((node) => {
      node.textContent = String(count);
    });
  });

  loadStorefront(new URLSearchParams(window.location.search));
})();
