<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_tenants_uihelpUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'helpUID'=>array('required'=>'no', 'blank'=>'no', 'name'=>'UI Panel'),
        'content'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Content'),
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
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.uihelpUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the existing content
    //
    $strsql = "SELECT ciniki_tenant_uihelp.id, "
        . "ciniki_tenant_uihelp.helpUID, "
        . "ciniki_tenant_uihelp.content "
        . "FROM ciniki_tenant_uihelp "
        . "WHERE ciniki_tenant_uihelp.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_tenant_uihelp.helpUID = '" . ciniki_core_dbQuote($ciniki, $args['helpUID']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'content');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.107', 'msg'=>'Unable to load content', 'err'=>$rc['err']));
    }
    if( isset($rc['content']) ) {
        $existing_uihelp = $rc['content'];
    }

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
    // Add the help content, if none exists
    //
    if( !isset($existing_uihelp) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.tenants.uihelp', $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.109', 'msg'=>'Unable to add the content', 'err'=>$rc['err']));
        }
    } 
    //
    // Update existing content
    //
    elseif( $args['content'] != $existing_uihelp['content'] ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.tenants.uihelp', $existing_uihelp['id'], array(
            'content' => $args['content'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'tenants');

    return array('stat'=>'ok');
}
?>
