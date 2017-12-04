<?php
//
// Description
// ===========
// This method will return all the information about an reports.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the reports is attached to.
// report_id:          The ID of the reports to get the details for.
//
// Returns
// -------
//
function ciniki_tenants_reportGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'report_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reports'),
        'addblock'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Add Block'),
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
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.reportGet');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

    $rsp = array('stat'=>'ok', 'report'=>array(), 'users'=>array(), 'reports'=>array());

    //
    // Get the list of available blocks
    //
    $rsp['blocks'] = array();
    foreach($ciniki['tenant']['modules'] as $module) {
        //
        // Check if the module has a hook for tenantReportBlocks
        //
        $rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'hooks', 'tenantReportBlocks');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], array());
            if( $rc['stat'] == 'ok' ) {
                $rsp['blocks'] = array_merge($rsp['blocks'], $rc['blocks']);
            }
        }
    }

    //
    // Check if a block is to be added
    //
    if( isset($args['addblock']) && isset($rsp['blocks'][$args['addblock']]) ) {
        $block = $rsp['blocks'][$args['addblock']];
        $strsql = "SELECT MAX(sequence) AS seq "
            . "FROM ciniki_tenant_report_blocks "
            . "WHERE report_id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'max');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['max']['seq']) && $rc['max']['seq'] > 0 ) {
            $seq = $rc['max']['seq'] + 1;
        } else {
            $seq = 1;
        }
        $options = array();
        foreach($block['options'] as $oid => $option) {
            $options[$oid] = (isset($option['default']) ? $option['default'] : '');
        }
        //
        // Add the block
        //
        $add_args = array(
            'report_id'=>$args['report_id'],
            'btype'=>10,
            'title'=>$rsp['blocks'][$args['addblock']]['name'],
            'sequence'=>$seq,
            'block_ref'=>$args['addblock'],
            'options'=>serialize($options),
            );
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd'); 
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.tenants.reportblock', $add_args, 0x07);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Return default for new Reports
    //
    if( $args['report_id'] == 0 ) {
        $rsp['report'] = array('id'=>0,
            'user_ids'=>array(),
            'title'=>'',
            'frequency'=>'',
            'flags'=>0x03,
            'next_date'=>'',
            'next_time'=>'',
            'blocks'=>array(),
        );
    }

    //
    // Get the details for an existing Reports
    //
    else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'reportLoad'); 
        $rc = ciniki_tenants_reportLoad($ciniki, $args['tnid'], $args['report_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['report'] = $rc['report'];
        if( isset($rsp['report']['blocks']) ) {
            //
            // Add the name of each block
            //
            foreach($rsp['report']['blocks'] as $bid => $block) {
                if( !isset($rsp['blocks'][$block['block_ref']]) ) {
                    unset($rsp['report']['blocks'][$bid]);
                    continue;
                }
//                $rsp['report']['blocks'][$bid]['name'] = $rsp['blocks'][$block['block_ref']]['name'];
            }
        }
    }

    //
    // Get the list of available users
    //
    $strsql = "SELECT u.id, u.display_name "
        . "FROM ciniki_tenant_users AS b "
        . "INNER JOIN ciniki_users AS u ON ("
            . "b.user_id = u.id "
            . ")" 
        . "WHERE b.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND b.status = 10 "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'users', 'fname'=>'id', 'fields'=>array('id', 'name'=>'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['users']) ) {
        $rsp['users'] = $rc['users'];
    }

    return $rsp;
}
?>
