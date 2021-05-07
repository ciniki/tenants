//
// The app to manage tenants domains for a tenant
//
function ciniki_tenants_domains() {
    
    this.domainFlags = {
        '1':{'name':'Primary'},
        '5':{'name':'Force SSL'},
        };
    this.domainStatus = {
        '1':'Active',
        '50':'Suspended',
        '60':'Deleted',
        };
    
    //
    // Main menu
    //
    this.menu = new M.panel('Web Domains',
        'ciniki_tenants_domains', 'menu',
        'mc', 'medium', 'sectioned', 'ciniki.tenants.domains.menu');
    this.menu.data = {};
    this.menu.sections = {
        'domains':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['multiline'],
            },
    };
    this.menu.noData = function(s) { return 'No domains added'; }
    this.menu.sectionData = function(s) { return this.data; }
    this.menu.cellValue = function(s, i, j, d) {
        var primary = '';
        if( d.domain.isprimary == 'yes' ) {
            primary = ' (primary)';
        }
        var managed = '';
        if( d.domain.managed_by != '' ) {
            managed = ' - ' + d.domain.managed_by;
        }
        return '<span class="maintext">' + d.domain.domain + primary + '</span><span class="subtext">' + d.domain.expiry_date + managed + '</span>';
    }
    this.menu.rowFn = function(s, i, d) {
        return 'M.ciniki_tenants_domains.edit.open(\'M.ciniki_tenants_domains.menu.open();\',\'' + d.domain.id + '\');';
    };
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.tenants.domainList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tenants_domains.menu;
            p.data = rsp.domains;
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addButton('add', 'Add', 'M.ciniki_tenants_domains.edit.open(\'M.ciniki_tenants_domains.menu.open();\',0);');
    this.menu.addClose('Back');

    //
    // Edit panel
    //
    this.edit = new M.panel('Edit Domain',
        'ciniki_tenants_domains', 'edit',
        'mc', 'medium', 'sectioned', 'ciniki.tenants.domains.edit');
    this.edit.data = {'status':'1'};
    this.edit.sections = {
        'info':{'label':'', 'fields':{
            'domain':{'label':'Domain/Site', 'type':'text'},
            'parent_id':{'label':'Alias to', 'type':'select', 'options':{}, 
                'complex_options':{'name':'domain', 'value':'id'},
                },
            'flags':{'label':'', 'type':'flags', 'join':'yes', 'flags':this.domainFlags},
            'status':{'label':'Status', 'type':'multitoggle', 'toggles':this.domainStatus},
            'expiry_date':{'label':'Expiry', 'type':'date'},
            'managed_by':{'label':'Managed', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_tenants_domains.edit.save();'},
            'delete':{'label':'Delete', 
                'visible':function() { return M.ciniki_tenants_domains.edit.domain_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_tenants_domains.edit.remove();',
                },
            }},
        };
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.tenants.domainHistory', 'args':{'tnid':M.curTenantID, 
            'domain_id':M.ciniki_tenants_domains.edit.domain_id, 'field':i}};
    }
    this.edit.open = function(cb, did) {
        if( did != null ) { this.domain_id = did; }
        M.api.getJSONCb('ciniki.tenants.domainGet', {'tnid':M.curTenantID, 'domain_id':this.domain_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tenants_domains.edit;
            p.data = rsp.domain;
            p.sections.info.fields.parent_id.options = rsp.domains;
            p.refresh();
            p.show(cb);
        });
    };
    this.edit.save = function() {
        if( this.domain_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.tenants.domainUpdate', 
                    {'tnid':M.curTenantID, 'domain_id':this.domain_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_tenants_domains.edit.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.tenants.domainAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_tenants_domains.edit.close();
            });
        }
    };
    this.edit.remove = function() {
        M.confirm("Are you sure you want to remove the domain '" + this.data.domain + "' ?",null,function() {
            M.api.getJSONCb('ciniki.tenants.domainDelete', 
                {'tnid':M.curTenantID, 'domain_id':M.ciniki_tenants_domains.edit.domain_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_tenants_domains.edit.close();
                });
        });
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_tenants_domains.edit.save();');
    this.edit.addClose('Cancel');

    //
    // Start the app
    //
    this.start = function(cb, ap, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(ap, 'ciniki_tenants_domains', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args != null && args.tenant != null && args.tenant != '' ) {
            M.curTenantID = args.tenant;
        }
        if( args != null && args.domain != null && args.domain != '' ) {
            this.edit.open(cb, args.domain);
        } else {
            this.menu.open(cb);
        }
    }
};
