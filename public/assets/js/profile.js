(() => {
  const form = document.querySelector('[data-profile-form]');
  const errorBox = document.querySelector('[data-profile-error]');
  const successBox = document.querySelector('[data-profile-success]');

  if (!form) return;

  const showError = (message) => {
    if (successBox) successBox.style.display = 'none';
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.style.display = 'block';
  };

  const showSuccess = (message) => {
    if (errorBox) errorBox.style.display = 'none';
    if (!successBox) return;
    successBox.textContent = message;
    successBox.style.display = 'block';
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    try {
      const formData = new FormData(form);
      formData.append('action', 'update-profile');

      const response = await fetch('/api/users.php', {
        method: 'POST',
        headers: { Accept: 'application/json' },
        body: formData,
      });

      const payload = await response.json();

      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || 'Unable to save changes.');
      }

      showSuccess(payload.message || 'Profile updated.');

      if (payload.user) {
        if (payload.user.name) form.querySelector('[name="name"]').value = payload.user.name;
        if (payload.user.email) form.querySelector('[name="email"]').value = payload.user.email;
      }

      const passwordField = form.querySelector('[name="new_password"]');
      if (passwordField) passwordField.value = '';
    } catch (error) {
      showError(error.message);
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
})();
