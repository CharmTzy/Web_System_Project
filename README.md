# Web System Project

Customer-facing e-commerce module for INF1005 System 2: product listing, filtering, quantity updates, and shopping cart.

## What Is Included

- Responsive Bootstrap storefront for a fictional B2C store.
- Product filtering by search, category, price range, stock status, and sort order.
- PHP session-based cart with add, update, and remove actions.
- MySQL-ready schema and seed data.
- Sample-data fallback so the UI still works even when MySQL is not configured locally.

## Folder Structure

```text
Web_System_Project/
├── app/
│   ├── Repositories/   # Product data access
│   ├── Services/       # Catalog and cart business logic
│   └── Support/        # Bootstrap helpers, DB, sample catalog, factory
├── config/             # App and database config
├── database/           # schema.sql and seed.sql
├── public/
│   ├── api/            # JSON endpoints for filtering and cart updates
│   └── assets/
│       ├── css/        # UI styling
│       └── js/         # Custom storefront interactions
├── resources/views/    # Layouts and partials
├── .env.example
└── bootstrap.php
```

## Run It

1. Copy `.env.example` to `.env`.
2. Create the database in MySQL, then run [schema.sql](/Users/waiyan/Documents/Web_System_Project/database/schema.sql) and [seed.sql](/Users/waiyan/Documents/Web_System_Project/database/seed.sql).
3. Start the PHP server from the project root:

```bash
php -S localhost:8000 -t public
```

4. Open [http://localhost:8000](http://localhost:8000).

If `.env` is missing or MySQL is unavailable, the storefront automatically falls back to the built-in sample catalog so you can still demo the flow.

## Stripe Checkout + Webhook (Checkout → Success)

This project now supports a Stripe-based checkout flow for customers:

1. Customer submits `/customer/checkout.php`
2. Server creates a **pending order** and a Stripe Checkout Session
3. Customer pays on Stripe-hosted checkout page
4. Stripe redirects to `/customer/checkout-success.php`
5. Stripe webhook (`/api/stripe-webhook.php`) confirms payment and marks order as `paid`

### Required `.env` values

```env
APP_URL="http://localhost:8000"
APP_CURRENCY="sgd"
STRIPE_SECRET_KEY="sk_test_..."
STRIPE_PUBLISHABLE_KEY="pk_test_..."
STRIPE_WEBHOOK_SECRET="whsec_..."
STRIPE_WEBHOOK_TOLERANCE="300"
```

### Local test steps

1. Start your app:

```bash
php -S localhost:8000 -t public
```

2. Install + login Stripe CLI (once):

```bash
stripe login
```

3. Forward webhook events to local app:

```bash
stripe listen --forward-to localhost:8000/api/stripe-webhook.php
```

4. Copy the shown `whsec_...` signing secret into `STRIPE_WEBHOOK_SECRET` in `.env`.

5. Run checkout in browser as a customer and use Stripe test card:

   - Card number: `4242 4242 4242 4242`
   - Any future expiry date
   - Any 3-digit CVC

6. Confirm:
   - success page shows paid status/order number/amount
   - `/customer/orders.php` shows order with paid status and card snapshot
   - cart is cleared after successful webhook processing

## Notes For Your Group

- This module is scoped for the customer side only.
- Roles, admin/seller product CRUD, checkout, reviews, and chat can plug into the current folder structure later.
- The cart summary card is intentionally positioned as the handoff point to System 4.
