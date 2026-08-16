===  ComboStore Server ===
	Contributors: PickPlugins
	Donate link: https://www.pickplugins.com/
	Tags: eCommerce 
	Requires at least: 3.8
	Tested up to: 6.7
	Stable tag: 1.0.0
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html

The plugin is designed to work with a Next.js frontend, typically deployed on Vercel, while WordPress acts as the backend/API server.


== Description ==


# ComboStore Server

**ComboStore Server** is a WordPress plugin that provides the backend/API functionality for **ComboStore**, a modern Next.js-based eCommerce platform powered by WordPress.

The plugin extends WordPress with the REST APIs and backend functionality required by the ComboStore frontend, including checkout, orders, payments, shipping, customers, and other eCommerce services.





# ComboStore Projects

ComboStore consists of three separate projects that work together to provide the complete eCommerce platform.

## 1. ComboStore Server

**ComboStore Server** is the WordPress backend/plugin.

It provides:

* WordPress REST API
* eCommerce backend functionality
* Product and category APIs
* Cart and checkout APIs
* Order management
* Customer management
* Shipping functionality
* Payment gateway integrations
* Authentication
* Database functionality
* Other backend services required by ComboStore

This project is installed as a WordPress plugin.

---

## 2. ComboStore Dashboard

**ComboStore Dashboard** is the administration application used to manage the store.

Repository:

