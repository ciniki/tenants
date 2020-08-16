<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_tenants_objects($ciniki) {
    
    $objects = array();

    $objects['domain'] = array(
        'name' => 'Domain Name',
        'sync' => 'yes',
        'o_name' => 'domain',
        'o_container' => 'domains',
        'table' => 'ciniki_tenant_domains',
        'fields' => array(
            'domain' => array('name'=>'Name'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'status' => array('name'=>'Status', 'default'=>'1'),
            'root_id,' => array('name'=>'', 'default'=>''),
            'expiry_date' => array('name'=>'Expiration Date', 'default'=>''),
            'managed_by' => array('name'=>'Managed By', 'default'=>''),
            ),
        'history_table' => 'ciniki_tenant_history',
        );
    $objects['uihelp'] = array(
        'name' => 'UI Help',
        'sync' => 'yes',
        'o_name' => 'content',
        'o_container' => 'content',
        'table' => 'ciniki_tenant_uihelp',
        'fields' => array(
            'helpUID' => array('name'=>'UI Panel'),
            'content' => array('name'=>'Content'),
            ),
        'history_table' => 'ciniki_tenant_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
