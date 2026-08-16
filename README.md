===  Email validation - Server ===
	Contributors: PickPlugins
	Donate link: https://www.pickplugins.com/Email validation
	Tags: product slider, woocommerce slider, carousel slider, product category slider, woo slider, carousel slider,  woocommerce product slider
	Requires at least: 3.8
	Tested up to: 6.7
	Stable tag: 1.0.6
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html

	Fully responsive and mobile ready Carousel Slider for your woo-commerce product. unlimited slider anywhere via short-codes and easy admin setting.

== Description ==



# ComboStore Server

**ComboStore Server** is a WordPress plugin that provides the backend infrastructure for **ComboStore**, a modern Next.js-based eCommerce platform powered by WordPress.

It extends WordPress with custom REST APIs, database tables, authentication, eCommerce functionality, order management, shipping, payments, customers, products, and other services required by the ComboStore frontend.

The plugin is designed to work with a **Next.js frontend**, typically deployed on Vercel, while WordPress acts as the backend/API server.

---

## Architecture

ComboStore uses a decoupled/headless architecture:

```text
                    ┌──────────────────────┐
                    │      Customer        │
                    │      Browser         │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │   ComboStore Next.js  │
                    │       Frontend       │
                    │      Vercel/CDN      │
                    └──────────┬───────────┘
                               │
                         REST API / HTTPS
                               │
                               ▼
                    ┌──────────────────────┐
                    │   WordPress Server   │
                    │  ComboStore Server   │
                    │       Plugin         │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │      MySQL / DB      │
                    └──────────────────────┘
```

### Components

* **Frontend:** Next.js
* **Backend:** WordPress
* **Backend Plugin:** ComboStore Server
* **Database:** MySQL/MariaDB
* **Frontend Hosting:** Vercel or other Node.js hosting
* **Backend Hosting:** Any WordPress-compatible hosting
* **Communication:** REST API over HTTPS

---

# Requirements

Before installing the plugin, make sure your server meets the following requirements.

### WordPress

* WordPress 6.x or later
* Pretty Permalinks enabled
* REST API enabled
* HTTPS/SSL strongly recommended

### PHP

* PHP 8.1+
* PHP extensions required by WordPress
* MySQLi/PDO support

### Database

* MySQL 5.7+ or MariaDB equivalent

### Frontend

ComboStore Server is designed to work with the ComboStore Next.js application.

Recommended:

* Node.js 20+
* Next.js
* HTTPS-enabled frontend domain

---

# Installation

## 1. Upload the Plugin

Upload the plugin to:

```text
/wp-content/plugins/combostore-server/
```

The directory should contain the main plugin file and its required dependencies.

Example:

```text
combostore-server/
├── combostore-server.php
├── includes/
├── admin/
├── api/
├── database/
├── assets/
└── readme.md
```

## 2. Activate the Plugin

Go to:

```text
WordPress Admin
→ Plugins
→ Installed Plugins
```

Find **ComboStore Server** and click **Activate**.

During activation, the plugin will create or update the required database tables.

---

# Configuration

After activation, configure the plugin from the WordPress administration area.

Typical configuration includes:

* Store settings
* API settings
* Authentication
* Shipping
* Payment gateways
* Email
* CORS
* Security
* Webhooks
* Frontend application settings

---

# Environment Configuration

The ComboStore Next.js frontend should store backend URLs and secrets in environment variables.

Example:

```env
NEXT_PUBLIC_STORE_URL=https://example.com
NEXT_PUBLIC_SERVER_URL=https://server.example.com
NEXT_PUBLIC_APP_URL=https://dashboard.example.com
```

### Important

Do **not** expose private API keys, secret keys, JWT secrets, payment credentials, or database credentials through `NEXT_PUBLIC_*` variables.

Anything prefixed with `NEXT_PUBLIC_` can be exposed to the browser.

Private credentials should remain on the WordPress server or inside server-side Next.js environment variables.

---

# REST API

ComboStore Server provides REST API endpoints for communication with the Next.js frontend.

The general API structure is:

```text
https://server.example.com/wp-json/
```

