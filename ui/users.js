//
//
function ciniki_tenants_users() {
    this.users = null;
    this.user = null;

    this.userFlags = {
        '1':{'name':'Name'},
        '2':{'name':'Title'},
        '3':{'name':'Phone'},
        '4':{'name':'Cell'},
        '5':{'name':'Fax'},
        '6':{'name':'Email'},
        '7':{'name':'Bio'},
        };

    this.users = new M.panel('Owners & Employees',
        'ciniki_tenants_users', 'users',
        'mc', 'xlarge', 'sectioned', 'ciniki.tenants.users');
    this.users.data = {};
    this.users.sections = {};
    this.users.cellValue = function(s, i, j, d) { 
        switch(j) {
            case 0: return d.user.firstname + ' ' + d.user.lastname;
            case 1: return d.user.title;
            case 2: return (d.user.modpermlist != null ? d.user.modpermlist : '');
        }
    }    
    this.users.rowFn = function(s, i, d) { return 'M.ciniki_tenants_users.edit.open(\'M.ciniki_tenants_users.users.open();\',\'' + s + '\',\'' + d.user.user_id + '\');'; }
    this.users.noData = function() { return 'No users'; }
    this.users.sectionData = function(s) { return this.data[s]; }
    this.users.open = function(cb) {
        //
        // Get the detail for the user.  Do this for each request, to make sure
        // we have the current data.  If the user switches tenants, then we
        // want this data reloaded.
        //
        M.api.getJSONCb('ciniki.tenants.userList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tenants_users.users;
            p.reset();
            p.data = [];

            // Add the user lists into the proper sections
            p.sections = {};
            for(i in rsp.permission_groups) {
                p.sections[i] = {'label':rsp.permission_groups[i].name,
                    'type':'simplegrid',
                    'num_cols':(rsp.permission_groups[i].name == 'Employees' ? 3 : 2),
                    'headerValues':['User', 'Title', 'Permissions'],
                    'cellClasses':['', '', ''],
                    'addTxt':'Add',
                    'addFn':'M.ciniki_tenants_users.showAdd(\'' + i + '\');',
                    };
            }
            for(i in rsp.groups) {
                if( p.sections[rsp.groups[i].group.permission_group] != null ) {
                    p.data[rsp.groups[i].group.permission_group] = rsp.groups[i].group.users;
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.users.addClose('Back');

    //
    // Edit user details
    //
    this.edit = new M.panel('User Details',
        'ciniki_tenants_users', 'edit',
        'mc', 'medium', 'sectioned', 'ciniki.tenants.users.edit');
    this.edit.data = {};
    this.edit.user_package = '';
    this.edit.user_permission_group = '';
    this.edit.sections = {
        'info':{'label':'Login', 'aside':'yes', 'list':{
            'firstname':{'label':'First', 'type':'noedit'},
            'lastname':{'label':'Last', 'type':'noedit'},
            'username':{'label':'Username', 'type':'noedit'},
            'email':{'label':'Email', 'type':'noedit'},
            'display_name':{'label':'Display', 'type':'noedit'},
            }},
        '_eid':{'label':'', 'active':'no', 'aside':'yes', 'fields':{
            'eid':{'label':'External ID', 'type':'text'},
            }},
        'details':{'label':'Contact Info', 'type':'simpleform', 'aside':'yes', 'fields':{
            'employee.title':{'label':'Title', 'type':'text'},
            'contact.phone.number':{'label':'Phone', 'type':'text'},
            'contact.cell.number':{'label':'Cell', 'type':'text'},
            'contact.fax.number':{'label':'Fax', 'type':'text'},
            'contact.email.address':{'label':'Email', 'type':'text'},
//              'employee-twitter':{'label':'Email', 'type':'text'},
            }},
//          '_web':{'label':'Web Options', 'visible':'no', 'type':'simpleform', 'fields':{
//              }},
        'modperms':{'label':'Permissions', 'visible':'no', 'fields':{
            }},
        '_image':{'label':'Image', 'active':'no', 'type':'imageform', 'fields':{
            'employee-bio-image':{'label':'', 'type':'image_id', 'controls':'all', 'hidelabel':'yes', 'history':'no'},
            }},
        '_image_caption':{'label':'', 'active':'no', 'fields':{
            'employee-bio-image-caption':{'label':'Caption', 'type':'text'},
            }},
        '_content':{'label':'Biography', 'active':'no', 'fields':{
            'employee-bio-content':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_tenants_users.edit.save();'},
            'delete':{'label':'Remove',},
            }},
        };
    this.edit.listLabel = function(s, i, d) { return d.label; }
    this.edit.listValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        if( i.match(/page-contact-user-display-flags/) ) {
            return {'method':'ciniki.web.pageSettingsHistory', 'args':{'tnid':M.curTenantID, 'field':i}};
        } else {
            return {'method':'ciniki.tenants.userDetailHistory', 'args':{'tnid':M.curTenantID, 
                'user_id':this.user_id, 'field':i}};
        }
    }
    this.edit.addDropImage = function(iid) {
        this.setFieldValue('employee-bio-image', iid);
        return true;
    };
    this.edit.deleteImage = function(fid) {
        this.setFieldValue('employee-bio-image', 0);
        return true;
    };
    this.edit.open = function(cb, s, uid, mod) {
        if( uid != null ) { this.user_id = uid; }
        if( s != null ) { 
            var g = s.split('.');
            this.package = g[0];
            this.permission_group = g[1];
        }
        this.sections._buttons.buttons.delete.fn = 'M.ciniki_tenants_users.edit.remove(' + this.user_id + ');';
        M.api.getJSONCb('ciniki.tenants.userDetails', {'tnid':M.curTenantID, 'user_id':this.user_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tenants_users.edit;
            if( M.curTenant.modules['ciniki.web'] != null && M.curTenant.modules['ciniki.web'].status == 1 ) {
                p.sections._image.active = 'yes';
                p.sections._content.active = 'yes';
                if( rsp.user['employee-bio-image-caption'] != null && rsp.user['employee-bio-image-caption'] != '' ) {
                    p.sections._image_caption.active = 'yes';
                    p.sections._image_caption.fields['employee-bio-image-caption'].active = 'yes';
                } else {
                    p.sections._image_caption.visible = 'no';
                    p.sections._image_caption.fields['employee-bio-image-caption'].active = 'no';
                }
            } else {
                p.sections._image.active = 'no';
                p.sections._image_caption.active = 'no';
                p.sections._content.active = 'no';
            }
            // Setup extra modperms
            if( p.permission_group == 'employees' && rsp.modperms != null ) {
                p.sections.modperms.fields = {};
                for(var i in rsp.modperms) {
                    p.sections.modperms.fields[i] = {
                        'label':rsp.modperms[i].label,
                        'type':rsp.modperms[i].type != null && rsp.modperms[i].type == 'toggle' ? 'multitoggle' : 'multiselect', 
                        'none':'yes',
                        'options':rsp.modperms[i].perms,
                        'toggles':rsp.modperms[i].perms,
                        };
                }
                p.sections.modperms.visible = 'yes';
                p.size = 'medium mediumaside';
            } else {
                p.size = 'medium';
            }
            p.data = rsp.user;
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.tenants.userUpdateDetails', {'tnid':M.curTenantID, 'user_id':this.user_id, 'permission_group':this.permission_group}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_tenants_users.edit.close();
            });
        } else {
            this.close();  
        }
    }
    this.edit.remove = function(id) {
        if( id != null && id > 0 ) {
            var usertype = 'Owner';
            if( this.permission_group == 'employees' ) {
                usertype = 'Employee';
            }
            M.confirm('Are you sure you want to remove this user as an ' + usertype + '?',null,function() {
                M.api.getJSONCb('ciniki.tenants.userRemove', {'tnid':M.curTenantID, 'user_id':id, 
                    'package':M.ciniki_tenants_users.edit.package, 
                    'permission_group':M.ciniki_tenants_users.edit.permission_group}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_tenants_users.users.open();
                    });
            });
        }
        return false;
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_tenants_users.edit.save();');
    this.edit.addClose('Cancel');

    this.start = function(cb, ap, aG) {
        args = {}
        if( aG != null ) { args = eval(aG); }
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(ap, 'ciniki_tenants_users', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 
        
        this.edit.sections._eid.active = M.modFlagSet('ciniki.tenants', 0x010000);

        if( args.user_id != null && args.user_id > 0 ) {
            this.edit.open(cb, null, args.user_id, null);
        } else {
            this.users.open(cb);
        }
    }

    this.showAdd = function(s) {
        var g = s.split('.');
        this.cur_package = g[0];
        this.cur_permission_group = g[1];
        M.startApp('ciniki.users.add',null,'M.ciniki_tenants_users.addUser(data);');
    };

    // 
    // Submit the form
    //
    this.addUser = function(data) {
        if( data != null && data.id > 0 ) {
            M.api.getJSONCb('ciniki.tenants.userAdd', {'tnid':M.curTenantID, 'user_id':data.id, 
                'package':this.cur_package, 'permission_group':this.cur_permission_group}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_tenants_users.users.open();
                });
        } else {
            M.ciniki_tenants_users.users.open();
        }
//      return false;
    }

}
