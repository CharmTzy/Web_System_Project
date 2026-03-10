# Meridian Mart — Architecture Document

## 1. Project Overview

Meridian Mart is a multi-seller B2C e-commerce platform built with vanilla PHP (no framework), MySQL, Bootstrap 5, and custom CSS/JS. It is a course project (INF1005) split across multiple systems. The current codebase covers **System 2: Storefront catalog and cart**.

The next module to build is **User Management & Address Management** with three roles: **admin**, **seller**, and **customer** — each with role-specific UI.

---

## 2. Current System Architecture

### 2.1 High-Level Flow

```
Browser
  │
  ├── GET /                 → public/index.php    (storefront page)
  ├── GET /cart.php         → public/cart.php      (cart review page)
  ├── GET /api/products.php → JSON catalog feed    (AJAX filtering)
  └── POST /api/cart.php    → JSON cart mutations   (add/update/remove)
        │
        ▼
  bootstrap.php
    ├── loads .env
    ├── registers PSR-4 autoloader (App\\ → app/)
    └── returns config arrays
        │
        ▼
  AppFactory::storefront($config)
    ├── Database → PDO connection (or null)
    ├── Repository: ProductRepository (MySQL) or SampleProductRepository (fallback)
    ├── CatalogService  → product listing, filtering, sorting
    └── CartService     → session-based cart (add, update, remove, summary)
        │
        ▼
  Views: resources/views/
    ├── layouts/header.php, footer.php
    └── partials/product-card.php, catalog-grid.php, cart-panel.php, cart-table.php
```

### 2.2 Directory Structure

```
Web_System_Project/
├── app/
│   ├── Repositories/
│   │   ├── CatalogRepositoryInterface.php   # Contract for product data access
│   │   ├── ProductRepository.php            # MySQL implementation (PDO)
│   │   └── SampleProductRepository.php      # In-memory fallback (no DB needed)
│   ├── Services/
│   │   ├── CatalogService.php               # Browse/filter/sort products
│   │   └── CartService.php                  # Session cart: add, update, remove, summary
│   └── Support/
│       ├── AppFactory.php                   # Wires repositories + services
│       ├── Database.php                     # PDO connection wrapper
│       ├── SampleCatalog.php                # Hard-coded product/category data
│       └── helpers.php                      # env(), e(), money(), render(), csrf helpers
├── config/
│   ├── app.php                              # App name, timezone, shipping config
│   └── database.php                         # MySQL connection params from .env
├── database/
│   ├── schema.sql                           # Full DB schema (users, products, carts, etc.)
│   └── seed.sql                             # Sample data (6 users, 5 categories, 10 products)
├── public/                                  # Web root (point PHP dev server here)
│   ├── index.php                            # Storefront homepage
│   ├── cart.php                             # Full cart page
│   ├── api/
│   │   ├── products.php                     # GET → filtered product JSON + HTML
│   │   └── cart.php                         # GET → cart state, POST → cart mutations
│   └── assets/
│       ├── css/app.css                      # All custom styles
│       └── js/
│           ├── store.js                     # Cart form handler, toast, drawer, quantity picker
│           ├── catalog.js                   # AJAX filtering, sort, sidebar sync
│           └── cart.js                      # Cart page quantity auto-submit
├── resources/views/
│   ├── layouts/
│   │   ├── header.php                       # <!DOCTYPE>, nav, sticky header, search bar
│   │   └── footer.php                       # Footer, toast, Bootstrap JS, page scripts
│   └── partials/
│       ├── product-card.php                 # Single product card (badges, price, add-to-cart)
│       ├── catalog-grid.php                 # Product grid loop (or empty state)
│       ├── cart-panel.php                   # Cart drawer (offcanvas) content
│       └── cart-table.php                   # Full cart page table with line items + summary
├── bootstrap.php                            # Entry point: session, env, autoloader, config
├── .env.example                             # Environment template
└── .gitignore
```

### 2.3 Tech Stack

| Layer      | Technology                                    |
|------------|-----------------------------------------------|
| Language   | PHP 8.1+ (strict_types, enums, match, named args) |
| Database   | MySQL 8 / MariaDB (InnoDB, utf8mb4)           |
| Frontend   | Bootstrap 5.3, custom CSS (CSS variables), vanilla JS |
| Fonts      | Sora (headings), Source Sans 3 (body)         |
| Server     | `php -S localhost:8000 -t public`             |
| Session    | PHP native sessions (cart storage)            |
| Security   | CSRF tokens, prepared statements (PDO), htmlspecialchars escaping |

### 2.4 Design Patterns in Use

- **Repository pattern** — `CatalogRepositoryInterface` with two implementations (MySQL / sample fallback)
- **Service layer** — `CatalogService` and `CartService` encapsulate business logic
- **Factory** — `AppFactory::storefront()` wires dependencies based on DB availability
- **View partials** — reusable PHP templates via `render()` helper with `extract()`
- **Graceful degradation** — app works without MySQL by falling back to `SampleProductRepository`

---

## 3. Current Database Schema

