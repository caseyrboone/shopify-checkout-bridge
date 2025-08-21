# Shopify Checkout Bridge (WordPress)

Developer-oriented plugin that demonstrates a **vault-first** card flow for integrating a Shopify checkout on a WordPress page. Card details are sent **directly** to Shopify’s Card Vault from the browser; your WordPress server only calls the Shopify Admin API to create the checkout and create the payment using the returned vault session ID.

**Intended audience:** engineers integrating Shopify with a custom WP front end. This is not a turnkey gateway—treat it as a starting point you can adapt to your store, theme, and API version.

---

## Quick Start

1) **Install**
- Upload the ZIP to *Plugins → Add New → Upload* and activate.

2) **Configure**
- *Settings → Shopify Checkout*: set
  - **Store domain**: `your-store.myshopify.com`
  - **Admin access token**: `shpat_…` (custom app token with least privilege)
  - **API version**: e.g., `2024-10`

3) **Add to a page**
- Insert the shortcode:
```
[shopify_checkout]
```
- The template includes simple inputs for testing. Replace them with your own product/cart UI and pass `variant_id` + `quantity` to the REST API.

---

## How It Works

- **Front end**
  - Collects minimal checkout + payment fields.
  - Sends card data **directly** to Shopify Card Vault (`/sessions`) → receives a `vault_session_id`.
- **Back end (WP REST)**
  - `POST /wp-json/shopify-checkout-bridge/v1/create-checkout` → returns `checkout.token`.
  - `POST /wp-json/shopify-checkout-bridge/v1/pay` → charges the checkout using the `vault_session_id`.

Both calls use your Admin API token and selected API version.

---

## Endpoints

### Create Checkout
`POST /wp-json/shopify-checkout-bridge/v1/create-checkout`

**Body (example)**
```json
{
  "email": "buyer@example.com",
  "line_items": [
    { "variant_id": 1234567890, "quantity": 1 }
  ],
  "shipping_address": {
    "first_name": "Ada",
    "last_name": "Lovelace",
    "address1": "123 Example St",
    "city": "Atlanta",
    "province": "GA",
    "country": "US",
    "zip": "30301"
  },
  "billing_address": { "first_name": "Ada", "last_name": "Lovelace" }
}
```

**Response (excerpt)**
```json
{
  "ok": true,
  "checkout": { "token": "abcd...", "id": 111, "web_url": "https://..." }
}
```

### Pay for Checkout
`POST /wp-json/shopify-checkout-bridge/v1/pay`

**Body (example)**
```json
{
  "checkout_token": "abcd...",
  "amount": "10.00",
  "vault_session_id": "vs_123...",
  "billing_first_name": "Ada",
  "billing_last_name": "Lovelace"
}
```

**Response (excerpt)**
```json
{ "ok": true, "payment": { /* Shopify response */ } }
```

> **Note:** The payments payload can differ by Shopify API version / gateway requirements (e.g., `session_id` vs `credit_card[vault_session_id]`). Adjust `includes/class-scb-api.php::rest_pay()` as needed.

---

## Front-End Notes

- JS vault host used in `public/js/checkout.js`: `https://elb.deposit.shopifycs.com/sessions` (confirm with current docs).
- Replace the demo “Line Item” inputs with your cart logic. Only `variant_id` and `quantity` are required per line.
- Always serve over **HTTPS**.

---

## (Optional) WooCommerce Cart Mapping

If you want to source line items from a WooCommerce cart, render them as hidden inputs for this plugin’s form:

```php
<?php foreach (WC()->cart->get_cart() as $i => $item): 
  $variant_id = (int) $item['variation_id'] ?: (int) $item['product_id'];
  $qty = (int) $item['quantity'];
  $json = wp_json_encode(['variant_id' => $variant_id, 'quantity' => $qty]);
?>
  <input type="hidden" name="line_items[<?php echo $i; ?>]" value='<?php echo esc_attr($json); ?>' />
<?php endforeach; ?>
```

Then place the `[shopify_checkout]` form on the checkout page (or a custom page) and remove the demo inputs from the template.

---

## Security & Compliance

- Card data is posted **browser → Shopify Card Vault** (does not pass through WP).
- Use **HTTPS** everywhere.
- Keep Admin tokens out of source control; scope to least privilege; rotate regularly.
- Validate prices/amounts server-side before calling the payment endpoint.
- Test against a development store and your current Admin API version.