ComboStore-specific endpoints may use a namespace such as:

```text
https://server.example.com/wp-json/combostore/v1/
```

Example:

```text
GET /wp-json/combostore/v1/products
```

```text
GET /wp-json/combostore/v1/products/{id}
```

```text
POST /wp-json/combostore/v1/orders
```

```text
GET /wp-json/combostore/v1/orders/{id}
```

> Always use HTTPS in production.

---

# Authentication

ComboStore Server supports authenticated API requests.

Depending on the implementation, authentication can be handled through:

* WordPress authentication
* JWT
* API keys
* Application authentication
* Custom ComboStore authentication

Authenticated requests should send the required credentials using HTTP headers.

Example:

```http
Authorization: Bearer YOUR_TOKEN
```

If an API key is required:

```http
X-API-Key: YOUR_API_KEY
```

Never place secret credentials inside frontend source code.

---

# CORS

Because ComboStore commonly uses separate frontend and backend domains, CORS must be configured correctly.

Example:

```text
Frontend:
https://store.example.com

Backend:
https://server.example.com
```

The WordPress server must allow requests from the trusted frontend origin.

Example response headers:

```http
Access-Control-Allow-Origin: https://store.example.com
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key
```

### Production Recommendation

Do not use:

```http
Access-Control-Allow-Origin: *
```

when authenticated requests or private customer data are involved.

Allow only trusted frontend domains.

---

# Database

ComboStore Server uses custom WordPress database tables for eCommerce-specific data.

Typical tables include:

```text
wp_cstore_orders
wp_cstore_order_items
wp_cstore_purchases
wp_cstore_expenses
wp_cstore_shipping_zones
wp_cstore_shipping_zone_locations
wp_cstore_shipping_methods
wp_cstore_shipping_rates
wp_cstore_pickup_locations
wp_cstore_shipping_carriers
```

The actual WordPress database prefix may be different.

For example:

```text
wp_
```

could be:

```text
abc_
```

The plugin should always use:

```php
$wpdb->prefix
```

instead of assuming that the database prefix is `wp_`.

---

# Orders

The order system manages the complete customer purchase lifecycle.

An order may contain:

* Customer information
* Billing information
* Shipping information
* Products
* Quantities
* Prices
* Discounts
* Shipping charges
* Taxes
* Payment method
* Payment status
* Order status
* Shipping method
* Tracking information
* Order notes

Example order flow:

```text
Pending
   ↓
Processing
   ↓
Packed
   ↓
Shipped
   ↓
Delivered
```

Orders may also be:

```text
Cancelled
Refunded
Failed
```

depending on the business workflow.

---

# Products

Product information can be managed through WordPress and exposed through the ComboStore API.

Depending on the implementation, products may support:

* Product title
* Description
* Short description
* SKU
* Price
* Sale price
* Stock
* Images
* Categories
* Tags
* Attributes
* Variations
* Weight
* Dimensions
* Status

The Next.js frontend retrieves product data through the REST API.

---

# Customers

ComboStore Server manages customer-related information required for orders and checkout.

Customer data may include:

* Name
* Email
* Phone
* Billing address
* Shipping address
* Order history
* Customer notes

Sensitive customer information should only be returned through authenticated endpoints where appropriate.

---

# Shipping

ComboStore Server supports configurable shipping methods and shipping zones.

The shipping system can be organized as:

```text
Shipping Zone
    ↓
Zone Location
    ↓
Shipping Method
    ↓
Shipping Rate
```

Example:

```text
Bangladesh
 ├── Rangpur
 │    ├── Home Delivery
 │    └── Local Pickup
 │
 └── Dhaka
      └── Courier Delivery
```

Shipping rates can depend on factors such as:

* Location
* Order amount
* Product
* Weight
* Quantity
* Shipping method
* Delivery zone

---

# Multiple Shipping Methods

An order containing multiple products may require different delivery rules.

The shipping calculation should determine the applicable methods based on:

1. Customer location
2. Product shipping requirements
3. Shipping zone
4. Shipping method
5. Shipping rate
6. Order contents