[ComboStore Dashboard — GitHub](https://github.com/pickplugins/combostore-dashboard?utm_source=chatgpt.com)

The dashboard communicates with the ComboStore Server through REST APIs.

It is used for managing the store, including:

* Products
* Categories
* Orders
* Customers
* Shipping
* Payments
* Store settings
* Reports and analytics
* Other administrative functionality

---

## 3. ComboStore Storefront

**ComboStore Storefront** is the customer-facing eCommerce application.

Repository:

[ComboStore Storefront — GitHub](https://github.com/pickplugins/combostore-storefront?utm_source=chatgpt.com)

The storefront provides the shopping experience for customers, including:

* Product browsing
* Product details
* Categories
* Shopping cart
* Checkout
* Customer account
* Order placement
* Payment
* Shipping selection
* Other customer-facing functionality

---

# ComboStore Project Structure

The three projects work together as follows:

```text
                         ComboStore
                             │
              ┌──────────────┼──────────────┐
              │              │              │
              ▼              ▼              ▼
       WordPress Server   Dashboard     Storefront
              │              │              │
              │              │              │
              │              └──────┐ ┌─────┘
              │                     │ │
              └─────────────────────┴─┘
                         REST API
```

A typical production setup can be:

```text
┌───────────────────────────────────────────────────────────┐
│                         Customers                         │
└─────────────────────────────┬─────────────────────────────┘
                              │
                              ▼
┌───────────────────────────────────────────────────────────┐
│                  ComboStore Storefront                    │
│                     Next.js App                           │
│                                                           │
│             Customer-facing eCommerce                     │
└─────────────────────────────┬─────────────────────────────┘
                              │
                              │ REST API
                              ▼
┌───────────────────────────────────────────────────────────┐
│                  ComboStore Server                        │
│                                                           │
│              WordPress + Plugin                           │
│                                                           │
│       API • Database • Orders • Payments • Shipping       │
└─────────────────────────────┬─────────────────────────────┘
                              │
                              │ REST API
                              ▼
┌───────────────────────────────────────────────────────────┐
│                  ComboStore Dashboard                     │
│                     Next.js App                            │
│                                                           │
│                Store Administration                       │
└───────────────────────────────────────────────────────────┘
```

## Recommended Deployment

A typical deployment may use separate domains or subdomains:

```text
Storefront:
https://store.example.com

Dashboard:
https://dashboard.example.com

Server:
https://server.example.com
```

The three applications communicate through HTTPS REST APIs.

### Example

```text
Customer
   │
   ▼
store.example.com
   │
   │ REST API
   ▼
server.example.com
   │
   │
   └── WordPress + ComboStore Server


Store Manager
   │
   ▼
dashboard.example.com
   │
   │ REST API
   ▼
server.example.com
```

The **ComboStore Server** plugin is therefore the central backend component that connects the **Storefront** and **Dashboard** applications with WordPress and the store's data.








---

# Requirements

Before installing ComboStore Server, make sure your server meets the following requirements.

### WordPress

* WordPress 6.x or later
* Pretty Permalinks enabled
* WordPress REST API enabled
* HTTPS/SSL strongly recommended

### PHP

* PHP 8.1 or later
* Standard PHP extensions required by WordPress
* MySQLi/PDO support

### Database

* MySQL 5.7+ or MariaDB equivalent

---

# Required WordPress Plugins

ComboStore Server requires the following WordPress plugins to be installed and activated.

## Enable CORS

**Enable CORS** allows the ComboStore Next.js frontend to communicate with the WordPress REST API from a different domain.

Install from the official WordPress Plugin Directory:

https://wordpress.org/plugins/enable-cors/

After installation, activate the plugin from:

```text
WordPress Admin
→ Plugins
→ Installed Plugins
→ Enable CORS
```

## JWT Authentication for WP REST API

ComboStore Server uses JWT-based authentication for secure communication between the frontend and WordPress REST API.

Install:

https://wordpress.org/plugins/jwt-authentication-for-wp-rest-api/

After installation, activate the plugin from:

```text
WordPress Admin
→ Plugins
→ Installed Plugins
→ JWT Authentication for WP REST API
```

### JWT Configuration

The JWT Authentication plugin requires the WordPress server to support the appropriate HTTP authorization headers.

Depending on the hosting environment, additional server configuration may be required.

For Apache, this may require:

```apache
RewriteEngine on
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
```

For Nginx, make sure the `Authorization` header is forwarded to PHP correctly.

> Always use HTTPS when using JWT authentication in production.

---

# Installation

## 1. Upload the Plugin

Upload the ComboStore Server plugin to:

```text
/wp-content/plugins/combostore-server/
```

Example:

```text
combostore-server/
├── combostore-server.php
├── includes/
├── api/
├── database/
├── admin/
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

During activation, the plugin will create or update the required database structures.

---

# REST API

ComboStore Server provides REST API endpoints for communication with the ComboStore Next.js frontend.

The WordPress REST API is available through:

```text
https://your-domain.com/wp-json/
```

ComboStore-specific endpoints use the ComboStore API namespace.

Example:

```text
https://your-domain.com/wp-json/combostore/v1/
```

Example endpoints:

```text
GET  /wp-json/combostore/v1/products
GET  /wp-json/combostore/v1/products/{id}
POST /wp-json/combostore/v1/orders
GET  /wp-json/combostore/v1/orders/{id}
```

The exact available endpoints depend on the installed version of ComboStore Server.

---

# API Error Response

ComboStore APIs should return consistent JSON responses.

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
    "message": "Invalid request"
}
```

Common HTTP status codes include:

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

# Payment Gateways

ComboStore Server can integrate with payment gateways through backend APIs.

Supported or planned integrations may include:

* bKash
* Nagad
* AamarPay
* SSLCommerz
* Cash on Delivery
* Custom payment gateways

Payment gateway credentials must be stored securely on the server.

Private merchant credentials must never be exposed to the browser or included in public frontend environment variables.

Recommended payment flow:

```text
Next.js Frontend
       │
       ▼
ComboStore REST API
       │
       ▼
Payment Gateway
       │
       ▼
Payment Verification
       │
       ▼
Update Order
```

Payment verification should always be performed server-side.

---

# Security

ComboStore Server handles customer, order, payment, and other potentially sensitive information.

Follow these security practices:

* Always use HTTPS.
* Keep WordPress and all plugins updated.
* Keep ComboStore Server updated.
* Use strong WordPress administrator passwords.
* Protect JWT secrets.
* Protect API keys.
* Never expose payment gateway secrets.
* Validate all API input.
* Sanitize database input.
* Escape output.
* Use `$wpdb->prepare()` for SQL queries.
* Restrict API access where appropriate.
* Maintain regular backups.

### SQL Queries

Always use prepared statements.

Correct:

```php
$wpdb->prepare(
    "SELECT * FROM {$table_orders} WHERE id = %d",
    $order_id
);
```

Avoid directly inserting variables into SQL queries:

```php
"SELECT * FROM {$table_orders} WHERE id = $order_id"
```

---

# Development

For development, enable WordPress debugging when required:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

WordPress debug logs are normally available at:

```text
wp-content/debug.log
```

Do not enable displaying PHP errors directly to visitors on a production website.

---

# Troubleshooting

## REST API Not Working

Check:

```text
WordPress Admin
→ Settings
→ Permalinks
```

Save the permalink settings again.

Then test:

```text
/wp-json/
```

If the REST API is unavailable, check:

* WordPress REST API
* Permalink configuration
* Server configuration
* SSL certificate
* Firewall/security plugins
* PHP errors
* Server logs

---

## CORS Error

If the browser reports an error such as:

```text
Access-Control-Allow-Origin
```

or:

```text
Request header field authorization is not allowed
```

make sure the **Enable CORS** plugin is installed and activated.

Also verify that the server allows the required HTTP methods and headers.

Typical headers required by ComboStore include:

```text
Content-Type
Authorization
```

If ComboStore uses additional API headers, those headers must also be allowed by the server.

---

## JWT Authentication Error

If authentication fails, check:

* JWT Authentication for WP REST API is activated.
* HTTPS is enabled.
* The JWT secret is configured correctly.
* The `Authorization` header reaches WordPress/PHP.
* The WordPress user credentials are correct.
* The token has not expired.
* Server rewrite configuration is correct.

---

## Database Error

Check:

```text
wp-content/debug.log
```

Also verify:

* Database connection
* Database permissions
* Table names
* SQL syntax
* Plugin database version
* Server PHP version

---

# Backup

Before updating ComboStore Server or making significant changes:

1. Back up the WordPress database.
2. Back up the plugin files.
3. Verify that the backup can be restored.
4. Update the plugin.
5. Test the REST API.
6. Test checkout and payment functionality.

For production stores, automated database backups are strongly recommended.

---

# Updating the Plugin

Before updating:

```text
1. Backup database
2. Backup plugin files
3. Check release notes
4. Update ComboStore Server
5. Verify REST API
6. Verify checkout
7. Verify payment gateways
8. Verify shipping
```

Do not perform major production updates without a recent backup.

---

# API Documentation

API documentation should be maintained as the ComboStore API evolves.

Recommended API documentation sections:

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
* Authentication requirements
* Parameters
* Request body
* Response
* Error responses
* Required permissions

---

# Contributing

Contributions and improvements are welcome.

Before submitting changes:

1. Follow the existing coding standards.
2. Test REST API endpoints.
3. Test authentication.
4. Test checkout.
5. Test payment integrations.
6. Test shipping calculations.
7. Test database operations.
8. Verify that existing functionality is not affected.

---

# License

ComboStore Server is distributed according to the license specified by the project owner.

---

# Support

For ComboStore development and technical support, please contact the ComboStore development team.

**ComboStore**
Modern eCommerce powered by WordPress and Next.js.

---

# Related Projects

ComboStore Server is designed to work with the **ComboStore Next.js frontend**.

The general communication flow is:

```text
Next.js Frontend
       │
       │ REST API
       ▼
WordPress
       │
       └── ComboStore Server
```

Recommended WordPress plugins:

* **ComboStore Server** — Core ComboStore backend functionality
* **Enable CORS** — Cross-origin REST API requests
* **JWT Authentication for WP REST API** — JWT-based API authentication

---

# Project Status

ComboStore Server is actively developed and may evolve as new eCommerce features, APIs, integrations, and improvements are introduced.




== Screenshots ==

1. screenshot-1
2. screenshot-2
3. screenshot-3
4. screenshot-4
5. screenshot-5

== Changelog ==


	= 1.0 =
    * 24/02/2025 Initial release.