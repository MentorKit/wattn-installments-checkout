<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Wattn User Profile Fields
 * 
 * Adds display fields to user profile pages showing birthdate and customer status
 */
class Wattn_User_Profile {

    /**
     * Initialize hooks
     */
    public static function init() {
        // Add fields to user profile pages
        add_action( 'show_user_profile', [ __CLASS__, 'display_wattn_fields' ] );
        add_action( 'edit_user_profile', [ __CLASS__, 'display_wattn_fields' ] );
        
        // Add manual verification button
        add_action( 'edit_user_profile', [ __CLASS__, 'add_verification_button' ] );
        
        // Handle manual verification
        add_action( 'admin_init', [ __CLASS__, 'handle_manual_verification' ] );
    }
    
    /**
     * Display Wattn customer information fields
     * 
     * @param WP_User $user User object
     */
    public static function display_wattn_fields( $user ) {
        $birthdate = get_user_meta( $user->ID, 'birthdate', true );
        if ( empty( $birthdate ) ) {
            $birthdate = get_user_meta( $user->ID, 'billing_birthdate', true );
        }
        
        $is_customer = get_user_meta( $user->ID, 'is_wattn_customer', true );
        $last_verified = get_user_meta( $user->ID, 'wattn_last_verified', true );
        
        ?>
        <h2>Wattn Customer Information</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label>Wattn Customer Status</label></th>
                <td>
                    <?php if ( $is_customer ) : ?>
                        <span style="color: #00a32a; font-weight: bold;">✓ Active Customer</span>
                    <?php else : ?>
                        <span style="color: #d63638; font-weight: bold;">✗ Not an Active Customer</span>
                    <?php endif; ?>
                    
                    <?php if ( $last_verified ) : ?>
                        <p class="description">
                            Last verified: <?php echo esc_html( date( 'Y-m-d H:i:s', $last_verified ) ); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ( ! $birthdate || empty( $user->first_name ) || empty( $user->last_name ) ) : ?>
                        <p class="description" style="color: #d63638;">
                            ⚠ Missing required data for verification (first name, last name, or birth date).
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Add manual verification button (only on edit_user_profile, not own profile)
     * 
     * @param WP_User $user User object
     */
    public static function add_verification_button( $user ) {
        if ( ! current_user_can( 'edit_users' ) ) {
            return;
        }
        
        $verify_url = add_query_arg( [
            'action' => 'wattn_verify_customer',
            'user_id' => $user->ID,
            '_wpnonce' => wp_create_nonce( 'wattn_verify_' . $user->ID ),
        ], admin_url( 'user-edit.php?user_id=' . $user->ID ) );
        
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th></th>
                <td>
                    <a href="<?php echo esc_url( $verify_url ); ?>" 
                       class="button button-secondary">
                        Verify Wattn Customer Status Now
                    </a>
                    <p class="description">
                        Click to manually check if this user is an active Wattn customer.
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Handle manual verification request
     */
    public static function handle_manual_verification() {
        if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'wattn_verify_customer' ) {
            return;
        }
        
        if ( ! isset( $_GET['user_id'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }
        
        $user_id = intval( $_GET['user_id'] );
        
        if ( ! current_user_can( 'edit_users' ) ) {
            wp_die( 'You do not have permission to perform this action.' );
        }
        
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wattn_verify_' . $user_id ) ) {
            wp_die( 'Invalid nonce.' );
        }
        
        // Perform verification
        $result = wattn_verify_customer( $user_id );
        
        // Store last verified timestamp
        update_user_meta( $user_id, 'wattn_last_verified', time() );
        
        // Redirect back with message
        $redirect_url = add_query_arg( [
            'user_id' => $user_id,
            'wattn_verified' => $result ? '1' : '0',
        ], admin_url( 'user-edit.php' ) );
        
        wp_redirect( $redirect_url );
        exit;
    }
}
