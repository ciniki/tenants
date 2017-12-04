<?php
//
// Description
// -----------
// This method will check the system table versions and the enabled modules
// on the remote and local servers to determine if the systems are compatible for 
// sync.  Each system must be running the same version of the code, and have
// the same versions of the database tables.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_tenants_syncCheck($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'sync_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sync'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.syncCheck');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLoad');
    $rc = ciniki_core_syncLoad($ciniki, $args['tnid'], $args['sync_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sync = $rc['sync'];

    //
    // Check the versions and return
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncCheckVersions');
    $rc = ciniki_core_syncCheckVersions($ciniki, $sync, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.81', 'msg'=>'Incompatible versions', 'err'=>$rc['err']));
    }

    // hard coded return value, so the sync information does not also get passed back.
    return array('stat'=>'ok');
}
?>
