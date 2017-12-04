<?php
//
// Description
// ===========
// This method will return subscription information for a tenant.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_tenants_subscriptionInfo($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.subscriptionInfo'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    //
    // Get the billing information from the subscription table
    //
    $strsql = "SELECT ciniki_tenants.name, "
        . "ciniki_tenants.uuid, "
        . "ciniki_tenant_subscriptions.id, "
        . "ciniki_tenant_subscriptions.status, "
        . "signup_date, "
        . "trial_days, "
        . "currency, "
        . "monthly, "
        . "yearly, "
        . "billing_email, "
        . "paypal_subscr_id, "
        . "paypal_payer_email, "
        . "paypal_payer_id, "
        . "paypal_amount, "
        . "IF(last_payment_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_tenant_subscriptions.last_payment_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS last_payment_date, "
        . "DATE_FORMAT(paid_until, '%b %e, %Y') AS paid_until, "
        . "DATE_FORMAT(trial_start_date, '%b %e, %Y') AS trial_start_date, "
        //. "trial_days - FLOOR((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_tenant_subscriptions.signup_date))/86400) AS trial_remaining, "
        . "payment_type, "
        . "payment_frequency, "
        . "notes "
        . "FROM ciniki_tenant_subscriptions, ciniki_tenants "
        . "WHERE ciniki_tenant_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_tenant_subscriptions.tnid = ciniki_tenants.id "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'subscription');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['subscription']) ) {
        $subscription = array('id'=>0, 'status'=>0, 'status_text'=>'No subscription', 'monthly'=>0, 'yearly'=>0);
    } else {
        $subscription = $rc['subscription'];
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $trial_end_date = new DateTime($subscription['trial_start_date'], new DateTimeZone('UTC'));
        if( $subscription['trial_days'] > 0 ) {
            $trial_end_date->add(new DateInterval('P' . $subscription['trial_days'] . 'D'));
        }
        $subscription['trial_remaining'] = $now->diff($trial_end_date)->format('%r%a');
        if( $subscription['trial_remaining'] > 0 ) {
            $subscription['trial_end'] = $trial_end_date->format($date_format);
            $subscription['trial_end_ts'] = $trial_end_date->format('U');
        }

        if( $subscription['currency'] == 'CAD' ) {
            $subscription['stripe_plan'] = 'cad_';
        } elseif( $subscription['currency'] == 'USD' ) {
            $subscription['stripe_plan'] = 'usd_';
        } else {
            $subscription['stripe_plan'] = '';
        }
        if( $subscription['stripe_plan'] != '' ) {
            if( $subscription['payment_frequency'] == 10 ) {
                $subscription['stripe_plan'] .= 'monthly';
                $subscription['stripe_quantity'] = floor($subscription['monthly']);
            } elseif( $subscription['payment_frequency'] == 20 ) {
                $subscription['stripe_plan'] .= 'yearly';
                $subscription['stripe_quantity'] = floor($subscription['yearly']);
            }
        }
         
        if( $subscription['status'] == 0 ) {
            $subscription['status_text'] = 'Unknown';
        } elseif( $subscription['status'] == 1 ) {
            $subscription['status_text'] = 'Update required';
        } elseif( $subscription['status'] == 2 ) {
            $subscription['status_text'] = 'Payment information required';
        } elseif( $subscription['status'] == 10 ) {
            $subscription['status_text'] = 'Active';
        } elseif( $subscription['status'] == 11 ) {
            $subscription['status_text'] = 'Free Subscription';
        } elseif( $subscription['status'] == 50 ) {
            $subscription['status_text'] = 'Suspended';
        } elseif( $subscription['status'] == 60 ) {
            $subscription['status_text'] = 'Cancelled';
        } elseif( $subscription['status'] == 61 ) {
            $subscription['status_text'] = 'Pending Cancel';
        }
    }

    if( isset($subscription['trail_remaining']) ) {
        $subscription['trial_remaining'] = 0;
        $subscription['trial_end'] = 0;
    } 

    //
    // get the tenant email address
    //
    $strsql = "SELECT detail_value "
        . "FROM ciniki_tenant_details "
        . "WHERE ciniki_tenant_details.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND detail_key = 'contact.email.address' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'contact');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['contact']['detail_value']) ) {
        $subscription['tenant_email'] = $rc['contact']['detail_value'];
    }
    if( isset($ciniki['config']['ciniki.tenants']['stripe.public']) ) {
        $subscription['stripe_public_key'] = $ciniki['config']['ciniki.tenants']['stripe.public'];
    }

    $rsp = array('stat'=>'ok', 'subscription'=>$subscription, 
        'paypal'=>array(
            'url'=>$ciniki['config']['ciniki.tenants']['paypal.url'],
            'tenant'=>$ciniki['config']['ciniki.tenants']['paypal.tenant'],
            'prefix'=>$ciniki['config']['ciniki.tenants']['paypal.item_name.prefix'],
            'ipn'=>$ciniki['config']['ciniki.tenants']['paypal.ipn']
            ),
        );

    return $rsp;
}
?>
