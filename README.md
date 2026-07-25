# GF Email Typo Guard

A lightweight Gravity Forms add-on that catches common email domain typos — like `gamil.com`, `gnail.com`, or `gmail.con` — and suggests the likely correct address inline, right under the field.

Turn it on per Email field from the form editor. No settings page, no external library, and no scripts or styles are loaded on any page unless a form on that page actually uses the feature.

## Features

- **Per-field opt-in.** Adds a single checkbox — "Suggest correction for likely domain typos" — to the Advanced tab of any Gravity Forms Email field. Nothing changes on fields where it's left unchecked.
- **Zero-dependency detection.** Compares the typed domain (including the TLD) against a short list of common providers using Levenshtein distance, so it catches both a misspelled provider name (`gamil.com`) and a misspelled TLD (`gmail.con`) with the same check.
- **Non-blocking by default.** The hint is a dismissible suggestion the user can click to accept, not a hard validation error — avoids frustrating people who genuinely use an uncommon domain.
- **Performance-first loading.** Front-end JS/CSS are only enqueued on pages where a rendered form has the feature enabled on at least one field, via Gravity Forms' own `gform_enqueue_scripts` hook. A page with no matching form loads nothing extra.
- **Optional server-side fallback.** A no-JS safety net that blocks submission until the address is confirmed is available behind a filter, for sites that want stricter enforcement.
- **Filterable.** Adjust the list of known domains and the match sensitivity without touching plugin code.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- [Gravity Forms](https://www.gravityforms.com/) (any recent version)

## Installation

1. Download the latest release or clone this repo.
2. Upload the `gf-email-typo-guard` folder to `/wp-content/plugins/`.
3. Activate **GF Email Typo Guard** from the Plugins screen in wp-admin.
4. Open a form in the Gravity Forms editor, select an Email field, go to its **Advanced** tab, and check **"Suggest correction for likely domain typos."**
5. Save the form.

## How it works

1. When the setting is checked for a field, `gform_field_content` adds a `data-gfetg-domain-check="1"` attribute to that field's `<input>` on the front end.
2. `gform_enqueue_scripts` fires once per form Gravity Forms actually renders on the page. If that form contains at least one flagged Email field, the plugin's script and stylesheet are enqueued; otherwise nothing loads.
3. The script watches only the flagged inputs. On blur, it checks the typed domain against a known-domain list and, if it's a close-but-imperfect match, shows a clickable "Did you mean `you@gmail.com`?" hint beneath the field.
4. Clicking the suggestion replaces the field value; editing the field again clears the hint.

## Filters

| Filter | Default | Purpose |
|---|---|---|
| `gfetg_common_domains` | ~20 major providers (Gmail, Yahoo, Outlook, iCloud, etc.) | Add or remove domains checked against, e.g. regional providers relevant to your users. |
| `gfetg_domain_distance_threshold` | `0.3` | Normalized edit-distance threshold for a match. Lower = stricter (fewer, more confident suggestions); higher = looser (more suggestions, more false positives). |
| `gfetg_enable_server_side_check` | `false` | Set to `true` to enable a server-side validation fallback that blocks submission until the suggested address is used or the user resubmits to confirm their original entry. Off by default since JS handles the primary UX. |

```php
// Example: add a regional provider to the domain list
add_filter( 'gfetg_common_domains', function ( $domains ) {
    $domains[] = 'example-regional-provider.com';
    return $domains;
} );

// Example: enable the stricter server-side fallback
add_filter( 'gfetg_enable_server_side_check', '__return_true' );
```

## File structure

```
gf-email-typo-guard/
├── gf-email-typo-guard.php        # Plugin bootstrap
├── includes/
│   ├── class-domain-list.php      # Shared, filterable domain list
│   ├── class-field-setting.php    # Form editor checkbox (Advanced tab)
│   ├── class-assets.php           # Conditional asset loading + field marking
│   └── class-validation.php       # Optional server-side fallback
└── assets/
    ├── js/email-typo-guard.js     # Front-end suggestion logic
    └── css/email-typo-guard.css   # Hint styling
```

## Known limitations

- Checks only the domain/TLD, not the mailbox name before the `@` — there's no dictionary to fuzzy-match personal mailbox names against, so a typo like `jhon@gmail.com` won't be caught. Gravity Forms' built-in "Email Confirmation" field option is a good complement for catching that class of mistake.
- The domain list is intentionally short. Expanding it increases false-positive risk on legitimate but uncommon domains.

## License

GPL-2.0-or-later
