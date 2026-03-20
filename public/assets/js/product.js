(() => {
  const page = document.querySelector('[data-product-screen]');

  if (!page) {
    return;
  }

  const detailHost = document.querySelector('[data-product-detail-host]');

  if (!detailHost) {
    return;
  }

  const renderState = (title, copy) => {
    detailHost.innerHTML = `
      <section class="empty-state empty-state--compact">
        <h3>${title}</h3>
        <p>${copy}</p>
        <a class="btn btn-brand" href="/index.html">Back to shop</a>
      </section>
    `;
  };

  const loadProduct = async () => {
    const params = new URLSearchParams(window.location.search);

    if (!params.get('id') && !params.get('slug')) {
      renderState('Choose a product first.', 'Open a product from the shop page to view its details.');
      return;
    }

    detailHost.classList.add('is-loading');

    try {
      const response = await fetch(`/api/product.php?${params.toString()}`, {
        headers: {
          Accept: 'application/json',
        },
      });

      const payload = await response.json();

      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || 'Unable to load this product.');
      }

      detailHost.innerHTML = payload.html || '';

      if (payload.title) {
        document.title = payload.title;
      }
    } catch (error) {
      renderState('Unable to load this product.', error.message || 'Please try again in a moment.');
    } finally {
      detailHost.classList.remove('is-loading');
    }
  };

  loadProduct();
})();
