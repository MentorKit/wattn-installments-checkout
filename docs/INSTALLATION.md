# SimplyLearn Installments – Installation & Setup

## Install
1. Zip the plugin folder or upload the provided ZIP in **Plugins → Add New → Upload**.
2. Activate **SimplyLearn Installments**.

## Configure
1. Go to **WooCommerce → Settings → Payments**.
2. Enable **Downpayment – External**.
3. Set:
   - **APR (%)**
   - **Monthly fee (NOK)** (default 30)
   - **Calculator basis**: Order total (default) or fixed amount
   - **Minimum basis total**: default 9000
   - (Optional) **Forward** settings (Provider URL, API key/secret, callback key).

## Order Meta
Saved on order:
- `installment_plan_months` (int)
- `installment_monthly_amount` (float, includes fee)
- `installment_total_credit_cost` (float, interest + all fees)
- `installment_terms_accepted` (bool)
- `payment_method_label` = `"Wattn Installment"`
- `installment_calc_basis_total` (float, if available)

## Classic Checkout only
This plugin targets the Classic (shortcode-based) checkout. To support WooCommerce Blocks, implement a Blocks bridge later.
