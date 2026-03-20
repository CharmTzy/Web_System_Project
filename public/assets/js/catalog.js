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
  const homeSections = document.querySelectorAll('[data-home-discovery]');
  const sidebarHost = document.querySelector('[data-catalog-sidebar-host]');
  const mobileFilterBody = document.querySelector('[data-mobile-filter-body]');
  const mobileFilterButton = document.querySelector('[data-mobile-filter-button]');
  const shell = document.getElementById('catalog-shell');
  const main = document.getElementById('catalog-main');
  const productsShown = document.querySelector('[data-products-shown]');
  const categoriesTotal = document.querySelector('[data-categories-total]');
  const catalogTitle = document.querySelector('[data-catalog-title]');
  const catalogCopy = document.querySelector('[data-catalog-copy]');
  let searchTimer = null;
  let latestSidebarHtml = '';
  let latestShowSidebarFilters = false;
  let resizeFrame = null;

  const isMobileViewport = () => window.matchMedia('(max-width: 767.98px)').matches;

  const hideOffcanvas = (element) => {
    if (!element || !window.bootstrap?.Offcanvas) {
      return;
    }

    const drawer = element.closest('.offcanvas');

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
    const sidebarHtml = latestShowSidebarFilters ? latestSidebarHtml : '';

    if (sidebarHost) {
      sidebarHost.innerHTML = !mobileViewport ? sidebarHtml : '';
    }

    if (mobileFilterBody) {
      mobileFilterBody.innerHTML = mobileViewport ? sidebarHtml : '';
    }

    if (mobileFilterButton) {
      mobileFilterButton.hidden = !latestShowSidebarFilters;
    }

    shell.classList.toggle('catalog-shell--with-sidebar', latestShowSidebarFilters && !mobileViewport);
    shell.classList.toggle('catalog-shell--without-sidebar', !latestShowSidebarFilters || mobileViewport);
    main.classList.toggle('catalog-main--full', !latestShowSidebarFilters || mobileViewport);

    if ((!latestShowSidebarFilters || !mobileViewport) && mobileFilterBody) {
      hideOffcanvas(mobileFilterBody);
    }
  };

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
        featuredSection.hidden = Boolean(payload.show_sidebar_filters) || (payload.featured_html || '').trim() === '';
      }

      homeSections.forEach((section) => {
        if (section !== featuredSection) {
          section.hidden = Boolean(payload.show_sidebar_filters);
        }
      });

      latestSidebarHtml = payload.sidebar_html || '';
      latestShowSidebarFilters = Boolean(payload.show_sidebar_filters);
      syncSidebarHosts();

      results.innerHTML = payload.results_html || '';

      if (catalogTitle) {
        if (payload.filters.search) {
          catalogTitle.textContent = `Results for "${payload.filters.search}"`;
        } else if (payload.filters.category) {
          catalogTitle.textContent = 'Filtered products';
        } else {
          catalogTitle.textContent = 'Popular gifts right now';
        }
      }

      if (catalogCopy) {
        if (payload.filters.search || payload.filters.category) {
          catalogCopy.textContent = 'Browse the products that match your search and category filters.';
        } else {
          catalogCopy.textContent = 'Fresh picks from independent sellers and trending home finds.';
        }
      }

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
    const form = sidebarHost?.querySelector('#catalog-filter-form')
      || mobileFilterBody?.querySelector('#catalog-filter-form');

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

  const handleFilterSubmit = (event) => {
    const form = event.target.closest('#catalog-filter-form');

    if (!form) {
      return;
    }

    event.preventDefault();
    loadStorefront(collectSidebarParams(), { updateHistory: true });
    hideOffcanvas(form);
  };

  const handleFilterChange = (event) => {
    const target = event.target;

    if (!(target instanceof HTMLElement) || target.getAttribute('name') === 'search') {
      return;
    }

    loadStorefront(collectSidebarParams(), { updateHistory: true });
  };

  const handleFilterInput = (event) => {
    const target = event.target;

    if (!(target instanceof HTMLInputElement) || target.name !== 'search') {
      return;
    }

    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
      loadStorefront(collectSidebarParams(), { updateHistory: true });
    }, 280);
  };

  sidebarHost?.addEventListener('submit', handleFilterSubmit);
  mobileFilterBody?.addEventListener('submit', handleFilterSubmit);
  sidebarHost?.addEventListener('change', handleFilterChange);
  mobileFilterBody?.addEventListener('change', handleFilterChange);
  sidebarHost?.addEventListener('input', handleFilterInput);
  mobileFilterBody?.addEventListener('input', handleFilterInput);

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
    hideOffcanvas(link);
  });

  window.addEventListener('popstate', () => {
    loadStorefront(new URLSearchParams(window.location.search));
  });

  window.addEventListener('resize', () => {
    window.cancelAnimationFrame(resizeFrame);
    resizeFrame = window.requestAnimationFrame(syncSidebarHosts);
  });

  document.addEventListener('storefront:cart-updated', (event) => {
    const count = event.detail?.count ?? 0;
    document.querySelectorAll('[data-hero-cart-count]').forEach((node) => {
      node.textContent = String(count);
    });
  });

  loadStorefront(new URLSearchParams(window.location.search));
})();