The final checkout response should provide the available shipping options and their calculated prices.

---

# Payment Gateways

ComboStore Server can integrate with payment gateways through backend APIs.

Examples include:

* bKash
* Nagad
* AamarPay
* SSLCommerz
* Cash on Delivery
* Custom payment gateways

Payment gateway credentials must be stored securely on the backend.

Never expose merchant secrets in:

```text
NEXT_PUBLIC_*
```

environment variables.

The recommended architecture is:

```text
Next.js
   │
   │ Create Payment
   ▼
WordPress API
   │
   ▼
Payment Gateway
   │
   ▼
Payment Callback/Webhook
   │
   ▼
WordPress
   │
   ▼
Update Order
```

---

# Webhooks & Callbacks

Payment providers may send callbacks or webhooks to the WordPress server.

The callback endpoint should:

1. Validate the request.
2. Verify the payment transaction.
3. Identify the order.
4. Confirm the payment status.
5. Update the order.
6. Prevent duplicate processing.

Never trust a payment status received directly from the browser.

Payment verification should happen server-side.

---

# Security

Security is a major requirement because ComboStore handles customer and order information.

### Recommended Practices

* Always use HTTPS.
* Validate all API requests.
* Sanitize input.
* Escape output.
* Use `$wpdb->prepare()` for SQL queries.
* Validate authentication tokens.
* Restrict CORS origins.
* Protect administrative endpoints.
* Verify payment callbacks.
* Never expose database credentials.
* Never expose private API secrets.
* Rate-limit sensitive endpoints where appropriate.

### SQL Queries

Always use prepared statements.

Correct:

```php
$wpdb->prepare(
    "SELECT * FROM {$table_orders} WHERE id = %d",
    $order_id
);
```

Avoid:

```php
"SELECT * FROM {$table_orders} WHERE id = $order_id"
```

---

# API Error Response

APIs should return consistent JSON responses.

Example success response:

```json
{
    "success": true,
    "data": {
        "id": 123,
        "status": "processing"
    }
}
```

Example error response:

```json
{
    "success": false,
    "message": "Invalid order ID"
}
```

HTTP status codes should be used appropriately.

Common codes:

```text
200 OK
201 Created
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
409 Conflict
422 Unprocessable Entity
500 Internal Server Error
```

---

# Development

Clone or download the plugin source code into the WordPress plugins directory.

Example:

```bash
cd wp-content/plugins

git clone YOUR_REPOSITORY_URL combostore-server
```

Activate the plugin from WordPress Admin.

For development, enable WordPress debugging:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Logs will normally be available at:

```text
wp-content/debug.log
```

---

# Code Structure

A recommended structure is:

```text
combostore-server/
│
├── combostore-server.php
│
├── includes/
│   ├── class-database.php
│   ├── class-auth.php
│   ├── class-settings.php
│   └── helpers.php
│
├── api/
│   ├── products/
│   ├── orders/
│   ├── customers/
│   ├── shipping/
│   ├── payments/
│   └── users/
│
├── database/
│   ├── class-install.php
│   ├── class-upgrade.php
│   └── migrations/
│
├── admin/
│   ├── settings/
│   └── pages/
│
├── integrations/
│   ├── bkash/
│   ├── nagad/
│   ├── aamarpay/
│   └── sslcommerz/
│
├── assets/
│
├── languages/
│
└── readme.md
```

The exact structure may differ depending on the implementation.

---

# Database Migrations

When modifying database structures, avoid directly changing existing production tables.

Instead, create a database version and migration process.

Example:

```php
define('CSTORE_DB_VERSION', '1.2.0');
```

When the plugin is upgraded:

```text
1.0.0
   ↓
1.1.0
   ↓
1.2.0
```

Each migration should be executed only once.

This prevents existing customer/order data from being lost during plugin updates.

---

# Production Deployment

Recommended production architecture:

```text
                 Internet
                    │
          ┌─────────┴─────────┐
          │                   │
          ▼                   ▼
     Next.js App         WordPress API
       Vercel              Server
          │                   │
          └─────────┬─────────┘
                    │
                 MySQL
```

