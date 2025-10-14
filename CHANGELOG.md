# Changelog

## 1.4.0 (2025-10-14)
- **Changed**: Removed 5% APR interest calculation from installment payments
- **Simplified**: Monthly payment calculation now uses simple division (principal / months + monthly fee)
- **Updated**: Checkout display text to reflect "no interest" pricing model
- **Hidden**: APR field in admin settings (kept for backward compatibility but set to 0)
- **Improved**: Clear messaging that only monthly fee (default 30 NOK) is charged, with no interest

## 1.3.1 (2025-10-14)
- **Added**: Member-only restriction for installment payment option
- **Added**: Prominent membership notice for non-members in checkout
- **Added**: Visual CTA banner encouraging non-members to become Wattn customers
- **Enhanced**: Payment gateway availability check now verifies Wattn customer status
- **Improved**: Clear messaging about installment payment benefits for members only

## 1.3.0 (2025-10-07)
- **Added**: Wattn customer verification system via API integration
- **Added**: User meta field `is_wattn_customer` to track active customer status
- **Added**: Automatic customer verification on user login and registration
- **Added**: Helper functions `is_wattn_customer()` and `wattn_verify_customer()` for easy integration
- **Added**: Profile update hook to re-verify customer status when user information changes
- **Enhanced**: Comprehensive error logging for customer verification process

## 1.2.4 (2025-10-07)
- **Removed**: Isolated repayment terms checkbox from installment checkout section
- **Removed**: Automatic order completion on installment payment processing
- **Improved**: Simplified checkout flow by removing redundant terms acceptance field
- **Fixed**: Order status now follows WooCommerce standard procedure instead of forcing completion

## 1.2.3 (2025-01-27)
- **Fixed**: All email and admin interface strings now use Norwegian language
- **Improved**: Consistent language between checkout frontend and email notifications
- **Updated**: Order notes and admin interface labels translated to Norwegian
- **Enhanced**: Email field labels now match checkout terminology

## 1.2.2 (2025-01-27)
- **Fixed**: Orders with installment payment now complete immediately instead of remaining pending
- **Improved**: Order status logic - installment orders are marked as completed when sent to external provider

## 1.2.1 (2025-01-27)
- **Fixed**: Installment calculator not displaying estimates when clicking month options
- **Added**: JavaScript calculator with real-time calculations
- **Added**: Total cost calculation showing complete amount customer will pay
- **Added**: Auto-selection of 36-month option on page load
- **Improved**: User-friendly wording ("Total sum du betaler" instead of "hovedstol + kredittkostnad")
- **Enhanced**: Visual styling with proper payment box background colors
- **Fixed**: Missing HTML structure for total cost display

## 1.2.0 (2025-09-19)
- Initial structured plugin release.
- Added 6/12/24/36 plans, APR %, monthly fee (default 30 NOK).
- Min-total gating (default 9000, configurable).
- Classic checkout calculator UI + admin order panel.
- Order meta persisted as specified.
- Optional external forward with signed payload + webhook endpoint.
