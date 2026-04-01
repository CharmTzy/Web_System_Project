(() => {
  const modalElement = document.querySelector("[data-order-review-modal]");
  const stateNode = document.querySelector("[data-order-review-state]");
  const storefront = window.Storefront || {};

  if (!modalElement || !stateNode || !window.bootstrap) {
    return;
  }

  let reviewState = {};

  try {
    reviewState = JSON.parse(stateNode.textContent || "{}");
  } catch (error) {
    reviewState = {};
  }

  const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
  const form = modalElement.querySelector("[data-order-review-form]");
  const feedback = modalElement.querySelector("[data-review-feedback]");
  const submitButton = modalElement.querySelector("[data-review-submit]");
  const deleteButton = modalElement.querySelector("[data-review-delete]");
  const productIdInput = form?.querySelector('[name="product_id"]');
  const titleInput = form?.querySelector('[name="title"]');
  const commentInput = form?.querySelector('[name="comment"]');
  const ratingInputs = [...modalElement.querySelectorAll('[name="rating"]')];
  const productNameNode = modalElement.querySelector("[data-order-review-product-name]");
  const sellerNameNode = modalElement.querySelector("[data-order-review-seller-name]");
  const productImageNode = modalElement.querySelector("[data-order-review-product-image]");
  const existingMediaSection = modalElement.querySelector("[data-order-review-existing-media]");
  const existingMediaList = modalElement.querySelector("[data-order-review-existing-media-list]");

  if (!form || !productIdInput || !titleInput || !commentInput) {
    return;
  }

  const clearFeedback = () => {
    feedback.innerHTML = "";
  };

  const showFeedback = (message, tone = "danger") => {
    feedback.innerHTML = `<div class="alert alert-${tone}" role="alert">${message}</div>`;
  };

  const setRating = (rating) => {
    ratingInputs.forEach((input) => {
      input.checked = Number(input.value) === Number(rating);
    });
  };

  const renderExistingMedia = (media) => {
    if (!existingMediaSection || !existingMediaList) {
      return;
    }

    const items = Array.isArray(media) ? media : [];

    if (items.length === 0) {
      existingMediaSection.hidden = true;
      existingMediaList.innerHTML = "";
      return;
    }

    existingMediaList.innerHTML = items
      .map((item) => {
        if (item.media_type === "video") {
          return `
            <video controls class="product-review-card__video">
              <source src="${item.file_path}">
              Your browser does not support video playback.
            </video>
          `;
        }

        return `<img src="${item.file_path}" alt="Review media" class="product-review-card__image">`;
      })
      .join("");

    existingMediaSection.hidden = false;
  };

  const updateTriggerLabels = (productId, hasReview) => {
    document.querySelectorAll("[data-review-trigger]").forEach((button) => {
      if ((button.dataset.reviewProductId || "") !== String(productId)) {
        return;
      }

        button.textContent = hasReview ? "Update review" : "Write review";
    });
  };

  const applyReviewState = (productId, review) => {
    if (review) {
      reviewState[String(productId)] = review;
    } else {
      delete reviewState[String(productId)];
    }

    updateTriggerLabels(productId, Boolean(review));
  };

  const openReviewModal = (trigger) => {
    const productId = trigger.dataset.reviewProductId || "";
    const review = reviewState[productId] || null;

    clearFeedback();
    form.reset();

    productIdInput.value = productId;
    titleInput.value = review?.title || "";
    commentInput.value = review?.comment || "";
    setRating(review?.rating || "");

    productNameNode.textContent = trigger.dataset.reviewProductName || "Product";
    sellerNameNode.textContent = trigger.dataset.reviewSellerName || "";
    productImageNode.src = trigger.dataset.reviewProductImage || "";
    productImageNode.alt = trigger.dataset.reviewProductName || "Product";

    deleteButton.hidden = !review;
    submitButton.textContent = review ? "Update review" : "Submit review";
    renderExistingMedia(review?.media || []);

    modal.show();
  };

  const submitReviewRequest = async (formData) => {
    const response = await fetch("/api/reviews.php", {
      method: "POST",
      headers: {
        Accept: "application/json",
      },
      body: formData,
    });

    const payload = await response.json();

    if (!response.ok || !payload.ok) {
      if (payload.login_url) {
        window.location.href = payload.login_url;
        return null;
      }

      throw new Error(payload.message || "Unable to update your review.");
    }

    return payload;
  };

  document.addEventListener("click", (event) => {
    const trigger = event.target.closest("[data-review-trigger]");

    if (!trigger) {
      return;
    }

    openReviewModal(trigger);
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearFeedback();

    submitButton.setAttribute("disabled", "disabled");
    deleteButton.setAttribute("disabled", "disabled");

    try {
      const formData = new FormData(form);
      const payload = await submitReviewRequest(formData);

      if (!payload) {
        return;
      }

      const productId = productIdInput.value;
      const existingReview = payload.data?.existing_review || null;

      applyReviewState(productId, existingReview);
      modal.hide();
      storefront.flashMessage?.(payload.message || "Review updated.");
    } catch (error) {
      showFeedback(error.message || "Unable to update your review.");
    } finally {
      submitButton.removeAttribute("disabled");
      deleteButton.removeAttribute("disabled");
    }
  });

  deleteButton.addEventListener("click", async () => {
    const productId = productIdInput.value;

    if (!productId || !window.confirm("Are you sure you want to delete this review?")) {
      return;
    }

    clearFeedback();

    submitButton.setAttribute("disabled", "disabled");
    deleteButton.setAttribute("disabled", "disabled");

    try {
      const formData = new FormData();
      formData.set("csrf_token", form.querySelector('[name="csrf_token"]')?.value || "");
      formData.set("product_id", productId);
      formData.set("action", "delete");

      const payload = await submitReviewRequest(formData);

      if (!payload) {
        return;
      }

      applyReviewState(productId, null);
      modal.hide();
      storefront.flashMessage?.(payload.message || "Review removed.");
    } catch (error) {
      showFeedback(error.message || "Unable to delete your review.");
    } finally {
      submitButton.removeAttribute("disabled");
      deleteButton.removeAttribute("disabled");
    }
  });

  modalElement.addEventListener("hidden.bs.modal", () => {
    clearFeedback();
    form.reset();
    setRating("");
    renderExistingMedia([]);
  });
})();
