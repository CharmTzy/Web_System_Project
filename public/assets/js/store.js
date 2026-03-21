(() => {
  const body = document.body;
  const toast = document.querySelector('[data-status-toast]');
  const liveRegion = document.getElementById('cart-live-region');
  const headerState = (window.__novaHeaderState = window.__novaHeaderState || {
    cartCount: 0,
    notificationCount: 0,
  });
  const minimumSkeletonMs = 900;
  const skeletonStartedAt = window.performance?.now?.() ?? Date.now();
  let pageRevealScheduled = false;

  const revealPageShell = () => {
    if (!body) {
      return;
    }

    body.classList.remove('page-loading');
    body.classList.add('page-ready');

    window.setTimeout(() => {
      document.querySelectorAll('[data-page-skeleton]').forEach((node) => {
        node.remove();
      });
    }, 320);
  };

  const schedulePageReveal = () => {
    if (!body || !body.classList.contains('page-loading') || pageRevealScheduled) {
      return;
    }

    pageRevealScheduled = true;
    const now = window.performance?.now?.() ?? Date.now();
    const remaining = Math.max(0, minimumSkeletonMs - (now - skeletonStartedAt));

    window.setTimeout(() => {
      window.requestAnimationFrame(() => {
        window.requestAnimationFrame(revealPageShell);
      });
    }, remaining);
  };

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
    const normalizedCount = Number.isFinite(Number(count)) ? Number(count) : 0;
    headerState.cartCount = normalizedCount;

    document.querySelectorAll('[data-cart-count], [data-hero-cart-count]').forEach((node) => {
      node.textContent = String(normalizedCount);
    });
  };

  const updateNotificationCount = (count) => {
    const normalizedCount = Number.isFinite(Number(count)) ? Number(count) : 0;
    headerState.notificationCount = normalizedCount;

    document.querySelectorAll('[data-notification-count]').forEach((node) => {
      node.textContent = String(normalizedCount);
    });
  };

  const applyHeaderCounts = () => {
    updateCartCount(headerState.cartCount);
    updateNotificationCount(headerState.notificationCount);
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

  const refreshCart = async () => {
    const response = await fetch('/api/cart.php', {
      headers: {
        Accept: 'application/json',
      },
    });

    const payload = await response.json();

    if (!response.ok || !payload.ok) {
      throw new Error(payload.message || 'Unable to load the cart.');
    }

    applyCartPayload(payload);

    return payload;
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
    const trigger = event.target.closest('[data-open-cart-drawer]');

    if (!trigger || event.defaultPrevented) {
      return;
    }

    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
      return;
    }

    const drawer = document.getElementById('cartDrawer');

    if (!drawer || !window.bootstrap) {
      return;
    }

    event.preventDefault();
    openCartDrawer();
  });

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

  document.addEventListener('DOMContentLoaded', () => {
    applyHeaderCounts();

    refreshCart().catch(() => {
      // Leave the page usable even if the cart snapshot fails.
    });
  });

  if (body && body.classList.contains('page-loading')) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', schedulePageReveal, { once: true });
    } else {
      schedulePageReveal();
    }

    window.addEventListener('pageshow', schedulePageReveal, { once: true });
    window.setTimeout(schedulePageReveal, 1400);
  }

  window.Storefront = {
    applyCartPayload,
    applyHeaderCounts,
    flashMessage,
    openCartDrawer,
    refreshCart,
    scheduleFormSubmit,
    updateCartCount,
    updateNotificationCount,
  };
})();
