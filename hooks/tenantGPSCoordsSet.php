<?php
//
// Description
// -----------
// This function will either poll the GPS device attached to
// the computer, or use the GPS coordinates in the config file
//
// Arguments
// ---------
// ciniki:
// tnid:
// args:
//
// Returns
// -------
//
function ciniki_tenants_hooks_tenantGPSCoordsSet(&$ciniki, $tnid, $args) {


    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // check of proper args passed
    //
    if( isset($args['latitude']) && isset($args['longitude']) && isset($args['altitude']) ) {
        //
        // Get the current GPS coords
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_tenant_details', 'tnid', $tnid, 'ciniki.tenants', 'coords', 'gps');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $current_coords = isset($rc['coords']) ? $rc['coords'] : array();

        //
        // Check the fields
        //
        foreach(['latitude', 'longitude', 'altitude'] as $coord) {
            if( !isset($current_coords['gps-current-' . $coord]) && $args[$coord] != $current_coords['gps-current-' . $coord] ) {
                $strsql = "INSERT INTO ciniki_tenant_details (tnid, detail_key, detail_value, date_added, last_updated) "
                    . "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "'"
                    . ", '" . ciniki_core_dbQuote($ciniki, 'gps-current-' . $coord) . "'"
                    . ", '" . ciniki_core_dbQuote($ciniki, $args[$coord]) . "'"
                    . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
                $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                    return $rc;
                }
            } else {
                $strsql = "UPDATE ciniki_tenant_details SET detail_value = '" . ciniki_core_dbQuote($ciniki, $args[$coord]) . "' "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND detail_key = 'gps-current-$coord' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                    return $rc;
                }
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
                2, 'ciniki_tenant_details', 'gps-current-' . $coord, 'detail_value', $args[$coord]);
            $ciniki['syncqueue'][] = array('push'=>'ciniki.tenants.details', 
                'args'=>array('id'=>'gps-current-' . $coord));
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    return array('stat'=>'ok');
}
?>
