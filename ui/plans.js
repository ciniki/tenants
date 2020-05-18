//
// The app to manage tenants plans for a tenant
//
function ciniki_tenants_plans() {
    
    this.planFlags = {
        '1':{'name':'Public'},
        };
    
    this.menu = new M.panel('Plans',
        'ciniki_tenants_plans', 'menu',
        'mc', 'medium', 'sectioned', 'ciniki.tenants.plans.menu');
    this.menu.data = {};
    this.menu.sections = {
        'plans':{'label':'', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Plan','Monthly','Yearly','Trial'],
            },
        '_buttons':{'label':'', 'buttons':{
            '_add':{'label':'Add Plan', 'fn':'M.ciniki_tenants_plans.edit.open(\'M.ciniki_tenants_plans.menu.open();\',0);'},
            }},
    };
    this.menu.noData = function(s) { return 'No plans added'; }
    this.menu.sectionData = function(s) { return this.data; }
    this.menu.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.plan.name + (d.plan.ispublic == 'yes' ? ' (public)' : '');
            case 1: return d.plan.monthly; 
            case 2: return d.plan.yearly; 
            case 3: return d.plan.trial_days; 
        }
    }
    this.menu.rowFn = function(s, i, d) {
        return 'M.ciniki_tenants_plans.edit.open(\'M.ciniki_tenants_plans.menu.open();\',\'' + d.plan.id + '\');';
    };
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.tenants.planList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tenants_plans.menu;
            p.data = rsp.plans;
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addButton('add', 'Add', 'M.ciniki_tenants_plans.edit.open(\'M.ciniki_tenants_plans.menu.open();\',0);');
    this.menu.addClose('Back');

    //
    // The edit plan panel
    //
    this.edit = new M.panel('Edit Plan',
        'ciniki_tenants_plans', 'edit',
        'mc', 'medium', 'sectioned', 'ciniki.tenants.plans.edit');
    this.edit.data = {'status':'1'};
    this.edit.sections = {
        'info':{'label':'', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'flags':{'label':'', 'type':'flags', 'join':'yes', 'flags':this.planFlags},
            'sequence':{'label':'Sequence', 'type':'text', 'size':'small'},
            'monthly':{'label':'Monthly', 'type':'text', 'size':'small'},
            'yearly':{'label':'Yearly', 'type':'text', 'size':'small'},
            'trial_days':{'label':'Trial', 'type':'text', 'size':'small'},
            }},
        '_modules':{'label':'Modules', 'fields':{
            'modules':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_tenants_plans.edit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_tenants_plans.edit.remove();'},
            }},
        };
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.tenants.planHistory', 'args':{'tnid':M.curTenantID, 
            'plan_id':M.ciniki_tenants_plans.edit.plan_id, 'field':i}};
    }
    this.edit.open = function(cb, did) {
        this.reset();
        if( did != null ) {
            this.plan_id = did;
        }
        if( this.plan_id > 0 ) {
            this.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.tenants.planGet', {'tnid':M.curTenantID, 'plan_id':this.plan_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_tenants_plans.edit;
                p.data = rsp.plan;
                p.refresh();
                p.show(cb);
            });
        } else {
            this.reset();
            this.sections._buttons.buttons.delete.visible = 'no';
            this.data = {};
            this.refresh();
            this.show(cb);
        }
    }
    this.edit.save = function() {
        if( this.plan_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.tenants.planUpdate', {'tnid':M.curTenantID, 'plan_id':this.plan_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_tenants_plans.edit.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.tenants.planAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_tenants_plans.edit.close();
            });
        }
    }
    this.edit.remove = function() {
        M.confirm("Are you sure you want to remove the plan '" + this.data.name + "' ?",null,function() {
            M.api.getJSONCb('ciniki.tenants.planDelete', {'tnid':M.curTenantID, 'plan_id':M.ciniki_tenants_plans.edit.plan_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_tenants_plans.edit.close();
            });
        });
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_tenants_plans.edit.save();');
    this.edit.addClose('Cancel');


    this.start = function(cb, ap, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(ap, 'ciniki_tenants_plans', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.menu.open(cb);
    }
};
