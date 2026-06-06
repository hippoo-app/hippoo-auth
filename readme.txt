=== Hippoo Auth ===

Contributors: Hippooo, hippoosupport  
Donate link: https://hippoo.app/  
Tags: WooCommerce, REST API, Social Login, Headless WooCommerce, JWT, WooCommerce API  
Requires at least: 5.8  
Tested up to: 6.8  
Stable tag: 1.0.3
Text Domain: hippoo-auth  
License: GPLv3  
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Extend your WooCommerce Store API with secure authentication endpoints for social and manual login. Ideal for custom apps, headless themes, or frontend frameworks.

== Description ==

**Hippoo Auth** enhances the WooCommerce Store API by adding secure JWT-based authentication endpoints. It supports login and signup via Google, Facebook, Apple, or manually with email and password. Once authenticated, users can retrieve and update their billing/shipping information, view order history, and more — all via API.

Perfect for developers building API-driven themes, custom mobile apps, or headless WooCommerce experiences.

Use it to build:

- Headless WooCommerce themes
- Custom frontend apps (React, Vue, etc.)
- Mobile apps using Flutter, React Native, or Kotlin
- API-based user dashboards

With Hippoo Auth, users can register, login (manually or via Google, Apple, or Facebook), and securely access:

- Order history and details  
- Billing and shipping addresses  
- JWT-based session tokens with refresh support  

🔐 Auth is handled via JWT for secure stateless sessions, ideal for frontend-heavy or decoupled environments.

📘 **Developer Docs:**  
[https://hippoo.app/hippoo-auth-web-service-documentation-for-authentication-and-user-management-in-woocommerce/](https://hippoo.app/hippoo-auth-web-service-documentation-for-authentication-and-user-management-in-woocommerce/)


== Features ==

- REST API login with JWT tokens  
- Social login (Google, Facebook, Apple)  
- Secure signup and password reset  
- Access to orders and addresses via API  
- Built-in token refresh and logout endpoint  
- Compatible with WooCommerce stores and custom frontends  
- No dependency on the Hippoo App  

== Installation ==

1. Upload the `hippoo-auth` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Visit the Hippoo Auth settings page to configure the plugin.
4. Use the endpoints immediately, or check the [official docs](https://hippoo.app/hippoo-auth-web-service-documentation-for-authentication-and-user-management-in-woocommerce/)

== External services ==

This plugin connects to third-party services for social authentication:

- **Google OAuth API**: Used to verify Google login tokens. Sends OAuth access token.  
  [Privacy Policy](https://policies.google.com/privacy), [Terms of Service](https://policies.google.com/terms)

- **Facebook Graph API**: Used to fetch user info (ID, email, name). Sends OAuth access token.  
  [Privacy Policy](https://www.facebook.com/about/privacy), [Terms](https://www.facebook.com/legal/terms)

- **Apple ID Authentication API**: Used for sign-in via Apple. Sends OAuth access token.  
  [Privacy Policy](https://www.apple.com/legal/privacy/en-ww/), [Terms](https://www.apple.com/legal/internet-services/)

These are required for enabling social login functionality.


== Frequently Asked Questions ==

**Q:** Can I use this plugin to build a headless WooCommerce frontend or mobile app?  
**A:** Yes. It’s designed specifically to enable secure access to WooCommerce data through custom APIs.

== Changelog ==

= 1.0.0 =
* Initial release.

= 1.0.1 =
* Minor bug fix.

= 1.0.3 =
* Maintenance Release

== Credits ==

This plugin was built with the Hippoo team — creators of the [Hippoo WooCommerce App](https://hippoo.app), a mobile app that helps you manage store orders, products, coupons, and more on the go.
