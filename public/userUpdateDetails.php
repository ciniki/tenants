<?php
//
// Description
// -----------
// This method will update the details for a user of a tenant. 
//
// Arguments
// ---------
// tnid:                 The ID of the tenant to get the details for.
// user_id:                     The ID of the user to set the details for.
// tenant.title:              (optional) The name to set for the user of the tenant.
// contact.phone.number:        (optional) The contact phone number for the user of the tenant.  
// contact.cell.number:         (optional) The cell number for the user of the tenant.
// contact.fax.number:          (optional) The fax number for the user of the tenant.
// contact.email.address:       (optional) The contact email address for the user of the tenant.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tenants_userUpdateDetails(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        'eid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'External ID'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.userUpdateDetails');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    // Required functions
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // Turn off autocommit
    //
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if eid needs updating
    //
    if( isset($args['eid']) ) {
        $strsql = "SELECT id "
            . "FROM ciniki_tenant_users "
            . "WHERE user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $existing_users = (isset($rc['rows'])?$rc['rows']:array());
        $strsql = "UPDATE ciniki_tenant_users SET "
            . "eid = '" . ciniki_core_dbQuote($ciniki, $args['eid']) . "' "
            . ", last_updated = UTC_TIMESTAMP() "
            . "WHERE user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
            return $rc;
        }
        foreach($existing_users as $user) {
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 
                'ciniki_tenant_history', $args['tnid'], 
                2, 'ciniki_tenant_users', $user['id'], 'eid', $args['eid']);
            $ciniki['syncqueue'][] = array('push'=>'ciniki.tenants.user', 
                'args'=>array('id'=>$user['id']));
        }
    }

    //
    // Get the existing details
    //
    $strsql = "SELECT id, detail_key, detail_value "
        . "FROM ciniki_tenant_user_details "
        . "WHERE ciniki_tenant_user_details.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_tenant_user_details.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.tenants', 'details', 'detail_key');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) ) {
        $existing_details = $rc['details'];
    } else {
        $existing_details = array();
    }

    //
    // Allowed tenant user detail keys 
    //
    $allowed_keys = array(
        'employee.title',
        'contact.phone.number',
        'contact.cell.number',
        'contact.fax.number',
        'contact.email.address',
        'employee-bio-image',
        'employee-bio-image-caption',
        'employee-bio-content',
        );
    foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
        if( in_array($arg_name, $allowed_keys) ) {
            //
            // Check if key already exists for user
            //
            if( !isset($existing_details[$arg_name]) ) {
                // Add new detail

                //
                // Get a new UUID
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
                $rc = ciniki_core_dbUUID($ciniki, 'ciniki.tenants');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $uuid = $rc['uuid'];

                $strsql = "INSERT INTO ciniki_tenant_user_details ("
                    . "uuid, tnid, user_id, "
                    . "detail_key, detail_value, date_added, last_updated) "
                    . "VALUES ("
                    . "'" . ciniki_core_dbQuote($ciniki, $uuid) . "'"
                    . ",'" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "'"
                    . ", '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'"
                    . ", '" . ciniki_core_dbQuote($ciniki, $arg_name) . "'"
                    . ", '" . ciniki_core_dbQuote($ciniki, $arg_value) . "'"
                    . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                    . "";
                $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                    return $rc;
                }
                $detail_id = $rc['insert_id'];
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 
                    'ciniki_tenant_history', $args['tnid'], 
                    1, 'ciniki_tenant_user_details', $detail_id, 'uuid', $uuid);
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 
                    'ciniki_tenant_history', $args['tnid'], 
                    1, 'ciniki_tenant_user_details', $detail_id, 'user_id', $args['user_id']);
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 
                    'ciniki_tenant_history', $args['tnid'], 
                    1, 'ciniki_tenant_user_details', $detail_id, 'detail_key', $arg_name);
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 
                    'ciniki_tenant_history', $args['tnid'], 
                    1, 'ciniki_tenant_user_details', $detail_id, 'detail_value', $arg_value);
                $ciniki['syncqueue'][] = array('push'=>'ciniki.tenants.user_detail', 
                    'args'=>array('id'=>$detail_id));
            } elseif( $existing_details[$arg_name] != $arg_value ) {
                // Update existing detail
                $detail_id = $existing_details[$arg_name]['id'];
                $strsql = "UPDATE ciniki_tenant_user_details SET "
                    . "detail_value = '" . ciniki_core_dbQuote($ciniki, $arg_value) . "' "
                    . ", last_updated = UTC_TIMESTAMP() "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $detail_id) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                    return $rc;
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 
                    'ciniki_tenant_history', $args['tnid'], 
                    2, 'ciniki_tenant_user_details', $detail_id, 'detail_value', $arg_value);
                $ciniki['syncqueue'][] = array('push'=>'ciniki.tenants.user_detail', 
                    'args'=>array('id'=>$detail_id));
            }
            
//          $strsql = "INSERT INTO ciniki_tenant_user_details (tnid, user_id, detail_key, detail_value, date_added, last_updated) "
//              . "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "'"
//              . ", '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'"
//              . ", '" . ciniki_core_dbQuote($ciniki, $arg_name) . "'"
//              . ", '" . ciniki_core_dbQuote($ciniki, $arg_value) . "'"
//              . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
//              . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $arg_value) . "' "
//              . ", last_updated = UTC_TIMESTAMP() "
//              . "";
        }
    }

    //
    // Check for web options
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    $rc = ciniki_tenants_checkModuleAccess($ciniki, $args['tnid'], 'ciniki', 'web');
    $fields = array(
        'page-contact-user-display-flags-',
        'page-contact-user-sort-order-',
        );
    if( $rc['stat'] == 'ok' ) {
        foreach($fields as $field_prefix) {
            $field = $field_prefix . $args['user_id'];
            if( isset($ciniki['request']['args'][$field]) && $ciniki['request']['args'][$field] != '') {
                // Update the web module
                $strsql = "INSERT INTO ciniki_web_settings (tnid, detail_key, detail_value, date_added, last_updated) "
                    . "VALUES ('" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['tnid']) . "'"
                    . ", '" . ciniki_core_dbQuote($ciniki, $field) . "' "
                    . ", '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "'"
                    . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                    . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "' "
                    . ", last_updated = UTC_TIMESTAMP() "
                    . "";
                $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.web');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.web');
                    return $rc;
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.web', 'ciniki_web_history', $args['tnid'], 
                    2, 'ciniki_web_settings', $field, 'detail_value', $ciniki['request']['args'][$field]);
                //
                // Update the page-contact-user-display field
                // - this function will update last_change for web module.
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'updateUserDisplay');
                $rc = ciniki_web_updateUserDisplay($ciniki, $args['tnid']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                    return $rc;
                }
            }
        }
    }

    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_updated for the tenant user
    //
    $strsql = "UPDATE ciniki_tenant_users SET last_updated = UTC_TIMESTAMP() "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of tenants this user is part of, and replicate that user for that tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'tenants');

    return array('stat'=>'ok');
}
?>
