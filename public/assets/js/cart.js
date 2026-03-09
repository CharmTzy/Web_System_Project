(() => {
  const cartPage = document.querySelector('[data-cart-page]');

  if (!cartPage) {
    return;
  }

  document.addEventListener('input', (event) => {
    const target = event.target;

    if (!(target instanceof HTMLElement)) {
      return;
    }

    const input = target.closest('[data-cart-quantity-input]');

    if (!input) {
      return;
    }

    const form = input.closest('form');

    if (form) {
      window.Storefront?.scheduleFormSubmit(form, 360);
    }
  });
})();

