<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Wattn Customer Verification
 * 
 * Handles checking if a user is an active Wattn customer via API
 * and stores the result in user meta.
 */
class Wattn_Customer_Verification {

    /**
     * API endpoint for customer verification
     */
    const API_ENDPOINT = 'https://input.wattn.no/api/v2/is-active-customer';
    
    /**
     * API key for authentication
     */
    const API_KEY = 'CC3KZGINWPBCITW80RXWU6TOZCR4QY1IT6DTAKUXZARK9GOJK4NJ18W9VQLO08RVPI4DIA3IWSEXKBAC6BSB696BGZR1Z0L7SBLI';
    
    /**
     * User meta key for storing customer status
     */
    const META_KEY = 'is_wattn_customer';
    
    /**
     * Initialize hooks
     */
    public static function init() {
        // Hook into user login
        add_action( 'wp_login', [ __CLASS__, 'on_user_login' ], 10, 2 );
        
        // Hook into user registration
        add_action( 'user_register', [ __CLASS__, 'on_user_register' ], 10, 1 );
        
        // Hook into profile update (optional - updates when user changes their info)
        add_action( 'profile_update', [ __CLASS__, 'on_profile_update' ], 10, 2 );
        
        // Hook into checkout page load to verify member status when benefits apply
        add_action( 'woocommerce_before_checkout_form', [ __CLASS__, 'on_checkout_page' ], 5 );
    }
    
    /**
     * Handle user login event
     * 
     * @param string $user_login Username
     * @param WP_User $user User object
     */
    public static function on_user_login( $user_login, $user ) {
        if ( $user instanceof WP_User ) {
            self::update_customer_status( $user->ID );
        }
    }
    
    /**
     * Handle user registration event
     * 
     * @param int $user_id User ID
     */
    public static function on_user_register( $user_id ) {
        self::update_customer_status( $user_id );
    }
    
    /**
     * Handle profile update event (when user updates their profile)
     * 
     * @param int $user_id User ID
     * @param WP_User $old_user_data Old user data
     */
    public static function on_profile_update( $user_id, $old_user_data ) {
        // Only update if first name, last name, or billing info changed
        $user = get_userdata( $user_id );
        if ( $user ) {
            $should_update = false;
            
            // Check if name fields changed
            if ( $user->first_name !== $old_user_data->first_name || 
                 $user->last_name !== $old_user_data->last_name ) {
                $should_update = true;
            }
            
            // Check if billing name changed (for WooCommerce users)
            if ( function_exists( 'get_user_meta' ) ) {
                $old_billing_first = get_user_meta( $user_id, 'billing_first_name', true );
                $old_billing_last = get_user_meta( $user_id, 'billing_last_name', true );
                
                // If billing info exists and might have changed, update
                if ( ! empty( $old_billing_first ) || ! empty( $old_billing_last ) ) {
                    $should_update = true;
                }
            }
            
            if ( $should_update ) {
                self::update_customer_status( $user_id );
            }
        }
    }
    
