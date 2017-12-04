<?php
//
// Description
// -----------
// This function will return the list of modules available in the system,
// and which modules the requested tenant has access to.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the module list for.
//
// Returns
// -------
// <modules>
//      <module label='Products' name='products' flags='On|Off' />
// </modules>
//
function ciniki_tenants_getModuleFlags($ciniki) {
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
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.getModuleFlags');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, flags "
        . "FROM ciniki_tenant_modules "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND status > 0 "
        . "ORDER BY name "
        . "";   
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.tenants', 'modules', 'name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['modules']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.47', 'msg'=>'No tenant found'));
    }
    $tenant_modules = $rc['modules'];

    //
    // Check for ciniki.tenants
    //
    if( !isset($tenant_modules['ciniki.tenants']) ) {
        $tenant_modules['ciniki.tenants'] = array('name'=>'Tenants', 
            'package'=>'ciniki', 'module'=>'tenants', 'flags'=>'0');
    }

    //
    // Check for the name and flags available for each module
    //
    foreach($tenant_modules as $mid => $module) {
        //
        // Check for info file
        //
        $tenant_modules[$mid]['proper_name'] = $module['name'];
        $info_filename = $ciniki['config']['ciniki.core']['root_dir'] . '/' . $module['package'] . '-mods/' . $module['module'] . '/_info.ini';
        if( file_exists($info_filename) ) {
            $info = parse_ini_file($info_filename);
            if( isset($info['name']) && $info['name'] != '' ) {
                $tenant_modules[$mid]['proper_name'] = $info['name'];
            } 
        }
        
        //
        // Check if flags file exists
        //
        $rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'private', 'flags');
        if( $rc['stat'] == 'ok' ) {
            $fn = $module['package'] . '_' . $module['module'] . '_flags';
            $rc = $fn($ciniki, $tenant_modules);
            $tenant_modules[$mid]['available_flags'] = $rc['flags'];
        }
    }

    return array('stat'=>'ok', 'modules'=>$tenant_modules);
}
?>
