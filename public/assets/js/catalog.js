(() => {
  const form = document.getElementById('catalog-filter-form');
  const results = document.getElementById('catalog-results');
  const resultsCount = document.querySelector('[data-results-count]');
  const activeFilterCount = document.querySelector('[data-active-filter-count]');
  const sortSelect = document.querySelector('[data-sort-select]');
  const clearLink = document.querySelector('[data-clear-link]');
  const sidebar = document.querySelector('[data-catalog-sidebar]');

  if (!form || !results) {
    return;
  }

  let searchTimer = null;

  const buildParams = () => {
    const params = new URLSearchParams(new FormData(form));

    if (sortSelect) {
      if (sortSelect.value && sortSelect.value !== 'featured') {
        params.set('sort', sortSelect.value);
      } else {
        params.delete('sort');
      }
    }

    [...params.entries()].forEach(([key, value]) => {
      if (value === '') {
        params.delete(key);
      }
    });

    const inStockToggle = form.querySelector('#in_stock');

    if (inStockToggle && !inStockToggle.checked) {
      params.delete('in_stock');
    }

    return params;
  };

  const buildCatalogHref = (params) => {
    const query = params.toString();
    return `/${query ? `?${query}` : ''}#catalog-feed`;
  };

  const hasSidebarFilters = (params) => {
    return ['search', 'category', 'min_price', 'max_price', 'in_stock'].some((key) => {
      const value = params.get(key);
      return value !== null && value !== '';
    });
  };

  const syncSidebarLinks = () => {
    const params = buildParams();

    document.querySelectorAll('[data-category-link]').forEach((link) => {
      const nextParams = new URLSearchParams(params);
      const categoryValue = link.dataset.categoryValue || '';

      if (categoryValue) {
        nextParams.set('category', categoryValue);
      } else {
        nextParams.delete('category');
      }

      link.setAttribute('href', buildCatalogHref(nextParams));
    });

    if (clearLink) {
      clearLink.setAttribute('href', '/#catalog-feed');
    }
  };

  const refreshCatalog = async () => {
    results.classList.add('is-loading');

    const params = buildParams();
    const query = params.toString();

    if (sidebar && !hasSidebarFilters(params)) {
      window.location.href = buildCatalogHref(params);
      return;
    }

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

      results.innerHTML = payload.html;

      if (resultsCount) {
        resultsCount.textContent = payload.summary;
      }

      if (activeFilterCount) {
        activeFilterCount.textContent = String(payload.active_filter_count ?? 0);
      }

      const url = new URL(window.location.href);
      url.search = query;
      window.history.replaceState({}, '', url);
      syncSidebarLinks();
    } catch (error) {
      window.Storefront?.flashMessage(error.message || 'Unable to filter the catalog.', 'error');
    } finally {
      results.classList.remove('is-loading');
    }
  };

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    refreshCatalog();
  });

  form.addEventListener('change', (event) => {
    const target = event.target;

    if (!(target instanceof HTMLElement) || target.getAttribute('name') === 'search') {
      return;
    }

    refreshCatalog();
  });

  const searchInput = form.querySelector('input[name="search"]');

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(refreshCatalog, 280);
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener('change', refreshCatalog);
  }

  syncSidebarLinks();
})();
