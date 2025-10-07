<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Safe money sanitizer (string to float) for admin settings.
 */
function sli_sanitize_money( $val ) {
    if ( $val === '' || $val === null ) return '';
    $v = str_replace( [ ' ', ',' ], [ '', '.' ], (string) $val );
    return is_numeric( $v ) ? (float) $v : '';
}

/**
 * Check if a user is an active Wattn customer
 * 
 * @param int $user_id User ID (defaults to current user)
 * @return bool True if the user is an active Wattn customer, false otherwise
 */
function is_wattn_customer( $user_id = null ) {
    if ( is_null( $user_id ) ) {
        $user_id = get_current_user_id();
    }
    
    if ( ! $user_id ) {
        return false;
    }
    
    if ( class_exists( 'Wattn_Customer_Verification' ) ) {
        return Wattn_Customer_Verification::is_wattn_customer( $user_id );
    }
    
    // Fallback to direct meta check
    $status = get_user_meta( $user_id, 'is_wattn_customer', true );
    return (bool) $status;
}

/**
 * Manually trigger customer verification for a user
 * 
 * @param int $user_id User ID
 * @return bool True if verification was successful, false otherwise
 */
function wattn_verify_customer( $user_id ) {
    if ( ! $user_id ) {
        return false;
    }
    
    if ( class_exists( 'Wattn_Customer_Verification' ) ) {
        return Wattn_Customer_Verification::update_customer_status( $user_id );
    }
    
    return false;
}