### 3.1 Entity-Relationship Summary

```
users (id, name, email, password_hash, role[admin|seller|customer])
  │
  ├── 1:1  seller_profiles (store_name, store_slug, support_email)
  │
  ├── 1:N  products (via seller_id)
  │         └── N:1 categories
  │
  └── 1:N  carts (via customer_id, nullable)
            └── 1:N cart_items
                      └── N:1 products
```

### 3.2 Existing Tables

| Table             | Purpose                              | Key Columns                          |
|-------------------|--------------------------------------|--------------------------------------|
| `users`           | All user accounts                    | id, name, email, password_hash, role |
| `seller_profiles` | Extended seller info                 | user_id (PK/FK), store_name, store_slug |
| `categories`      | Product categories                   | id, name, slug                       |
| `products`        | Product listings                     | id, seller_id, category_id, sku, name, price, stock_quantity |
| `carts`           | Shopping carts                       | id, customer_id, session_token, status |
| `cart_items`      | Cart line items                      | id, cart_id, product_id, quantity, unit_price |

---

## 4. User Management Module — Architecture Plan

### 4.1 Scope

Build authentication (register, login, logout, session management) and profile management for three roles. Each role has a different dashboard/UI after login.

### 4.2 Roles & Permissions

| Capability                    | Admin | Seller | Customer |
|-------------------------------|:-----:|:------:|:--------:|
| Register (self-service)       |   -   |   -    |    Y     |
| Login / logout                |   Y   |   Y    |    Y     |
| View own profile              |   Y   |   Y    |    Y     |
| Edit own profile              |   Y   |   Y    |    Y     |
| Manage addresses              |   -   |   -    |    Y     |
| View seller store profile     |   Y   |   Y    |    -     |
| Edit seller store profile     |   -   |   Y    |    -     |
| List / manage all users       |   Y   |   -    |    -     |
| Create seller accounts        |   Y   |   -    |    -     |
| Deactivate / reactivate users |   Y   |   -    |    -     |

> **Note**: Admin and seller accounts are created by an admin. Only customers self-register.

### 4.3 New Database Tables

```sql
-- Customer shipping/billing addresses
CREATE TABLE addresses (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    label       VARCHAR(50)  NOT NULL DEFAULT 'Home',   -- e.g. Home, Office, Other
    recipient   VARCHAR(120) NOT NULL,
    line_1      VARCHAR(255) NOT NULL,
    line_2      VARCHAR(255) DEFAULT NULL,
    city        VARCHAR(100) NOT NULL,
    state       VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20)  NOT NULL,
    country     VARCHAR(80)  NOT NULL DEFAULT 'Singapore',
    phone       VARCHAR(30)  DEFAULT NULL,
    is_default  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_addresses_user (user_id),
    CONSTRAINT fk_addresses_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

The existing `users` table already has the `role` ENUM and `password_hash` column — no schema change needed there. You may optionally add:

```sql
ALTER TABLE users
    ADD COLUMN phone       VARCHAR(30) DEFAULT NULL AFTER email,
    ADD COLUMN avatar_url  VARCHAR(255) DEFAULT NULL AFTER phone,
    ADD COLUMN is_active   TINYINT(1) NOT NULL DEFAULT 1 AFTER role;
```

### 4.4 Proposed File Structure (New Files)

```
app/
├── Repositories/
│   ├── UserRepository.php              # CRUD for users table
│   └── AddressRepository.php           # CRUD for addresses table
├── Services/
│   ├── AuthService.php                 # register, login, logout, session guard
│   ├── UserService.php                 # profile updates, admin user management
│   └── AddressService.php              # customer address CRUD, default toggling
└── Support/
    └── helpers.php                     # Add: auth(), current_user(), redirect(), is_role()

public/
├── login.php                           # Login form page
├── register.php                        # Customer registration page
├── logout.php                          # Destroy session, redirect
├── profile.php                         # View/edit own profile (all roles)
├── admin/
│   ├── index.php                       # Admin dashboard
│   ├── users.php                       # User list with search, filter by role
│   └── user-edit.php                   # Edit any user / create seller
├── seller/
│   ├── index.php                       # Seller dashboard
│   └── store-profile.php              # Edit store name, slug, support email
├── customer/
│   └── addresses.php                   # List, add, edit, delete addresses
└── api/
    ├── auth.php                        # POST login/register JSON endpoint
    ├── users.php                       # Admin user management API
    └── addresses.php                   # Customer address CRUD API

resources/views/
├── layouts/
│   └── header.php                      # Update: show login/logout, role nav links
├── auth/
│   ├── login-form.php
│   └── register-form.php
├── profile/
│   └── profile-form.php
├── admin/
│   ├── dashboard.php
│   ├── user-list.php
│   └── user-form.php
├── seller/
│   ├── dashboard.php
│   └── store-form.php
└── customer/
    ├── address-list.php
    └── address-form.php
