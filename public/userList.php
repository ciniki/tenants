<?php
//
// Description
// -----------
// This method will return a list of the users who have permissions within a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:             The ID of the tenant to lock.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_tenants_userList($ciniki) {
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
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.userList');
    // Ignore error that module isn't enabled, tenants is on by default.
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Get the flags for this module and send back the permission groups available
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'flags');
    $rc = ciniki_tenants_flags($ciniki, $modules);
    $flags = $rc['flags'];

    //
    // Check if running in ham mode
    //
    $strsql = "SELECT id, name, flags "
        . "FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.105', 'msg'=>'Unable to load details', 'err'=>$rc['err']));
    }
    $tenant = $rc['tenant'];

    if( ($tenant['flags']&0x02) == 0x02 ) {
        $rsp = array('stat'=>'ok', 'permission_groups'=>array(
            'ciniki.owners'=>array('name'=>'Operators'),
            ));
   
    } else {
        $rsp = array('stat'=>'ok', 'permission_groups'=>array(
            'ciniki.owners'=>array('name'=>'Owners'),
            ));
        if( isset($modules['ciniki.tenants']) && ($modules['ciniki.tenants']['flags']&0x01) == 1 ) {
            $rsp['permission_groups']['ciniki.employees'] = array('name'=>'Employees');
        }
        if( isset($modules['ciniki.tenants']) ) {
            foreach($flags as $flag) {
                $flag = $flag['flag'];
                if( isset($flag['group']) 
                    && ($modules['ciniki.tenants']['flags']&pow(2, $flag['bit']-1)) > 0 
                    ) {
                    $rsp['permission_groups'][$flag['group']] = array('name'=>$flag['name']);
                }
            }
        }
    }

    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01
        && (!isset($ciniki['config']['ciniki.core']['single_tenant_mode']) 
            || $ciniki['config']['ciniki.core']['single_tenant_mode'] != 'yes'
            ) 
        ) {
        $rsp['permission_groups']['ciniki.resellers'] = array('name'=>'Resellers');
    }

    //
    // Get the additional module permission groups
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.tenants', 0x040000) ) {
        foreach($ciniki['tenant']['modules'] as $module) {
            $rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'hooks', 'permissionGroups');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $args['tnid'], array());
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['permission_groups']) ) {
                    foreach($rc['permission_groups'] as $k => $g) {
                        $rsp['permission_groups'][$k] = $g;
                    }
                }
            }
        }
    }


    //
    // Get the list of users who have access to this tenant
    //
    $strsql = "SELECT ciniki_tenant_users.user_id, "
        . "ciniki_users.username, "
        . "ciniki_users.firstname, "
        . "ciniki_users.lastname, "
        . "ciniki_users.display_name, "
        . "ciniki_users.email, "
        . "ciniki_tenant_users.eid, "
        . "CONCAT_WS('.', ciniki_tenant_users.package, ciniki_tenant_users.permission_group) AS permission_group, "
        . "IFNULL(titles.detail_value, '') AS title "
        . "FROM ciniki_tenant_users "
        . "INNER JOIN ciniki_users ON ("
            . "ciniki_tenant_users.user_id = ciniki_users.id "
            . ") "
        . "LEFT JOIN ciniki_tenant_user_details AS titles ON ("
            . "ciniki_tenant_users.user_id = titles.user_id "
            . "AND titles.detail_key = 'employee.title' "
            . "AND titles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' " 
            . ") "
        . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' " 
        . "AND ciniki_tenant_users.status = 10 "
        . "ORDER BY permission_group "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'groups', 'fname'=>'permission_group', 'name'=>'group', 
            'fields'=>array('permission_group')),
        array('container'=>'users', 'fname'=>'user_id', 'name'=>'user', 
            'fields'=>array('user_id', 'eid', 'username', 'firstname', 'lastname', 'display_name', 'email', 'title')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['groups']) ) {
        $rsp['groups'] = $rc['groups'];
    } else {
        $rsp['groups'] = array();
    }

    return $rsp;
}
?>
