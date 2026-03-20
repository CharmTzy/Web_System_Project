/**
 * Session-aware header for static HTML pages.
 * Fetches /api/session.php and updates the header links
 * to show logged-in state (name, role, sign out) or guest state (sign in).
 */
(() => {
  const actionsEl = document.querySelector('.site-header__actions--market');
  const mobileLinksEl = document.querySelector('.mobile-drawer__links');

  if (!actionsEl) return;

  fetch('/api/session.php', {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  })
    .then((r) => r.json())
    .then((data) => {
      if (!data.ok || !data.logged_in) return;

      const user = data.user;
      const role = user.role;

      let dashboardUrl = '/profile.php';
      if (role === 'admin') dashboardUrl = '/admin/';
      else if (role === 'seller') dashboardUrl = '/seller/';
      else if (role === 'customer') dashboardUrl = '/profile.php';

      // Update desktop header
      actionsEl.innerHTML =
        '<a class="header-action-link" href="' + dashboardUrl + '">' + escHtml(user.name) + '</a>' +
        '<span class="header-action-badge">' + escHtml(capitalize(role)) + '</span>' +
        '<a class="header-action-link" href="/logout.php">Sign out</a>' +
        '<a class="header-cart-link header-cart-link--market" href="/cart.html">' +
          '<span>Cart</span>' +
          '<strong data-cart-count>0</strong>' +
        '</a>';

      // Update mobile drawer links
      if (mobileLinksEl) {
        mobileLinksEl.innerHTML =
          '<a href="' + dashboardUrl + '" data-bs-dismiss="offcanvas">' + escHtml(user.name) + ' (' + escHtml(capitalize(role)) + ')</a>' +
          '<a href="/profile.php" data-bs-dismiss="offcanvas">Profile</a>' +
          '<a href="/logout.php" data-bs-dismiss="offcanvas">Sign out</a>' +
          '<a href="/cart.html">Cart</a>';
      }
    })
    .catch(() => {
      // Silently fail — guest state is the default
    });

  function escHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }
})();
