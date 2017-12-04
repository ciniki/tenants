<?php
//
// Description
// -----------
// This function will return the list of modules available in the system,
// and which modules the requested tenant has access to.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the module list for.
// MODULE_NAME:         The name of the module, and the value if it's On or Off.
//
// Returns
// -------
// <modules>
//      <module name='Products' active='On|Off' />
// </modules>
//
function ciniki_tenants_updateModules($ciniki) {
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
    // Check access to tnid as owner, or sys admin. 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.updateModules');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, module, status "
        . "FROM ciniki_tenant_modules WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "'";  
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.tenants', 'modules', 'name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_modules = $rc['modules'];

    //  
    // Get the list of available modules
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'getModuleList');
    $rc = ciniki_core_getModuleList($ciniki);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $mod_list = $rc['modules'];

    //  
    // Start transaction
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Find all the modules which are to change status
    //
    foreach($mod_list as $module) {
        $name = $module['package'] . '.' . $module['name'];
        if( isset($ciniki['request']['args'][$name]) ) {
            $strsql = "INSERT INTO ciniki_tenant_modules "
                . "(tnid, package, module, flags, status, ruleset, date_added, last_updated) "
                . "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $module['package']) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $module['name']) . "', "
                . "0, " 
                . "'" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$name]) . "', "
                . "'', UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                . "ON DUPLICATE KEY UPDATE "
                    . "status = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$name]) . "' "
                    . "";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                return $rc;
            } 
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
                2, 'ciniki_tenant_modules', $name, 'status', $ciniki['request']['args'][$name]);
        }
    }

    //
    // Update the last_updated date so changes will be sync'd
    //
    $strsql = "UPDATE ciniki_tenants SET last_updated = UTC_TIMESTAMP() "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    } 

    return array('stat'=>'ok');
}
?>
