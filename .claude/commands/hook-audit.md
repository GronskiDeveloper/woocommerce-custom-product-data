---
description: Audyt trójstopniowego łańcucha hooków WooCommerce po każdej zmianie
---

Recenzujesz zmianę w `woocommerce-custom-product-data.php`. Plugin przenosi konfigurację produktu z front-endu przez sesję, koszyk, checkout i order — psucie któregokolwiek ogniwa oznacza, że realne zamówienia klienta lądują niekompletne w admin panelu.

Zanim zaakceptujesz diff, upewnij się, że wszystkie sześć poniższych warunków zachodzi:

1. **Trójstopniowy łańcuch jest zachowany:** `woocommerce_add_cart_item_data` → `woocommerce_get_item_data` → `woocommerce_checkout_create_order_line_item`. Brak któregokolwiek — konfiguracja się gubi.
2. **`add_cart_item_data` **zwraca** zmodyfikowaną tablicę.** Nie woła `WC()->cart->add(...)` wewnątrz (typowy błąd LLM — traktuje filter jak action, dodaje item podwójnie).
3. **`unique_key` jest ustawione i deterministyczne** (np. `md5(json_encode($config))`) — dwie różne konfiguracje dają różne klucze, dwie identyczne dają ten sam. Bez tego WooCommerce mergnie różne konfiguracje w jedną linię koszyka.
4. **Sanityzacja parowana poprawnie:** `sanitize_text_field` / `wp_kses_post` przy **zapisie** do `post_meta`; `esc_html` / `esc_attr` przy **display**. Nie odwrotnie. XSS wchodzi dokładnie tam, gdzie odwrócenie tego wzoru.
5. **Klucz meta jest namespaced** (`gd_config`, nie `config`). Bez prefixu — kolizja z każdym innym pluginem, który używa tej nazwy.
6. **Brak `session_start()` ani `$_SESSION`.** WooCommerce ma `WC()->session`. Natywne sesje PHP łamią pluginy cache'ujące (WP Rocket, W3 Total Cache, LiteSpeed Cache).

Jeśli którykolwiek warunek pęka — zablokuj zmianę.

Sprawdź też mentalny flow: front-end konfigurator → filter na cart-item-data → survive session dla guesta bez cookies → widoczne w mini-cart → widoczne w checkout → zapisane na zamówieniu → widoczne w admin order page (`show_order_meta`). Jeśli którykolwiek krok nie działa, zamówienie klienta się zepsuje po cichu — problem wypłynie dopiero przy skardze klienta.
