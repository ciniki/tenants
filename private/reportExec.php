<?php
//
// Description
// ===========
// This function runs the report and builds the text and pdf versions.
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
function ciniki_tenants_reportExec($ciniki, $tnid, $report_id) {

    //
    // Load the report
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'reportLoad');
    $rc = ciniki_tenants_reportLoad($ciniki, $tnid, $report_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['report']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.113', 'msg'=>'Unable to find report.'));
    }
    $report = $rc['report'];

    //
    // Return if there are no blocks
    //
    if( !isset($report['blocks']) ) {
        $report['text'] = "The report is empty\n\n";
        $report['html'] = "The report is empty\n\n";
        return array('stat'=>'ok', 'report'=>$report);
    }

    //
    // Add the block data (chunks)
    //
    foreach($report['blocks'] as $bid => $block) {
        list($pkg, $mod, $blockname) = explode('.', $block['block_ref']);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'tenantReportBlock');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tnid, $block);
            if( $rc['stat'] != 'ok' ) {
                error_log('RPTERR[01]: ' . print_r($rc, true));
            } elseif( isset($rc['chunks']) ) {
                $report['blocks'][$bid]['chunks'] = $rc['chunks'];
            }
        }
    }

    //
    // Load functions required to assemble report
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'reportStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'reportBlock');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'reportChunkMessage');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'reportChunkText');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'reportChunkTable');

    //
    // Start the report
    //
    $rc = ciniki_tenants_reportStart($ciniki, $tnid, $report);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'report'=>$report, 'err'=>array('code'=>'ciniki.tenants.114', 'msg'=>'Unable to start report', 'err'=>$rc['err']));
    }

    //
    // Go through all the blocks/chunks
    //
    foreach($report['blocks'] as $bid => $block) {
        if( isset($block['chunks']) ) { 
            $rc = ciniki_tenants_reportBlock($ciniki, $tnid, $report, $block);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'report'=>$report, 'err'=>array('code'=>'ciniki.tenants.114', 'msg'=>'Unable to add block', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok', 'report'=>$report);
}
?>
