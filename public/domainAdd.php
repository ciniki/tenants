<?php
//
// Description
// -----------
// This method will add a new domain to a tenant.  
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_tenants_domainAdd($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'domain'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Domain'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Flags'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Status'),
        'expiry_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'date', 'name'=>'Expiry Date'),
        'managed_by'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Managed'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.domainAdd');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Start transaction
    //
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // FIXME: Add ability to set modules when site is added, right now default to most apps on
    //
    $strsql = "INSERT INTO ciniki_tenant_domains (tnid, "
        . "domain, flags, status, expiry_date, managed_by, "
        . "date_added, last_updated ) VALUES ( "
        . "'" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['domain']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['expiry_date']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['managed_by']) . "', "
        . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return $rc;
    }
    if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.40', 'msg'=>'Unable to add domain'));
    }
    $domain_id = $rc['insert_id'];

    //
    // Add all the fields to the change log
    //
    $changelog_fields = array(
        'domain',
        'flags',
        'status',
        'expiry_date',
        'managed_by',
        );
    foreach($changelog_fields as $field) {
        if( isset($args[$field]) && $args[$field] != '' ) {
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
                1, 'ciniki_tenant_domains', $domain_id, $field, $args[$field]);
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok', 'id'=>$domain_id);
}
?>
