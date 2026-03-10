(() => {
  const container = document.querySelector('[data-addresses-container]');
  const modal = document.querySelector('[data-address-modal]');
  const modalForm = document.querySelector('[data-address-form]');
  const modalTitle = document.querySelector('[data-address-modal-title]');
  const modalClose = document.querySelector('[data-address-modal-close]');
  const errorBox = document.querySelector('[data-address-error]');
  const actionInput = document.querySelector('[data-address-action]');
  const idInput = document.querySelector('[data-address-id]');
  const submitBtn = document.querySelector('[data-address-submit-btn]');

  if (!container || !modal || !modalForm) return;

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

  const showModal = (title = 'Add address') => {
    if (modalTitle) modalTitle.textContent = title;
    if (errorBox) errorBox.style.display = 'none';
    modal.style.display = 'flex';
  };

  const hideModal = () => {
    modal.style.display = 'none';
    modalForm.reset();
    if (actionInput) actionInput.value = 'create';
    if (idInput) idInput.value = '';
  };

  const showError = (message) => {
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.style.display = 'block';
  };

  const refreshList = async () => {
    try {
      const response = await fetch('/api/addresses.php', {
        headers: { Accept: 'application/json' },
      });
      const payload = await response.json();
      if (payload.ok && payload.html) {
        const listEl = container.querySelector('.section-block');
        if (listEl) listEl.outerHTML = payload.html;
      }
    } catch (e) {
      // silent refresh failure
    }
  };

  const sendAction = async (action, addressId) => {
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('action', action);
    formData.append('address_id', String(addressId));

    const response = await fetch('/api/addresses.php', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: formData,
    });

    const payload = await response.json();

    if (!response.ok || !payload.ok) {
      throw new Error(payload.message || 'Request failed.');
    }

    return payload;
  };

  if (modalClose) {
    modalClose.addEventListener('click', hideModal);
  }

  modal.addEventListener('click', (event) => {
    if (event.target === modal) hideModal();
  });

  container.addEventListener('click', async (event) => {
    const addBtn = event.target.closest('[data-address-add]');
    if (addBtn) {
      hideModal();
      showModal('Add address');
      return;
    }

    const editBtn = event.target.closest('[data-address-edit]');
    if (editBtn) {
      hideModal();
      if (actionInput) actionInput.value = 'update';
      if (idInput) idInput.value = editBtn.dataset.addressEdit;

      const fields = {
        recipient: 'recipient',
        line_1: 'line1',
        line_2: 'line2',
        city: 'city',
        state: 'state',
        postal_code: 'postalCode',
        country: 'country',
        phone: 'phone',
        label: 'label',
      };

      for (const [name, dataKey] of Object.entries(fields)) {
        const input = modalForm.querySelector(`[name="${name}"]`);
        if (input) input.value = editBtn.dataset[dataKey] || '';
      }

      showModal('Edit address');
      return;
    }

    const defaultBtn = event.target.closest('[data-address-default]');
    if (defaultBtn) {
      try {
        await sendAction('set-default', defaultBtn.dataset.addressDefault);
        await refreshList();
        window.Storefront?.flashMessage('Default address updated.');
      } catch (error) {
        window.Storefront?.flashMessage(error.message, 'error');
      }
      return;
    }

    const deleteBtn = event.target.closest('[data-address-delete]');
    if (deleteBtn) {
      if (!confirm('Remove this address?')) return;
      try {
        await sendAction('delete', deleteBtn.dataset.addressDelete);
        await refreshList();
        window.Storefront?.flashMessage('Address removed.');
      } catch (error) {
        window.Storefront?.flashMessage(error.message, 'error');
      }
    }
  });

  modalForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (submitBtn) submitBtn.disabled = true;

    try {
      const response = await fetch('/api/addresses.php', {
        method: 'POST',
        headers: { Accept: 'application/json' },
        body: new FormData(modalForm),
      });

      const payload = await response.json();

      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || 'Unable to save address.');
      }

      hideModal();
      await refreshList();
      window.Storefront?.flashMessage(payload.message || 'Address saved.');
    } catch (error) {
      showError(error.message);
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
})();
