# Wattn Customer Verification

## Overview

The Wattn Customer Verification system automatically checks if users are active Wattn customers via the Wattn API and stores this information in WordPress user meta. This allows you to provide different experiences or permissions based on customer status.

## How It Works

### Automatic Verification

The system automatically verifies customer status in the following scenarios:

1. **User Login** - Every time a user logs into the website
2. **User Registration** - When a new user account is created
3. **Profile Update** - When a user updates their name or billing information

### Required User Data

For verification to work, the following data is required:

- **First Name** - From billing info or WordPress profile
- **Last Name** - From billing info or WordPress profile  
- **Birth Date** - Stored in user meta as `billing_birthdate` or `birthdate` (format: `yyyy-mm-dd`)

If any required data is missing, the user will be marked as `is_wattn_customer = false`.

### User Meta

The verification result is stored in the user meta field:

- **Meta Key**: `is_wattn_customer`
- **Values**: `true` (active customer) or `false` (not an active customer)

## API Integration

### Endpoint

```
GET https://input.wattn.no/api/v2/is-active-customer
```

### Parameters

- `FirstName` - User's first name
- `LastName` - User's last name
- `Birthdate` - Birth date in `yyyy-mm-dd` format

### Response

```json
{
  "isActiveCustomer": true
}
```

## Usage Examples

### Check If Current User Is a Wattn Customer

```php
if ( is_wattn_customer() ) {
    // User is an active Wattn customer
    echo 'Welcome back, valued customer!';
} else {
    // User is not an active customer
    echo 'Become a Wattn customer today!';
}
```

### Check Specific User

```php
$user_id = 123;
if ( is_wattn_customer( $user_id ) ) {
    // Specific user is an active customer
}
```

### Manually Trigger Verification

```php
$user_id = get_current_user_id();
$result = wattn_verify_customer( $user_id );

if ( $result ) {
    echo 'Verification completed successfully';
} else {
    echo 'Verification failed - missing required data';
}
```

### Check in WooCommerce Checkout

```php
add_filter( 'woocommerce_available_payment_gateways', function( $gateways ) {
    if ( ! is_wattn_customer() ) {
        // Remove installment gateway for non-customers
        unset( $gateways['sli_installments'] );
    }
    return $gateways;
} );
```

### Display Customer Badge

```php
add_action( 'woocommerce_account_dashboard', function() {
    if ( is_wattn_customer() ) {
        echo '<div class="wattn-customer-badge">';
        echo '<strong>✓ Wattn Customer</strong>';
        echo '</div>';
    }
} );
```

## Storing Birth Date

To enable customer verification, users need to have their birth date stored. Here are some options:

### Option 1: Add to WooCommerce Checkout

```php
// Add birthdate field to checkout
add_filter( 'woocommerce_checkout_fields', function( $fields ) {
    $fields['billing']['billing_birthdate'] = [
        'type'        => 'date',
        'label'       => __( 'Birth Date', 'sl-installments' ),
        'required'    => true,
        'class'       => [ 'form-row-wide' ],
        'priority'    => 25,
    ];
    return $fields;
} );

// Save birthdate to user meta
add_action( 'woocommerce_checkout_update_user_meta', function( $user_id ) {
    if ( isset( $_POST['billing_birthdate'] ) ) {
        update_user_meta( $user_id, 'billing_birthdate', sanitize_text_field( $_POST['billing_birthdate'] ) );
    }
} );
```

### Option 2: Add to User Profile

```php
// Add birthdate field to user profile
add_action( 'show_user_profile', function( $user ) {
    $birthdate = get_user_meta( $user->ID, 'birthdate', true );
    ?>
    <h3>Wattn Customer Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="birthdate">Birth Date</label></th>
            <td>
                <input type="date" name="birthdate" id="birthdate" 
                       value="<?php echo esc_attr( $birthdate ); ?>" 
                       class="regular-text" />
                <p class="description">Required for Wattn customer verification (format: yyyy-mm-dd)</p>
            </td>
        </tr>
    </table>
    <?php
} );

add_action( 'edit_user_profile', function( $user ) {
    // Same as show_user_profile
} );

// Save birthdate
add_action( 'personal_options_update', function( $user_id ) {
    if ( isset( $_POST['birthdate'] ) ) {
        update_user_meta( $user_id, 'birthdate', sanitize_text_field( $_POST['birthdate'] ) );
    }
} );

add_action( 'edit_user_profile_update', function( $user_id ) {
    // Same as personal_options_update
} );
```

## Error Logging

When `WP_DEBUG` is enabled, the system logs verification events to the error log:

```
[Wattn Customer Verification] User ID: 123 | Status: active_customer | Verified with name: John Doe, birthdate: 1980-01-15
[Wattn Customer Verification] User ID: 456 | Status: incomplete_data | Missing required user data for verification
[Wattn Customer Verification API Error] API request failed: Connection timeout
```

## Security

- API key is stored as a constant in the class (can be moved to WordPress options if needed)
- All API requests use HTTPS
- User data is sanitized before sending to API
- Birth dates are validated and formatted to `yyyy-mm-dd`
- API responses are validated before storing results

## Performance Considerations

### Caching

The verification result is cached in user meta, so API calls are only made when:
- User logs in
- User registers
- User updates their profile information

### Async Verification (Optional Enhancement)

For high-traffic sites, you may want to queue verifications:

```php
// Example using WordPress cron
add_action( 'wp_login', function( $user_login, $user ) {
    // Queue instead of immediate verification
    wp_schedule_single_event( time(), 'wattn_verify_user', [ $user->ID ] );
}, 10, 2 );

add_action( 'wattn_verify_user', function( $user_id ) {
    wattn_verify_customer( $user_id );
} );
```

## Troubleshooting

### User Not Being Verified

1. **Check if birth date is stored**:
   ```php
   $user_id = 123;
   $birthdate = get_user_meta( $user_id, 'billing_birthdate', true );
   var_dump( $birthdate ); // Should output yyyy-mm-dd format
   ```

2. **Check if name is stored**:
   ```php
   $user = get_userdata( $user_id );
   var_dump( $user->first_name, $user->last_name );
   // OR check billing info
   var_dump( 
       get_user_meta( $user_id, 'billing_first_name', true ),
       get_user_meta( $user_id, 'billing_last_name', true )
   );
   ```

3. **Manually trigger verification**:
   ```php
   $result = wattn_verify_customer( $user_id );
   var_dump( $result ); // Should be true if successful
   ```

4. **Enable debug logging**:
   Add to `wp-config.php`:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   ```
   Then check `/wp-content/debug.log` for verification logs.

### API Connection Issues

If you see API errors in the logs:

1. Check firewall/server allows outbound HTTPS to `input.wattn.no`
2. Verify API key is correct in the class constant
3. Test the API endpoint manually with curl (see example in main documentation)

## Future Enhancements

Possible improvements that could be added:

1. **Admin Settings Page** - Move API key and endpoint to WordPress admin settings
2. **Manual Bulk Verification** - Admin tool to verify all existing users
3. **Verification Status Column** - Add column in Users admin list showing verification status
4. **Scheduled Re-verification** - Periodic re-checks for all users (e.g., monthly)
5. **Webhook Support** - Allow Wattn to push customer status updates
6. **Cache Expiry** - Add time-based expiry to force re-verification after X days
