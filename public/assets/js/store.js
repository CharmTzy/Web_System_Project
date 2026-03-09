(() => {
  const toast = document.querySelector('[data-status-toast]');
  const liveRegion = document.getElementById('cart-live-region');

  const flashMessage = (message, tone = 'success') => {
    if (liveRegion) {
      liveRegion.textContent = message;
    }

    if (!toast) {
      return;
    }

    toast.textContent = message;
    toast.dataset.tone = tone;
    toast.classList.add('is-visible');

    window.clearTimeout(flashMessage.timeoutId);
    flashMessage.timeoutId = window.setTimeout(() => {
      toast.classList.remove('is-visible');
    }, 2400);
  };

  const updateCartCount = (count) => {
    document.querySelectorAll('[data-cart-count]').forEach((node) => {
      node.textContent = String(count);
    });
  };

  const applyCartPayload = (payload) => {
    updateCartCount(payload.count ?? 0);

    if (payload.drawer_html) {
      document.querySelectorAll('[data-cart-drawer]').forEach((node) => {
        node.innerHTML = payload.drawer_html;
      });
    }

    if (payload.cart_html) {
      document.querySelectorAll('[data-cart-page]').forEach((node) => {
        node.innerHTML = payload.cart_html;
      });
    }

    document.dispatchEvent(new CustomEvent('storefront:cart-updated', { detail: payload }));
  };

  const openCartDrawer = () => {
    const drawer = document.getElementById('cartDrawer');

    if (!drawer || !window.bootstrap) {
      return;
    }

    window.bootstrap.Offcanvas.getOrCreateInstance(drawer).show();
  };

  const scheduleFormSubmit = (form, delay = 220) => {
    window.clearTimeout(form._cartTimer);
    form._cartTimer = window.setTimeout(() => {
      form.requestSubmit();
    }, delay);
  };

  const sendCartForm = async (form) => {
    const response = await fetch('/api/cart.php', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
      },
      body: new FormData(form),
    });

    const payload = await response.json();

    if (!response.ok || !payload.ok) {
      throw new Error(payload.message || 'Unable to update the cart.');
    }

    return payload;
  };

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-quantity-button]');

    if (!button) {
      return;
    }

    const picker = button.closest('[data-quantity-picker]');
    const input = picker ? picker.querySelector('[data-quantity-input]') : null;

    if (!input) {
      return;
    }

    const min = Number(input.min || 1);
    const max = Number(input.max || Number.MAX_SAFE_INTEGER);
    const current = Number(input.value || min);
    const delta = button.dataset.quantityButton === 'increment' ? 1 : -1;
    const next = Math.min(max, Math.max(min, current + delta));

    input.value = String(Number.isNaN(next) ? min : next);
    input.dispatchEvent(new Event('input', { bubbles: true }));

    if (input.dataset.autoSubmit === 'true') {
      const form = input.closest('form');

      if (form) {
        scheduleFormSubmit(form);
      }
    }
  });

  document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-cart-form]');

    if (!form) {
      return;
    }

    event.preventDefault();

    const submitButton = event.submitter || form.querySelector('button[type="submit"]');

    if (submitButton) {
      submitButton.setAttribute('disabled', 'disabled');
    }

    try {
      const payload = await sendCartForm(form);
      applyCartPayload(payload);
      flashMessage(payload.message || 'Cart updated.');

      if (form.dataset.openCart === 'true') {
        openCartDrawer();
      }
    } catch (error) {
      flashMessage(error.message || 'Unable to update the cart.', 'error');
    } finally {
      if (submitButton) {
        submitButton.removeAttribute('disabled');
      }
    }
  });

  window.Storefront = {
    applyCartPayload,
    flashMessage,
    openCartDrawer,
    scheduleFormSubmit,
  };
})();

