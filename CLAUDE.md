# AI workflow notes — WooCommerce Custom Product Data

Kept in the repo because I build with Claude Code (Anthropic) and want the AI/human split legible from the source tree, not just claimed in a README.

## Human vs AI split on this repo

| Layer | Who did it | Why |
|---|---|---|
| Hook strategy (which of the ~200 WooCommerce hooks to use, and in what order) | **Human** | This is the whole plugin. Get it wrong (e.g. hooking `woocommerce_add_to_cart` instead of `woocommerce_add_cart_item_data`) and payloads land in the wrong cart line, or don't persist to the order. Not delegable. |
| The three-hook chain (`add_cart_item_data` → `get_item_data` → `checkout_create_order_line_item`) | **Human-designed, AI-coded** | I decided the sequence; Claude wrote the individual handler signatures once I said which hooks. |
| Sanitization (`wp_kses_post`, `esc_html`, `sanitize_text_field`) | **Human-audited** | Content flows from the front-end into the database and back onto the order page. Every field goes through the *appropriate* sanitizer — `esc_html` on display, `sanitize_text_field` on write. Claude drafted, I checked the pairing on each. |
| Order-meta storage key (`gd_config`) | **Human** | Namespacing decision — prefixed with `gd_` so it never collides with another plugin's meta. |
| PHPDoc + comments | **AI-drafted** | Copy-paste-shaped work. |
| README with WooCommerce version compatibility + configurator use case | **Human** | Positioning is mine — this plugin exists specifically as the bridge between a 3D configurator and the WooCommerce order pipeline, and the README says so. |

## What I verified before pushing

- `php -l woocommerce-custom-product-data.php` → clean.
- Read every hook handler top-to-bottom after the AI draft. Rejected two things Claude proposed: (1) `stripslashes()` on payload (WP already normalizes; double-stripping breaks JSON containing quoted strings), (2) storing the whole payload in a single `post_meta` row (order-meta best practice is one key per attribute for queryability — I kept the single key on purpose because the payload is a config blob, and documented why in the plugin file).
- Mental-tested the flow: front-end configurator → `apply_filters('woocommerce_add_cart_item_data', ...)` → survives session → visible in mini-cart → visible in checkout → stored on order → visible in admin order page.
- Compatibility target: WooCommerce 6.0+, WordPress 5.8+, PHP 7.4+ — everything the plugin uses is available on those minimums.

## Known gotchas for the next AI edit

- **Don't use `session_start()` or `$_SESSION`.** WooCommerce has its own session (`WC()->session`), and it survives page loads for guests without cookies breaking. Adding native PHP sessions here breaks caching plugins.
- **`add_cart_item_data` returns the modified array, doesn't push to it.** Common LLM mistake — treats it as a `do_action` and calls `WC()->cart->add(...)` inside. That double-adds items.
- **`checkout_create_order_line_item` fires per line item.** Don't loop over the cart inside the handler — you're already in the loop.
- **`unique_key` in cart-item-data.** If two configurations differ, they must produce different `unique_key` hashes, or WooCommerce merges them into one line. The `md5(json_encode(...))` in the handler is load-bearing.
- Sanitization is order-sensitive: sanitize on write (into `post_meta`), escape on display (out to HTML). Not the other way around.

## When to reach for Claude on this project vs code it yourself

- **Reach for Claude:** adding a REST API endpoint for external systems to read the configuration, adding admin-panel UI for editing saved configurations, adding CSV/XML export of order line items.
- **Do it yourself:** anything touching the three-hook chain or the sanitization pairs. This is where a subtle bug corrupts real customer orders.
