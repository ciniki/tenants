<?php
//
// Description
// ===========
// This function will load all the data for a report.
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant the reports is attached to.
// report_id:           The ID of the reports to get the details for.
//
// Returns
// -------
//
function ciniki_tenants_reportLoad($ciniki, $tnid, $report_id) {
    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

    //
    // Load the report
    //
    $strsql = "SELECT ciniki_tenant_reports.id, "
        . "ciniki_tenant_reports.title, "
        . "ciniki_tenant_reports.frequency, "
        . "ciniki_tenant_reports.flags, "
        . "ciniki_tenant_reports.next_date, "
        . "ciniki_tenant_reports.next_date AS next_time "
        . "FROM ciniki_tenant_reports "
        . "WHERE ciniki_tenant_reports.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_tenant_reports.id = '" . ciniki_core_dbQuote($ciniki, $report_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'reports', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'frequency', 'flags', 'next_date', 'next_time'),
            'utctotz'=>array(   
                'next_date'=>array('format'=>$date_format, 'timezone'=>$intl_timezone),
                'next_time'=>array('format'=>$time_format, 'timezone'=>$intl_timezone),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.107', 'msg'=>'Reports not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['reports'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.108', 'msg'=>'Unable to find Reports'));
    }
    $report = $rc['reports'][0];

    //
    // Get the users for the report
    //
    $strsql = "SELECT id, uuid, user_id "
        . "FROM ciniki_tenant_report_users "
        . "WHERE report_id = '" . ciniki_core_dbQuote($ciniki, $report_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $users = $rc['rows'];
        foreach($users as $user) {
            $report['user_ids'][] = $user['user_id'];
        }
    }

    //
    // Get the blocks for the reports
    //
    $strsql = "SELECT id, btype, title, sequence, block_ref, options "
        . "FROM ciniki_tenant_report_blocks "
        . "WHERE report_id = '" . ciniki_core_dbQuote($ciniki, $report_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY sequence "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'blocks', 'fname'=>'id', 'fields'=>array('id', 'btype', 'title', 'sequence', 'block_ref', 'options')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['blocks']) ) {
        $blocks = $rc['blocks'];
        foreach($blocks as $block) {
            $block['options'] = unserialize($block['options']);
            $report['blocks'][] = $block;
        }
    }

    return array('stat'=>'ok', 'report'=>$report);
}
?>
