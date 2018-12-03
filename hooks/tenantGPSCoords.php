<?php
//
// Description
// -----------
// This function will either poll the GPS device attached to
// the computer, or use the GPS coordinates in the config file
//
// Arguments
// ---------
// ciniki:
// tnid:
// args:
//
// Returns
// -------
//
function ciniki_tenants_hooks_tenantGPSCoords(&$ciniki, $tnid, $args) {

    $latitude = 0;
    $longitude = 0;
    $altitude = 0;

    //
    // Check for config file setting for lat/long
    //
    if( isset($ciniki['config']['ciniki.core']['latitude']) ) {
        $latitude = $ciniki['config']['ciniki.core']['latitude'];
    }
    if( isset($ciniki['config']['ciniki.core']['longitude']) ) {
        $longitude = $ciniki['config']['ciniki.core']['longitude'];
    }
    if( isset($ciniki['config']['ciniki.core']['altitude']) ) {
        $altitude = $ciniki['config']['ciniki.core']['altitude'];
    }

    //
    // FIXME: Check for GPS device and poll
    //

    return array('stat'=>'ok', 'latitude'=>$latitude, 'longitude'=>$longitude, 'altitude'=>$altitude);
}
?>
