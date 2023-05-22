<?php
//
// Description
// -----------
// This script will create the script to export and import all the tables for a tenant with
// only their rows in the export.
//

//
// Initialize Ciniki by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/dbHashQuery.php');
require_once($ciniki_root . '/ciniki-mods/core/private/dbQuote.php');
require_once($ciniki_root . '/ciniki-mods/core/private/objectUpdate.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    header("Status: 500 Processing Error", true, 500);
    exit;
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];
$ciniki['session']['user']['id'] = -3;  // Setup to Ciniki Robot

if( !isset($argv[3]) || $argv[3] == '' ) {
    print "Usage: setuptestsql.php <tnid> <fromdb> <todb>\n";
    exit;
}
$tnid = $argv[1];
$fromdb = $argv[2];
$todb = $argv[3];

//
// Output the start of script
//
print "#/bin/sh\n\n"
    . "# Create command: php ./setuptestsql.php $tnid '$fromdb' '$todb'\n\n"
    . "# Load Core\n"
    . "mysqldump $fromdb ciniki_core_api_keys | mysql $todb\n"
    . "mysqldump $fromdb --where='id=$tnid' ciniki_tenants | mysql $todb\n"
    . "mysqldump $fromdb --where='id=$tnid' ciniki_tenant_details | mysql $todb\n"
    . "mysqldump $fromdb --where='id=$tnid' ciniki_tenant_modules | mysql $todb\n"
    . "mysqldump $fromdb --where='id=$tnid' ciniki_tenant_users | mysql $todb\n"
    . "\n";

//
// Get this list of active modules for a tenant
// 
$strsql = "SELECT package, module "
    . "FROM ciniki_tenant_modules AS modules "
    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
    . "AND status > 0 "
    . "AND status < 90 "
    . "ORDER BY package, module "
    . "";
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'item');
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.119', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
}
foreach($rc['rows'] as $row) {
    $pkg = $row['package'];
    $mod = $row['module'];

    //
    // Get the list of tables
    //
    $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'private', 'objects');
    if( $rc['stat'] == 'ok' && isset($rc['function_call']) ) {
        print "# $pkg.$mod\n";
        $fn = $rc['function_call'];
        $rc = $fn($ciniki);
        if( isset($rc['objects']) ) {
            foreach($rc['objects'] as $obj) {
                if( isset($obj['table']) ) {
                    print "mysqldump $fromdb --where='id=$tnid' {$obj['table']} | mysql $todb\n";
                }
            }
        }
        print "\n";
    }
}

exit;
?>
