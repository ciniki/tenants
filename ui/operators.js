//
//
function ciniki_tenants_operators() {
    this.operators = null;
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

    this.operators = new M.panel('Operators',
        'ciniki_tenants_operators', 'operators',
        'mc', 'medium', 'sectioned', 'ciniki.tenants.operators');
    this.operators.data = {};
    this.operators.sections = {};
    this.operators.cellValue = function(s, i, j, d) { 
        switch(j) {
            case 0: return d.user.username;
            case 1: return d.user.firstname + ' ' + d.user.lastname; 
            }
        }    
    this.operators.rowFn = function(s, i, d) { return 'M.ciniki_tenants_operators.edit.open(\'M.ciniki_tenants_operators.operators.open();\',\'' + s + '\',\'' + d.user.user_id + '\');'; }
    this.operators.noData = function() { return 'No operators'; }
    this.operators.sectionData = function(s) { return this.data[s]; }
    this.operators.open = function(cb) {
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
            var p = M.ciniki_tenants_operators.operators;
            p.reset();
            p.data = [];

            // Add the user lists into the proper sections
            p.sections = {};
            for(i in rsp.permission_groups) {
                p.sections[i] = {'label':rsp.permission_groups[i].name,
                    'type':'simplegrid',
                    'num_cols':2,
                    'headerValues':null,
                    'cellClasses':[''],
                    'addTxt':'Add',
                    'addFn':'M.ciniki_tenants_operators.showAdd(\'' + i + '\');',
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
    this.operators.addClose('Back');

    //
    // Edit user details
    //
    this.edit = new M.panel('User Details',
        'ciniki_tenants_operators', 'edit',
        'mc', 'medium', 'sectioned', 'ciniki.tenants.operators.edit');
    this.edit.data = {};
    this.edit.user_package = '';
    this.edit.user_permission_group = '';
    this.edit.sections = {
        '_username':{'label':'Callsign', 'fields':{
            'username':{'hidelabel':'yes', 'type':'text', 'livesearch':'no'}, 
            }},
        '_email':{'label':'Email Address', 'fields':{
            'email':{'hidelabel':'yes', 'type':'email', 'livesearch':'no'},
            }},
        'name':{'label':'Contact', 'fields':{
            'firstname':{'label':'First', 'type':'text'},
            'lastname':{'label':'Last', 'type':'text'},
            'display_name':{'label':'Display', 'type':'text'},
            }},
//        'info':{'label':'Login', 'list':{
//            'username':{'label':'Callsign', 'type':'noedit'},
//            'email':{'label':'Email', 'type':'noedit'},
//            'firstname':{'label':'First', 'type':'noedit'},
//            'lastname':{'label':'Last', 'type':'noedit'},
//            'display_name':{'label':'Display', 'type':'noedit'},
//            }},
        '_eid':{'label':'', 'active':'no', 'fields':{
            'eid':{'label':'External ID', 'type':'text'},
            }},
/*        'details':{'label':'Contact Info', 'type':'simpleform', 
            'visible':function() { return M.curTenant.hamMode == null || M.curTenant.hamMode != 'yes' ? 'yes' : 'no'; },
            'fields':{
                'employee.title':{'label':'Title', 'type':'text'},
                'contact.phone.number':{'label':'Phone', 'type':'text'},
                'contact.cell.number':{'label':'Cell', 'type':'text'},
                'contact.fax.number':{'label':'Fax', 'type':'text'},
                'contact.email.address':{'label':'Email', 'type':'text'},
//              'employee-twitter':{'label':'Email', 'type':'text'},
            }}, */
//          '_web':{'label':'Web Options', 'visible':'no', 'type':'simpleform', 'fields':{
//              }},
/*        '_image':{'label':'Image', 'active':'no', 'type':'imageform', 'fields':{
            'employee-bio-image':{'label':'', 'type':'image_id', 'controls':'all', 'hidelabel':'yes', 'history':'no'},
            }},
        '_image_caption':{'label':'', 'active':'no', 'fields':{
            'employee-bio-image-caption':{'label':'Caption', 'type':'text'},
            }},
        '_content':{'label':'Biography', 'active':'no', 'fields':{
            'employee-bio-content':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea', 'size':'large'},
            }}, */
        '_buttons':{'label':'', 
//            'visible':function() { return M.curTenant.hamMode == null || M.curTenant.hamMode != 'yes' ? 'yes' : 'no'; },
            'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_tenants_operators.edit.save();'},
                'passwd':{'label':'Set Password', 
                    'visible':function() { return (M.userPerms&0x01) == 0x01 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_tenants_operators.edit.setPassword();',
                    },
//                'edit':{'label':'Edit', 
//                    'visible':function() { return (M.userPerms&0x01) == 0x01 ? 'yes' : 'no'; },
//                    'fn':'M.startApp("ciniki.sysadmin.user",null,"M.ciniki_tenants_operators.edit.open();","mc",{"id":M.ciniki_tenants_operators.edit.user_id});',
//                    },
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
    }
    this.edit.deleteImage = function(fid) {
        this.setFieldValue('employee-bio-image', 0);
        return true;
    }
    this.edit.setPassword = function() {
        var newpassword = prompt("New password:", "");
        if( newpassword != null && newpassword != '' ) {
            M.api.postJSONCb('ciniki.users.setPassword', {'user_id':this.user_id}, 'password='+encodeURIComponent(newpassword),
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.alert('Password set');
                });
        } else {
            M.alert('No password specified, nothing changed');
        }
    }
    this.edit.open = function(cb, s, uid, mod) {
        if( uid != null ) { this.user_id = uid; }
        if( s != null ) { 
            var g = s.split('.');
            this.package = g[0];
            this.permission_group = g[1];
        }
        this.sections._buttons.buttons['delete'].fn = 'M.ciniki_tenants_operators.edit.remove(' + this.user_id + ');';
        M.api.getJSONCb('ciniki.tenants.userDetails', {'tnid':M.curTenantID, 'user_id':this.user_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tenants_operators.edit;
/*            if( M.curTenant.modules['ciniki.web'] != null ) {
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
            } */
            p.data = rsp.user;
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
/*            M.api.postJSONCb('ciniki.users.userUpdate', {'user_id':this.user_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sysadmin_user.edit.close();
            }); */
            M.api.postJSONCb('ciniki.tenants.userUpdateDetails', {'tnid':M.curTenantID, 'user_id':this.user_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_tenants_operators.edit.close();
            });
        } else {
            this.close();  
        }
    }
    this.edit.remove = function(id) {
        if( id != null && id > 0 ) {
            if( confirm('Are you sure you want to remove this user as an Operator?') ) {
                M.api.getJSONCb('ciniki.tenants.userRemove', {'tnid':M.curTenantID, 'user_id':id, 
                    'package':this.package, 'permission_group':this.permission_group}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_tenants_operators.operators.open();
                    });
            }
        }
        return false;
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_tenants_operators.edit.save();');
    this.edit.addClose('Cancel');

    //
    // The add panel
    this.add = new M.panel('Add',
        'ciniki_tenants_operators', 'add', 
        'mc', 'medium', 'sectioned', 'ciniki.tenants.operators.add');
    this.add.default_data = {};
    this.add.data = {};
    this.add.sections = {   
        'username':{'label':'Callsign', 'fields':{
            'user.username':{'hidelabel':'yes', 'type':'text', 'livesearch':'no'}, 
            }},
        'email':{'label':'Email Address', 'fields':{
            'email.address':{'hidelabel':'yes', 'type':'email', 'livesearch':'no'},
            }},
        'name':{'label':'Contact', 'fields':{
            'user.firstname':{'label':'First', 'type':'text'},
            'user.lastname':{'label':'Last', 'type':'text'},
            'user.display_name':{'label':'Display', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_tenants_operators.add.save();'},
        }},
        };
    this.add.liveSearchCb = function(s, i, value) {
        if( i == 'user.username' ) {
            M.api.getJSONBgCb('ciniki.users.searchUsername', {'tnid':M.curTenantID, 'start_needle':value, 'limit':'10'}, 
                function(rsp) { 
                    M.ciniki_tenants_operators.add.liveSearchShow(s, i, M.gE(M.ciniki_tenants_operators.add.panelUID + '_' + i), rsp.users); 
                });
            return true;
        } else if( i == 'email.address' ) {
            M.api.getJSONBgCb('ciniki.users.searchEmail', {'tnid':M.curTenantID, 'start_needle':value, 'limit':'10'}, 
                function(rsp) { 
                    M.ciniki_tenants_operators.add.liveSearchShow(s, i, M.gE(M.ciniki_tenants_operators.add.panelUID + '_' + i), rsp.users); 
                });
            return true;
        }
    };
    this.add.liveSearchResultValue = function(s, f, i, j, d) { 
        switch(f) {
            case 'email.address': return d.user.email;
            case 'user.username': return d.user.username;
        }
        return '';
    }
    this.add.liveSearchResultRowFn = function(s, f, i, j, d) { return 'M.ciniki_tenants_operators.add.close({\'id\':' + d.user.id + '});'}

    this.add.fieldValue = function(s, i, d) { return ''; }
    this.add.save = function() {
        if( this.formValue('email.username') == '' ) {
            M.alert("You must specify a callsign.");
            return false;
        }
        if( this.formValue('email.address') == '' ) {
            M.alert("You must specify a email address.");
            return false;
        }
        if( this.formValue('user.firstname') == '' ) {
            M.alert("You must specify a first name.");
            return false;
        }

        // Serialize the form data into a string for posting
        var c = this.serializeForm('yes');
        M.api.postJSONCb('ciniki.users.add', {'tnid':M.curTenantID, 'welcome_email':'yes'}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_tenants_operators.add.close({'id':rsp.id});
            });
    }
    this.add.addButton('add', 'Add', 'M.ciniki_tenants_operators.add.save();');
    this.add.addClose('Cancel');


    this.start = function(cb, ap, aG) {
        args = {}
        if( aG != null ) { args = eval(aG); }
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(ap, 'ciniki_tenants_operators', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 
        
        this.edit.sections._eid.active = M.modFlagSet('ciniki.tenants', 0x010000);
        if( (M.userPerms&0x01) == 1 ) {
            this.add.sections.email.fields['email.address'].livesearch = 'yes';
            this.add.sections.username.fields['user.username'].livesearch = 'yes';
        }

        if( args.user_id != null && args.user_id > 0 ) {
            this.edit.open(cb, null, args.user_id, null);
        } else {
            this.operators.open(cb);
        }
    }

    this.showAdd = function(s) {
        var g = s.split('.');
        this.cur_package = g[0];
        this.cur_permission_group = g[1];
        this.add.reset();
        this.add.show('M.ciniki_tenants_operators.addUser(data);');
//        M.startApp('ciniki.users.add',null,'M.ciniki_tenants_operators.addUser(data);');
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
                    M.ciniki_tenants_operators.operators.open();
                });
        } else {
            M.ciniki_tenants_operators.operators.open();
        }
    }
}
