(() => {
  const root = document.querySelector('[data-product-manager]');
  if (!root) return;

  const seedNode = root.querySelector('[data-product-manager-seed]');
  const form = root.querySelector('[data-product-form]');
  const list = root.querySelector('[data-product-list]');
  const stats = root.querySelector('[data-product-stats]');
  const searchInput = root.querySelector('[data-product-search]');
  const filterSelect = root.querySelector('[data-product-filter]');
  const formTitle = root.querySelector('[data-product-form-title]');
  const submitButton = root.querySelector('[data-product-submit]');
  const cancelButton = root.querySelector('[data-product-cancel]');
  const createButton = root.querySelector('[data-product-create]');
  const resetButton = root.querySelector('[data-product-reset]');
  const errorBox = root.querySelector('[data-product-error]');
  const successBox = root.querySelector('[data-product-success]');

  const storageKey = root.dataset.storageKey || 'product-manager-demo';
  const roleLabel = root.dataset.roleLabel || 'Admin';
  const currency = root.dataset.currency || 'USD';
  const seedProducts = parseJson(seedNode?.textContent, []);
  const formatter = new Intl.NumberFormat('en-US', { style: 'currency', currency });
  let editingId = null;
  let products = loadProducts();

  function parseJson(value, fallback) {
    try {
      return value ? JSON.parse(value) : fallback;
    } catch (_error) {
      return fallback;
    }
  }

  function loadProducts() {
    const saved = parseJson(window.localStorage.getItem(storageKey), null);
    return Array.isArray(saved) && saved.length ? saved : seedProducts;
  }

  function persistProducts() {
    window.localStorage.setItem(storageKey, JSON.stringify(products));
  }

  function showMessage(type, message) {
    if (type === 'error') {
      if (successBox) successBox.style.display = 'none';
      if (errorBox) {
        errorBox.textContent = message;
        errorBox.style.display = 'block';
      }
      return;
    }

    if (errorBox) errorBox.style.display = 'none';
    if (successBox) {
      successBox.textContent = message;
      successBox.style.display = 'block';
    }
  }

  function clearMessages() {
    if (errorBox) errorBox.style.display = 'none';
    if (successBox) successBox.style.display = 'none';
  }

  function resetForm(options = {}) {
    const { keepMessage = false } = options;
    form.reset();
    form.elements.product_id.value = '';
    form.elements.status.value = 'Draft';
    editingId = null;
    formTitle.textContent = 'Add Product';
    submitButton.textContent = 'Save product';
    cancelButton.style.display = 'none';
    if (!keepMessage) {
      clearMessages();
    }
  }

  function focusForm() {
    root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    form.elements.name.focus();
  }

  function summarizeProducts() {
    const activeCount = products.filter((product) => product.status === 'Active').length;
    const featuredCount = products.filter((product) => product.featured).length;
    const inventoryCount = products.reduce((sum, product) => sum + Number(product.stock || 0), 0);

    stats.innerHTML = [
      statCard('Total products', String(products.length)),
      statCard('Active listings', String(activeCount)),
      statCard('Featured', String(featuredCount)),
      statCard('Units in stock', String(inventoryCount)),
    ].join('');
  }

  function statCard(label, value) {
    return `
      <article class="hero-stat-card">
        <span class="hero-stat-card__label">${escapeHtml(label)}</span>
        <strong>${escapeHtml(value)}</strong>
      </article>
    `;
  }

  function filteredProducts() {
    const search = (searchInput?.value || '').trim().toLowerCase();
    const status = filterSelect?.value || 'all';

    return products
      .filter((product) => {
        const matchesSearch =
          !search ||
          [product.name, product.category, product.sku, product.description]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(search));
        const matchesStatus = status === 'all' || product.status === status;
        return matchesSearch && matchesStatus;
      })
      .sort((a, b) => new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime());
  }

  function renderProducts() {
    const visibleProducts = filteredProducts();
    summarizeProducts();

    if (!visibleProducts.length) {
      list.innerHTML = `
        <div class="empty-state empty-state--compact">
          <h3>No products found.</h3>
          <p>Try a different search, or create a new product for this ${escapeHtml(roleLabel.toLowerCase())} workspace.</p>
        </div>
      `;
      return;
    }

    list.innerHTML = visibleProducts
      .map((product) => {
        const stockLabel = Number(product.stock) > 0 ? `${product.stock} in stock` : 'No stock';
        return `
          <article class="product-admin-card" data-product-id="${escapeHtml(product.id)}">
            <div class="product-admin-card__image">
              <img src="${escapeAttribute(product.image || '/assets/images/products/product-fallback.svg')}" alt="${escapeAttribute(product.name)}">
            </div>
            <div class="product-admin-card__content">
              <div class="product-admin-card__top">
                <div>
                  <div class="product-admin-card__chips">
                    <span class="pill-badge pill-badge--soft">${escapeHtml(product.category)}</span>
                    <span class="pill-badge ${product.status === 'Active' ? 'pill-badge--soft' : 'pill-badge--dark'}">${escapeHtml(product.status)}</span>
                    ${product.featured ? '<span class="pill-badge pill-badge--accent">Featured</span>' : ''}
                  </div>
                  <h4>${escapeHtml(product.name)}</h4>
                </div>
                <strong class="product-admin-card__price">${formatter.format(Number(product.price || 0))}</strong>
              </div>
              <p>${escapeHtml(product.description || 'No description yet.')}</p>
              <dl class="product-admin-card__meta">
                <div><dt>SKU</dt><dd>${escapeHtml(product.sku)}</dd></div>
                <div><dt>Stock</dt><dd>${escapeHtml(stockLabel)}</dd></div>
                <div><dt>Updated</dt><dd>${escapeHtml(formatDate(product.updated_at))}</dd></div>
              </dl>
              <div class="product-admin-card__actions">
                <button class="btn btn-brand-outline" type="button" data-action="edit">Edit</button>
                <button class="btn btn-brand-outline product-admin-card__delete" type="button" data-action="delete">Delete</button>
              </div>
            </div>
          </article>
        `;
      })
      .join('');
  }

  function formatDate(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'Just now';
    return new Intl.DateTimeFormat('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    }).format(date);
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function escapeAttribute(value) {
    return escapeHtml(value);
  }

  function productFromForm() {
    const data = new FormData(form);
    const name = String(data.get('name') || '').trim();
    const category = String(data.get('category') || '').trim();
    const sku = String(data.get('sku') || '').trim();
    const description = String(data.get('description') || '').trim();

    if (!name || !category || !sku || !description) {
      throw new Error('Please complete all required product fields.');
    }

    return {
      id: editingId || `prd-${Date.now()}`,
      name,
      category,
      price: Number(data.get('price') || 0),
      stock: Number(data.get('stock') || 0),
      sku,
      status: String(data.get('status') || 'Draft'),
      image: String(data.get('image') || '').trim() || '/assets/images/products/product-fallback.svg',
      description,
      featured: data.get('featured') === '1',
      updated_at: new Date().toISOString(),
    };
  }

  function startEdit(productId) {
    const product = products.find((item) => item.id === productId);
    if (!product) return;

    editingId = product.id;
    form.elements.product_id.value = product.id;
    form.elements.name.value = product.name;
    form.elements.category.value = product.category;
    form.elements.price.value = String(product.price);
    form.elements.stock.value = String(product.stock);
    form.elements.sku.value = product.sku;
    form.elements.status.value = product.status;
    form.elements.image.value = product.image || '';
    form.elements.description.value = product.description || '';
    form.elements.featured.checked = Boolean(product.featured);
    formTitle.textContent = `Edit ${product.name}`;
    submitButton.textContent = 'Update product';
    cancelButton.style.display = '';
    clearMessages();
    focusForm();
  }

  function deleteProduct(productId) {
    const product = products.find((item) => item.id === productId);
    if (!product) return;

    const confirmed = window.confirm(`Delete "${product.name}"? This only removes it from the frontend demo list.`);
    if (!confirmed) return;

    products = products.filter((item) => item.id !== productId);
    persistProducts();
    if (editingId === productId) {
      resetForm();
    }
    renderProducts();
    showMessage('success', 'Product deleted from the local demo workspace.');
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();

    try {
      const product = productFromForm();
      const existingIndex = products.findIndex((item) => item.id === product.id);

      if (existingIndex >= 0) {
        products.splice(existingIndex, 1, product);
        showMessage('success', 'Product updated successfully.');
      } else {
        products.unshift(product);
        showMessage('success', 'Product added successfully.');
      }

      persistProducts();
      renderProducts();
      resetForm({ keepMessage: true });
    } catch (error) {
      showMessage('error', error.message || 'Unable to save product.');
    }
  });

  list.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;

    const card = button.closest('[data-product-id]');
    const productId = card?.dataset.productId;
    if (!productId) return;

    if (button.dataset.action === 'edit') {
      startEdit(productId);
      return;
    }

    if (button.dataset.action === 'delete') {
      deleteProduct(productId);
    }
  });

  [searchInput, filterSelect].forEach((control) => {
    control?.addEventListener('input', renderProducts);
    control?.addEventListener('change', renderProducts);
  });

  cancelButton.addEventListener('click', resetForm);
  createButton.addEventListener('click', () => {
    resetForm();
    focusForm();
  });
  resetButton.addEventListener('click', resetForm);

  renderProducts();
  resetForm();
})();
