<?php
//
// Description
// ===========
// This method will return the list of tenants, their status and subcription information
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_tenants_subscriptionStatus($ciniki) {
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, 0, 'ciniki.tenants.subscriptionStatus'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    //
    // Get the billing information from the subscription table
    //
    $strsql = "SELECT ciniki_tenants.id, ciniki_tenants.status AS tenant_status, "
        . "ciniki_tenants.name, "
        . "IFNULL(ciniki_tenant_subscriptions.status, 0) AS status_id, "
        . "IFNULL(ciniki_tenant_subscriptions.status, 0) AS status, "
        . "trial_days, payment_type, payment_frequency, "
        . "currency, "
        . "IFNULL(monthly,0)+(IFNULL(yearly,0)/12) as monthly, "
        . "IFNULL(yearly,0)+(IFNULL(monthly,0)*12) as yearly, "
//      . "IFNULL(monthly,0) as monthly_total, "
//      . "IFNULL(monthly,0)*12 AS yearly, "
//      . "IFNULL(monthly,0)*12 AS yearly_total, "
        . "paypal_subscr_id, paypal_payer_email, paypal_payer_id, paypal_amount, "
        . "IF(signup_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_tenant_subscriptions.signup_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS signup_date, "
        . "IF(last_payment_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_tenant_subscriptions.last_payment_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS last_payment_date, "
        . "IF(paid_until='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_tenant_subscriptions.paid_until, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y')) AS paid_until, "
        . "IF(trial_start_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_tenant_subscriptions.trial_start_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS trial_start_date, "
        . "trial_days - FLOOR((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_tenant_subscriptions.trial_start_date))/86400) AS trial_remaining "
        . "FROM ciniki_tenants "
        . "LEFT JOIN ciniki_tenant_subscriptions ON (ciniki_tenants.id = ciniki_tenant_subscriptions.tnid) "
        . "ORDER BY ciniki_tenant_subscriptions.status, ciniki_tenants.name "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'statuses', 'fname'=>'status', 'name'=>'status',
            'fields'=>array('name'=>'status_id', 'status', 'monthly', 'yearly'),
            'sums'=>array('monthly', 'yearly'),
            'maps'=>array(
                'status'=>array('0'=>'Unknown', '1'=>'Update required', '2'=>'Trial', '10'=>'Active', '11'=>'Free Subscription', '50'=>'Suspended', '60'=>'Cancelled'),
                )),
        array('container'=>'tenants', 'fname'=>'id', 'name'=>'tenant',     
            'fields'=>array('id', 'name', 'tenant_status', 'status', 'signup_date', 
                'trial_days', 'currency', 'monthly', 'yearly',
                'payment_type', 'payment_frequency', 'paid_until', 'last_payment_date', 
                'trial_start_date', 'trial_remaining'),
            'maps'=>array(
                'tenant_status'=>array('0'=>'Unknown', '1'=>'Active', '50'=>'Suspended', '60'=>'Deleted'),
                'status'=>array(''=>'None', '0'=>'Unknown', '1'=>'Update required', '2'=>'Trial', '10'=>'Active', '11'=>'Free Subscription', '50'=>'Suspended', '60'=>'Cancelled'),
                'payment_frequency'=>array('10'=>'monthly', '20'=>'yearly'),
                )),
        ));
    if( isset($rc['statuses']) ) {
        foreach($rc['statuses'] as $sid => $status) {
            $rc['statuses'][$sid]['status']['monthly'] = number_format($status['status']['monthly'], 2);
            $rc['statuses'][$sid]['status']['yearly'] = number_format($status['status']['yearly'], 2);
            foreach($status['status']['tenants'] as $bid => $tenant) {
                $rc['statuses'][$sid]['status']['tenants'][$bid]['tenant']['monthly'] = number_format($tenant['tenant']['monthly'], 2);
            }
        }
    }
    return $rc;
}
?>
