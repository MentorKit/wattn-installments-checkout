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
