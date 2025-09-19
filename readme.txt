=== Wattn Installments Checkout ===
Contributors: wattn
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Wattn Installments Checkout - Classic Checkout installments gateway with 6/12/24/36 plans, APR + monthly fee, min-total gating, and order meta storage. Optional forward to external provider.

== Description ==

- Classic Checkout only (not WooCommerce Blocks).
- Shows plans: 6, 12, 24, 36 months.
- APR (%) plus a fixed monthly fee (default 30 NOK).
- Calculator shows monthly payment (incl. fee) and total credit cost (interest + fees).
- Gateway only appears when the (basis) total > minimum (default 9000).
- Saves order meta:
  - installment_plan_months (int)
  - installment_monthly_amount (float)
  - installment_total_credit_cost (float)
  - installment_terms_accepted (bool)
  - payment_method_label ("Wattn Installment")
  - installment_calc_basis_total (float, optional)
- Optional forward-to-provider with HMAC and webhook.

== Installation ==

1. Upload the `simplylearn-installments` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Go to **WooCommerce → Settings → Payments** → enable **Wattn Installments**.
4. Set APR (%), monthly fee, and (optionally) fixed basis amount or provider settings.

== Changelog ==

= 1.2.0 =
* Initial public structure release with includes + assets.
* 6/12/24/36 plans, APR, monthly fee, min-total gating (default 9000, configurable).
* Classic checkout calculator and admin/email meta display.
