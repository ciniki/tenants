<?php
//
// Description
// ===========
// This method will return the domain information for a tenant domain.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_tenants_domainGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'domain_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Domain'), 
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
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.domainGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    $dt = new DateTime('now');
    if( $args['domain_id'] == 0 ) {
        $domain = array(
            'id' => 0,
            'domain' => '',
            'flags' => 0,
            'status' => 1,
            'expiry_date' => $dt->format('b j, Y'),
            'managed_by' => 'EasyDNS',
            );

    } else {
        $strsql = "SELECT ciniki_tenant_domains.id, "
            . "domain, "
            . "parent_id, "
            . "flags, "
            . "status, "
            . "DATE_FORMAT(expiry_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS expiry_date, "
            . "managed_by, "
            . "date_added, "
            . "last_updated "
            . "FROM ciniki_tenant_domains "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_tenant_domains.id = '" . ciniki_core_dbQuote($ciniki, $args['domain_id']) . "' "
            . "";
        
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'domain');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['domain']) ) {
            return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.tenants.42', 'msg'=>'Unable to find domain'));
        }
        $domain = $rc['domain'];
    }

    //
    // Get the list of domains for this tenant
    //
    $strsql = "SELECT id, domain "
        . "FROM ciniki_tenant_domains "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'domains', 'fname'=>'id', 
            'fields'=>array('id', 'domain'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.111', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $domains = isset($rc['domains']) ? $rc['domains'] : array();
    array_unshift($domains, array('id'=>0, 'domain'=>'No Alias'));


    return array('stat'=>'ok', 'domain'=>$domain, 'domains'=>$domains);
}
?>
