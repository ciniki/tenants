<?php
//
// Description
// -----------
// This function will return the utc offset for the tenant timezone.
// Currently this is fixed to America/Toronto, but will be updated in the future.
//
// Info
// ----
// Status:          beta
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_tenants_timezoneOffset($ciniki, $format='mysql') {

    //
    // Check if the user is logged in, otherwise return 
    //
    $tz = timezone_open('America/Toronto');

    if( $format == 'seconds' ) {
        $utc_offset = timezone_offset_get($tz, date_create());
    } else {
        $utc_offset = sprintf("%+03d:00", (timezone_offset_get($tz, date_create()))/3600);
    }

    return $utc_offset;
}
?>
