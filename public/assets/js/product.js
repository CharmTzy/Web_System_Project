(() => {
  const page = document.querySelector("[data-product-screen]");

  if (!page) {
    return;
  }

  const detailHost = document.querySelector("[data-product-detail-host]");
  const storefront = window.Storefront || {};

  if (!detailHost) {
    return;
  }

  const renderState = (title, copy) => {
    detailHost.innerHTML = `
      <section class="empty-state empty-state--compact">
        <h3>${title}</h3>
        <p>${copy}</p>
        <a class="btn btn-brand" href="/">Back to shop</a>
      </section>
    `;
  };

  const initMediaGallery = (root) => {
    const panels = [...root.querySelectorAll("[data-product-media-panel]")];
    const thumbs = [...root.querySelectorAll("[data-product-media-thumb]")];

    if (panels.length < 2 || thumbs.length < 2) {
      return;
    }

    const setActive = (targetId) => {
      panels.forEach((panel) => {
        const isActive = panel.dataset.productMediaPanel === targetId;
        panel.hidden = !isActive;
        panel.classList.toggle("is-active", isActive);

        if (!isActive) {
          panel.querySelectorAll("video").forEach((video) => video.pause());
        }
      });

      thumbs.forEach((thumb) => {
        const isActive = thumb.dataset.productMediaThumb === targetId;
        thumb.classList.toggle("is-active", isActive);
        thumb.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
    };

    thumbs.forEach((thumb) => {
      thumb.addEventListener("click", () => {
        setActive(thumb.dataset.productMediaThumb || "0");
      });
    });
  };

  const loadProduct = async () => {
    const params = new URLSearchParams(window.location.search);

    if (!params.get("id") && !params.get("slug")) {
      renderState("Choose a product first.", "Open a product from the shop page to view its details.");
      return;
    }

    detailHost.classList.add("is-loading");

    try {
      const response = await fetch(`/api/product.php?${params.toString()}`, {
        headers: {
          Accept: "application/json",
        },
      });

      const payload = await response.json();

      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || "Unable to load this product.");
      }

      detailHost.innerHTML = payload.html || "";
      initMediaGallery(detailHost);

      if (payload.title) {
        document.title = payload.title;
      }
    } catch (error) {
      renderState("Unable to load this product.", error.message || "Please try again in a moment.");
    } finally {
      detailHost.classList.remove("is-loading");
    }
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

  document.addEventListener("submit", async (event) => {
    const form = event.target.closest("[data-review-form]");

    if (!form) {
      return;
    }

    event.preventDefault();

    const submitter = event.submitter || form.querySelector('button[type="submit"]');

    if (submitter) {
      submitter.setAttribute("disabled", "disabled");
    }

    try {
      const formData = new FormData(form);

      const payload = await submitReviewRequest(formData);

      if (!payload) {
        return;
      }

      await loadProduct();
      storefront.flashMessage?.(payload.message || "Review updated.");
    } catch (error) {
      storefront.flashMessage?.(error.message || "Unable to update your review.", "error");
    } finally {
      if (submitter) {
        submitter.removeAttribute("disabled");
      }
    }
  });

  document.addEventListener("click", async (event) => {
    const button = event.target.closest("[data-review-delete]");

    if (!button) {
      return;
    }

    const form = button.closest("[data-review-form]");

    if (!form) {
      return;
    }

    const confirmed = window.confirm("Are you sure you want to delete your review?");

    if (!confirmed) {
      return;
    }

    button.setAttribute("disabled", "disabled");

    try {
      const formData = new FormData();
      formData.set("csrf_token", form.querySelector('[name="csrf_token"]')?.value || "");
      formData.set("product_id", form.querySelector('[name="product_id"]')?.value || "");
      formData.set("action", "delete");

      const payload = await submitReviewRequest(formData);

      if (!payload) {
        return;
      }

      await loadProduct();
      storefront.flashMessage?.(payload.message || "Review removed.");
    } catch (error) {
      storefront.flashMessage?.(error.message || "Unable to delete your review.", "error");
    } finally {
      button.removeAttribute("disabled");
    }
  });

  document.addEventListener("click", (event) => {
    const filterButton = event.target.closest("[data-review-filter]");

    if (!filterButton) {
      return;
    }

    const reviewSection = filterButton.closest("[data-product-reviews]");

    if (!reviewSection) {
      return;
    }

    const filter = filterButton.dataset.reviewFilter || "all";
    const cards = [...reviewSection.querySelectorAll("[data-review-card]")];
    const emptyState = reviewSection.querySelector("[data-review-empty-state]");

    reviewSection.querySelectorAll("[data-review-filter]").forEach((button) => {
      const isActive = button === filterButton;
      button.classList.toggle("is-active", isActive);
      button.setAttribute("aria-pressed", isActive ? "true" : "false");
    });

    let visibleCount = 0;

    cards.forEach((card) => {
      const rating = card.dataset.reviewRating || "";
      const hasMedia = card.dataset.reviewHasMedia === "true";

      let isVisible = true;

      if (filter.startsWith("rating-")) {
        isVisible = rating === filter.replace("rating-", "");
      } else if (filter === "media") {
        isVisible = hasMedia;
      }

      card.hidden = !isVisible;
      card.classList.toggle("product-review-card--hidden", !isVisible);
      card.style.display = isVisible ? "" : "none";

      if (isVisible) {
        visibleCount += 1;
      }
    });

    if (emptyState) {
      emptyState.hidden = visibleCount > 0;
      emptyState.style.display = visibleCount > 0 ? "none" : "";
    }
  });

  loadProduct();
})();
