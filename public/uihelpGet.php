<?php
//
// Description
// ===========
// This method will return all the information about an ui help.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the ui help is attached to.
// helpuid:          The ID of the ui help to get the details for.
//
// Returns
// -------
//
function ciniki_tenants_uihelpGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'help_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'UI Help'),
        'helpUID'=>array('required'=>'no', 'blank'=>'no', 'name'=>'UI Help Panel'),
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
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.uihelpGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return the help 
    //
    if( isset($args['helpUID']) ) {
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
        $content = isset($rc['content']) ? $rc['content'] : array('content'=>'No help added');
        $content['content_display'] = nl2br($content['content']);
    }

    //
    // Return default for new UI Help
    //
    elseif( isset($args['help_id']) && $args['help_id'] == 0 ) {
        $content = array('id'=>0,
            'helpUID'=>'',
            'content'=>'',
        );
    }

    //
    // Get the details for an existing UI Help
    //
    elseif( isset($args['help_id']) ) {
        $strsql = "SELECT ciniki_tenant_uihelp.id, "
            . "ciniki_tenant_uihelp.helpUID, "
            . "ciniki_tenant_uihelp.content "
            . "FROM ciniki_tenant_uihelp "
            . "WHERE ciniki_tenant_uihelp.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_tenant_uihelp.id = '" . ciniki_core_dbQuote($ciniki, $args['help_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'content');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.107', 'msg'=>'Unable to load content', 'err'=>$rc['err']));
        }
        $content = isset($rc['content']) ? $rc['content'] : array('content'=>'Nothing added');
    }
    //
    // Nothing specified
    //
    else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.108', 'msg'=>'No panel specified'));
    }

    return array('stat'=>'ok', 'content'=>$content);
}
?>
