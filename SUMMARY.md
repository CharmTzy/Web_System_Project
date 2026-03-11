# User Management & Address Management — Implementation Summary

## What Was Built

This module adds **authentication**, **user management** (3 roles: admin, seller, customer), and **address management** to the existing Meridian Mart storefront. Each role gets a different UI after login.

### New Files Created (30+ files)

#### Database Layer
| File | Purpose |
|------|---------|
| `database/migration_users_addresses.sql` | Adds `phone`, `avatar_url`, `is_active` columns to `users`; creates `addresses` table with foreign key to `users` |

#### Backend — Repositories (`app/Repositories/`)
| File | Purpose |
|------|---------|
| `UserRepository.php` | Full CRUD for `users` table — findById, findByEmail, emailExists, create, update, listAll, plus seller profile read/upsert |
| `AddressRepository.php` | Full CRUD for `addresses` table — findById, listByUser, countByUser, create, update, delete, setDefault, clearDefault |

#### Backend — Services (`app/Services/`)
| File | Purpose |
|------|---------|
| `AuthService.php` | Register (customer self-signup), login (email + password verify), logout (session destroy + cookie clear), session regeneration |
| `UserService.php` | Profile view/update (all roles), admin user list/create/edit, admin toggle active, seller store profile update |
| `AddressService.php` | Customer address CRUD, max 5 limit, default address toggling, auto-promote next address when default is deleted |

#### API Endpoints (`public/api/`)
| File | Methods | Purpose |
|------|---------|---------|
| `auth.php` | POST | Login and register — returns JSON with redirect URL per role |
| `users.php` | GET, POST | Profile read/update, admin create/update users, seller store update |
| `addresses.php` | GET, POST | Customer address list, create, update, delete, set-default |

#### Pages (`public/`)
| File | Access | Purpose |
|------|--------|---------|
| `login.php` | Guest only | Sign-in form (redirects to profile if already logged in) |
| `register.php` | Guest only | Customer self-registration form |
| `logout.php` | Authenticated | Destroys session, redirects to login |
| `profile.php` | Authenticated | View/edit own name, email, phone, password (all roles) |
| `admin/index.php` | Admin only | Dashboard with user count stats |
| `admin/users.php` | Admin only | User list table with search and role filter |
| `admin/user-edit.php` | Admin only | Create new user or edit existing (incl. seller store fields, active toggle) |
| `seller/index.php` | Seller only | Dashboard showing store name and slug |
| `seller/store-profile.php` | Seller only | Edit store name, slug, support email |
| `customer/addresses.php` | Customer only | Address book with add/edit modal, set-default, delete |

#### Views (`resources/views/`)
| File | Purpose |
|------|---------|
| `auth/login-form.php` | Login card with email + password fields |
| `auth/register-form.php` | Registration card with name, email, phone, password, confirm |
| `profile/profile-form.php` | Profile edit card (name, email, phone, change password) |
| `admin/dashboard.php` | Stat cards partial (total users, sellers, customers) |
| `admin/user-list.php` | User table with search bar, role dropdown, edit links |
| `admin/user-form.php` | Create/edit user form (conditionally shows seller fields, active toggle) |
| `seller/dashboard.php` | Seller stat cards (store name, slug) |
| `seller/store-form.php` | Store profile edit form |
| `customer/address-list.php` | Address card grid with default badge, edit/delete/set-default buttons |
| `customer/address-form.php` | Modal overlay form for adding/editing addresses |

#### JavaScript (`public/assets/js/`)
| File | Purpose |
|------|---------|
| `auth.js` | AJAX form submit for login/register, error display, redirect on success |
| `profile.js` | AJAX profile update, success/error messages, clears password field |
| `admin-users.js` | AJAX user create/edit, seller fields toggle on role select, active checkbox |
| `seller-store.js` | AJAX store profile save |
| `addresses.js` | Full address modal: open/close, populate on edit, AJAX create/update/delete/set-default, list refresh |

#### Updated Existing Files
| File | Change |
|------|--------|
| `resources/views/layouts/header.php` | Added `$isLoggedIn`, `$sessionRole`, `$sessionName` variables; role-aware nav links (admin/seller/customer menus); auth section (sign in/register or user name + role badge + sign out) |
| `public/assets/css/app.css` | Added styles for: auth cards, auth forms, error/success boxes, header auth row, admin table, address cards, address modal backdrop, `.btn-sm`, `.table-responsive` |

#### Deployment Files
| File | Purpose |
|------|---------|
| `Dockerfile` | PHP 8.2 + Apache + PDO MySQL, document root set to `/public`, Cloud Run PORT support |
| `public/.htaccess` | Apache rewrite rules for clean URLs |
| `.dockerignore` | Excludes `.git`, `.env`, `.vscode`, markdown files from Docker build |

---

## How It Works

### Authentication Flow
1. User visits `/login.php` or `/register.php`
2. Form submits via AJAX (`auth.js`) to `POST /api/auth.php`
3. `AuthService` validates input, verifies password with `password_verify()`, regenerates session ID
4. Session stores `user_id`, `user_role`, `user_name`
5. JSON response includes role-based redirect: admin -> `/admin/`, seller -> `/seller/`, customer -> `/`
6. Header nav dynamically shows role-specific links based on `$_SESSION`

