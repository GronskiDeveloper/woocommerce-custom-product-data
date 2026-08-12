# Praca AI-first — notatki dla tego repo

Trzymam ten plik w repozytorium, ponieważ buduję z Claude Code (Anthropic) i chcę, żeby podział „człowiek/AI" był widoczny z drzewa plików, a nie deklarowany w README. Rekruter, klient albo kolega z zespołu ma tu dowody, nie ogólniki.

## Podział pracy człowiek vs AI

| Warstwa | Kto zrobił | Dlaczego tak |
|---|---|---|
| Strategia hooków (który z ~200 hooków WooCommerce użyć i w jakiej kolejności) | **Człowiek** | To cały plugin. Zły dobór (np. hook w `woocommerce_add_to_cart` zamiast `woocommerce_add_cart_item_data`) sprawia, że payloady lądują w niewłaściwej linii koszyka albo w ogóle nie trafiają na zamówienie. Nie do zlecenia AI. |
| Trójstopniowy łańcuch (`add_cart_item_data` → `get_item_data` → `checkout_create_order_line_item`) | **Design człowieka, kod AI** | Ja zdecydowałem o sekwencji; Claude napisał sygnatury poszczególnych handlerów po tym, jak podałem hooki. |
| Sanityzacja (`wp_kses_post`, `esc_html`, `sanitize_text_field`) | **Audyt człowieka** | Treść płynie z front-endu do bazy i z powrotem na stronę zamówienia. Każde pole idzie przez *odpowiedni* sanityzator — `esc_html` przy display, `sanitize_text_field` przy zapisie. Claude zrobił draft, ja sprawdziłem parowanie dla każdego pola. |
| Klucz meta zamówienia (`gd_config`) | **Człowiek** | Decyzja o namespace'owaniu — prefix `gd_`, żeby nigdy nie kolidowało z meta innego pluginu. |
| PHPDoc + komentarze | **Draft AI** | Copy-paste-shaped work. |
| README z kompatybilnością wersji WooCommerce + kejs użycia konfiguratora | **Człowiek** | Pozycjonowanie jest moje — ten plugin istnieje specyficznie jako bridge między konfiguratorem 3D a pipeline'em zamówień WooCommerce, i README to mówi. |

## Co zweryfikowałem przed wypchnięciem

- `php -l woocommerce-custom-product-data.php` → czysto.
- Przeczytałem każdy handler hooka od góry do dołu po drafcie AI. Odrzucone dwie rzeczy zaproponowane przez Claude: (1) `stripslashes()` na payload (WP już normalizuje; podwójne strippingowanie psuje JSON z quoted stringami), (2) trzymanie całego payloadu w jednej rzędzie `post_meta` (best practice order-meta to jeden klucz per atrybut dla queryability — utrzymałem pojedynczy klucz świadomie, bo payload to blob konfiguracyjny, i udokumentowałem to w pliku pluginu).
- Ręczny test mentalny flow: front-end konfigurator → `apply_filters('woocommerce_add_cart_item_data', ...)` → sesja przetrwa → widoczne w mini-cart → widoczne w checkout → zapisane na zamówieniu → widoczne w admin order page.
- Cel kompatybilności: WooCommerce 6.0+, WordPress 5.8+, PHP 7.4+ — wszystko, czego plugin używa, jest dostępne na tych minimach.

## Znane pułapki dla następnej iteracji AI

- **Nie używać `session_start()` ani `$_SESSION`.** WooCommerce ma własną sesję (`WC()->session`), która przeżywa page loads dla guestów bez łamania cookies. Dodanie natywnych sesji PHP tutaj psuje pluginy cache'ujące.
- **`add_cart_item_data` zwraca zmodyfikowaną tablicę, nie pushuje.** Częsty błąd LLM — traktuje to jak `do_action` i woła `WC()->cart->add(...)` wewnątrz. To dodaje itemy podwójnie.
- **`checkout_create_order_line_item` odpala się per line item.** Nie iteruj po koszyku wewnątrz handlera — jesteś już w pętli.
- **`unique_key` w cart-item-data.** Jeśli dwie konfiguracje się różnią, muszą wyprodukować różne hashe `unique_key`, inaczej WooCommerce mergnie je do jednej linii. `md5(json_encode(...))` w handlerze jest kluczowe.
- Sanityzacja jest order-sensitive: sanitize przy zapisie (do `post_meta`), escape przy display (na HTML). Nie odwrotnie.

## Kiedy sięgać po Claude na tym projekcie, a kiedy pisać samodzielnie

- **Sięgnąć po Claude:** dodanie endpointu REST API, żeby zewnętrzne systemy mogły czytać konfigurację, dodanie UI admin-panel do edycji zapisanych konfiguracji, dodanie eksportu CSV/XML linii zamówienia.
- **Zrobić samodzielnie:** cokolwiek dotykającego trójstopniowego łańcucha hooków albo par sanityzacji. To miejsca, gdzie subtelny bug korupcjuje realne zamówienia klienta.
