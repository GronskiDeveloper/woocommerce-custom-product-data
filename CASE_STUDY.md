# Case study - `woocommerce-custom-product-data`, most którego brakowało

Krótka retrospektywa: dlaczego napisałem pojedynczy plik-plugin zamiast rozbudowywać istniejący, i co on realnie odblokowuje dla klientów, którzy chcą konfigurator 3D + WooCommerce.

## Brief (30 sekund)

Plugin WordPress/WooCommerce, który robi jedno: **dokleja dowolne dane konfiguracyjne do koszyka, checkoutu i zamówienia**. Nic więcej.

Przykładowy use case: klient konfiguruje własne biurko w moim konfiguratorze 3D (`grodev/threejs-product-configurator-starter` albo custom Three.js widget), klika „do koszyka" - i w koszyku, w mailu potwierdzającym, w panelu admina WooCommerce widać:

- Wymiary (2200 × 900 × 750 mm)
- Wykończenie (dąb naturalny olej)
- Kolor stelaża (matte black)
- Uchwyty (aluminiowe, wersja B)
- Cena wyliczona przez konfigurator (nie z domyślnej ceny produktu)

Trzy twarde wymagania:

1. **Jeden plik.** `woocommerce-custom-product-data.php` - i tyle. Bez `admin/`, `includes/`, `assets/`, `templates/`. WordPress-plugin-boilerplate to overkill dla tego, co robi ten plugin, a każda dodatkowa folder = dodatkowa powierzchnia security audit.
2. **Zero opinion na temat frontendu.** Plugin nie ma UI konfiguratora. Ma tylko backend hook: `add_to_cart` z parametrem `custom_data` (JSON) → serializacja do meta → deserializacja przy wyświetlaniu. Frontend robisz w Three.js, React, vanilla JS - cokolwiek.
3. **WooCommerce-native.** Używa standardowych hooków (`woocommerce_add_cart_item_data`, `woocommerce_get_item_data`, `woocommerce_checkout_create_order_line_item`). Zero „hackowania core", zero override templates.

## Gdzie faktycznie zdarzyła się praca człowieka

### 1. Wybór hooków (człowiek, po przeczytaniu WooCommerce dev docs)

Plugin przechodzi przez pięć punktów cyklu życia zamówienia:

1. `woocommerce_add_cart_item_data` - dokleja custom_data do cart item
2. `woocommerce_get_item_data` - wyświetla w koszyku (ładne key/value)
3. `woocommerce_before_calculate_totals` - pozwala pluginowi *nadpisać* cenę produktu ceną z konfiguratora
4. `woocommerce_checkout_create_order_line_item` - zapisuje do order meta (nie tylko cart)
5. `woocommerce_order_item_meta_start` - wyświetla w mailach, admin panelu, koncie klienta

To *nie jest oczywiste* dla kogoś, kto pierwszy raz pisze plugin WC - większość tutoriali zatrzymuje się na punkcie 2 („widoczne w koszyku") i klient zostaje z zamówieniem gdzie w panelu admina wymiary biurka są niewidzialne. Ja przechodzę cały cykl.

### 2. Sanityzacja danych (człowiek)

`custom_data` przychodzi z frontendu jako JSON. Wszystko idzie przez `sanitize_text_field` / `wp_kses_post` per pole, przed serializacją. Nikt nie wstrzyknie `<script>` przez wymiar biurka.

### 3. AI zrobił boilerplate pluginu (Claude, ~30 min)

Nagłówek pluginu WP (metadata), setup hooków, funkcje pomocnicze - Claude Code. Podałem: „single-file plugin do WC 8+, PHP 7.4+, hooki: [lista 5 z sekcji wyżej], dane przez wp_kses". Draft trafił, poprawiłem sanityzację (Claude domyślnie użył `esc_html` zamiast `sanitize_text_field` - błąd, bo esc_html to output, nie input).

## Gdzie to się sprawdza

- **Konfigurator produktowy 3D → WooCommerce.** To głównie po to napisałem. Wpinam konfigurator (Three.js, React albo custom), i wynik konfiguracji ląduje w zamówieniu.
- **Personalizacja produktów** (grawer, kolor nici, wymiar rolety). Sklep Woo + widget frontend + ten plugin = pełne pipeline'e.
- **B2B - konfiguracje ofertowe.** Klient B2B składa zapytanie z konfiguracją, plugin trzyma to jako order meta do dalszej obróbki przez handlowca.

## Kiedy nie ten plugin

- Jeśli potrzebujesz gotowego konfiguratora z UI - to jest **backend bridge**, nie frontend. Frontend musisz mieć.
- Jeśli robisz *warianty produktowe* w standardowym rozumieniu (rozmiar S/M/L z osobną ceną) - WooCommerce ma to natywnie (variations). Ten plugin jest do rzeczy które nie mieszczą się w variations (nieskończone kombinacje typu konfigurator).

## Kontakt

Wdrożenie konfiguratora 3D + integracja z WooCommerce (frontend + ten plugin + wdrożenie na Twoim shopie): [dominik@grodev.pl](mailto:dominik@grodev.pl) · [grodev.pl/konfigurator-produktowy-3d](https://grodev.pl/konfigurator-produktowy-3d).

Plugin MIT - używaj, forkuj, dołącz do własnych projektów bez licencyjnej biurokracji.
