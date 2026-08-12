# WooCommerce Custom Product Data

Attach **arbitrary configuration / personalization data** to a WooCommerce cart item, show it in cart & checkout, and persist it to the order (admin, emails, customer account) — all **server-side**.

This is the exact bridge a **3D product configurator** (or any custom product form) needs to talk to WooCommerce: the configurator sends the chosen options, and they travel cleanly all the way to the order.

Built by **[GroDev](https://grodev.pl)** — a studio that ships custom 3D configurators and WooCommerce integrations.

## What it does

1. **Captures** a configuration payload posted with *add to cart* (a JSON object of `label => value`).
2. **Renders** it as readable rows under the product in cart & checkout.
3. **Keeps configured lines distinct** — two different configurations of the same product don't merge into one cart line.
4. **Persists** it onto the order line item, so it shows in admin, order emails and the customer's account.

No settings page, no bloat — one file, standard WooCommerce hooks, PHP 8, escaped and sanitized.

## Install

Copy `woocommerce-custom-product-data.php` into `wp-content/plugins/woocommerce-custom-product-data/` and activate it, or drop the `WC_Custom_Product_Data` class into your own theme/plugin.

## How a configurator sends data

From your Three.js configurator's *Add to cart* call, include a `gd_config` field with a JSON string:

```javascript
const config = {
  'Model':      'M1',
  'Finish':     'Oak, brushed',
  'Width':      '180 cm',
  'Config ID':  'CFG-2026-08-1234',
};

const body = new URLSearchParams({
  'add-to-cart': productId,
  'quantity':    1,
  'gd_config':   JSON.stringify(config),
});

await fetch('/?wc-ajax=add_to_cart', { method: 'POST', body });
```

That's it — the configuration now shows in the cart, checkout, order and emails.

## ⚠️ Production note: pricing stays on the server

This plugin carries the *configuration*, not the *price*. In a real setup you should never compute the price in the browser (users can edit it, and manufacturers change prices often). Price the configuration on the server (a small endpoint that receives the config and returns the price), then add the correctly-priced product to the cart. This plugin then makes that configuration visible end-to-end.

## Hooks used

| Hook | Purpose |
|---|---|
| `woocommerce_add_cart_item_data` | capture + make the line unique |
| `woocommerce_get_item_data` | render in cart/checkout |
| `woocommerce_checkout_create_order_line_item` | persist to the order |

## See it in production

Live 3D configurators wired into shops/CRM, built with exactly this kind of bridge:

- [basen3d.grodev.pl](https://basen3d.grodev.pl) · [brama3d.grodev.pl](https://brama3d.grodev.pl) · [pergola3d.grodev.pl](https://pergola3d.grodev.pl) · [sauna3d.grodev.pl](https://sauna3d.grodev.pl)

Full portfolio and pricing: **[grodev.pl/konfigurator-produktowy-3d](https://grodev.pl/konfigurator-produktowy-3d)**.

## Need a custom WooCommerce build or configurator?

If you sell configurable or personalized products and want a clean WooCommerce integration (or a full 3D configurator with the source code owned by you), reach out at **[grodev.pl](https://grodev.pl)**.

## License

MIT.

---

*Made by [Dominik Groński / GroDev](https://grodev.pl) · Poznań, Poland · WooCommerce · WordPress · PHP · Laravel · Three.js*
