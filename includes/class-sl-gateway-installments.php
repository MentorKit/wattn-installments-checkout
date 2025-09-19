<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SLI_Gateway_Installments extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'sli_installments';
        $this->method_title       = __( 'Downpayment – External', 'sl-installments' );
        $this->method_description = __( 'Classic Checkout only. Presents 6/12/24/36 month plans, APR + monthly fee, min-total gating, and order meta storage. Optional forward to external provider.', 'sl-installments' );
        $this->title              = __( 'Downpayment', 'sl-installments' );
        $this->has_fields         = true;
        $this->supports           = [ 'products' ];

        $this->init_form_fields();
        $this->init_settings();

        // Load settings
        $this->enabled      = $this->get_option( 'enabled', 'yes' );
        $this->title        = $this->get_option( 'title', __( 'Downpayment', 'sl-installments' ) );
        $this->apr_percent  = (float) $this->get_option( 'apr_percent', '0' );
        $this->monthly_fee  = (float) $this->get_option( 'monthly_fee', '30' ); // NOK per month
        $this->basis_mode   = $this->get_option( 'basis_mode', 'order_total' ); // order_total | fixed
        $this->basis_fixed  = $this->sanitize_money( $this->get_option( 'basis_fixed', '' ) );
        $this->min_total    = (float) $this->get_option( 'min_total', '9000' );

        // Optional forward
        $this->forward_mode = $this->get_option( 'forward_mode', 'off' ); // off|post
        $this->provider_url = rtrim( $this->get_option( 'provider_url', '' ), '/' );
        $this->api_key      = (string) $this->get_option( 'api_key', '' );
        $this->api_secret   = (string) $this->get_option( 'api_secret', '' );
        $this->callback_key = (string) $this->get_option( 'callback_key', '' );
        $this->send_customer= $this->get_option( 'send_customer', 'yes' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

        // Admin + emails
        add_action( 'woocommerce_admin_order_data_after_order_details', [ $this, 'admin_show_plan' ] );
        add_filter( 'woocommerce_email_order_meta_fields', [ $this, 'email_show_plan' ], 10, 3 );

        // Callback
        add_action( 'rest_api_init', function() {
            register_rest_route( 'sli/v1', '/callback', [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_callback' ],
                'permission_callback' => '__return_true',
            ] );
        } );
    }

    /** Only available if basis total > min_total */
    public function is_available() {
        if ( 'yes' !== $this->enabled ) return false;
        $basis = $this->get_basis_for_context();
        if ( $basis <= $this->min_total ) return false;
        return parent::is_available();
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __( 'Enable/Disable', 'sl-installments' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Downpayment – External (Classic only)', 'sl-installments' ),
                'default' => 'yes',
            ],
            'title' => [
                'title'       => __( 'Title', 'sl-installments' ),
                'type'        => 'text',
                'default'     => __( 'Downpayment', 'sl-installments' ),
                'desc_tip'    => true,
                'description' => __( 'Shown to customers at checkout.', 'sl-installments' ),
            ],
            'apr_percent' => [
                'title'       => __( 'APR (annual interest rate, %)', 'sl-installments' ),
                'type'        => 'number',
                'custom_attributes' => [ 'step' => '0.01', 'min' => '0' ],
                'default'     => '0',
                'description' => __( 'Annual Percentage Rate used for the monthly payment calculation (e.g., 9.9).', 'sl-installments' ),
            ],
            'monthly_fee' => [
                'title'       => __( 'Monthly fee (NOK)', 'sl-installments' ),
                'type'        => 'number',
                'custom_attributes' => [ 'step' => '0.01', 'min' => '0' ],
                'default'     => '30',
                'description' => __( 'Fixed monthly fee added to each installment.', 'sl-installments' ),
            ],
            'basis_mode' => [
                'title'       => __( 'Calculator basis', 'sl-installments' ),
                'type'        => 'select',
                'default'     => 'order_total',
                'options'     => [
                    'order_total' => __( 'Use order total (default)', 'sl-installments' ),
                    'fixed'       => __( 'Use fixed amount (set below)', 'sl-installments' ),
                ],
                'description' => __( 'Stored to order meta as installment_calc_basis_total.', 'sl-installments' ),
            ],
            'basis_fixed' => [
                'title'       => __( 'Fixed basis amount', 'sl-installments' ),
                'type'        => 'text',
                'default'     => '',
                'description' => __( 'Optional. If set and "Use fixed amount" is selected, calculator will use this amount.', 'sl-installments' ),
            ],
            'min_total' => [
                'title'       => __( 'Minimum basis total (NOK) to show method', 'sl-installments' ),
                'type'        => 'number',
                'custom_attributes' => [ 'step' => '0.01', 'min' => '0' ],
                'default'     => '9000',
                'description' => __( 'Payment method is only available when basis total exceeds this value.', 'sl-installments' ),
            ],
            // Optional forward
            'section_forward' => [
                'title'       => __( 'External forward (optional)', 'sl-installments' ),
                'type'        => 'title',
                'description' => __( 'If enabled, customers are auto-forwarded to a provider checkout with a signed payload.', 'sl-installments' ),
            ],
            'forward_mode' => [
                'title'       => __( 'Forward mode', 'sl-installments' ),
                'type'        => 'select',
                'default'     => 'off',
                'options'     => [
                    'off'  => __( 'Off (store selection only)', 'sl-installments' ),
                    'post' => __( 'Forward via POST (auto-submit form)', 'sl-installments' ),
                ],
            ],
            'provider_url' => [
                'title'       => __( 'Provider checkout URL', 'sl-installments' ),
                'type'        => 'text',
                'default'     => '',
                'description' => __( 'Example: https://pay.example.com/checkout', 'sl-installments' ),
            ],
            'api_key' => [
                'title' => __( 'API Key', 'sl-installments' ),
                'type'  => 'text',
                'default' => '',
            ],
            'api_secret' => [
                'title' => __( 'API Secret (HMAC)', 'sl-installments' ),
                'type'  => 'password',
                'default' => '',
            ],
            'callback_key' => [
                'title'       => __( 'Callback key (optional)', 'sl-installments' ),
                'type'        => 'text',
                'description' => __( 'A shared token the provider returns to help verify callbacks.', 'sl-installments' ),
                'default'     => '',
            ],
            'send_customer' => [
                'title'       => __( 'Include customer details in payload', 'sl-installments' ),
                'type'        => 'checkbox',
                'label'       => __( 'Send email/name/phone to provider', 'sl-installments' ),
                'default'     => 'yes',
            ],
        ];
    }

    /** UI: 6/12/24/36 + calculator + terms */
    public function payment_fields() {
        $basis = $this->get_basis_for_context();
        $apr   = max( 0.0, (float) $this->apr_percent );
        $fee   = max( 0.0, (float) $this->monthly_fee );
        $dec   = wc_get_price_decimals();
        $cur   = get_woocommerce_currency_symbol();

        echo '<div class="sli-wrap">';
        echo '<p>' . esc_html__( 'Choose a downpayment plan:', 'sl-installments' ) . '</p>';
        ?>
        <div class="sli-plan-list">
            <label><input type="radio" name="sli_plan" value="6m"  required> <?php echo esc_html__( '6 months', 'sl-installments' ); ?></label>
            <label><input type="radio" name="sli_plan" value="12m" required> <?php echo esc_html__( '12 months (1 year)', 'sl-installments' ); ?></label>
            <label><input type="radio" name="sli_plan" value="24m" required> <?php echo esc_html__( '24 months (2 years)', 'sl-installments' ); ?></label>
            <label><input type="radio" name="sli_plan" value="36m" required> <?php echo esc_html__( '36 months (3 years)', 'sl-installments' ); ?></label>
        </div>

        <p class="sli-apr-fee">
            <em><?php printf( esc_html__( 'APR: %s%% • Monthly fee: %s %s', 'sl-installments' ),
                esc_html( number_format( $apr, 2 ) ),
                esc_html( $cur ),
                esc_html( number_format( $fee, 2 ) )
            ); ?></em>
        </p>

        <div id="sli-calc"
             data-basis="<?php echo esc_attr( number_format( $basis, 2, '.', '' ) ); ?>"
             data-apr="<?php echo esc_attr( number_format( $apr, 6, '.', '' ) ); ?>"
             data-fee="<?php echo esc_attr( number_format( $fee, 2, '.', '' ) ); ?>"
             data-decimals="<?php echo esc_attr( (int) $dec ); ?>"
             data-curr="<?php echo esc_attr( $cur ); ?>">
            <p class="sli-calc-row">
                <strong><?php esc_html_e( 'Estimated monthly payment (incl. fee):', 'sl-installments' ); ?></strong>
                <span id="sli-monthly">—</span>
            </p>
            <p class="sli-calc-row sml">
                <?php esc_html_e( 'Total credit cost (interest + fees):', 'sl-installments' ); ?>
                <span id="sli-credit">—</span>
            </p>
        </div>

        <p class="sli-terms">
            <label>
                <input type="checkbox" name="sli_terms" value="1" required>
                <?php esc_html_e( 'I accept the installment terms.', 'sl-installments' ); ?>
            </label>
        </p>

        <input type="hidden" name="sli_basis" value="<?php echo esc_attr( number_format( $basis, 2, '.', '' ) ); ?>">
        <input type="hidden" name="sl_payment_method_label" value="Wattn Installment">
        </div>
        <?php
    }

    /** Validate selection + terms */
    public function validate_fields() {
        $valid_codes = [ '6m','12m','24m','36m' ];
        $plan  = isset( $_POST['sli_plan'] ) ? sanitize_text_field( $_POST['sli_plan'] ) : '';
        $terms = ! empty( $_POST['sli_terms'] );

        if ( ! in_array( $plan, $valid_codes, true ) ) {
            wc_add_notice( __( 'Please select a downpayment plan.', 'sl-installments' ), 'error' );
            return false;
        }
        if ( ! $terms ) {
            wc_add_notice( __( 'Please accept the installment terms.', 'sl-installments' ), 'error' );
            return false;
        }
        return true;
    }

    /** Place order: compute monthly incl. fee; store meta; optional forward */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        $plan_code  = sanitize_text_field( $_POST['sli_plan'] ?? '' );
        $months     = $this->code_to_months( $plan_code );
        $terms_acc  = ! empty( $_POST['sli_terms'] );
        $basis_in   = isset( $_POST['sli_basis'] ) ? (float) $_POST['sli_basis'] : 0.0;
        $basis_used = ( $basis_in > 0 ) ? $basis_in : (float) $order->get_total();

        $apr  = max( 0.0, (float) $this->apr_percent );
        $fee  = max( 0.0, (float) $this->monthly_fee );
        $dec  = wc_get_price_decimals();

        $monthly_base = $this->calc_monthly( $basis_used, $apr, $months );
        $monthly      = round( $monthly_base + $fee, $dec );

        $total_credit_cost = max( 0.0, round( $monthly * $months - $basis_used, $dec ) );

        // Required order meta
        $order->update_meta_data( 'installment_plan_months',            (int) $months );
        $order->update_meta_data( 'installment_monthly_amount',         (float) $monthly );
        $order->update_meta_data( 'installment_total_credit_cost',      (float) $total_credit_cost );
        $order->update_meta_data( 'installment_terms_accepted',         (bool)  $terms_acc );
        $order->update_meta_data( 'payment_method_label',               'Wattn Installment' );
        if ( $basis_used > 0 ) {
            $order->update_meta_data( 'installment_calc_basis_total',   (float) $basis_used );
        }
        $order->update_meta_data( '_sli_plan_label', $this->plan_label( $months ) );
        $order->update_meta_data( '_sli_monthly_fee', (float) $fee );
        $order->save();

        $order->update_status( 'pending', __( 'Waiting for external downpayment provider', 'sl-installments' ) );

        if ( $this->forward_mode === 'post' && ! empty( $this->provider_url ) ) {
            $payload      = $this->build_provider_payload( $order, $plan_code, $basis_used, $monthly, $total_credit_cost, $months, $apr, $fee );
            $payload_json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES );
            $signature    = $this->api_secret ? hash_hmac( 'sha256', $payload_json, $this->api_secret ) : '';
            $html         = $this->build_auto_post_form( $this->provider_url, [
                'payload'   => base64_encode( $payload_json ),
                'signature' => $signature,
            ] );
            $data_url = 'data:text/html;base64,' . base64_encode( $html );
            return [ 'result' => 'success', 'redirect' => $data_url ];
        }

        return [ 'result' => 'success', 'redirect' => $this->get_return_url( $order ) ];
    }

    /** ===== Helpers ===== */

    private function get_basis_for_context(): float {
        if ( $this->basis_mode === 'fixed' && $this->basis_fixed > 0 ) {
            return (float) $this->basis_fixed;
        }
        if ( function_exists('WC') && WC()->cart ) {
            $totals = WC()->cart->get_totals();
            return isset( $totals['total'] ) ? (float) $totals['total'] : 0.0;
        }
        return 0.0;
    }

    private function code_to_months( $code ): int {
        switch ( $code ) {
            case '12m': return 12;
            case '24m': return 24;
            case '36m': return 36;
            default:    return 6;
        }
    }

    private function plan_label( $months ): string {
        switch ( (int) $months ) {
            case 12: return __( '12 months (1 year)', 'sl-installments' );
            case 24: return __( '24 months (2 years)', 'sl-installments' );
            case 36: return __( '36 months (3 years)', 'sl-installments' );
            default: return __( '6 months', 'sl-installments' );
        }
    }

    /** Amortizing monthly (without fee) */
    private function calc_monthly( $principal, $apr_percent, $months ) {
        if ( $months <= 0 ) return 0.0;
        $r = max( 0.0, (float) $apr_percent ) / 100.0 / 12.0;
        if ( $r <= 0.0 ) { return $principal / $months; }
        $pow = pow( 1 + $r, $months );
        return $principal * ( $r * $pow ) / ( $pow - 1 );
    }

    /** External payload (optional) */
    private function build_provider_payload( WC_Order $order, $plan_code, $basis_used, $monthly, $credit_cost, $months, $apr_percent, $monthly_fee ) {
        $payload = [
            'merchant_key' => (string) $this->api_key,
            'order_id'     => (string) $order->get_id(),
            'amount'       => (int) round( $order->get_total() * 100 ),
            'currency'     => $order->get_currency(),
            'plan_id'      => (string) $plan_code,
            'calc'         => [
                'basis'         => (float) $basis_used,
                'months'        => (int)   $months,
                'apr_percent'   => (float) $apr_percent,
                'monthly_fee'   => (float) $monthly_fee,
                'monthly'       => (float) $monthly,       // incl. fee
                'total_credit'  => (float) $credit_cost,   // interest + fees
            ],
            'return_urls'  => [
                'success'  => add_query_arg( 'sli', 'success', $this->get_return_url( $order ) ),
                'cancel'   => add_query_arg( 'sli', 'cancel', wc_get_checkout_url() ),
                'callback' => get_rest_url( null, 'sli/v1/callback' ),
            ],
            'ts'           => time(),
        ];

        if ( $this->send_customer === 'yes' ) {
            $payload['customer'] = [
                'email' => $order->get_billing_email(),
                'name'  => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
                'phone' => $order->get_billing_phone(),
            ];
        }
        return apply_filters( 'sli_provider_payload', $payload, $order, $plan_code, $this );
    }

    private function build_auto_post_form( $action, $fields ) {
        $inputs = '';
        foreach ( $fields as $k => $v ) {
            $inputs .= sprintf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $k ), esc_attr( $v ) );
        }
        return '<!doctype html><html><meta charset="utf-8"><body>
            <p>'.esc_html__( 'Redirecting to payment provider…', 'sl-installments' ).'</p>
            <form id="sli-forward" method="post" action="'.esc_attr( $action ).'">'.$inputs.'</form>
            <script>document.getElementById("sli-forward").submit();</script>
        </body></html>';
    }

    /** Admin order screen box */
    public function admin_show_plan( $order ) {
        if ( ! $order instanceof WC_Order ) { return; }
        $label   = $order->get_meta( '_sli_plan_label' );
        $months  = $order->get_meta( 'installment_plan_months' );
        $monthly = $order->get_meta( 'installment_monthly_amount' );
        $credit  = $order->get_meta( 'installment_total_credit_cost' );
        $basis   = $order->get_meta( 'installment_calc_basis_total' );
        $terms   = $order->get_meta( 'installment_terms_accepted' );
        $fee     = $order->get_meta( '_sli_monthly_fee' );

        if ( ! $label && ! $months ) return;

        echo '<div class="order_data_column sli-admin-box"><h3>' . esc_html__( 'Downpayment', 'sl-installments' ) . '</h3>';
        if ( $label )   echo '<p><strong>' . esc_html__( 'Selected plan:', 'sl-installments' ) . '</strong> ' . esc_html( $label ) . '</p>';
        if ( $months )  echo '<p><strong>' . esc_html__( 'Months:', 'sl-installments' ) . '</strong> ' . esc_html( (int) $months ) . '</p>';
        if ( $basis !== '' )   echo '<p><strong>' . esc_html__( 'Basis total:', 'sl-installments' ) . '</strong> ' . wc_price( (float) $basis ) . '</p>';
        if ( $monthly !== '' ) echo '<p><strong>' . esc_html__( 'Monthly amount (incl. fee):', 'sl-installments' ) . '</strong> ' . wc_price( (float) $monthly ) . '</p>';
        if ( $fee !== '' )     echo '<p><strong>' . esc_html__( 'Monthly fee:', 'sl-installments' ) . '</strong> ' . wc_price( (float) $fee ) . '</p>';
        if ( $credit !== '' )  echo '<p><strong>' . esc_html__( 'Total credit cost:', 'sl-installments' ) . '</strong> ' . wc_price( (float) $credit ) . '</p>';
        echo '<p><strong>' . esc_html__( 'Terms accepted:', 'sl-installments' ) . '</strong> ' . ( $terms ? 'Yes' : 'No' ) . '</p>';
        echo '</div>';
    }

    /** Include summary in order emails */
    public function email_show_plan( $fields, $sent_to_admin, $order ) {
        if ( $order instanceof WC_Order ) {
            $label   = $order->get_meta( '_sli_plan_label' );
            $months  = $order->get_meta( 'installment_plan_months' );
            $monthly = $order->get_meta( 'installment_monthly_amount' );
            $credit  = $order->get_meta( 'installment_total_credit_cost' );
            $basis   = $order->get_meta( 'installment_calc_basis_total' );

            if ( $label ) {
                $fields['sli_plan'] = [
                    'label' => __( 'Downpayment plan', 'sl-installments' ),
                    'value' => wp_kses_post( $label ),
                ];
            }
            if ( $months ) {
                $fields['sli_months'] = [
                    'label' => __( 'Months', 'sl-installments' ),
                    'value' => (int) $months,
                ];
            }
            if ( $basis !== '' ) {
                $fields['sli_basis'] = [
                    'label' => __( 'Basis total', 'sl-installments' ),
                    'value' => wc_price( (float) $basis ),
                ];
            }
            if ( $monthly !== '' ) {
                $fields['sli_monthly'] = [
                    'label' => __( 'Monthly amount (incl. fee)', 'sl-installments' ),
                    'value' => wc_price( (float) $monthly ),
                ];
            }
            if ( $credit !== '' ) {
                $fields['sli_credit'] = [
                    'label' => __( 'Total credit cost', 'sl-installments' ),
                    'value' => wc_price( (float) $credit ),
                ];
            }
        }
        return $fields;
    }

    /** Provider webhook */
    public function handle_callback( \WP_REST_Request $req ) {
        $payload_b64 = $req->get_param( 'payload' );
        $signature   = $req->get_param( 'signature' );
        $cb_key      = $req->get_param( 'cb_key' );

        if ( ! $payload_b64 ) {
            return new \WP_REST_Response( [ 'ok' => false, 'error' => 'missing-payload' ], 400 );
        }
        $payload_json = base64_decode( $payload_b64 );
        if ( $this->api_secret ) {
            $calc = hash_hmac( 'sha256', $payload_json, $this->api_secret );
            if ( ! hash_equals( (string) $calc, (string) $signature ) ) {
                return new \WP_REST_Response( [ 'ok' => false, 'error' => 'bad-signature' ], 403 );
            }
        }
        if ( $this->callback_key && $cb_key !== $this->callback_key ) {
            return new \WP_REST_Response( [ 'ok' => false, 'error' => 'cb-key-mismatch' ], 403 );
        }

        $data     = json_decode( $payload_json, true );
        $order_id = isset( $data['order_id'] ) ? (int) $data['order_id'] : 0;
        $status   = isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'failed';
        $txn      = isset( $data['transaction_id'] ) ? sanitize_text_field( $data['transaction_id'] ) : '';

        $order = $order_id ? wc_get_order( $order_id ) : null;
        if ( ! $order ) {
            return new \WP_REST_Response( [ 'ok' => false, 'error' => 'order-not-found' ], 404 );
        }

        if ( $txn ) {
            $order->update_meta_data( '_sli_txn', $txn );
        }

        switch ( $status ) {
            case 'success':
                $order->payment_complete( $txn ?: '' );
                $order->add_order_note( __( 'Downpayment confirmed by provider.', 'sl-installments' ) );
                break;
            case 'canceled':
                $order->update_status( 'cancelled', __( 'Customer canceled at provider.', 'sl-installments' ) );
                break;
            default:
                $order->update_status( 'failed', __( 'Provider reported failure/decline.', 'sl-installments' ) );
        }
        $order->save();

        return new \WP_REST_Response( [ 'ok' => true ], 200 );
    }

    /** Sanitize "money-like" text to float */
    private function sanitize_money( $val ) {
        if ( $val === '' || $val === null ) return '';
        $v = str_replace( [ ' ', ',' ], [ '', '.' ], (string) $val );
        return is_numeric( $v ) ? (float) $v : '';
    }
}