### Frontend

Deploy the ComboStore Next.js application to:

* Vercel
* VPS
* Dedicated server
* Other supported hosting

### Backend

Deploy WordPress + ComboStore Server to:

* VPS
* CloudPanel
* Managed WordPress hosting
* cPanel
* Other WordPress-compatible hosting

---

# Updating the Plugin

Before updating the plugin:

1. Backup the database.
2. Backup the plugin files.
3. Check the migration/version requirements.
4. Update the plugin.
5. Verify REST APIs.
6. Verify checkout.
7. Verify payment gateways.
8. Verify shipping calculations.
9. Verify existing orders.

Never deploy database changes to production without a backup.

---

# Environment Separation

For larger deployments, maintain separate environments:

```text
Development
     ↓
Staging
     ↓
Production
```

Example:

```text
dev-server.example.com
staging-server.example.com
server.example.com
```

Each environment should use its own:

* Database
* API credentials
* Payment credentials
* API keys
* Frontend URL
* CORS configuration

---

# Troubleshooting

## REST API Not Working

Check:

```text
WordPress → Settings → Permalinks
```

Save the permalink settings again.

Then test:

```text
/wp-json/
```

If the REST API is unavailable, check:

* WordPress REST API
* Rewrite rules
* Server configuration
* SSL
* Security/firewall plugins
* PHP errors

---

## CORS Error

If the browser reports:

```text
Access-Control-Allow-Origin
```

or:

```text
Request header field authorization is not allowed
```

check the server's CORS configuration.

Make sure required headers are allowed:

```text
Content-Type
Authorization
X-API-Key
```

Also make sure the frontend origin is explicitly allowed.

---

## Authentication Error

Check:

* Token validity
* API key
* Authorization header
* Token expiration
* WordPress user permissions
* Server clock/timezone
* CORS configuration

---

## Database Error

Check:

```text
wp-content/debug.log
```

Also verify:

* Database credentials
* Table names
* Database permissions
* Migration version
* SQL syntax

---

# Backup

Before performing major updates:

### Database

Create a complete MySQL/MariaDB backup.

### Files

Back up:

```text
wp-content/plugins/combostore-server/
```

and any custom configuration.

For production stores, automated daily database backups are strongly recommended.

---

# API Documentation

API documentation should be maintained separately as the API grows.

Recommended documentation structure:

```text
API
├── Authentication
├── Products
├── Categories
├── Customers
├── Cart
├── Checkout
├── Orders
├── Shipping
├── Payments
├── Coupons
├── Users
└── Webhooks
```

Each endpoint should document:

* HTTP method
* Endpoint
* Authentication
* Parameters
* Request body
* Response
* Error responses
* Required permissions

---

# Contributing

Contributions are welcome.

Before submitting changes:

1. Follow the existing coding conventions.
2. Test the REST API.
3. Test database changes.
4. Test authentication.
5. Test checkout.
6. Test payment integrations.
7. Test shipping calculations.
8. Ensure existing functionality is not broken.

---

# License

ComboStore Server is distributed according to the license specified by the project owner.

---

# Support

For ComboStore development and technical support, please contact the ComboStore development team.

**ComboStore**
Modern headless eCommerce powered by WordPress and Next.js.

---

## Related Projects

### ComboStore Frontend

The Next.js frontend communicates with this WordPress plugin through the REST API.

```text
Next.js → ComboStore Server → WordPress → Database
```

### Recommended Production Setup

```text
Frontend
Next.js + Vercel

Backend
WordPress + ComboStore Server

Database
MySQL / MariaDB

Communication
HTTPS + REST API

Authentication
Secure server-side authentication

Payments
Backend payment gateway integrations
```

---

## Project Status

ComboStore Server is actively developed and may evolve as new eCommerce features, APIs, integrations, and database modules are introduced.




== Screenshots ==

1. screenshot-1
2. screenshot-2
3. screenshot-3
4. screenshot-4
5. screenshot-5

== Changelog ==


	= 1.0 =
    * 24/02/2025 Initial release.