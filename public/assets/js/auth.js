(() => {
  const form = document.querySelector('[data-auth-form]');
  const errorBox = document.querySelector('[data-auth-error]');

  if (!form) return;

  const showError = (message) => {
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.style.display = 'block';
  };

  const hideError = () => {
    if (!errorBox) return;
    errorBox.style.display = 'none';
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    hideError();

    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    try {
      const response = await fetch('/api/auth.php', {
        method: 'POST',
        headers: { Accept: 'application/json' },
        body: new FormData(form),
      });

      const payload = await response.json();

      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || 'Something went wrong.');
      }

      window.location.href = payload.redirect || '/';
    } catch (error) {
      showError(error.message || 'Unable to process your request.');
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
})();
