//
// This file contains the UI to setup the intl settings for the tenant.
//
function ciniki_tenants_intl() {
    this.init = function() {
        this.main = new M.panel('Localization',
            'ciniki_tenants_intl', 'main',
            'mc', 'medium', 'sectioned', 'ciniki.tenants.intl.main');
        this.main.data = {};
        this.main.sections = {
//          'info':{'label':'', 'type':'htmlcontent'},
            'locale':{'label':'', 
                'visible':function() { return M.curTenant.hamMode == null || M.curTenant.hamMode != 'yes' ? 'yes' : 'no'; },
                'fields':{
                    'intl-default-locale':{'label':'Locale', 'type':'select', 'options':{}},
                }},
            'currency':{'label':'', 
                'visible':function() { return M.curTenant.hamMode == null || M.curTenant.hamMode != 'yes' ? 'yes' : 'no'; },
                'fields':{
                    'intl-default-currency':{'label':'Currency', 'type':'select', 'options':{}},
                }},
            'timezone':{'label':'', 'fields':{
                'intl-default-timezone':{'label':'Time Zone', 'type':'select', 'options':{}},
                }},
            'measurement':{'label':'', 'fields':{
                'intl-default-distance-units':{'label':'Distance Units', 'type':'select', 'options':{}},
                }},
// temp & windspeed not currently used. Can be added back later if needed
// remove Feb 9, 2020 by andrew
//            'temperature':{'label':'', 'fields':{
//                'intl-default-temperature-units':{'label':'Temperature Units', 'type':'select', 'options':{}},
//                }},
//            'windspeed':{'label':'', 'fields':{
//                'intl-default-windspeed-units':{'label':'Windspeed Units', 'type':'select', 'options':{}},
//                }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_tenants_intl.saveIntl();'},
                }},
        };
        this.main.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.tenants.getDetailHistory', 'args':{'tnid':M.curTenantID, 'field':i}};
        }
        this.main.fieldValue = function(s, i, d) { return this.data[i]; }
        this.main.addButton('save', 'Save', 'M.ciniki_tenants_intl.saveIntl();');
        this.main.addClose('Cancel');
    }

    this.start = function(cb, appPrefix) {
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_tenants_intl', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 
        
        //
        // Load details
        //
        var rsp = M.api.getJSONCb('ciniki.tenants.settingsIntlGet', {'tnid':M.curTenantID}, 
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                var p = M.ciniki_tenants_intl.main;
                p.data = rsp.settings;
                p.sections.locale.fields['intl-default-locale'].options = {};
                for(i in rsp.locales) {
                    p.sections.locale.fields['intl-default-locale'].options[rsp.locales[i].locale.id] = rsp.locales[i].locale.name;
                }
                p.sections.currency.fields['intl-default-currency'].options = {};
                for(i in rsp.currencies) {
                    p.sections.currency.fields['intl-default-currency'].options[rsp.currencies[i].currency.id] = rsp.currencies[i].currency.name;
                }
                p.sections.timezone.fields['intl-default-timezone'].options = {};
                for(i in rsp.timezones) {
                    p.sections.timezone.fields['intl-default-timezone'].options[rsp.timezones[i].id] = rsp.timezones[i].id;
                }
                if( p.sections.measurement != null ) {
                    p.sections.measurement.fields['intl-default-distance-units'].options = {};
                    for(i in rsp.distanceunits) {
                        p.sections.measurement.fields['intl-default-distance-units'].options[rsp.distanceunits[i].id] = rsp.distanceunits[i].name;
                    }
                }
                if( p.sections.temperature != null ) {
                    p.sections.temperature.fields['intl-default-temperature-units'].options = {};
                    for(i in rsp.temperatureunits) {
                        p.sections.temperature.fields['intl-default-temperature-units'].options[rsp.temperatureunits[i].id] = rsp.temperatureunits[i].name;
                    }
                }
                if( p.sections.windspeed != null ) {
                    p.sections.windspeed.fields['intl-default-windspeed-units'].options = {};
                    for(i in rsp.windspeedunits) {
                        p.sections.windspeed.fields['intl-default-windspeed-units'].options[rsp.windspeedunits[i].id] = rsp.windspeedunits[i].name;
                    }
                }
                p.refresh();
                p.show(cb);
        });
    }

    // 
    // Submit the form
    //
    this.saveIntl = function() {
        // Serialize the form data into a string for posting
        var c = this.main.serializeForm('no');
        if( c != '' ) {
            var rsp = M.api.postJSONCb('ciniki.tenants.settingsIntlUpdate', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_tenants_intl.main.close();
                });
        } else {
            this.main.close();
        }
    }
}
