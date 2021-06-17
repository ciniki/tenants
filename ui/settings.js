//
// This class will display the form to allow admins and tenant owners to 
// change the details of their tenant
//
function ciniki_tenants_settings() {

    this.menu = new M.panel('Tenant Settings', 'ciniki_tenants_settings', 'menu', 'mc', 'narrow', 'sectioned', 'ciniki.tenants.settings.menu');
    this.menu.addClose('Back');

    this.start = function(cb, ap, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer('mc', 'ciniki_tenants_settings', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 
        
        // 
        // Clear old menu
        //
        this.menu.reset();

        //
        // Setup the Tenant Settings 
        //
        this.menu.title = 'Tenant Settings';
        if( M.curTenant.hamMode != null && M.curTenant.hamMode == 'yes' ) {
            this.menu.title = 'Station Settings';
            this.menu.sections = {
                '':{'label':'', 'aside':'yes', 'list':{
                    'info':{'label':'Station Info', 'fn':'M.startApp(\'ciniki.tenants.info\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'operators':{'label':'Operators', 'fn':'M.startApp(\'ciniki.tenants.operators\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'intl':{'label':'Localization', 'fn':'M.startApp(\'ciniki.tenants.intl\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    }}};

        } else if( M.stMode != null && M.stMode == 'yes' ) {
            this.menu.sections = {
                '':{'label':'', 'aside':'yes', 'list':{
                    'info':{'label':'Tenant Info', 'fn':'M.startApp(\'ciniki.tenants.info\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'users':{'label':'Owners & Employees', 'fn':'M.startApp(\'ciniki.tenants.users\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'intl':{'label':'Localization', 'fn':'M.startApp(\'ciniki.tenants.intl\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    }}};
        } else {
            this.menu.sections = {
                '':{'label':'', 'aside':'yes', 'list':{
                    'info':{'label':'Tenant Info', 'fn':'M.startApp(\'ciniki.tenants.info\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'users':{'label':'Owners & Employees', 'fn':'M.startApp(\'ciniki.tenants.users\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'permissions':{'label':'Permissions', 'visible':'no', 'fn':'M.startApp(\'ciniki.tenants.permissions\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'social':{'label':'Social Media', 'fn':'M.startApp(\'ciniki.tenants.social\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'intl':{'label':'Localization', 'fn':'M.startApp(\'ciniki.tenants.intl\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'billing':{'label':'Billing', 'fn':'M.startApp(\'ciniki.tenants.billing\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    }}};
        }
        //
        // FIXME: Move into bugs settings
        //
        if( (M.curTenant.modules['ciniki.bugs'] != null && (M.userPerms&0x01) == 0x01) 
            || M.curTenant.modules['ciniki.wineproduction'] != null
            ) {
            this.menu.sections[''].list.permissions.visible = 'yes';
        }

        if( M.curTenant.modules['ciniki.artcatalog'] != null && M.modFlagSet('ciniki.tenants', 0x020000) == 'yes' ) {
            this.menu.sections['']['list']['backups'] = {'label':'Backups', 'fn':'M.startApp(\'ciniki.tenants.backups\', null, \'M.ciniki_tenants_settings.menu.show();\');'};
        }

        //
        // Check for settings_menu_items
        //
        if( M.curTenant.settings_menu_items != null ) {
            this.menu.sections['modules'] = {'label':'', 'aside':'yes', 'list':{}};
            for(var i in M.curTenant.settings_menu_items) {
                var item = {'label':M.curTenant.settings_menu_items[i].label};
                if( M.curTenant.settings_menu_items[i].edit != null ) {
                    var args = '';
                    if( M.curTenant.settings_menu_items[i].edit.args != null ) {
                        for(var j in M.curTenant.settings_menu_items[i].edit.args) {
                            args += (args != '' ? ', ':'') + '\'' + j + '\':' + eval(M.curTenant.settings_menu_items[i].edit.args[j]);
                        }
                        item.fn = 'M.startApp(\'' + M.curTenant.settings_menu_items[i].edit.app + '\',null,\'M.ciniki_tenants_settings.menu.show();\',\'mc\',{' + args + '});';
                    } else {
                        item.fn = 'M.startApp(\'' + M.curTenant.settings_menu_items[i].edit.app + '\',null,\'M.ciniki_tenants_settings.menu.show();\');';
                    }
                }
                this.menu.sections.modules.list[i] = item;
            }
        }
    
        //
        // Advaned options for Sysadmins or resellers, only on server installs
        //
        if( M.blackbox == null && M.userID > 0 && ((M.userPerms&0x01) == 0x01 || M.curTenant.permissions.resellers != null) ) {
            //
            // Setup the advanced section for resellers and admins
            //
            this.menu.sections['advanced'] = {'label':'Admin', 'list':{
                'modules':{'label':'Modules', 'fn':'M.startApp(\'ciniki.tenants.modules\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                'moduleflags':{'label':'Module Flags', 'fn':'M.startApp(\'ciniki.tenants.moduleflags\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
            }};
            if( (M.curTenant.modules['ciniki.directory'] != null && (M.curTenant.modules['ciniki.directory'].flags&0x01) > 0)
                || (M.curTenant.modules['ciniki.artistprofiles'] != null && (M.curTenant.modules['ciniki.artistprofiles'].flags&0x01) > 0)
                ) {
                this.menu.sections['advanced']['list']['apis'] = {'label':'Connected Services', 'fn':'M.startApp(\'ciniki.tenants.apis\',null,\'M.ciniki_tenants_settings.menu.show();\');'};
            }

//            this.menu.sections['advanced'] = {'label':'Advanced', 'list':{
//                    'integrityfix':{'label':'Database Integrity Fix', 'fn':'M.ciniki_tenants_settings.fixallintegrity();'},
//                }};
            if( M.curTenant.modules['ciniki.products'] != null ) {
                this.menu.sections['advanced']['list']['products'] = {'label':'Products', 'fn':'M.startApp(\'ciniki.products.types\',null,\'M.ciniki_tenants_settings.menu.show();\');'};
            }
            if( M.curTenant.modules['ciniki.wng'] != null ) {
                this.menu.sections['advanced']['list']['websites'] = {'label':'Websites', 'fn':'M.startApp(\'ciniki.wng.sites\',null,\'M.ciniki_tenants_settings.menu.show();\');'};
            }
            this.menu.size = 'narrow narrowaside';

            //
            // Setup the sysadmin only options
            //
            if( M.blackbox == null && M.userID > 0 && (M.userPerms&0x01) == 0x01 ) {
                this.menu.sections['admin'] = {'label':'SysAdmin', 'list':{
                    'sync':{'label':'Syncronization', 'fn':'M.startApp(\'ciniki.tenants.sync\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'CSS':{'label':'CSS', 'fn':'M.startApp(\'ciniki.tenants.css\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'webdomains':{'label':'Domains', 'fn':'M.startApp(\'ciniki.tenants.domains\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'assets':{'label':'Image Assets', 'fn':'M.startApp(\'ciniki.tenants.assets\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
//                    'fixhistory':{'label':'Fix History', 'fn':'M.ciniki_tenants_settings.fixallhistory();'},
                    'imagecropper':{'label':'Image Cropper', 'fn':'M.startApp(\'ciniki.images.cropper\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    'ifbupgrade':{'label':'IFB Upgrade', 'fn':'M.startApp(\'ciniki.customers.ifbupgrade\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
//                    'checkimages':{'label':'Check Image Storage', 'fn':'M.ciniki_tenants_settings.checkimagestorage("no");'},
//                    'checkimagesclean':{'label':'Check Image Storage & Clean DB', 'fn':'M.ciniki_tenants_settings.checkimagestorage("yes");'},
//                    'moveproductfiles':{'label':'Check Product Files', 'fn':'M.ciniki_tenants_settings.moveproductstorage("no");'},
//                    'moveproductfilesclean':{'label':'Check Product Files & Clean DB', 'fn':'M.ciniki_tenants_settings.moveproductstorage("yes");'},
    //              'fixhistory':{'label':'Fix History', 'fn':'M.startApp(\'ciniki.tenants.fixhistory\', null, \'M.ciniki_tenants_settings.menu.show();\');'},
                    }};
//                if( M.curTenant.modules['ciniki.artclub'] != null ) {
//                    this.menu.sections.admin.list['movemembers'] = {'label':'Move Members', 'fn':'M.ciniki_tenants_settings.movemembers();'};
//                }
                if( M.curTenantID == M.masterTenantID ) {
                    this.menu.sections.admin.list['plans'] = {'label':'Plans', 'fn':'M.startApp(\'ciniki.tenants.plans\', null, \'M.ciniki_tenants_settings.menu.show();\');'};
                }
            }
        }

        //
        // Show the settings menu
        //
        this.menu.show(cb);
    }



    this.fixallintegrity = function() {
        M.startLoad();
        this.dbfixintegrity();
    }

    this.dbfixintegrity = function() {
        if( this.fixintegrity('ciniki.tenants') == false ) {
            M.stopLoad();
            return false;
        }
        if( this.fixintegrity('ciniki.images') == false ) {
            M.stopLoad();
            return false;
        }
        var mods = [
            'ciniki.artcatalog',
            'ciniki.atdo',
            'ciniki.customers',
            'ciniki.events',
            'ciniki.exhibitions',
            'ciniki.gallery',
            'ciniki.images',
            'ciniki.links',
            'ciniki.products',
            'ciniki.projects',
            'ciniki.services',
            'ciniki.web',
            'ciniki.wineproduction',
            ];
        for(i in mods) {
            if( M.curTenant.modules[mods[i]] != null ) {
                if( this.fixintegrity(mods[i]) == false ) {
                    M.stopLoad();
                    return false;
                }
            }
        }
        M.stopLoad();
        M.alert('done');
    }

    this.fixintegrity = function(module) {
        var rsp = M.api.getJSON(module + '.dbIntegrityCheck', 
            {'tnid':M.curTenantID, 'fix':'yes'});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        return true;
    };

    this.fixallhistory = function() {
        if( this.fixhistory('ciniki.users') == false ) {
            return false;
        }
        if( this.fixhistory('ciniki.tenants') == false ) {
            return false;
        }
        if( this.fixhistory('ciniki.images') == false ) {
            return false;
        }
        var mods = [
            'ciniki.artcatalog',
            'ciniki.atdo',
            'ciniki.customers',
            'ciniki.events',
            'ciniki.exhibitions',
            'ciniki.gallery',
            'ciniki.images',
            'ciniki.links',
            'ciniki.products',
            'ciniki.projects',
            'ciniki.services',
            'ciniki.wineproduction',
            ];
        for(i in mods) {
            if( M.curTenant.modules[mods[i]] != null ) {
                if( this.fixhistory(mods[i]) == false ) {
                    return false;
                }
            }
        }
        M.alert('done');
    }

    this.fixhistory = function(module) {
        var rsp = M.api.getJSON(module + '.historyFix', {'tnid':M.curTenantID});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        return true;
    };

    this.movemembers = function(module) {
        var rsp = M.api.getJSON('ciniki.artclub.memberCopyToCustomers', {'tnid':M.curTenantID});
        if( rsp.stat != 'ok' ) {
            M.alert('failed');
            M.api.err(rsp);
            return false;
        }
        return true;
    };

    this.checkimagestorage = function(clear) {
        M.api.getJSONCb('ciniki.images.dbCheckImageStorage', {'tnid':M.curTenantID, 'clear':clear}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.alert('All images in storage');        
        });
    };
    this.moveproductstorage = function(clear) {
        M.api.getJSONCb('ciniki.products.dbMoveFileStorage', {'tnid':M.curTenantID, 'clear':clear}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.alert('All files in storage');        
        });
    };
}