```

### 4.5 Authentication Flow

```
  [Login Page]
       │
       ▼
  POST /api/auth.php  { action: 'login', email, password, csrf_token }
       │
       ▼
  AuthService::login(email, password)
       ├── UserRepository::findByEmail(email)
       ├── password_verify(password, password_hash)
       ├── Check is_active flag
       ├── Regenerate session ID (session fixation prevention)
       └── Store in $_SESSION: user_id, role, name
       │
       ▼
  Redirect based on role:
       ├── admin    → /admin/
       ├── seller   → /seller/
       └── customer → /
```

### 4.6 Session & Authorization Helpers

Add to `helpers.php`:

```php
function auth(): ?array                    // Returns current user array or null
function current_user_id(): ?int           // Shortcut for auth()['id']
function is_logged_in(): bool              // auth() !== null
function is_role(string ...$roles): bool   // Check current user's role
function require_auth(): void              // Redirect to /login.php if not logged in
function require_role(string ...$roles): void  // 403 if role mismatch
function redirect(string $url): never      // header('Location: ...') + exit
```

### 4.7 Role-Specific UI Routing

| URL Pattern       | Access        | Purpose                                    |
|--------------------|---------------|--------------------------------------------|
| `/`                | Public        | Storefront (unchanged)                     |
| `/cart.php`        | Public        | Cart (unchanged, but link to user if logged in) |
| `/login.php`       | Guest only    | Login form                                 |
| `/register.php`    | Guest only    | Customer self-registration                 |
| `/logout.php`      | Authenticated | Destroy session                            |
| `/profile.php`     | Authenticated | Own profile (all roles)                    |
| `/admin/*`         | Admin only    | Admin dashboard, user management           |
| `/seller/*`        | Seller only   | Seller dashboard, store profile            |
| `/customer/*`      | Customer only | Address book                               |

### 4.8 Header Navigation Changes

The header should adapt based on login state and role:

**Guest (not logged in):**
```
[ Shop ] [ Cart ] ........................... [ Login ] [ Register ]
```

**Customer:**
```
[ Shop ] [ Cart ] [ My Addresses ] .............. [ Hi, Name ▾ ] [ Logout ]
```

**Seller:**
```
[ Shop ] [ Seller Dashboard ] [ Store Profile ] ..... [ Hi, Name ▾ ] [ Logout ]
```

**Admin:**
```
[ Shop ] [ Admin Dashboard ] [ Manage Users ] ....... [ Hi, Name ▾ ] [ Logout ]
```

---

## 5. Address Management — Detail Design

### 5.1 Features

- Customer can **add** multiple addresses (label: Home, Office, Other)
- Customer can **edit** and **delete** addresses
- Customer can set one address as **default** (used for checkout handoff to System 4)
- Each address stores: recipient name, line 1, line 2, city, state, postal code, country, phone
- Max 5 addresses per customer (enforced in service layer)

### 5.2 Address CRUD API

| Method | Endpoint               | Action                        |
|--------|------------------------|-------------------------------|
| GET    | `/api/addresses.php`           | List current user's addresses |
| POST   | `/api/addresses.php`           | Create new address            |
| POST   | `/api/addresses.php?action=update` | Update existing address   |
| POST   | `/api/addresses.php?action=delete` | Delete address            |
| POST   | `/api/addresses.php?action=set-default` | Set as default       |

All endpoints require authentication (customer role) and CSRF validation.

---

## 6. Security Considerations

| Concern                | Approach                                                       |
|------------------------|----------------------------------------------------------------|
| Password storage       | `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)             |
| Session fixation       | `session_regenerate_id(true)` on login                         |
| CSRF                   | Existing `csrf_token()` / `verify_csrf()` helpers              |
| SQL injection          | PDO prepared statements (already in use)                       |
| XSS                    | `e()` helper (htmlspecialchars) on all output                  |
| Authorization          | `require_role()` guard at top of protected pages               |
| Brute force (optional) | Rate-limit login attempts via session counter or DB timestamps |

---

## 7. Implementation Sequence

| Step | Task                                                    | Files Touched                              |
|------|---------------------------------------------------------|--------------------------------------------|
| 1    | Add `addresses` table + alter `users` table             | `database/schema.sql`, `database/seed.sql` |
| 2    | Build `UserRepository` + `AddressRepository`            | `app/Repositories/`                        |
| 3    | Build `AuthService`, `UserService`, `AddressService`    | `app/Services/`                            |
| 4    | Add auth/session helpers to `helpers.php`               | `app/Support/helpers.php`                  |
| 5    | Create login/register pages + API endpoint              | `public/login.php`, `register.php`, `api/auth.php` |
| 6    | Create profile page (shared by all roles)               | `public/profile.php`                       |
| 7    | Create admin pages (dashboard, user list, user edit)    | `public/admin/`                            |
| 8    | Create seller pages (dashboard, store profile)          | `public/seller/`                           |
| 9    | Create customer address pages + API                     | `public/customer/`, `api/addresses.php`    |
| 10   | Update header nav to show role-aware links              | `resources/views/layouts/header.php`       |
| 11   | Wire cart to logged-in user (link `carts.customer_id`)  | `CartService`, `api/cart.php`              |
