# GiftCraft Emporium

GiftCraft Emporium is a multi-vendor e-commerce marketplace for handmade and curated gifts. It is built with native PHP and SQLite, with a Tailwind CSS interface. There are no frameworks and no build step.

It has two kinds of account:

- **Buyers** browse, search, save to a wishlist, check out across several shops at once, track each package and review what they received.
- **Sellers** open a shop and manage products, stock, orders, reviews and their storefront from the **Seller Hub**.

The marketplace starts **completely empty**. There are no sample products, categories or accounts. Sellers create the catalogue and buyers create the orders.

## Quick start

Requirements: PHP 8.1 or newer with the `pdo_sqlite` extension enabled.

```bash
php setup.php                          # creates an empty database in data/
php -S 127.0.0.1:8000 router.php       # starts the development server
```

Open http://127.0.0.1:8000. On Windows you can double-click `run_app.bat`; on macOS or Linux run `./run_app.sh`.

Then try it end to end:

1. **Create account → I want to sell.** Name your shop, then **Add product** with a photo, price and stock.
2. In another browser (or a private window), **Create account → I want to shop**. Add the product to your cart and check out.
3. Back in the Seller Hub, open **Orders**. Accept the order, mark it shipped (with a tracking number) and then delivered.
4. As the buyer, open **My orders** to follow the package, then leave a verified review.

To wipe everything and start over, run `php setup.php --fresh`.

## Features

### Storefront
- Home page with hero, categories, new arrivals, best sellers and featured shops (each section appears once there is data for it)
- Shop page with full-text search, category filter, price range, in-stock filter, five sort orders and pagination
- Product pages with an image gallery, sale pricing, live stock status, ratings breakdown, verified reviews with seller replies, and related products
- A public storefront page for every seller
- 3D gallery and visualizer (three.js) for any product photo

### Buyers
- Persistent cart grouped by shop, with stock checks and a free-shipping nudge for each shop
- Checkout with saved addresses, gift notes and cash on delivery
- One order is split into one **package per seller**, each with its own status, tracking number and timeline
- Cancel a package until it ships (its stock is restored automatically)
- Wishlist, address book, profile and password settings
- Reviews limited to verified buyers, one review per product, editable

### Sellers (Seller Hub)
- Dashboard: 30-day sales, orders to fulfil, stock alerts, average rating, a 14-day sales chart, recent orders and top sellers
- A setup checklist for new shops
- Products: create and edit with up to 6 photos (choose a cover, remove photos), categories (pick an existing one or type a new one), compare-at price, SKU, stock, and draft or active status
- Quick inline stock updates and filters (active, draft, low stock, archived)
- Orders: status tabs and search, accept → ship (carrier and tracking) → deliver, or cancel with a reason
- Reply publicly to reviews
- Store settings: name, tagline, story and logo

## Project structure

```
index.php              Front controller: page/action routing and role-based access control
router.php             Router for PHP's built-in server (blocks private files)
setup.php              CLI: create or reset the database
includes/
  config.php           Settings (currency, shipping, upload limits...)
  schema.php           Database schema, migrated automatically on first use
  db.php               PDO connection and transaction helper
  helpers.php          Escaping, CSRF, flash messages, money, uploads, icons
  auth.php             Current user, login/logout, roles
  catalog.php          Product search, categories, reviews
  orders.php           Cart, checkout, order status workflow
actions/               POST handlers (one file per action)
views/
  layouts/             Store, Seller Hub and sign-in layouts
  partials/            Shared <head> and UI components
  seller/              Seller Hub pages
  auth/                Sign in and registration
assets/                CSS, JavaScript and images
data/                  SQLite database (git-ignored)
uploads/               Product photos and shop logos (git-ignored)
```

## Configuration

Edit `includes/config.php` or set these environment variables:

| Variable | Default | Purpose |
|---|---|---|
| `GIFTCRAFT_CURRENCY` | `$` | Currency symbol shown on prices |
| `GIFTCRAFT_DB_PATH` | `data/giftcraft.sqlite` | Database file location |
| `GIFTCRAFT_TIMEZONE` | `php.ini` value, else UTC | Timezone for dates and the sales chart |
| `GIFTCRAFT_DEBUG` | `0` | Show error details on the error page |

Shipping is a flat fee per seller package (`SHIPPING_FLAT_CENTS`), and it is free above `FREE_SHIPPING_THRESHOLD_CENTS`. All amounts are stored as integers in the smallest currency unit.

## Security

- Passwords are hashed with `password_hash`, and the session ID is regenerated when a user signs in
- Every state-changing request is a POST with a CSRF token
- Every query uses prepared statements, and all output is escaped
- Pages and actions are allow-listed, and each one declares which role may use it
- Every seller and buyer resource is checked for ownership
- Stock is decremented atomically inside a write transaction, so items cannot be oversold
- Uploads are checked by their image content, renamed randomly and size-limited
- `router.php` and `.htaccess` block web access to `data/`, `includes/`, `actions/`, `views/` and dotfiles. `uploads/.htaccess` disables scripts in the uploads folder.

## Deploying

The app runs on any PHP host with SQLite. Point the web root at the project folder and make `data/` and `uploads/` writable by PHP. On Apache, the included `.htaccess` files protect the private folders. On nginx, deny `/data`, `/includes`, `/actions` and `/views`, and route other requests to `index.php`.

The interface loads Tailwind CSS from its CDN, which is ideal for development. For production traffic, you can compile a static stylesheet with the Tailwind CLI using the theme in `views/partials/head.php`.

## Not included yet

- Online card or UPI payments: checkout supports cash on delivery, and `PAYMENT_METHODS` in `includes/orders.php` is the place to add a gateway such as Stripe or Razorpay
- Email notifications and password reset emails
- Taxes and discount codes

## License

This project is intended for educational purposes.
