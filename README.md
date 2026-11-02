![Drift Creative Systems](.github/banner.jpg)

# Drift: Gift Cards

WooCommerce gift cards, by [Drift Creative Systems](https://driftcreativesystems.co.uk/).
Sell, email, redeem and self-serve balance-check gift cards, built for the
WooCommerce Blocks Cart and Checkout. A standalone plugin (not theme-coupled),
so it's portable to any WooCommerce site.

- Requires WordPress 6.0+, PHP 7.4+ and WooCommerce (Blocks Cart/Checkout).
- Licence: GPL-2.0-or-later.

## Updates

This plugin self-updates via [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker):
no WordPress.org listing and no manual re-upload per site. It reads releases
from the public [drift-creative-systems/drift-gift-cards](https://github.com/drift-creative-systems/drift-gift-cards)
repo (no token needed) and surfaces new versions in the normal wp-admin
"Update available" notice.

**Cutting a release:**
1. Bump `Version:` in `drift-gift-cards.php` (plugin header **and** the
   `DRIFT_GIFT_CARDS_VERSION` constant; both must match) and add a dated entry to
   `CHANGELOG.md`.
2. Merge `develop` into `main` and publish a GitHub release tagged `vX.Y.Z` on `main`.
   `.github/workflows/release.yml` checks both versions match the tag, builds
   `drift-gift-cards.zip` and attaches it to the release; sites update from that zip.
3. `vendor/` is committed to this repo on purpose (see `.gitignore`): the plugin
   ships the update-checker library itself, so sites never need to run Composer.
   If you add/update a Composer dependency, run `composer update --no-dev` and
   commit the resulting `vendor/` and `composer.lock` changes together.

## Setup

1. Upload/activate as a normal plugin (needs WooCommerce active). The plugin
   folder must be `drift-gift-cards`.
2. On activation it creates `wp_drift_gift_cards` — check it exists via
   phpMyAdmin/Adminer if anything looks off (WordPress dbDelta is fussy
   about exact SQL formatting).
3. **Products → Gift Card Settings** — upload the gift card image.
4. Create/edit a Simple Product ("Gift Voucher"), tick **Gift card?** under
   Product Data → General, set preset amounts (e.g. `25,50,75,100`) and/or
   a min/max for a custom amount.
5. Add the product to the Gift Vouchers page.
6. Drop `[drift_gift_cards_balance_check]` on the Gift Vouchers page (or anywhere) so
   customers can self-serve balance lookups.
7. Optionally set the **Printed card fee** on the Settings page (defaults to
   £10) — this is what's charged when a customer ticks "send a printed gift
   card by post" on the product page.

## Printed gift cards

- Ticking "send a printed gift card by post" on the product page reveals a
  postal address block (name, address lines, town/city, postcode, country).
  The address is required server-side once ticked — the same as any other
  cost-bearing checkout option.
- The fee is added as a single combined cart fee covering every printed
  card in the order (so ordering several gift cards, only some printed,
  isn't overcharged).
- Address details are stored as order line item meta (`_drift_gift_cards_ship_*`,
  visible on the admin order screen for whoever handles fulfilment) and
  shown in the cart/checkout item summary. This is fulfilment data only —
  there's no automated printing/postage integration, someone still needs
  to actually print and post the card.
- The digital code is still emailed as normal; the printed card is
  additional, not a replacement.

## How redemption works

- Customer enters a code in the "Have a gift card?" field injected into
  the Blocks checkout sidebar (more than one code can be applied at once).
- Applied codes live in the WC session as a provisional list — nothing is
  debited yet.
- The combined balance is applied as a single cart fee (negative), so
  it's deducted **after tax**, same as cash — matches Pimwick's behaviour.
- On `woocommerce_checkout_create_order` (while the order is being built,
  before payment), each applied code is re-validated against its live DB
  balance and debited atomically. Throwing an exception at this point is
  WooCommerce's documented way to abort order creation cleanly — so if a
  balance changed underneath the session (spent elsewhere, expired)
  nothing is created and nothing is left debited. Any codes already
  deducted for that same order are rolled back first.

## Things worth testing before going live

- Full purchase → email → redeem → balance-check loop, including a
  gift card used to *partially* cover an order (rest paid by Stripe).
- A cancelled/failed Stripe payment after a code was "applied" but before
  `payment_complete` — the session just clears, nothing should get debited.
- Order refund/cancellation on an order that *issued* a gift card that's
  since been partly spent — currently only untouched cards get voided
  automatically; a partially-spent card on a refunded order needs a
  manual look via the admin list (adjust balance to 0 if appropriate).
- Scheduled delivery dates rely on WP-Cron — fine on normal hosting, but
  if the site's cron is disabled/offloaded, wire `drift_gift_cards_send_scheduled_gift_card`
  into whatever real cron runner is in use.
- The checkout field re-render currently calls
  `wp.data.dispatch('wc/store/cart').invalidateResolutionForStore()` to
  refresh totals after apply/remove — worth confirming this fires cleanly
  against whatever WooCommerce Blocks version is running; a full-page
  reload fallback is already wired in if it doesn't.

## Not built (deliberately out of scope)

- Multiple gift card images / email designer — one site, one image.
- Redemption restrictions by product/category.
- PDF gift cards, QR codes.
- Multi-currency, WooCommerce Subscriptions compatibility.

Any of the above can be bolted on later without restructuring — the DB
schema and hooks are generic on purpose.

## Licence

GPL-2.0-or-later.

Copyright © 2026 Drift Creative Systems Ltd

Drift Creative Systems Ltd. Registered in England and Wales, company number 17548220. Registered office: 167-169 Great Portland Street, 5th Floor, London W1W 5PF
