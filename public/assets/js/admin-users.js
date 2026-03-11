(() => {
  const form = document.querySelector('[data-admin-user-form]');
  const errorBox = document.querySelector('[data-admin-user-error]');
  const successBox = document.querySelector('[data-admin-user-success]');
  const roleSelect = document.getElementById('admin-user-role');
  const sellerFields = document.getElementById('seller-fields');

  if (roleSelect && sellerFields) {
    roleSelect.addEventListener('change', () => {
      sellerFields.style.display = roleSelect.value === 'seller' ? '' : 'none';
    });
  }

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
      const userId = formData.get('user_id');
      formData.append('action', userId ? 'admin-update' : 'admin-create');

      if (userId && !form.querySelector('[name="is_active"]')?.checked) {
        formData.set('is_active', '0');
      }

      const response = await fetch('/api/users.php', {
        method: 'POST',
        headers: { Accept: 'application/json' },
        body: formData,
      });

      const payload = await response.json();

      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || 'Unable to save.');
      }

      if (!userId && payload.user) {
        window.location.href = '/admin/user-edit.php?id=' + payload.user.id;
        return;
      }

      showSuccess(payload.message || 'User saved.');
    } catch (error) {
      showError(error.message);
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
})();
