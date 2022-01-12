<?php
/**
 * Code related to spam detection and prevention.
 */
// Constants. Define these in wp-config.php to override.
if ( ! defined( 'PMPRO_SPAM_ACTION_NUM_LIMIT' ) ) {
    define( 'PMPRO_SPAM_ACTION_NUM_LIMIT', 10 );
}
if ( ! defined( 'PMPRO_SPAM_ACTION_TIME_LIMIT' ) ) {
    define( 'PMPRO_SPAM_ACTION_TIME_LIMIT', 900 );  // in seconds
}

/**
 * Determine whether the current visitor a spammer.
 *
 * @since 2.7
 *
 * @return bool Whether the current visitor a spammer.
 */
function pmpro_is_spammer() {
    $is_spammer = false;
    
    $activity = pmpro_get_spam_activity();
    if ( count( $activity ) >= PMPRO_SPAM_ACTION_NUM_LIMIT ) {
        $is_spammer = true;
    }
    
    return apply_filters( 'pmpro_is_spammer', $is_spammer, $activity );
}

/**
 * Get spam activity by IP.
 * @since 2.7
 * @param string $ip    The IP address to get activity for.
 * @return array|False  The array of activity if successful, false if no IP. 
 */
function pmpro_get_spam_activity( $ip = null ) {
    if ( empty( $ip ) ) {
        $ip = pmpro_get_ip();        
    }
    
    // If we can't determine the IP, let's bail.
    if ( empty( $ip ) ) {
        return false;
    }
    
    $transient_key = 'pmpro_spam_activity_' . $ip;
    $activity = get_transient( $transient_key );    
    if ( empty( $activity ) ) { $activity = []; }
    
    // Remove old items.
	$new_activity = [];
	$now = current_time( 'timestamp', true ); // UTC
    foreach( $activity as $item ) {
		if ( $item > $now-( PMPRO_SPAM_ACTION_TIME_LIMIT ) ) {
			$new_activity[] = $item;
		}
	}
    
    return $new_activity;    
}

/**
 * Track spam activity.
 * When we hit a certain number, the spam flag will trigger.
 * For now we are only tracking credit card declines their timestamps.
 * IP address isn't a perfect way to track this, but it's the best we have.
 *
 * @since 2.7
 * @param string $ip    The IP address to track activity for.
 * @return bool         True if successful. False if we couldn't determine the IP.
 */
function pmpro_track_spam_activity( $ip = null ) {
    if ( empty( $ip ) ) {
        $ip = pmpro_get_ip();        
    }
    
    // If we can't determine the IP, let's bail.
    if ( empty( $ip ) ) {
        return false;
    }
        
    $activity = pmpro_get_spam_activity( $ip );
    $now = current_time( 'timestamp', true ); // UTC
    array_unshift( $activity, $now );
    
    // If we have more than the limit, don't bother storing them.
    if ( count( $activity ) > PMPRO_SPAM_ACTION_NUM_LIMIT ) {
        rsort( $activity );
        $activity = array_slice( $activity, 0, PMPRO_SPAM_ACTION_NUM_LIMIT );
    }
    
    // Save to transient.
    $transient_key = 'pmpro_spam_activity_' . $ip;
    set_transient( $transient_key, $activity );
    
    return true;
}

/**
 * Clears all stored spam activity for an IP address.
 * Note that the pmpro_get_spam_activity function clears out old values
 * automatically, and this should only be used to completely clear the activity.
 *
 * @since 2.7
 * @param string $ip    The IP address to clear activity for.
 * @return bool         True if successful. False if we couldn't determine the IP.
 */
function pmpro_clear_spam_activity( $ip = null ) {
    if ( empty( $ip ) ) {
        $ip = pmpro_get_ip();        
    }
    
    // If we can't determine the IP, let's bail.
    if ( empty( $ip ) ) {
        return false;
    }
    
    $transient_key = 'pmpro_spam_activity_' . $ip;
    
    delete_transient( $transient_key );
}

/**
 * Disable checkout and billing update forms for spammers.
 *
 * We're using the pmpro_required_billing_fields filter because it is
 * consistently used on the checkout and update billing pages.
 * The pmpro_setMessage() function sets the $pmpro_msgt value to error,
 * which stops the forms from working and shows the corresponding error.
 * We return the $required_fields parameter to keep the filter working.
 *
 * @since 2.7
 */
function pmpro_disable_checkout_for_spammers( $required_fields ) {    
    if ( pmpro_was_checkout_form_submitted() && pmpro_is_spammer() ) {
        pmpro_setMessage( __( 'Suspicious activity detected. Try again in a few minutes.', 'paid-memberships-pro' ), 'pmpro_error' );        
    }
    
    return $required_fields;
}
add_filter( 'pmpro_required_billing_fields', 'pmpro_disable_checkout_for_spammers', 10, 1 );