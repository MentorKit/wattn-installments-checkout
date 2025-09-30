# Changelog

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