### Role-Based Access Control
Every protected page checks at the top:
```php
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: /login.php');
    exit;
}
```
API endpoints do the same and return 401/403 JSON responses.

### Address Management
- Customer visits `/customer/addresses.php` -> sees address card grid
- "Add new address" opens a modal overlay (`address-form.php`)
- Form submits via AJAX to `POST /api/addresses.php`
- On success, address list refreshes via `GET /api/addresses.php` (returns re-rendered HTML)
- Edit populates the same modal with existing data, changes action to `update`
- Delete and set-default use inline AJAX calls
- Max 5 addresses enforced in `AddressService`; first address auto-set as default

### Admin User Management
- Admin sees all users in a searchable, filterable table
- Can create users of any role (seller creation auto-creates `seller_profiles` entry)
- Can edit name, email, phone, password, active status
- Active toggle lets admin deactivate/reactivate accounts (deactivated users cannot log in)

---

## INF1005 Assignment Requirements — Compliance Matrix

### General Requirements

| Requirement | Status | How It's Satisfied |
|-------------|--------|--------------------|
| Main landing page introducing the company | DONE (existing) | `public/index.php` — hero banner, metrics, category shortcuts, featured deals, product feed |
| Common menu for navigation | DONE | `resources/views/layouts/header.php` — sticky header with role-aware nav, search bar, cart link, auth links |
| Sub-pages for products/services | DONE (existing) | Product catalog grid on index, category filtering, individual product cards |
| Sub-page with more details (e.g. About Us) | **NEEDED** | Not yet built — recommend adding `public/about.php` |
| Back-end CRUD functionality | DONE | **Users**: Create (register + admin create), Read (list, profile), Update (profile, admin edit), Delete (deactivate). **Addresses**: full Create, Read, Update, Delete. **Products/Cart**: existing CRUD from storefront module |

### Technical Requirements

| Requirement | Status | Evidence |
|-------------|--------|----------|
| Responsive, mobile-friendly (HTML5, Bootstrap, CSS) | DONE | Bootstrap 5.3 grid, custom CSS with `@media` breakpoints at 1200px, 992px, 768px. All new pages use Bootstrap grid. |
| Custom JavaScript for dynamic client-side functionality | DONE | 8 custom JS files — AJAX form submissions, modal open/close, dynamic list refresh, quantity pickers, toast notifications, catalog filtering |
| PHP and MySQL for all back-end (CRUD) | DONE | PHP 8.1+ strict mode, PDO with prepared statements, MySQL InnoDB. Full CRUD across users, addresses, products, carts, cart_items tables |
| Proper form validation and sanitization | DONE | Server-side: `trim()`, `filter_var()`, `mb_strlen()`, `mb_substr()` in all services. Client-side: HTML5 `required`, `minlength`, `maxlength`, `type="email"`, `pattern` attributes. CSRF tokens on all POST forms |
| Secure from XSS and SQL injection | DONE | **XSS**: all output wrapped in `e()` (htmlspecialchars). **SQLi**: 100% PDO prepared statements with named parameters. No raw string interpolation in SQL |
| User passwords protected appropriately | DONE | `password_hash($password, PASSWORD_DEFAULT)` (bcrypt) for storage, `password_verify()` for login. Passwords never logged or returned in API responses |
| W3C and WCAG standards | MOSTLY | Semantic HTML5 (`<main>`, `<nav>`, `<article>`, `<section>`), `aria-label` attributes, `<label>` for all inputs, `role="search"`, `aria-live="polite"` regions, `visually-hidden` labels |

### Submission Requirements

| Requirement | Status | Notes |
|-------------|--------|-------|
| Published to LAMP server on Google Cloud | READY | `Dockerfile` provided for Google Cloud Run deployment (LAMP-compatible: Apache + PHP + MySQL via Cloud SQL) |
| .zip of source code | READY | Can be zipped from project root |
| Report in PDF | NEEDED | Must be written separately by team |
| URL to live website | PENDING | Generated after Cloud Run deployment |

### Test Credentials (for report)

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@meridianmart.test` | `password` |
| Seller | `seller1@meridianmart.test` | `password` |
| Seller | `seller2@meridianmart.test` | `password` |
| Customer | `customer@meridianmart.test` | `password` |

All seeded users share the same bcrypt hash. New customers can also self-register at `/register.php`.

---

## What's Still Needed (from other team members or future work)

| Item | Priority | Notes |
|------|----------|-------|
| About Us page (`public/about.php`) | High | Required by assignment — "sub-page with more details about the company" |
| Checkout / Order module | Medium | Cart summary card already says "Connect checkout in System 4" — this is the handoff point |
| Product CRUD for sellers | Medium | Sellers currently can only manage their store profile, not add/edit products |
| Reviews / Ratings | Low | Schema has `average_rating` and `review_count` columns but no `reviews` table yet |
| Chat system | Low | Optional feature |
