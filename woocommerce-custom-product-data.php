<?php
/**
 * Plugin Name:       WooCommerce Custom Product Data
 * Plugin URI:        https://github.com/GronskiDeveloper/woocommerce-custom-product-data
 * Description:       Attach arbitrary configuration / personalization data to a WooCommerce cart item, show it in cart & checkout, and persist it to the order (admin + emails). The exact bridge a 3D product configurator needs.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Dominik Groński (GroDev)
 * Author URI:        https://grodev.pl
 * License:           MIT
 * Text Domain:       wc-custom-product-data
 *
 * Built by GroDev — https://grodev.pl — a studio that ships custom 3D product
 * configurators and WooCommerce integrations. See 9 live configurators at
 * https://grodev.pl/konfigurator-produktowy-3d
 */

if (!defined('ABSPATH')) {
    exit; // No direct access.
}

/**
 * Small, framework-free helper class. Drop it into any theme/plugin, or use as-is.
 *
 * The idea: a configurator (or any custom form) sends its chosen options as an
 * associative array. We attach it to the cart item, render it for the customer,
 * and copy it onto the order line item so it survives into admin, emails and the
 * customer's account. All server-side — the browser never holds the pricing logic.
 */
final class WC_Custom_Product_Data
{
    /** The cart-item key under which we store the configuration payload. */
    private const KEY = 'gd_config';

    public static function boot(): void
    {
        $self = new self();

        // 1. Accept configuration coming from the product page / configurator.
        add_filter('woocommerce_add_cart_item_data', [$self, 'captureConfig'], 10, 3);

        // 2. Show it to the customer in cart & checkout.
        add_filter('woocommerce_get_item_data', [$self, 'renderInCart'], 10, 2);

        // 3. Make each configured line unique (so two different configs of the
        //    same product don't merge into one cart line).
        add_filter('woocommerce_cart_item_name', [$self, 'noop'], 10, 1); // placeholder hook point

        // 4. Persist onto the order line item (admin, emails, account).
        add_action('woocommerce_checkout_create_order_line_item', [$self, 'saveToOrder'], 10, 4);
    }

    /**
     * Reads the configuration from the add-to-cart request.
     *
     * Expects a POSTed field `gd_config` that is a JSON string, e.g. sent by a
     * Three.js configurator's "Add to cart" call:
     *   fetch('/?wc-ajax=add_to_cart', { body: { 'gd_config': JSON.stringify(config), ... } })
     *
     * @param array $cartItemData
     * @param int   $productId
     * @param int   $variationId
     * @return array
     */
    public function captureConfig(array $cartItemData, int $productId, int $variationId): array
    {
        if (empty($_POST[self::KEY])) {
            return $cartItemData;
        }

        $raw = wp_unslash($_POST[self::KEY]);
        $config = json_decode(is_string($raw) ? $raw : '', true);

        if (!is_array($config) || $config === []) {
            return $cartItemData;
        }

        // Sanitize keys and scalar values; ignore anything nested/unexpected.
        $clean = [];
        foreach ($config as $label => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $clean[sanitize_text_field((string) $label)] = sanitize_text_field((string) $value);
        }

        if ($clean === []) {
            return $cartItemData;
        }

        $cartItemData[self::KEY] = $clean;

        // Force a unique cart line per distinct configuration.
        $cartItemData['unique_key'] = md5(wp_json_encode($clean) . microtime());

        return $cartItemData;
    }

    /**
     * Renders the configuration as readable rows under the product in cart/checkout.
     *
     * @param array $itemData
     * @param array $cartItem
     * @return array
     */
    public function renderInCart(array $itemData, array $cartItem): array
    {
        if (empty($cartItem[self::KEY]) || !is_array($cartItem[self::KEY])) {
            return $itemData;
        }

        foreach ($cartItem[self::KEY] as $label => $value) {
            $itemData[] = [
                'key'     => esc_html($label),
                'value'   => esc_html($value),
                'display' => '',
            ];
        }

        return $itemData;
    }

    /**
     * Copies the configuration onto the order line item as order-item meta.
     *
     * @param WC_Order_Item_Product $item
     * @param string                $cartItemKey
     * @param array                 $values
     * @param WC_Order              $order
     */
    public function saveToOrder($item, string $cartItemKey, array $values, $order): void
    {
        if (empty($values[self::KEY]) || !is_array($values[self::KEY])) {
            return;
        }

        foreach ($values[self::KEY] as $label => $value) {
            // Visible order-item meta => shows in admin, emails and account.
            $item->add_meta_data((string) $label, (string) $value, true);
        }
    }

    public function noop($name)
    {
        return $name;
    }
}

add_action('plugins_loaded', static function (): void {
    if (class_exists('WooCommerce')) {
        WC_Custom_Product_Data::boot();
    }
});
