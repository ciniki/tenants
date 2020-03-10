<?php
//
// Description
// -----------
// This method will search a field for the search string provided.
//
// Arguments
// ---------
// api_key:
// auth_token:
// start_needle:    The search string to search the field for.
//
// limit:           (optional) Limit the number of results to be returned. 
//                  If the limit is not specified, the default is 25.
// 
// Returns
// -------
//
function ciniki_tenants_searchTenants($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_tenants_checkAccess($ciniki, 0, 'ciniki.tenants.searchTenants'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Search for tenants
    //
    $strsql = "SELECT ciniki_tenants.id, ciniki_tenants.name "
        . "FROM ciniki_tenants "
        . "LEFT JOIN ciniki_tenant_users ON ("
            . "ciniki_tenants.id = ciniki_tenant_users.tnid "
            . ") "
        . "LEFT JOIN ciniki_users ON ("
            . "ciniki_tenant_users.user_id = ciniki_users.id "
            . ") "
        . "WHERE (ciniki_tenants.name like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_tenants.name like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_users.username like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_users.display_name like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_users.display_name like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "ORDER BY name COLLATE latin1_general_cs "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'tenants', 'fname'=>'id', 'name'=>'tenant', 
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenants']) || !is_array($rc['tenants']) ) {
        return array('stat'=>'ok', 'tenants'=>array());
    }

    return array('stat'=>'ok', 'tenants'=>$rc['tenants']);
}
?>