    /**
     * Handle checkout page load event
     * Updates customer status when user visits checkout to ensure benefits are current
     */
    public static function on_checkout_page() {
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            self::update_customer_status( $user_id );
        }
    }
    
    /**
     * Update customer status for a user
     * 
     * @param int $user_id User ID
     * @return bool True if updated successfully, false otherwise
     */
    public static function update_customer_status( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }
        
        // Get user data for API request
        $data = self::get_user_data_for_api( $user );
        
        if ( ! $data || empty( $data['first_name'] ) || empty( $data['last_name'] ) || empty( $data['birthdate'] ) ) {
            // If we don't have required data, set as false and return
            update_user_meta( $user_id, self::META_KEY, false );
            self::log_verification( $user_id, 'incomplete_data', 'Missing required user data for verification' );
            return false;
        }
        
        // Check customer status via API
        $is_customer = self::check_customer_status( $data['first_name'], $data['last_name'], $data['birthdate'] );
        
        // Update user meta
        update_user_meta( $user_id, self::META_KEY, $is_customer );
        update_user_meta( $user_id, 'wattn_last_verified', time() );
        
        // Log the result
        self::log_verification( $user_id, $is_customer ? 'active_customer' : 'not_customer', sprintf(
            'Verified with name: %s %s, birthdate: %s',
            $data['first_name'],
            $data['last_name'],
            $data['birthdate']
        ) );
        
        return true;
    }
    
    /**
     * Get user data needed for API request
     * 
     * @param WP_User $user User object
     * @return array|false Array with first_name, last_name, birthdate or false if data incomplete
     */
    private static function get_user_data_for_api( $user ) {
        $first_name = '';
        $last_name = '';
        $birthdate = '';
        
        // Try to get billing info first (for WooCommerce users)
        if ( function_exists( 'get_user_meta' ) ) {
            $billing_first = get_user_meta( $user->ID, 'billing_first_name', true );
            $billing_last = get_user_meta( $user->ID, 'billing_last_name', true );
            
            if ( ! empty( $billing_first ) && ! empty( $billing_last ) ) {
                $first_name = $billing_first;
                $last_name = $billing_last;
            }
        }
        
        // Fallback to WordPress user fields
        if ( empty( $first_name ) && ! empty( $user->first_name ) ) {
            $first_name = $user->first_name;
        }
        if ( empty( $last_name ) && ! empty( $user->last_name ) ) {
            $last_name = $user->last_name;
        }
        
        // Try to get birthdate from custom field
        $birthdate = get_user_meta( $user->ID, 'billing_birthdate', true );
        if ( empty( $birthdate ) ) {
            $birthdate = get_user_meta( $user->ID, 'birthdate', true );
        }
        
        // Return false if any required field is missing
        if ( empty( $first_name ) || empty( $last_name ) || empty( $birthdate ) ) {
            return false;
        }
        
        // Validate and format birthdate (should be yyyy-mm-dd)
        $birthdate = self::validate_birthdate( $birthdate );
        if ( ! $birthdate ) {
            return false;
        }
        
        return [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'birthdate'  => $birthdate,
        ];
    }
    
    /**
     * Validate and format birthdate to yyyy-mm-dd
     * 
     * @param string $date Date string
     * @return string|false Formatted date or false if invalid
     */
    private static function validate_birthdate( $date ) {
        if ( empty( $date ) ) {
            return false;
        }
        
        // Try to parse the date
        $timestamp = strtotime( $date );
        if ( ! $timestamp ) {
            return false;
        }
        
        // Format to yyyy-mm-dd
        return date( 'Y-m-d', $timestamp );
    }
    
    /**
     * Check if user is an active customer via API
     * 
     * @param string $first_name First name
     * @param string $last_name Last name
     * @param string $birthdate Birthdate in yyyy-mm-dd format
     * @return bool True if active customer, false otherwise
     */
    private static function check_customer_status( $first_name, $last_name, $birthdate ) {
        $url = add_query_arg( [
            'FirstName' => urlencode( $first_name ),
            'LastName'  => urlencode( $last_name ),
            'Birthdate' => urlencode( $birthdate ),
        ], self::API_ENDPOINT );
        
        $response = wp_remote_get( $url, [
            'headers' => [
                'accept'    => 'application/json',
                'x-api-key' => self::API_KEY,
            ],
            'timeout' => 10,
        ] );
        
        // Check for errors
        if ( is_wp_error( $response ) ) {
            self::log_api_error( 'API request failed: ' . $response->get_error_message() );
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 200 ) {
            self::log_api_error( 'API returned status code: ' . $status_code );
            return false;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( ! is_array( $data ) || ! isset( $data['isActiveCustomer'] ) ) {
            self::log_api_error( 'Invalid API response format' );
            return false;
        }
        
        return (bool) $data['isActiveCustomer'];
    }
    
    /**
     * Get customer status for a user
     * 
     * @param int $user_id User ID
     * @return bool True if active customer, false otherwise
     */
    public static function is_wattn_customer( $user_id ) {
        $status = get_user_meta( $user_id, self::META_KEY, true );
        return (bool) $status;
    }
    
    /**
     * Log verification event
     * 
     * @param int $user_id User ID
     * @param string $status Status (active_customer, not_customer, incomplete_data, etc.)
     * @param string $message Additional message
     */
    private static function log_verification( $user_id, $status, $message = '' ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }
        
        error_log( sprintf(
            '[Wattn Customer Verification] User ID: %d | Status: %s | %s',
            $user_id,
            $status,
            $message
        ) );
    }
    
    /**
     * Log API errors
     * 
     * @param string $message Error message
     */
    private static function log_api_error( $message ) {
        error_log( '[Wattn Customer Verification API Error] ' . $message );
    }
}
