# Zgłaszanie podatności

Bezpieczeństwo tego projektu jest dla mnie ważne — jeśli znalazłeś podatność, zgłoś ją **prywatnie** zamiast otwierać publicznego issue.

## Kanały zgłoszenia

- **Preferowany:** [Security Advisory na GitHubie](https://github.com/GronskiDeveloper/woocommerce-custom-product-data/security/advisories/new) (prywatny, tylko dla mnie do przejrzenia).
- **Alternatywnie:** e-mail bezpośrednio na **dominik@grodev.pl** z tematem `[SECURITY] woocommerce-custom-product-data`.

## Co warto zawrzeć w zgłoszeniu

- Opis podatności (co jest do wykorzystania, jak).
- Kroki reprodukcji (albo minimalny PoC).
- Ocena wpływu (co atakujący może zrobić — kradzież danych, wykonanie kodu, DoS itd.).
- Ewentualnie sugerowany fix.

## Reakcja

- **Potwierdzenie odbioru:** w ciągu 72h.
- **Wstępna ocena:** w ciągu 7 dni.
- **Fix + release:** zależnie od skali (krytyczne — priorytetowo).

Podziękuję imiennie w release notes / CHANGELOG (o ile nie prosisz o anonimowość).


## Kontekst tego projektu

Ten plugin przetwarza dane z front-endu przez cały pipeline WooCommerce. **Najkrytyczniejsze podatności to takie, które omijają sanityzację** — np. stored XSS przez payload konfiguracji, SQL injection w custom meta query, bypass unique_key mergujący konfiguracje różnych klientów w jedną linię zamówienia.

Autor: [Dominik Groński / GroDev](https://grodev.pl)
