/**
 * Session-aware header for static HTML pages.
 * Fetches /api/session.php and updates the header links
 * to show logged-in state (name, role, sign out) or guest state (sign in).
 */
(() => {
  const actionsEl = document.querySelector('.site-header__actions--market');
  const mobileLinksEl = document.querySelector('.mobile-drawer__links');
  const marketSubnav = document.querySelector('.market-subnav');
  const mobileNav = document.querySelector('.mobile-drawer__nav');
  const isStorefrontCustomerScreen = document.body?.matches?.('[data-catalog-page], [data-product-screen], [data-cart-screen]') ?? false;
  const headerState = (window.__novaHeaderState = window.__novaHeaderState || {
    cartCount: 0,
    notificationCount: 0,
  });

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

      if ((role === 'admin' || role === 'seller') && isStorefrontCustomerScreen) {
        window.location.replace(role === 'admin' ? '/admin/' : '/seller/');
        return;
      }

      const notificationsUrl = '/profile.php#notifications';

      let dashboardUrl = '/profile.php';
      if (role === 'admin') dashboardUrl = '/admin/';
      else if (role === 'seller') dashboardUrl = '/seller/';
      else if (role === 'customer') dashboardUrl = '/profile.php';

      // Update desktop header
      if (role === 'customer') {
        syncCustomerNav(marketSubnav);
        syncCustomerNav(mobileNav);
        headerState.notificationCount = Number(data.notification_count || 0);

        actionsEl.innerHTML = [
          buildIconAction({
            href: dashboardUrl,
            label: 'Account',
            icon: 'account',
          }),
          buildIconAction({
            href: notificationsUrl,
            label: 'Notification',
            icon: 'notification',
            badge: headerState.notificationCount,
            badgeAttr: 'data-notification-count',
          }),
          buildIconAction({
            href: '/cart.html',
            label: 'Cart',
            icon: 'cart',
            badge: headerState.cartCount,
            badgeAttr: 'data-cart-count',
            extraAttrs: ' data-open-cart-drawer="true" aria-controls="cartDrawer" aria-haspopup="dialog"',
          }),
          buildIconAction({
            href: '/logout.php',
            label: 'Sign out',
            icon: 'signout',
          }),
        ].join('');

        applyKnownHeaderCounts();
      } else {
        actionsEl.innerHTML =
          '<a class="header-action-link" href="' + dashboardUrl + '">' + escHtml(user.name) + '</a>' +
          '<span class="header-action-badge">' + escHtml(capitalize(role)) + '</span>' +
          '<a class="header-action-link" href="/logout.php">Sign out</a>' +
          '<a class="header-cart-link header-cart-link--market" href="/cart.html" data-open-cart-drawer="true" aria-controls="cartDrawer" aria-haspopup="dialog">' +
            '<span>Cart</span>' +
            '<strong data-cart-count>0</strong>' +
          '</a>';
      }

      // Update mobile drawer links
      if (mobileLinksEl) {
        if (role === 'customer') {
          mobileLinksEl.innerHTML =
            '<a href="' + dashboardUrl + '">Account</a>' +
            '<a href="' + notificationsUrl + '">Notification</a>' +
            '<a href="/cart.html">Cart</a>' +
            '<a href="/logout.php">Sign out</a>';
        } else {
          mobileLinksEl.innerHTML =
            '<a href="' + dashboardUrl + '">' + escHtml(user.name) + ' (' + escHtml(capitalize(role)) + ')</a>' +
            '<a href="/profile.php">Profile</a>' +
            '<a href="/logout.php">Sign out</a>' +
            '<a href="/cart.html">Cart</a>';
        }
      }
    })
    .catch(() => {
      // Silently fail — guest state is the default
    });

  function applyKnownHeaderCounts() {
    document.querySelectorAll('[data-cart-count]').forEach((node) => {
      node.textContent = String(headerState.cartCount ?? 0);
    });

    document.querySelectorAll('[data-notification-count]').forEach((node) => {
      node.textContent = String(headerState.notificationCount ?? 0);
    });
  }

  function buildIconAction({ href, label, icon, badge = null, badgeAttr = '', extraAttrs = '' }) {
    return (
      '<a class="header-icon-action' + (badge !== null ? ' header-icon-action--with-badge' : '') + '" href="' + href + '"' + extraAttrs + '>' +
        '<span class="header-icon-action__icon-wrap">' +
          renderIcon(icon) +
          (badge !== null ? '<strong class="header-icon-action__badge" ' + badgeAttr + '>' + escHtml(String(badge)) + '</strong>' : '') +
        '</span>' +
        '<span class="header-icon-action__label">' + escHtml(label) + '</span>' +
      '</a>'
    );
  }

  function renderIcon(icon) {
    const icons = {
      account: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.25 4.25 0 1 0 0-8.5 4.25 4.25 0 0 0 0 8.5Zm-7 8a7 7 0 0 1 14 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
      notification: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.25a4.25 4.25 0 0 1 4.25 4.25v2.15c0 .95.32 1.88.9 2.64l1.1 1.46a.75.75 0 0 1-.6 1.2H6.35a.75.75 0 0 1-.6-1.2l1.1-1.46a4.38 4.38 0 0 0 .9-2.64V8.5A4.25 4.25 0 0 1 12 4.25Zm-1.8 13.5a1.8 1.8 0 0 0 3.6 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
      cart: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 5.5h2.2l1.5 8.2a1 1 0 0 0 1 .8h8.4a1 1 0 0 0 1-.75l1.4-5.25H7.2M9 19a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 9 19Zm7 0a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 16 19Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
      signout: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6.5H7.5A1.5 1.5 0 0 0 6 8v8a1.5 1.5 0 0 0 1.5 1.5H10M13 16.5 17.5 12 13 7.5M17.5 12H9" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
    };

    return icons[icon] || '';
  }

  function escHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function syncCustomerNav(container) {
    if (!container) return;

    const registerLink = container.querySelector('a[href="/register.php"]');
    const helpLink = container.querySelector('a[href="/help.php"]');

    if (registerLink) {
      registerLink.href = '/customer/coupons.php';
      registerLink.textContent = 'Coupons';
    }

    if (container.querySelector('a[href="/customer/orders.php"]')) {
      return;
    }

    const ordersLink = document.createElement('a');
    ordersLink.href = '/customer/orders.php';
    ordersLink.textContent = 'Orders';

    const chatLink = document.createElement('a');
    chatLink.href = '/customer/chat.php';
    chatLink.textContent = 'Chat';

    const addressesLink = document.createElement('a');
    addressesLink.href = '/customer/addresses.php';
    addressesLink.textContent = 'Addresses';

    if (helpLink) {
      container.insertBefore(ordersLink, helpLink);
      container.insertBefore(chatLink, helpLink);
      container.insertBefore(addressesLink, helpLink);
    } else {
      container.appendChild(ordersLink);
      container.appendChild(chatLink);
      container.appendChild(addressesLink);
    }
  }
})();
