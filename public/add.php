<?php
//
// Description
// -----------
// This function will add a new tenant.  You must be a sys admin to 
// be authorized to add a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
//
// Returns
// -------
// <rsp stat='ok' id='new tenant id' />
//
function ciniki_tenants_add(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'plan_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Plan'), 
        'payment_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Payment'), 
        'tenant.name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant Name'), 
        'tenant.sitename'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Sitename'), 
        'tenant.tagline'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Tagline'), 
        'tenant.category'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Category'), 
        'owner.name.first'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner First Name'), 
        'owner.name.last'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Last Name'), 
        'owner.name.display'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Display Name'), 
        'owner.email.address'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Email'), 
        'owner.username'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Username'), 
        'owner.password'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Password'), 
        'contact.person.name'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Contact'), 
        'contact.email.address'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Email'), 
        'contact.phone.number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Phone'), 
        'contact.cell.number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Cell'), 
        'contact.tollfree.number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Tollfree'), 
        'contact.fax.number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Fax'), 
        'contact.address.street1'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address Line 1'), 
        'contact.address.street2'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address Line 2'), 
        'contact.address.city'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'City'), 
        'contact.address.province'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Province'), 
        'contact.address.postal'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Postal'), 
        'contact.address.country'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Country'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, 0, 'ciniki.tenants.add');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Load timezone settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $ciniki['config']['ciniki.core']['master_tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // If the sitename is not specified, then create
    //
    if( $args['tenant.sitename'] == '' ) {
        $args['tenant.sitename'] = preg_replace('/[^a-z0-9\-_]/', '', strtolower($args['tenant.name']));
    }

    //
    // Check the sitename is proper format
    //
    if( preg_match('/[^a-z0-9\-_]/', $args['tenant.sitename']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.23', 'msg'=>'Illegal characters in sitename.  It can only contain lowercase letters, numbers, underscores (_) or dash (-)'));
    }
    
    //
    // Load required functions
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // Turn off autocommit
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the tenant to the database
    //
    $strsql = "INSERT INTO ciniki_tenants (uuid, name, category, sitename, tagline, status, reseller_id, date_added, last_updated) VALUES ("
        . "UUID(), "
        . "'" . ciniki_core_dbQuote($ciniki, $args['tenant.name']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $args['tenant.category']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $args['tenant.sitename']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $args['tenant.tagline']) . "' "
        . ", 1 "
        . ", '" . ciniki_core_dbQuote($ciniki, $ciniki['config']['ciniki.core']['master_tnid']) . "' "
        . ", UTC_TIMESTAMP(), UTC_TIMESTAMP())";
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return $rc;
    }
    if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.24', 'msg'=>'Unable to add tenant'));
    }
    $tnid = $rc['insert_id'];
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
        1, 'ciniki_tenants', $tnid, 'name', $args['tenant.name']);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
        1, 'ciniki_tenants', $tnid, 'tagline', $args['tenant.tagline']);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
        1, 'ciniki_tenants', $tnid, 'sitename', $args['tenant.sitename']);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
        1, 'ciniki_tenants', $tnid, 'status', '1');

    if( $tnid < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.25', 'msg'=>'Unable to add tenant'));
    }

    //
    // Allowed tenant detail keys 
    //
    $allowed_keys = array(
        'contact.address.street1',
        'contact.address.street2',
        'contact.address.city',
        'contact.address.province',
        'contact.address.postal',
        'contact.address.country',
        'contact.person.name',
        'contact.phone.number',
        'contact.tollfree.number',
        'contact.fax.number',
        'contact.email.address',
        );
    $customer_address_args = array();
    foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
        if( in_array($arg_name, $allowed_keys) ) {
            $strsql = "INSERT INTO ciniki_tenant_details (tnid, detail_key, detail_value, date_added, last_updated) "
                . "VALUES ('" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $arg_name) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $arg_value) . "', "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP()) ";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                return $rc;
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
                1, 'ciniki_tenant_details', $arg_name, 'detail_value', $arg_value);
        }
    }

    //
    // Check if user needs to be added
    //
    $user_id = 0;
    if( (isset($args['owner.username']) && $args['owner.username'] != '')
        || (isset($args['owner.email.address']) && $args['owner.email.address'] != '') ) {

        //
        // Check if user already exists
        //
        $strsql = "SELECT id, email, username "
            . "FROM ciniki_users "
            . "WHERE username = '" . ciniki_core_dbQuote($ciniki, $args['owner.username']) . "' "
            . "OR email = '" . ciniki_core_dbQuote($ciniki, $args['owner.email.address']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.26', 'msg'=>'Unable to lookup user'));
        }
        $user_id = 0;
        if( isset($rc['user']) ) {
            // User exists, check if email different
            if( $rc['user']['email'] != $args['owner.email.address'] ) {
                // Username matches, but email doesn't, they are trying to create a new account
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.27', 'msg'=>'Username already taken'));
            }
            else {
                $user_id = $rc['user']['id'];
            }
        } else {
            //
            // User doesn't exist, so can be created
            //
            if( !isset($args['owner.name.first']) || $args['owner.name.first'] == '' 
                || !isset($args['owner.name.last']) || $args['owner.name.last'] == '' 
                || !isset($args['owner.name.display']) || $args['owner.name.display'] == '' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.28', 'msg'=>'You must specify a first, last and display name'));
            }
            $strsql = "INSERT INTO ciniki_users (uuid, date_added, email, username, firstname, lastname, display_name, "
                . "perms, status, timeout, password, temp_password, temp_password_date, last_updated) VALUES ("
                . "UUID(), "
                . "UTC_TIMESTAMP()" 
                . ", '" . ciniki_core_dbQuote($ciniki, $args['owner.email.address']) . "'" 
                . ", '" . ciniki_core_dbQuote($ciniki, $args['owner.username']) . "'" 
                . ", '" . ciniki_core_dbQuote($ciniki, $args['owner.name.first']) . "'" 
                . ", '" . ciniki_core_dbQuote($ciniki, $args['owner.name.last']) . "'" 
                . ", '" . ciniki_core_dbQuote($ciniki, $args['owner.name.display']) . "'" 
                . ", 0, 1, 0, "
                . "SHA1('" . ciniki_core_dbQuote($ciniki, $args['owner.password']) . "'), "
                . "SHA1('" . ciniki_core_dbQuote($ciniki, '') . "'), "
                . "UTC_TIMESTAMP(), "
                . "UTC_TIMESTAMP())";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
            if( $rc['stat'] != 'ok' ) { 
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.29', 'msg'=>'Unable to add owner'));
            } else {
                $user_id = $rc['insert_id'];
            }
        }
    }

    //
    // Add the tenant owner
    //
    if( $user_id > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.30', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
        }
        $tenant_user_uuid = $rc['uuid'];
        
        $strsql = "INSERT INTO ciniki_tenant_users (tnid, user_id, uuid, "
            . "package, permission_group, status, date_added, last_updated) VALUES ("
            . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ", '" . ciniki_core_dbQuote($ciniki, $user_id) . "' "
            . ", '" . ciniki_core_dbQuote($ciniki, $tenant_user_uuid) . "' "
            . ", 'ciniki', 'owners', 10, UTC_TIMESTAMP(), UTC_TIMESTAMP())";
        $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.31', 'msg'=>'Unable to add ciniki owner'));
        } 
    }

    //
    // Add the customer to the master tenant
    //
    if( (isset($args['owner.name.first']) && $args['owner.name.first'] != '')
        || (isset($args['owner.name.last']) && $args['owner.name.last'] != '') 
        ) {
        //
        // Add the customer
        //
        $customer_args = array(
            'type'=>'1',
            'first'=>(isset($args['owner.name.first'])&&$args['owner.name.first']!='')?$args['owner.name.first']:'',
            'last'=>(isset($args['owner.name.last'])&&$args['owner.name.last']!='')?$args['owner.name.last']:'',
            'company'=>(isset($args['tenant.name'])&&$args['tenant.name']!='')?$args['tenant.name']:'',
            'email_address'=>(isset($args['owner.email.address'])&&$args['owner.email.address']!='')?$args['owner.email.address']:'',
            'flags'=>0x01,
            'address1'=>(isset($args['contact.address.street1'])&&$args['contact.address.street1']!='')?$args['contact.address.street1']:'',
            'address2'=>(isset($args['contact.address.street2'])&&$args['contact.address.street2']!='')?$args['contact.address.street2']:'',
            'city'=>(isset($args['contact.address.city'])&&$args['contact.address.city']!='')?$args['contact.address.city']:'',
            'province'=>(isset($args['contact.address.province'])&&$args['contact.address.province']!='')?$args['contact.address.province']:'',
            'postal'=>(isset($args['contact.address.postal'])&&$args['contact.address.postal']!='')?$args['contact.address.postal']:'',
            'country'=>(isset($args['contact.address.country'])&&$args['contact.address.country']!='')?$args['contact.address.country']:'',
            );
        if( isset($args['contact.phone.number']) && $args['contact.phone.number'] != '' ) {
            $customer_args['phone_label_1'] = 'Work';
            $customer_args['phone_number_1'] = $args['contact.phone.number'];
        }
        if( isset($args['contact.cell.number']) && $args['contact.cell.number'] != '' ) {
            $customer_args['phone_label_2'] = 'Cell';
            $customer_args['phone_number_2'] = $args['contact.cell.number'];
        }
        if( isset($args['contact.tollfree.number']) && $args['contact.tollfree.number'] != '' ) {
            $customer_args['phone_label_3'] = 'Tollfree';
            $customer_args['phone_number_3'] = $args['contact.tollfree.number'];
        }
        if( isset($args['contact.fax.number']) && $args['contact.fax.number'] != '' ) {
            $customer_args['phone_label_4'] = 'Fax';
            $customer_args['phone_number_4'] = $args['contact.fax.number'];
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerAdd');
        $rc = ciniki_customers_hooks_customerAdd($ciniki, $ciniki['config']['ciniki.core']['master_tnid'], $customer_args);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
            return $rc;
        }
        $customer_id = $rc['id'];
    }

    //
    // Check if a plan was specified and then setup for that plan
    //
    if( isset($args['plan_id']) && $args['plan_id'] > 0 ) {
        $strsql = "SELECT tnid, modules, monthly, trial_days "
            . "FROM ciniki_tenant_plans "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['plan_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $ciniki['config']['ciniki.core']['master_tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'plan');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
            return $rc;
        }
        if( !isset($rc['plan']) ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.32', 'msg'=>'Unable to find plan'));
        }
        $plan = $rc['plan'];

        $modules = preg_split('/,/', $plan['modules']);
        foreach($modules as $module) {
            list($pmod,$flags) = explode(':', $module);
            $mod = explode('.', $pmod);
            $strsql = "INSERT INTO ciniki_tenant_modules (tnid, "
                . "package, module, status, flags, ruleset, date_added, last_updated, last_change) VALUES ("
                . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $mod[0]) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $mod[1]) . "', "
                . "1, "
                . "'" . ciniki_core_dbQuote($ciniki, $flags) . "', "
                . "'', UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                return $rc;
            }
            //
            // Check if there is an initialization script for the module when the tenant is enabled
            //
            $rc = ciniki_core_loadMethod($ciniki, $mod[0], $mod[1], 'private', 'moduleInitialize');
            if( $rc['stat'] == 'ok' ) {
                $fn = $mod[0] . '_' . $mod[1] . '_moduleInitialize';
                $rc = $fn($ciniki, $tnid);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.33', 'msg'=>'Unable to initialize module ' . $mod[0] . '.' . $mod[1], 'err'=>$rc['err']));
                }
            }
        }

        //
        // Add the subscription plan
        //
        if( isset($args['payment_type']) && $args['payment_type'] == 'monthlypaypal' ) {
            $strsql = "INSERT INTO ciniki_tenant_subscriptions (tnid, status, "
                . "signup_date, trial_start_date, trial_days, currency, "
                . "monthly, discount_percent, discount_amount, payment_type, payment_frequency, "
                . "date_added, last_updated) VALUES ("
                . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
                . "2, UTC_TIMESTAMP(), UTC_TIMESTAMP(), "
                . "' " . ciniki_core_dbQuote($ciniki, $plan['trial_days']) . "' "
                . ", 'CAD', "
                . "'" . ciniki_core_dbQuote($ciniki, $plan['monthly']) . "', "
                . "0, 0, 'paypal', 10, "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                return $rc;
            } 
        }

        //
        // Add the yearly invoice to the master tenant
        //
        elseif( $args['payment_type'] == 'yearlycheque' && isset($customer_id) ) {
            $tz = new DateTimeZone($intl_timezone);
            $dt = new DateTime('now', $tz);
            $dt->add(new DateInterval('P' . $plan['trial_days'] . 'D'));
            $invoice_args = array(
                'source_id'=>'0',
                'status'=>'10',
                'customer_id'=>$customer_id,
                'invoice_number'=>'',
                'invoice_type'=>'12',
                'invoice_date'=>$dt->format('Y-m-d 12:00:00'),
                'items'=>array(array('description'=>'Web Hosting',
                    'quantity'=>'12',
                    'status'=>'0',
                    'flags'=>0,
                    'object'=>'ciniki.tenants.tenant',
                    'object_id'=>$tnid,
                    'price_id'=>'0',
                    'code'=>'',
                    'shipped_quantity'=>'0',
                    'unit_amount'=>$plan['monthly'],
                    'unit_discount_amount'=>'0',
                    'unit_discount_percentage'=>'0',
                    'taxtype_id'=>'0',
                    'notes'=>'{{thismonth[\'M Y\']}} - {{lastmonth[\'M\']}} {{nextyear[\'Y\']}}',
                    )),
                );
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceAdd');
            $rc = ciniki_sapos_hooks_invoiceAdd($ciniki, $ciniki['config']['ciniki.core']['master_tnid'], $invoice_args);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                return $rc;
            } 
            if( !isset($rc['id']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.34', 'msg'=>'Unable to create invoice'));
            }
        }
    }

    //
    // Commit the changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Send welcome email with login information
    //
    if( isset($args['owner.email.address']) && $args['owner.email.address'] != ''
        && $args['owner.username'] != '' ) {
        //
        // Load the tenant mail template
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'loadTenantTemplate');
        $rc = ciniki_mail_loadTenantTemplate($ciniki, $ciniki['config']['ciniki.core']['master_tnid'], array('title'=>'Welcome'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $template = $rc['template'];
        $theme = $rc['theme'];

        //
        // Create the email
        //
        $subject = "Welcome to Ciniki";
        $manager_url = $ciniki['config']['ciniki.core']['manage.url'];
        $msg = "<tr><td style='" . $theme['td_body'] . "'>"
            . "<p style='" . $theme['p'] . "'>"
            . 'Thank you for choosing the Ciniki platform to manage your tenant. '
            . "Please save this email for future reference.  We've included some important information and links below."
            . "</p>\n\n<p style='" . $theme['p'] . "'>"
            . "To get started, you can login at <a style='" . $theme['a'] . "' href='$manager_url'>$manager_url</a> with your email address and the password shown below."
            . "</p>\n\n<p style='" . $theme['p'] . "'>"
            . "";
        $msg .= "<p style='" . $theme['p'] . "'>"
            . "Email: " . $args['owner.email.address'] . "<br/>\n"
            . "Username: " . $args['owner.username'] . "<br/>\n"
            . "Password: " . $args['owner.password'] . "<br/>\n"
            . "Ciniki Manager: <a style='" . $theme['a'] . "' href='$manager_url'>$manager_url</a><br/>\n"
            . "";
        if( isset($plan) && preg_match('/ciniki\.web/', $plan['modules']) ) {
            $weburl = "http://" . $ciniki['config']['ciniki.web']['master.domain'] . '/' . $args['tenant.sitename'] . "<br/>\n";
            $msg .= "Your website: <a style='" . $theme['a'] . "' href='$weburl'>$weburl</a><br/>\n";
        }
        $msg .= "</p>\n\n";

        $htmlmsg = $template['html_header']
            . $msg
            . $template['html_footer']
            . "";
        $textmsg = $template['text_header']
            . strip_tags($msg)
            . $template['text_footer']
            . "";
        $ciniki['emailqueue'][] = array('to'=>$args['owner.email.address'],
            'subject'=>$subject,
            'htmlmsg'=>$htmlmsg,
            'textmsg'=>$textmsg,
            );
    }

    return array('stat'=>'ok', 'id'=>$tnid);
}
?>
