<?php
//
// Description
// -----------
// This function will load the tenant details required by the ciniki.wng module.
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tenants_hooks_wngTenantLoad($ciniki, $tnid, $args) {

    //
    // Get the tenant name and tagline
    //
    $strsql = "SELECT uuid, name "
        . "FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.110', 'msg'=>'Unable to get tenant details'));
    }
    $tenant = array(
        'uuid' => $rc['tenant']['uuid'],
        'name' => $rc['tenant']['name'],
        'modules' => array(),
        );

    //
    // Check if the module is enabled for this tenant, don't really care about the ruleset
    //
    $strsql = "SELECT modules.status AS module_status, "
        . "CONCAT_WS('.', modules.package, modules.module) AS module_id, "
        . "modules.flags "
        . "FROM ciniki_tenants AS tenants, ciniki_tenant_modules AS modules "
        . "WHERE tenants.id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND tenants.id = modules.tnid "
        . "AND (modules.status = 1 OR modules.status = 2) "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.tenants', 'modules', 'module_id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant['modules'] = isset($rc['modules']) ? $rc['modules'] : array();

    return array('stat'=>'ok', 'tenant'=>$tenant);
}
?>
