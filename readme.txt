=== SimplyLearn Installments ===
Contributors: simplylearn
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Classic Checkout installments gateway with 6/12/24/36 plans, monthly fee (no interest), min-total gating, and order meta storage. Optional forward to external provider.

== Description ==

- Classic Checkout only (not WooCommerce Blocks).
- Shows plans: 6, 12, 24, 36 months.
- No interest charged - only a fixed monthly fee (default 30 NOK, configurable).
- Calculator shows monthly payment (incl. fee) and total credit cost (fees only, no interest).
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
3. Go to **WooCommerce → Settings → Payments** → enable **Downpayment – External**.
4. Set monthly fee (default 30 NOK) and (optionally) fixed basis amount or provider settings.

== Changelog ==

= 1.4.0 =
* Removed 5% APR interest calculation from installment payments.
* Simplified monthly payment calculation to use simple division (principal / months + monthly fee).
* Updated checkout display to reflect "no interest" pricing model.
* Clear messaging that only monthly fee (default 30 NOK) is charged, with no interest.

= 1.3.1 =
* Added member-only restriction for installment payment option.
* Added Wattn customer verification and membership notice.

= 1.3.0 =
* Added Wattn customer verification system via API integration.

= 1.2.4 =
* Removed isolated repayment terms checkbox.
* Improved checkout flow.

= 1.2.3 =
* Fixed all email and admin interface strings to use Norwegian language.

= 1.2.2 =
* Fixed orders with installment payment now complete immediately.

= 1.2.1 =
* Fixed installment calculator and added real-time calculations.

= 1.2.0 =
* Initial public structure release with includes + assets.
* 6/12/24/36 plans, APR, monthly fee, min-total gating (default 9000, configurable).
* Classic checkout calculator and admin/email meta display.
