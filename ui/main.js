//
// This class will display the form to allow admins and tenant owners to 
// change the details of their tenant
//
function ciniki_tenants_main() {
    this.tenants = null;
    this.menu = null;
    this.helpContentSections = {};

    this.statusOptions = {
        '10':'Ordered',
        '20':'Started',
        '25':'SG Ready',
        '30':'Racked',
        '40':'Filtered',
        '60':'Bottled',
        '100':'Removed',
        '*':'Unknown',
        };

    this.init = function() {
        //
        // Build the menus for the tenant, based on what they have access to
        //
        this.menu = new M.panel('Tenant Menu', 'ciniki_tenants_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.tenants.main.menu');
        this.menu.data = {};
        this.menu.liveSearchCb = function(s, i, value) {
            if( this.sections[s].search != null && value != '' ) {
                var sargs = (this.sections[s].search.args != null ? this.sections[s].search.args : []);
                sargs['tnid'] = M.curTenantID;
                sargs['start_needle'] = encodeURIComponent(value);
                sargs['limit'] = 10;
                var container = this.sections[s].search.container;
                M.api.getJSONBgCb(this.sections[s].search.method, sargs, function(rsp) {
                    M.ciniki_tenants_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_tenants_main.menu.panelUID + '_' + s), rsp[container]);
                });
                return true;
            }
        };
        this.menu.liveSearchResultClass = function(s, f, i, j, d) {
            if( this.sections[s].search != null ) {
                if( this.sections[s].search.cellClasses != null && this.sections[s].search.cellClasses[j] != null ) {
                    return this.sections[s].search.cellClasses[j];
                }
                return '';
            }
            return '';
        };
        this.menu.liveSearchResultValue = function(s, f, i, j, d) {
            if( this.sections[s].search != null && this.sections[s].search.cellValues != null ) {
                return eval(this.sections[s].search.cellValues[j]);
            }
            return '';
        }
        this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
            if( this.sections[s].search != null ) {
                if( this.sections[s].search.edit != null ) {
                    var args = '';
                    for(var i in this.sections[s].search.edit.args) {
                        args += (args != '' ? ', ':'') + '\'' + i + '\':' + eval(this.sections[s].search.edit.args[i]);
                    }
                    return 'M.startApp(\'' + this.sections[s].search.edit.method + '\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{' + args + '});';
                } 
                return null;
            }
            return null;
        };
        this.menu.liveSearchResultRowClass = function(s, f, i, d) {
            if( this.sections[s].search.rowClass != null ) {
                return eval(this.sections[s].search.rowClass);
            }
            return '';
        };
        this.menu.liveSearchResultRowStyle = function(s, f, i, d) {
            if( this.sections[s].search.rowStyle != null ) {
                return eval(this.sections[s].search.rowStyle);
            }
            return '';
        };
        this.menu.liveSearchSubmitFn = function(s, search_str) {
            if( this.sections[s].search != null && this.sections[s].search.submit != null ) {
                var args = {};
                for(var i in this.sections[s].search.submit.args) {
                    args[i] = eval(this.sections[s].search.submit.args[i]);
                }
                M.startApp(this.sections[s].search.submit.method,null,'M.ciniki_tenants_main.showMenu();','mc',args);
            }
        };
        this.menu.liveSearchResultCellFn = function(s, f, i, j, d) {
//            if( this.sections[s].search != null ) {
//                if( this.sections[s].search.cellFns != null && this.sections[s].search.cellFns[j] != null ) {
//                    return eval(this.sections[s].search.cellFns[j]);
//                }
//                return '';
//            }
/*            if( d.app != null && d.app != '' ) {
                var args = '{';
                if( d.args != null ) {
                    for(var i in d.args) {
                        args[i] = eval(d.args[i]);
                    }
                }
                return 'M.startApp(\'' + d.app + '\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',);';
            } */
            // FIXME: This needs to move into hooks/uiSettings
            if( this.sections[s].id == 'calendars' || s == 'datepicker' ) {
                if( j == 0 && d.start_ts > 0 ) {
                    return 'M.startApp(\'ciniki.calendars.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'date\':\'' + d.date + '\'});';
                }
                if( d.module == 'ciniki.wineproduction' ) {
                    return 'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'appointment_id\':\'' + d.id + '\'});';
                }
                if( d.module == 'ciniki.atdo' ) {
                    return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'atdo_id\':\'' + d.id + '\'});';
                }
                if( d.app == 'ciniki.customers.reminders' ) {
                    return 'M.startApp(\'ciniki.customers.reminders\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'reminder_id\':\'' + d.id + '\',\'source\':\'tenantmenu\'});';
                }
            }
            return '';
        };
        this.menu.liveSearchResultCellColour = function(s, f, i, j, d) {
            if( this.sections[s].search != null ) {
                if( this.sections[s].search.cellColours != null && this.sections[s].search.cellColours[j] != null ) {
                    return eval(this.sections[s].search.cellColours[j]);
                }
                return '';
            }
            return '';
        };
        this.menu.cellValue = function(s, i, j, d) {
            if( s == '_messages' ) {
                return M.multiline((d.viewed == 'no' ? ('<b>'+d.subject+'</b>') : d.subject)
                    + M.subdue(' [', d.project_name, ']'),
                    d.last_followup_user + ' - ' + d.last_followup_age);
            }
            if( s == '_tasks' ) {
                switch (j) {
                    case 0: return '<span class="icon">' + M.curTenant.atdo.priorities[d.priority] + '</span>';
                    case 1: 
                        var pname = '';
                        if( d.project_name != null && d.project_name != '' ) {
                            pname = ' <span class="subdue">[' + d.project_name + ']</span>';
                        }
                        return '<span class="maintext">' + d.subject + pname + '</span><span class="subtext">' + d.assigned_users + '&nbsp;</span>';
                    case 2: return '<span class="maintext">' + d.due_date + '</span><span class="subtext">' + d.due_time + '</span>';
                }
            }
            if( s == '_timetracker_projects' ) {
                switch(j) {
                    case 0: return d.name;
                    case 1: return (d.today_length_display != null ? d.today_length_display : '-');
                    case 2: 
                        if( d.entry_id > 0 ) {
                            return '<button onclick="M.ciniki_tenants_main.menu.stopEntry(\'' + d.entry_id + '\');">Stop</button>';
                        } else {
                            return '<button onclick="M.ciniki_tenants_main.menu.startEntry(\'' + d.id + '\');">Start</button>';
                        }
                }
            }
            if( s == '_timetracker_entries' ) {
                switch(j) {
                    case 0: return M.multiline(d.project_name, d.notes);
                    case 1: return M.multiline(d.start_dt_display, (d.end_dt_display != '' ? d.end_dt_display : '-'));
                    case 2: return d.length_display;
                }
            }
        };
        this.menu.rowFn = function(s, i, d) {
            if( s == '_timetracker_entries' ) {
                return 'M.startApp(\'ciniki.timetracker.tracker\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'entry_id\':\'' + d.id + '\'});';
            }
            if( (s == '_tasks' || s == '_messages') && d != null ) {
                return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'atdo_id\':\'' + d.id + '\'});';
            }
            return null;
        };
        this.menu.rowClass = function(s, i, d) {
            if( s == '_timetracker_projects' ) {
                if( d.entry_id > 0 ) {
                    return 'statusgreen aligncenter';
                } else {
                    return 'statusred aligncenter';
                }
            }
            if( s == '_tasks' && d.status != 'closed' ) {
                switch(d.priority) {
                    case '10': return 'statusyellow';
                    case '30': return 'statusorange';
                    case '50': return 'statusred';
                }
            }
            if( s == 'datepicker' ) {
                var dt = new Date();
                if( (dt.getFullYear() + '-' + ('00' + (dt.getMonth()+1)).substr(-2) + '-' + dt.getDate()) == this.date ) {
                    return 'statusgreen';
                }
            }
            return null;
        }
        this.menu.helpSections = function() {
            return M.ciniki_tenants_main.helpContentSections;
        }
    }

    this.start = function(cb, ap, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer('mc', 'ciniki_tenants_main', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        //
        // Get the tnid to be opened
        //
        if( args.id != null && args.id != '' ) {
            this.openTenant(cb, args.id);
        } else {
            M.alert('Tenant not found');
            return false;
        }
    }

    //
    // Open a tenant for the specified ID
    //
    this.openTenant = function(cb, id) {
        if( id != null ) {
            M.curTenantID = id;
            // (re)set the tenant object
            delete M.curTenant;
            M.curTenant = {'id':id};
        }
        if( M.curTenantID == null ) {
            M.alert('Invalid tenant');
        }

        //
        // Reset all buttons
        //
        this.menu.leftbuttons = {};
        this.menu.rightbuttons = {};

        //
        // If both callbacks are null, then this is the root of the menu system
        //
        M.menuHome = this.menu;
        if( cb == null ) {
            // 
            // Add the buttons required on home menu
            //
            this.menu.addButton('account', 'Account', 'M.startApp(\'ciniki.users.main\',null,\'M.home();\');');
            this.menu.addLeftButton('logout', 'Logout', 'M.logout();');
            if( M.stMode == null && M.userID > 0 && (M.userPerms&0x01) == 0x01 ) {
                this.menu.addLeftButton('sysadmin', 'Admin', 'M.startApp(\'ciniki.sysadmin.main\',null,\'M.home();\');');
            }
//          M.menuHome = this.menu;
        } else {
            this.menu.addClose('Back');
            if( typeof(Storage) !== 'undefined' ) {
                localStorage.setItem("lastTenantID", M.curTenantID);
            }
        }
        this.menu.cb = cb;

        this.openTenantSettings();
    }

    this.openTenantSettings = function() {
        // 
        // Get the list of owners and employees for the tenant
        //
        M.api.getJSONCb('ciniki.tenants.getUserSettings', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_tenants_main.openTenantFinish(rsp);
        });
    }

    this.openTenantFinish = function(rsp) {
        // 
        // Setup menu name
        //
        M.curTenant.name = rsp.name;

        //
        // Setup CSS
        //
        if( rsp.css != null && rsp.css != '' ) {
            M.gE('tenant_colours').innerHTML = rsp.css;
        } else {
            M.gE('tenant_colours').innerHTML = M.defaultTenantColours;
        }

        //
        // Check if ham radio statio
        //
        if( rsp.flags != null && (rsp.flags&0x02) == 0x02 ) {
            M.curTenant.hamMode = 'yes';
        }

        //
        // Setup employees
        //
        M.curTenant.employees = {};
        var ct = 0;
        for(i in rsp.users) {
            M.curTenant.employees[rsp.users[i].user.id] = rsp.users[i].user.display_name;
            ct++;
        }
        M.curTenant.numEmployees = ct;

        // 
        // Setup tenant permissions for the user
        //
        M.curTenant.permissions = {};
        M.curTenant.permissions = rsp.permissions;

        // 
        // Setup the settings for activated modules
        //
        if( rsp.settings != null && rsp.settings['ciniki.bugs'] != null ) {
            M.curTenant.bugs = {};
            M.curTenant.bugs.priorities = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
            if( M.size == 'compact' ) {
                M.curTenant.bugs.priorityText = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
            } else {
                M.curTenant.bugs.priorityText = {'10':'<span class="icon">Q</span> Low', '30':'<span class="icon">W</span> Medium', '50':'<span class="icon">E</span> High'};
            }
            M.curTenant.bugs.settings = rsp.settings['ciniki.bugs'];
        }
        if( rsp.settings != null && rsp.settings['ciniki.atdo'] != null ) {
            M.curTenant.atdo = {};
            M.curTenant.atdo.priorities = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
            if( M.size == 'compact' ) {
                M.curTenant.atdo.priorityText = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
            } else {
                M.curTenant.atdo.priorityText = {'10':'<span class="icon">Q</span> Low', '30':'<span class="icon">W</span> Medium', '50':'<span class="icon">E</span> High'};
            }
            M.curTenant.atdo.settings = rsp.settings['ciniki.atdo'];
        }
        if( rsp.settings != null && rsp.settings['ciniki.customers'] != null ) {
            M.curTenant.customers = {'settings':rsp.settings['ciniki.customers']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.taxes'] != null ) {
            M.curTenant.taxes = {'settings':rsp.settings['ciniki.taxes']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.services'] != null ) {
            M.curTenant.services = {'settings':rsp.settings['ciniki.services']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.mail'] != null ) {
            M.curTenant.mail = {'settings':rsp.settings['ciniki.mail']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.artcatalog'] != null ) {
            M.curTenant.artcatalog = {'settings':rsp.settings['ciniki.artcatalog']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.sapos'] != null ) {
            M.curTenant.sapos = {'settings':rsp.settings['ciniki.sapos']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.products'] != null ) {
            M.curTenant.products = {'settings':rsp.settings['ciniki.products']};
        }
        if( rsp.settings != null ) {
            if( M.curTenant.settings == null ) {
                M.curTenant.settings = {};
            }
            if( rsp.settings['googlemapsapikey'] != null && rsp.settings['googlemapsapikey'] != '' ) {
                M.curTenant.settings.googlemapsapikey = rsp.settings['googlemapsapikey'];
            }
            if( rsp.settings['uiAppOverrides'] != null && rsp.settings['uiAppOverrides'] != '' ) {
                M.curTenant.settings.uiAppOverrides = rsp.settings['uiAppOverrides'];
            }
        }
        if( rsp.intl != null ) {
            M.curTenant.intl = rsp.intl;
        }

        var modules = {};
        for(i in rsp.modules) {
            modules[rsp.modules[i].module.name] = rsp.modules[i].module;
            if( rsp.settings != null && rsp.settings[rsp.modules[i].module.name] != null ) {
                modules[rsp.modules[i].module.name].settings = rsp.settings[rsp.modules[i].module.name];
            }
        }
        M.curTenant.modules = modules;

        //
        // FIXME: Check if tenant is suspended status, and display message
        //

        //
        // Show the menu, which loads modules and display up to date message counts, etc.
        //
        this.showMenuFinish(rsp, 'yes');
    };

    //
    // This function is called upon return from opening a main menu item
    //
    this.showMenu = function() {
        //
        // Get the list of modules (along with other information that's not required)
        //
        M.api.getJSONCb('ciniki.tenants.getUserSettings', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_tenants_main.openTenantFinish(rsp, 'no');
        });
    }

    this.showMenuFinish = function(r, autoopen) {
        this.menu.title = M.curTenant.name;
        //
        // If sysadmin, or tenant owner
        //
        if( M.userID > 0 && ( (M.userPerms&0x01) == 0x01 || M.curTenant.permissions.owners != null || M.curTenant.permissions.resellers != null )) {
            this.menu.addButton('settings', 'Settings', 'M.startApp(\'ciniki.tenants.settings\',null,\'M.ciniki_tenants_main.openTenantSettings();\');');
            M.curTenant.settings_menu_items = r.settings_menu_items;
        }

        var c = 0;
        var join = -1;  // keep track of how many are already joined together

        var perms = M.curTenant.permissions;

        //
        // Check that the module is turned on for the tenant, and the user has permissions to the module
        //

        this.menu.sections = {};
        var tenant_possession = 'our';
        var g = 0;
        var menu_search = 0;

        //
        // Build the main menu from the items supplied
        //
        if( r.menu_items != null ) {
            // Get the number of search items
            for(var i in r.menu_items) {
                if( r.menu_items[i].search != null ) {
                    menu_search++
                }
            }
            if( menu_search < 2 ) {
                menu_search = 0;
            }
            for(var i in r.menu_items) {
                var item = {'label':r.menu_items[i].label};
                //
                // Check if there is help content for the internal help mode
                //
                if( r.menu_items[i].helpcontent != null && r.menu_items[i].helpcontent != '' ) {
                    this.helpContentSections[i] = {
                        'label':r.menu_items[i].label, 
                        'type':'htmlcontent', 
                        'html':r.menu_items[i].helpcontent,
                        };
                }
                if( r.menu_items[i].edit != null ) {
                    var args = '';
                    if( r.menu_items[i].edit.args != null ) {
                        for(var j in r.menu_items[i].edit.args) {
                            args += (args != '' ? ', ':'') + '\'' + j + '\':' + eval(r.menu_items[i].edit.args[j]);
                        }
                        item.fn = 'M.startApp(\'' + r.menu_items[i].edit.app + '\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{' + args + '});';
                    } else {
                        item.fn = 'M.startApp(\'' + r.menu_items[i].edit.app + '\',null,\'M.ciniki_tenants_main.showMenu();\');';
                    }
                } else if( r.menu_items[i].fn != null ) {
                    item.fn = r.menu_items[i].fn;
                }
                if( r.menu_items[i].count != null ) {
                    item.count = r.menu_items[i].count;
                }
                if( r.menu_items[i].add != null && menu_search > 0 ) {
                    var args = '';
                    for(var j in r.menu_items[i].add.args) {
                        args += (args != '' ? ', ':'') + '\'' + j + '\':' + eval(r.menu_items[i].add.args[j]);
                    }
                    item.addFn = 'M.startApp(\'' + r.menu_items[i].add.app + '\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{' + args + '});';
                }

                if( r.menu_items[i].search != null && menu_search > 0 ) {
                    item.search = r.menu_items[i].search;
                    if( r.menu_items[i].id != null ) {
                        item.id = r.menu_items[i].id;
                    }
                    item.type = 'livesearchgrid';
                    item.searchlabel = item.label;
                    item.aside = 'yes';
                    item.label = '';
                    item.livesearchcols = item.search.cols;
                    item.noData = item.search.noData;
                    if( item.search.headerValues != null ) {
                        item.headerValues = item.search.headerValues;
                    }
                    if( r.menu_items[i].search.searchtype != null && r.menu_items[i].search.searchtype != '' ) {
                        item.livesearchtype = r.menu_items[i].search.searchtype;
                    }
                    item['flexcolumn'] = 1;
                    item['minwidth'] = '10em';
                    item['width'] = '30em';
                    item['maxwidth'] = '40em';
                    this.menu.sections[c++] = item;
                    menu_search = 1;
                }
                else if( r.menu_items[i].subitems != null ) {
                    item.aside = 'yes';
                    item.list = {};
                    for(var j in r.menu_items[i].subitems) {
                        var args = '';
                        for(var k in r.menu_items[i].subitems[j].edit.args) {
                            args += (args != '' ? ', ':'') + '\'' + k + '\':' + eval(r.menu_items[i].subitems[j].edit.args[k]);
                        }
                        item.list[j] = {'label':r.menu_items[i].subitems[j].label, 'fn':'M.startApp(\'' + r.menu_items[i].subitems[j].edit.app + '\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{' + args + '});'};
                        if( r.menu_items[i].subitems[j].count != null ) {
                            item.list[j].count = r.menu_items[i].subitems[j].count;
                        }
                    }
                    this.menu.sections[c] = item;
                    menu_search = 0;
                    join = 0;
                    c++;
                    this.menu.sections[c] = {'label':'Menu', 'aside':'yes', 'list':{}, 'flexcolumn':1, 'minwidth':'10em', 'width':'30em', 'maxwidth':'40em'};
                }
                else if( join > -1 ) {
                    this.menu.sections[c].list['item_' + i] = item;
                    join++;
//                    this.menu.sections[c].list['item_' + i] = {'label':r.menu_items[i].label, 'fn':fn};
                } else {
                    this.menu.sections[c++] = {'label':'', 'aside':'yes', 'list':{'_':item}, 'flexcolumn':1, 'minwidth':'10em', 'width':'30em', 'maxwidth':'40em'};
//                    this.menu.sections[c++] = {'label':'', 'list':{'_':{'label':r.menu_items[i].label, 'fn':fn}}};
                }
                if( c > 4 && join < 0 ) {
                    join = 0;
                    this.menu.sections[c] = {'label':' &nbsp; ', 'aside':'yes', 'list':{}, 'flexcolumn':1, 'minwidth':'10em', 'width':'30em', 'maxwidth':'40em'};
                }
            }
        }

        //
        // Check for archived modules
        //
        if( r.archived_items != null ) {
            c++;
            this.menu.sections[c] = {'label':'Archive', 'list':{}, 'flexcolumn':1, 'minwidth':'10em', 'width':'30em', 'maxwidth':'40em'};
            for(var i in r.archived_items) {
                var item = {'label':r.archived_items[i].label};
                if( r.archived_items[i].edit != null ) {
                    var args = '';
                    if( r.archived_items[i].edit.args != null ) {
                        for(var j in r.archived_items[i].edit.args) {
                            args += (args != '' ? ', ':'') + '\'' + j + '\':' + eval(r.archived_items[i].edit.args[j]);
                        }
                        item.fn = 'M.startApp(\'' + r.archived_items[i].edit.app + '\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{' + args + '});';
                    } else {
                        item.fn = 'M.startApp(\'' + r.archived_items[i].edit.app + '\',null,\'M.ciniki_tenants_main.showMenu();\');';
                    }
                } else if( r.archived_items[i].fn != null ) {
                    item.fn = r.archived_items[i].fn;
                }
                this.menu.sections[c].list['item_' + i] = item;
            }
        }

        //
        // Setup the auto split if long menu
        //
        if( join > 8 ) {
            this.menu.sections[c].as = 'yes';
        }

        //
        // Check if we should autoopen the submenu when there is only one menu item.
        //
        if( autoopen == 'yes' && c == 1 
            && this.menu.sections[0].list != null 
            && this.menu.sections[0].list._ != null 
            && this.menu.sections[0].list._.fn != null ) {
            this.menu.autoopen = 'skipped';
            eval(this.menu.sections[0].list._.fn);
        } else {
            this.menu.autoopen = 'no';
        }

        // Set size of menu based on contents
        if( menu_search == 1 ) {
            this.menu.size = 'medium';
        } else {
            this.menu.size = 'narrow';
        }
        //
        // Show the calendar, tasks and time tracker on main menu screen
        //
        if( M.modFlagOn('ciniki.tenants', 0x0100) 
            && (perms.owners != null || perms.employees != null || perms.resellers != null || (M.userPerms&0x01) == 1) 
            ) {
            this.menu.sections[0].label = 'Menu';
            this.menu.size = 'flexible';
            if( M.modOn('ciniki.calendars') ) {
                if( this.menu.date == null ) {
                    var dt = new Date();
                    dt.setHours(0);
                    dt.setMinutes(0);
                    dt.setSeconds(0);
                    this.menu.date = dt.getFullYear() + '-' + ('00' + (dt.getMonth()+1)).substr(-2) + '-' + dt.getDate();
                }
                this.menu.datePickerValue = function(s, d) { return this.date; }
                this.menu.scheduleDate = function(s, d) { return this.date; }
                this.menu.sections['datepicker'] = {'label':'Calendar', 'type':'datepicker', 
                    'livesearch':'yes', 'livesearchtype':'appointments', 
                    'livesearchempty':'no', 'livesearchcols':2, 
                    'addTxt':'Add',
                    'addTopFn':'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'add\':"appointment"});',
                    'search':{
                        'method':'ciniki.calendars.search',
                        'args':[],
                        'container':'appointments',
                        'searchtype':'appointments',
                        'cols':2,
                        'cellClasses':['multiline slice_0', 'schedule_appointment'],
                        'cellColours':[
                            '\'\'',
                            'if( d.colour != null && d.colour != \'\' ) { d.colour; } else { \'#77ddff\'; }'
                            ],
                        'cellValues':[
                            'if( d.start_ts == 0 ) { "unscheduled"; } '
                                + 'else if( d.appointment_allday == "yes" ) { d.start_date.split(/ [-]+:/)[0]; } '
                                + 'else { \'<span class="maintext">\' + d.start_date.split(/ [0-9]+:/)[0] + \'</span><span class="subtext">\' + d.start_date.split(/, [0-9][0-9][0-9][0-9] /)[1] + \'</span>\'}',
                            'var t="";'
                            + 'if( d.secondary_colour != null && d.secondary_colour != \'\') {'
                                + 't+=\'<span class="colourswatch" style="background-color:\' + d.secondary_colour + \'">\';'
                                + 'if( d.secondary_colour_text != null && d.secondary_colour_text != \'\' ) {'
                                    + 't += d.secondary_colour_text; '
                                + '} else {'
                                    + 't += \'&nbsp;\'; '
                                + '} '
                                + 't += \'</span> \''
                            + '} '
                            + 't += d.subject;'
                            + 'if( d.secondary_text != null && d.secondary_text != \'\' ) {'
                                + 't += \' <span class="secondary">\' + d.secondary_text + \'</span>\';'
                            + '} '
                            + 't;',
                            ],
                        'submit':{'method':'ciniki.calendars.main', 'args':{'search':'search_str'}},
                        },
                    'fn':'M.ciniki_tenants_main.menu.showSelectedDayCb',
                    'flexcolumn':2,
                    'flexgrow':5,
                    'minwidth':'20em',
                    'width':'20em',
                    'hint':'Search',
                    'headerValues':null,
                    'noData':'No appointments found',
                    };
                this.menu.sections['schedule'] = {'label':'', 'type':'dayschedule', 'calloffset':0,
                    'flexcolumn':2,
                    'flexgrow':5,
                    'minwidth':'20em',
                    'width':'20em',
                    'start':'8:00',
                    'end':'20:00',
                    'notimelabel':'All Day',
                    };
                this.menu.showSelectedDayCb = function(i, scheduleDate) {
                    var h = M.gE(this.panelUID + '_datepicker_calendar');
                    if( h != null ) { 
                        this.toggleDatePickerCalendar(scheduleDate, null);
                    }
                    if( scheduleDate != null ) { this.date = scheduleDate; }
                    M.api.getJSONBgCb('ciniki.calendars.appointments', 
                        {'tnid':M.curTenantID, 'date':this.date}, function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            var p = M.ciniki_tenants_main.menu;
                            p.data.schedule = rsp.appointments;
                            p.refreshSection('datepicker');
                            p.refreshSection('schedule');
                        });
                }
                this.menu.appointmentEventText = function(ev) {
                    var t = '';
                    if( ev.secondary_colour != null && ev.secondary_colour != '' ) {
                        t += '<span class="colourswatch" style="background-color:' + ev.secondary_colour + '">';
                        if( ev.secondary_colour_text != null && ev.secondary_colour_text != '' ) { t += ev.secondary_colour_text; }
                        else { t += '&nbsp;'; }
                        t += '</span> '
                    }
                    t += ev.subject;
                    if( ev.secondary_text != null && ev.secondary_text != '' ) {
                        t += ' <span class="secondary">' + ev.secondary_text + '</span>';
                    }
                    return t;
                }
                this.menu.appointmentTimeFn = function(d, t, ad) {
                    if( M.curTenant.modules['ciniki.fatt'] != null ) {
                        return 'M.startApp(\'ciniki.fatt.offerings\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'add\':\'courses\',\'date\':\'' + d + '\',\'time\':\'' + t + '\',\'allday\':\'' + ad + '\'});';
                    } else {
                        return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'add\':\'appointment\',\'date\':\'' + d + '\',\'time\':\'' + t + '\',\'allday\':\'' + ad + '\'});';
                    }
                };
                this.menu.appointmentFn = function(ev) {
                    if( ev.app != null ) {
                        return 'M.startApp(\'' + ev.app + '\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'appointment_id\':\'' + ev.id + '\'});';
                    } else {
                        if( ev.module == 'ciniki.wineproduction' ) {
                            return 'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'appointment_id\':\'' + ev.id + '\'});';
                        } 
                        if( ev.module == 'ciniki.atdo' ) {
                            return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'atdo_id\':\'' + ev.id + '\'});';
                        }
                        if( ev.module == 'ciniki.fatt' ) {
                            return 'M.startApp(\'ciniki.fatt.offerings\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'appointment_id\':\'' + ev.id + '\'});';
                        }
                    }
                    return '';
                };
                this.menu.calTimeout = null;
                this.menu.loadCalendar = function() {
                    M.api.getJSONBgCb('ciniki.calendars.appointments', {'tnid':M.curTenant.id, 'date':M.ciniki_tenants_main.menu.date},
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            var p = M.ciniki_tenants_main.menu;
                            p.data.schedule = rsp.appointments;
                            p.refreshSection('schedule');
                            if( p.calTimeout != null ) {
                                clearTimeout(p.calTimeout);
                            }
                            p.calTimeout = setTimeout(M.ciniki_tenants_main.menu.loadCalendar, (5*60*1000));
                        });
                }
                this.menu.loadCalendar();
            } else {
                if( this.menu.calTimeout != null ) {
                    clearTimeout(this.menu.calTimeout);
                }
            }
            if( M.modFlagAny('ciniki.atdo', 0x20) == 'yes' ) {
                this.menu.sections._messages = {'label':'Messages', 'visible':'yes', 'type':'simplegrid', 'num_cols':1,
                    'flexcolumn':3,
                    'flexgrow':2,
                    'limit':10,
                    'changeTxt':'View Messages',
                    'changeFn':'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'messages\':\'yes\'});',
                    'minwidth':'20em',
                    'width':'20em',
                    'cellClasses':['multiline'],
                    'noData':'Loading...',
                    'addTxt':'Add',
                    'addTopFn':'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'add\':\'message\'});',
                    };
                this.menu.messagesTimeout = null;
                this.menu.loadMessages = function() {
                    M.api.getJSONBgCb('ciniki.atdo.messagesList', {'tnid':M.curTenant.id, 
                        'assigned':'yes', 'status':'open'}, function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            var p = M.ciniki_tenants_main.menu;
                            p.data._messages = rsp.messages;
                            p.sections._messages.noData = 'No messages';
                            p.refreshSection('_messages');
                            if( p.messagesTimeout != null ) {
                                clearTimeout(p.messagesTimeout);
                            }
                            p.messagesTimeout = setTimeout(M.ciniki_tenants_main.menu.loadMessages, (5*60*1000));
                        });
                }
                this.menu.loadMessages();
            } else {
                if( this.menu.calTimeout != null ) {
                    clearTimeout(this.menu.calTimeout);
                }
            }
            if( M.modFlagAny('ciniki.atdo', 0x02) == 'yes' ) {
                this.menu.sections._tasks = {'label':'Tasks', 'visible':'yes', 'type':'simplegrid', 'num_cols':3,
                    'flexcolumn':3,
                    'flexgrow':2,
                    'limit':10,
                    'minwidth':'20em',
                    'width':'20em',
                    'headerValues':['', 'Task', 'Due'],
                    'cellClasses':['multiline aligncenter', 'multiline', 'multiline'],
                    'noData':'Loading...',
                    'changeTxt':'View Tasks',
                    'changeFn':'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'tasks\':\'yes\'});',
                    'addTxt':'Add',
                    'addTopFn':'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'add\':\'task\'});',
                    };
                // Need to query enough rows to get at least 10 including assigned users, average 5 employees assigned.
                this.menu.tasksTimeout = null;
                this.menu.loadTasks = function() {
                    M.api.getJSONBgCb('ciniki.atdo.tasksList', {'tnid':M.curTenant.id,
                        'assigned':'yes', 'status':'open', 'limit':50},
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            var p = M.ciniki_tenants_main.menu;
                            p.data._tasks = rsp.tasks;
                            p.sections._tasks.noData = 'No tasks';
                            p.refreshSection('_tasks');
                            if( p.tasksTimeout != null ) {
                                clearTimeout(p.tasksTimeout);
                            }
                            p.tasksTimeout = setTimeout(M.ciniki_tenants_main.menu.loadTasks, (5*60*1000));
                        });
                }
                this.menu.loadTasks();
            } else {
                if( this.menu.calTimeout != null ) {
                    clearTimeout(this.menu.calTimeout);
                }
            }
            if( M.modOn('ciniki.timetracker') ) {
                this.menu.data._timetracker_projects = {};
                this.menu.data._timetracker_entries = {};
                this.menu.sections._timetracker_projects = {'label':'Time Tracker', 'type':'simplegrid', 'num_cols':3,
                    'minwidth':'20em',
                    'flexcolumn':4,
                    'flexgrow':1,
                    'maxwidth':'30em',
                    'cellClasses':['', '', 'alignright'],
                    'footerClasses':['', '', 'alignright'],
                    'noData':'Loading...',
                    };
                this.menu.sections._timetracker_entries = {'label':'Recent', 'type':'simplegrid', 'num_cols':3,
                    'minwidth':'20em',
                    'flexcolumn':4,
                    'flexgrow':1,
                    'maxwidth':'30em',
                    'cellClasses':['multiline', 'multiline', ''],
                    'limit':15,
                    'noData':'Loading...',
                    };
                this.menu.startEntry = function(id) {
                    M.api.getJSONBgCb('ciniki.timetracker.tracker', {'tnid':M.curTenantID, 'action':'start', 'project_id':id}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        var p = M.ciniki_tenants_main.menu;
                        p.data = rsp;
                        p.sections._timetracker_projects.label = 'Time Tracker - ' + rsp.today_length_display;
                        p.data._timetracker_projects = rsp.projects;
                        p.data._timetracker_entries = rsp.entries;
                        p.refreshSections(['_timetracker_projects', '_timetracker_entries']);
                    });
                }
                this.menu.stopEntry = function(id) {
                    M.api.getJSONCb('ciniki.timetracker.tracker', {'tnid':M.curTenantID, 'action':'stop', 'entry_id':id}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        var p = M.ciniki_tenants_main.menu;
                        p.data = rsp;
                        p.sections._timetracker_projects.label = 'Time Tracker - ' + rsp.today_length_display;
                        p.data._timetracker_projects = rsp.projects;
                        p.data._timetracker_entries = rsp.entries;
                        p.refreshSections(['_timetracker_projects', '_timetracker_entries']);
                    });
                }
                this.menu.timeTrackerTimeout = null;
                this.menu.loadTimeTracker = function() {
                    M.api.getJSONBgCb('ciniki.timetracker.tracker', {'tnid':M.curTenant.id},
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            var p = M.ciniki_tenants_main.menu;
                            p.sections._timetracker_projects.label = 'Time Tracker - ' + rsp.today_length_display;
                            p.data._timetracker_projects = rsp.projects;
                            p.data._timetracker_entries = rsp.entries;
                            p.sections._timetracker_projects.noData = 'No projects';
                            p.sections._timetracker_entries.noData = 'No entries';
                            p.refreshSections(['_timetracker_projects', '_timetracker_entries']);
                            if( p.timeTrackerTimeout != null ) {
                                clearTimeout(p.timeTrackerTimeout);
                            }
                            p.timeTrackerTimeout = setTimeout(M.ciniki_tenants_main.menu.loadTimeTracker, (1*60*1000));
                        });
                }
                this.menu.loadTimeTracker();
            } else {
                if( this.menu.timeTrackerTimeout != null ) {
                    clearTimeout(this.menu.timeTrackerTimeout);
                }
            }
        }
        //
        // Check if there should be a task list displayed
        //
        else if( M.curTenant.modules['ciniki.atdo'] != null && M.curTenant.atdo != null
            && M.curTenant.atdo.settings['tasks.ui.mainmenu.category.'+M.userID] != null 
            && M.curTenant.atdo.settings['tasks.ui.mainmenu.category.'+M.userID] != ''
            && (perms.owners != null || perms.employees != null || perms.resellers != null || (M.userPerms&0x01) == 1) 
            ) {
            this.menu.data._tasks = {};
            this.menu.sections[0].label = 'Menu';
            this.menu.sections._tasks = {'label':'Tasks', 'visible':'yes', 'type':'simplegrid', 'num_cols':3,
                'flexcolumn':3,
                'flexgrow':2,
                'minwidth':'20em',
                'maxwidth':'40em',
                'width':'20em',
                'headerValues':['', 'Task', 'Due'],
                'cellClasses':['multiline aligncenter', 'multiline', 'multiline'],
                'noData':'No tasks found',
                };
            M.api.getJSONCb('ciniki.atdo.tasksList', {'tnid':M.curTenant.id,
                'category':M.curTenant.atdo.settings['tasks.ui.mainmenu.category.'+M.userID], 'assigned':'yes', 'status':'open'},
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_tenants_main.menu;
                    if( rsp.tasks != null && rsp.tasks.length > 0 ) {
                        p.data._tasks = rsp.tasks;
                        p.refreshSection('_tasks');
                        p.size = 'medium mediumaside';
                        M.gE(p.panelUID).children[0].className = 'medium mediumaside';
                    } 
                });
        }

        //
        // Check if add to home screen should be shown
        //
//      if( M.device == 'ipad' && !window.navigator.standalone ) {
//          this.menu.sections.addtohomescreen = {'label':'', 'list':{
//              'add':{'label':'Download App', 'fn':''},
//              }},
//      }

        this.menu.refresh();
        this.menu.show();
    }
}
