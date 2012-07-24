

var PermissionManager = function  (objects){
    
    var obj = this;
 
    
    obj.objects = objects;
    
    obj.backendUrl  = permissionBackendUrl;
    obj.aros        = null;
    obj.actions     = null;
    obj.tabCount    = 0;
    
    obj.currentTab  = null;
    obj.aroToAdd    = null;
    obj.modelTabMap = new Object();
    obj.models      = new Array();
    
    obj.ajaxAction = function (action, params, successFunc){
        $.ajax({
            type: "POST",
            url: obj.backendUrl + '/' + action,
            data: params,
            success: function(data){
                var data = JSON.parse(data);
                if(data.error == true)
                    displayError(data.error_msg);
                else
                    successFunc(data.data);
            }
        });
    };
    
    obj.loadActions = function(followUp){
        //Fetch all used models
        var models = new Object();
        
        for(var i = 0; i != obj.objects.length; i++){
            var tmp = obj.objects[i];
            
            if(models[tmp.model] == undefined)
                models[tmp.model] = true;
        }
        
        obj.ajaxAction('listActions',
        {
            'models': models
        }
        , 
        function (data){
            var tmpFollow   = followUp;
            obj.actions     = data;
            followUp();
        }
        );
    };

    obj.loadAros = function(followUp){
        var params = {
            'objects' : obj.objects
            };
        obj.ajaxAction('listAros', 
            params, function(data){
                var follow = followUp;
                obj.aros = data;
                follow();
            });
    };
    
    obj.buildDialog = function(followUp){
        
        //Only build if it has not been build yet
        if(obj.currentTab == null){
            $('#permissionTabs').tabs({
                //Add the click event listener
               select : function(event, ui){
                    obj.currentTab = '#'+$(ui.panel).attr('id');
                    obj.tabCount = ui.index;
                }
            } );
            
            var firstModel = null;
            for(model in obj.aros){
                if(firstModel == null){
                    firstModel = model;
                }
                obj.models.push(model);
                obj.addTab(model, obj.aros[model]);
                
            }

            //The first tab's active
            obj.currentTab  = obj.modelTabMap[firstModel];
            obj.tabCount    = 0;
        }
        
        followUp();
    }
    
    obj.addTab = function(model, objects){
        var container = $('#permissionTabs');
        var id = 'tabs-'+ (obj.tabCount);
        container.tabs('add', '#'+id, model);
        obj.currentTab = '#'+id;
        obj.modelTabMap[model] = obj.currentTab;
        
        obj.addPermissionSelection(id);
        obj.addAroSelection(model, id);
        obj.addAutoComplete(model, id);
        obj.addEventHandlers(id);
        
        //The next one has an id, which is 1 greater
        obj.tabCount++;
          
    }
    
    
    obj.addPermissionsIcon = function(div, permissionName, permission, hasPermission){
        var img         = $('<img class="permission"></img>');
        img.attr({
           'alt'    : permission.name,
           'title'  : permission.short_desc == undefined ? 
                        permission.name : permission.short_desc,
           'src'    : hasPermission ? 
                        permission.enabled_img : permission.disabled_img
        });
        
        //Meta-data
        img.data('img-permission-name', permissionName);
        img.data('img-permission-id', permission.id);
        img.data('img-permission-granted', hasPermission);    
            
        img.appendTo(div);
    }
    
    obj.addPermissionSelection = function(id){
        
        var lft = $('<div class="pull-left selectionContainer"></div>');
        obj.addSelectionLinks(lft);
        
        var div = $('<div class="actions"></div>');
        
        for(actionName in obj.actions){
            var action = obj.actions[actionName];
            obj.addPermissionsIcon(div, actionName, action, false);
        }
        
        
        var cont = $('<div class="blockContainer"></div>');
        
        cont.append(lft);
        cont.append(div);
        cont.append($("<p style='clear:both' />"));
        $(obj.currentTab).append(cont);
    }
    
    obj.addSelectionLinks = function(cont){
        var a = $('<a href="#" class="selectAll">All</a>');
        
        var b = $('<a href="#" class="deselectAll">None</a>');
        
        a.appendTo(cont);
        b.appendTo(cont);
    }
    
    obj.addMarkerEvent   = function(selector){
        //Allows us to add the click handler also for later attached elements
        if(typeof selector == "undefined")
            selector = obj.currentTab+' .aroRow';
        
        $(selector).on('click',function(evt){
                $(this).toggleClass('marked'); 
                var local = obj;
                local.refreshOverallPermissions();
        });
    }
    
    obj.addPermissionEvent = function(selector){
        //Allows us to add the click handler also for later attached elements
        if(typeof selector == "undefined")
            selector = obj.currentTab+' .permission';
        $(selector).on('click', function(evt){
           obj.applyPermission(this);
           evt.stopImmediatePropagation();
           evt.preventDefault();
        });
    }
    
    obj.addEventHandlers = function(containerId){
        //Firstly - Permissions
        obj.addPermissionEvent();
        
        //Secondly - Marking
        obj.addMarkerEvent();
        
        //Selections
        $(obj.currentTab+' .selectAll').click(function(evt){ 
            $(obj.currentTab+' .aroRow').each(function(){
                $(this).addClass('marked'); 
                var local = obj;
                local.refreshOverallPermissions();
            });
        });
        
        $(obj.currentTab+' .deselectAll').click(function(evt){ 
            $(obj.currentTab+' .aroRow').each(function(){
                $(this).removeClass('marked'); 
                var local = obj;
                local.refreshOverallPermissions();
            });
        });
    }
    
    obj.getCurrentModel = function(){;
        return obj.models[obj.tabCount];
    }
    
    obj.setPermission = function(permission, mode, select){
        var model = obj.getCurrentModel();
        
        var params = {
            'permission'  : permission,
            'mode'        : mode,
            'selection'   : select,
            'model'       : model,
            'acos'        : obj.objects
        };

        obj.ajaxAction('setPermission', params 
            , function(data){
                //Display the results
                for(model in data.executed){
                    var modelExecutions = data.executed[model];
                    
                    for(aroId in modelExecutions){
                        var aroExecutions = modelExecutions[aroId];
                        
                        for(executionKey in aroExecutions){
                            var execution = aroExecutions[executionKey];

                            obj.renewPermission(model, execution.aro, execution.action, execution.mode);
                        }
                    }
                }
            });
        
    }
    
    obj.renewPermission = function(model, aro, action, mode){
        var tab = obj.modelTabMap[model];
        
        //javascript aberrates from the standard inhere. No idea why.
        window.model    =   model;
        window.aro      =   aro;
        window.action   =   action;
        window.mode     =   mode;
        
        //Run through all and check if it's this aro
        $(tab+' .aroRow').each(function(){
            if($(this).data('img-aro-id') == window.aro){

                //Run through the permissions and check if they're this action
                $(this).find(' .permission').each(function(){
                    if($(this).data('img-permission-name') == window.action){
                        //Okay - set the image
                        var action = obj.actions[window.action];
                        var img    = window.mode == 'true' ? action.enabled_img : action.disabled_img;
                        $(this).attr('src', img);
                        $(this).data('img-permission-granted', window.mode);
                    }
                })
            }
        })
    }
    
    obj.applyPermission = function(trigger){
        //Check if this is a single aro or the selection
        var aro = $(trigger).closest('.aroRow');
        
        //What's the new mode, grant or deny?
        var newMode = (! $(trigger).data('img-permission-granted')) ? true : false;
        var permission = $(trigger).data('img-permission-name');
        
        var selection = new Array();
        //If this is applied on the selection
        if(aro.length == 0){
            //Fetch Users
            $(obj.currentTab + ' .marked').each(function(){
                selection.push($(this).data('img-aro-id'));
            });
        }
        else{
            selection.push($(aro).data('img-aro-id'));
        }
        
        if(selection.length > 0)
            obj.setPermission(permission, newMode, selection);
    }
    
    obj.addAroSelection = function(model, id){
        var div = $('<div class="aroContainer"></div>');
            
        for(aroId in obj.aros[model])
            obj.addAroRow(div,model, obj.aros[model][aroId]);
            
        $(obj.currentTab).append(div);
    }
    
    obj.addAroRow = function(div, model, aro){
        var userRow = $('<div class="aroRow"></div>')
        
        userRow.data('img-aro-id', aro.aro.id);
        userRow.data('img-aro-model', model);

        var aroName = $('<div class="aroName">'+aro.aro.name+'</div>');

        var aroPermissionsDiv = $('<div class="aroPermissions"></div>');
        obj.addAroPermissions(aroPermissionsDiv, obj.actions, aro.actions);
            
        userRow.append(aroName);
        userRow.append(aroPermissionsDiv);
        userRow.appendTo(div);
    }
    
    obj.addAroPermissions = function(div, permissions, aroPermissions){
        for(permissionName in permissions){
            var permission      = permissions[permissionName];
            var hasPermission   = obj.hasPermission(permissionName, aroPermissions);

            obj.addPermissionsIcon(div, permissionName, permission, hasPermission);
        }
    }
    
    obj.hasPermission = function(permission, aroPermissions){
        for(var i = 0; i != aroPermissions.length; i++){
            if(aroPermissions[i].name == permission)
                return true;
        }
        return false;
    }
    
    obj.refreshOverallPermissions = function(){
        //Iterate over all the generalActions
        $(obj.currentTab+' .actions img').each(function(){
            var actionName = $(this).data('img-permission-name');
            var isGrantedForAll = true;
            var counter         = 0;

            //Now, fetch all the selected aros and check
            $(obj.currentTab+' .marked .permission').each(
                function(){
                    ///Exclude all unwanted actions
                    if($(this).data('img-permission-name') != actionName)
                        return true;
                    counter++;
              
                    var may = $(this).data('img-permission-granted');
                    if(!may)
                        isGrantedForAll = false;
                });

            //IF there's no selection - it's false
            if(counter == 0)
                isGrantedForAll = false;
           
            var action = obj.actions[actionName];
            if(isGrantedForAll)
                $(this).attr('src', action.enabled_img);
            else
                $(this).attr('src', action.disabled_img);
            $(this).data('img-permission-granted', isGrantedForAll);
        });
    }
    
    
    obj.addAutoComplete = function(model, id){
        var autoComplete = $('<input type="text" class="autoComplete" ></input>');     
        autoComplete.appendTo($('#'+id));
        
        
        $(autoComplete).autocomplete({
            source: obj.backendUrl + '/autoComplete?model=' +obj.getCurrentModel(),
            minLength: 2,
            select: function( event, ui ) {
                obj.aroToAdd = {
                  'id'      : ui.item.id,
                  'name'   : ui.item.label
                };
            }
        });
        
        //Add the button
        var button = $('<a class="pull-left btn btn-primary btn-small">Add</a>');
        button.appendTo($('#'+id));
        
        
        //Add the aro if clicked
        button.click(function(){
            if(obj.aroToAdd == null)
                alert('You have not chosen any Aro');
            else{
                
                //We need another structure
                var tmp = new Object();
                tmp.aro = obj.aroToAdd;
                //He's assumed to have no privileges in the beginning
                tmp.actions = new Array(); 

                var container = $(obj.currentTab+ ' .aroContainer');
                obj.addAroRow(
                    $(obj.currentTab+ ' .aroContainer'),
                    obj.getCurrentModel(),
                    tmp
                );
                //Shall be able to mark
                var elem = $(obj.currentTab+ ' .aroContainer .aroRow').last();
                obj.addPermissionEvent($(elem).find('.permission'));
                obj.addMarkerEvent(elem);
                obj.aroToAdd = null;
            }
        })
    }
    
    obj.showDialog = function(){
        $('#permissionDialog').dialog('open');
    }
    
    obj.destroy    = function(){
        //the uls are for a bug of jquery
        $('#permissionTabs').tabs('destroy').empty().html('<ul></ul>');
    }
    
    this.startUp = function (followUp){
        obj.loadActions(
            function(){ 
                obj.loadAros(
                    function(){ 
                        obj.buildDialog(
                            function(){
                                obj.showDialog();
                            }
                            );
                    });
            });
    };
    
};

