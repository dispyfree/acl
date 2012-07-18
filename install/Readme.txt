Contents:

1) Preliminaries: What does this extension do?
- How does ACL work?
- Features of this extension
- Limitations of this extension
- How does ACL differ from RBAC?
- Installation
2) How can I use this extension? 
3) A word on the configuration
4) Possible Improvements
5) Known Bugs**
6) Versions




1) What does this extension do?
--------------------------------
    
  This extension provides a full-fledged implementation of ACL-based access     control. It's ideas are heavily leaned on the way cakePHP implements ACL, but extended both in convenience and power.  
    
    
### How does ACL work?

    
In the following I'll only elucidate the really important concepts inherent to ACL - and I'll try to be as brief as possible. For detailed description of how ACL works, delve into specialized texts.
    
Every ACL-system consists of at least two types: the ACOs (Access Control Objects) and AROs (Access Request Objects).
ACOs are all the objects which are accessed (=> passive behavior), in particular on which specific actions are performed. Their counterpart are the AROs. Those objects take the active part, they try to perform specific actions on the ACOs. 
                                                                                                   
Each single ARO-node can be linked to several ACOs by a Permission. All connections between AROs and ACOs also indicate what actions may be performed by the involved ARO-node on the involved ACO-node.  In addition to that, both ACOs and AROs may be located in a hierarchy. Therefore they can have parents, inheriting the permissions of the parent node.
    
If an action is to be performed, the permission lookup is performed in three stages:

1. **A general permission** lookup (Maybe an Aro is permitted to perform Action X always (for all models), or always on that model)

2. **The regular ACL-Lookup** (does such a permission exist?)

3. **ACL-Lookup with enabled Business-Rules **(does a permission exist to which a connection exists in case Business Rule X applies on either Aco Y or Aro Z?)
    
    
    
### Features

    
- multiple groups for each node (both ACO and ARO) 
- Unlimited depth in hierarchy
- Business-rules for both Acos and Aros [optional]
- General Permission Sets [optional]
- fine-grained control of the grant-permission [optional]
- supports also access control filters with backward-compatible syntax [optional]
- non-recursive permission lookup 
- convenient, integrated interface
- transactions as well as exceptions upon crucial errors
    
### Limitations

- Due to parametrized static method calls this extension is limited to PHP versions >= 5.3.0. 
- this extension supports solely positive rights-management
 => the access check only checks if you are explicitely allowed to do something,  it doesn't checkif you are explicitely _denied_ access

- A fellow told me that INNER JOINs have a different syntax (regarding `` and so on) on different DBMS. The extension was developed with MySQL and was never tested with competitors such as Oracle or PGSQL but it should be an easy task to adjust the things if I knew what to change.

- The system currently requires numerical primary keys which are all named "id" for both your ACOs and AROs. I keep my eyes peeled but nothing has shown up yet to remedy that in an easy way.
    
    
### How does ACL differ from RBAC?

    
As the name already states, Role-based Access Control grants or denies permissions through roles. 
Access Control Lists uses a more general approach using generalized objects and their relations. In general, ACL is more suited if the access control has to be fine-grained at the object-/document-level. That is, if specific permissions to specific ACOs attached to AROs take precedence over specific permissions to types of ACOs attached to specific users. 
    
The gist is that the builtin Yii RBAC-system only allows so-called business-rules to check if a given object "belongs" to the given user (or one of his groups).
This has several drawbacks:

1) In order to check the business-rules Yii has to load every (possible suitable) single rule out of the database and execute it (it's plain PHP-code)

2) Those rules are fixed (PHP-expressions). Therefore one is not able to alter the permissions dynamically without rewriting all the rules.

3) It's a bad habit to store PHP-Code in the database. 

4) Those rules tend to be broader and thus there is no really convient "fine-grained" control possible. Of course it's possible to achieve the same things possible with ACL using RBAC. Even with a Smart you can reach 70 miles/h.
        
_**In fact, everything you can do with RBAC is also possible with ACL.**_

The Business-Rules of this ACL-extension are:

1) Not stored in the database but in a dedicated class.

2) Faster than RBAC ones because only the groups having an alias (=> in most cases only the ones with Business-rules) are loaded. 
        
        
### Installation

    
1) Copy the files in your "modules"-section of your application
2) Edit main.php as follows:

~~~
[php]
'import'=>array(
        'application.modules.acl.components.*',
        'application.modules.acl.models.*'
      )
~~~


3) Create the necessary tables using sql.txt
4) You are done.
**

Installation of the Access Control Filter: **
(It's not possible to introduce a better procedure, as the core development team has rejected to make things protected.)
    
First, you have to update the framework itself. Go to framework/web/auth/CAccessControlFilter.php
and change line 67 to:

~~~
[php]
protected $_rules=array();
~~~    

In order to use the new Filter, overwrite the method "filterAccessControl" in your controllers:

~~~
[php]
public function filterAccessControl($filterChain) {
            $filter = new ExtAccessControlFilter;
            $filter->setRules($this->accessRules());
            $filter->filter($filterChain);
        }
~~~      

You are done. There are no changes in syntax or behavior to keep in mind. Roles correspond to User-Groups (and thus Aro-Objects). Everything works just as it did before.
        
        
2) How can I use this extension? 
---------------------------------

    
### Configuration:
      
In order to use this feature, you have add the "RequestingActiveRecordBehavior" to your Model or you can derive your requesting model 
(usually the User) from "RequestingActiveRecord" instead of "CActiveRecord".
(see RestrictedActiveRecord::model)
 e.g.:       

~~~
[php]
class User extends RequestingActiveRecord{ ... }
~~~


Secondly, you do the same with the objects to restrict access on - you just use "RestrictedActiveRecordBehavior" as the behavior or "RestrictedActiveRecord" as the base class. 

Please note that all of your objects you are going to deal with (especially aros) have to exist in the database _before_ you can perform any actions on/with them.
Note that whenever an object is used with a method supporting autoLoading (basically all of join, leave, is, grant, may), the objects are accordingly created or saved as of version 0.4.2. 

Keep in mind that you _need_ to assign permissions for a created object (aco) to a specific aro-object unless you are using a special setup with generalPermissions and/or Business-rules. And in order to do that, you must do one of the following steps _before_ you create an object, unless you have such an aforementioned setup:

1. You can login as a User so that Yii::app()->user is set up accordingly. It's not sufficient to set the CUserIdentity manually, you'll need a corresponding object in the database (see RestrictedActiveRecord::$model)

2. You can also set RestrictedActiveRecord::$inAttendance to a random CActiveREcord-instance or a direct ACL-Object

From now on, whenever you try to to select, update or delete records using the ActiveRecord-objects, access will be automatically checked and you will only get the objects to which the user has access.
Feel free to do such stuff:	  

~~~
[php]
$img = Image::model()->find(...)  // is this my object?
		  $img->key = value;
		  $img->save();                     //May I update this object? (notice: an exception is thrown 
                                        // if rules are violated)
		  
		  $images = Image::model()->findAll(...) //You only get the ones you have access to
~~~

That's it. You don't believe it? Just try it:->
		    
Take a glance at the behaviors:
- by default, every user can create a new object of every type (see the configuration on how to change this)
- by default, a user has all rights on the objects he himself created (see the configuration on how to change this)

- if the user is a guest, his rights are equal to the group named "Guest" (see the configuration on how to change this)

      	 => if you don't create such a group and assign rights, he has no rights

      	 => assign all rights for guests to this group and NOWHERE else

         => you can bypass the Access Check setting  RestrictedActiveRecord::byPassCheck to true.

         => you can "view" the database through the "glasses" of any other ARO than the current user: take a squint at RestrictedActiveRecord::inAttendance
         
This may be necessary in certain circumstances, for example if guests can create objects  and may update them (employing an authentication mechanism such as cookies) but he himself isn't represented by any dedicated ARO-object (because he is a guest). To anyway allow him to update his own objects, you can bypass the check easily. Alternatively, you could also use Business-Rules (later more on this).
		    
        
             
### Rights Management    

~~~
[php]
//Recall: we need the activeRecord, not the identity
        $user = User::model()->findByPk(Yii::app()->user->id);
        
        //$activeRecord can be of any type derived vom CActiveRecord
        $obj  = new $activeRecord();
        $obj->save();      
		  
        //See later for actions 
        $user->grant($obj, $actions);
        $user->deny($obj, $actions);
        $user->may($obj, $action);
        
        //$group can be any valid alias-string             
        $group = "MyGroup";
        
        //checks whether the user is in the given group
        $user->is($group);
        
        //note: if strict-mode is disabled, the group will automatically be created upon the first access
        //otherwise, you have to use the RGroup-Class to create the group
        $user->join($group);
        $user->leave($group);
        
        // Note that it is possible to deny or grant rights multiple times, but it won't 
        // have any effect but letting the performance drop. 
~~~

    

### Identification of objects

Every ACL-Object (Aro or Aco) can be dinstinguished using three techniques:

a) The object itself (it's ID). This ID is automatically created when the object
   is instantiated. Most times you won't use this direct one. If you bother to     do, use RGroup or CGroup.

b)  Using the unique ActiveRecord - Identification: Most ACL-Objects correspond to a real object in your database. You can therefore identify the ACL-Objects by their associated objects: array('model' => 'yourModel', 'foreign_key' => 'aNumericalID');

c) Using an Alias.   
Not all ACL-Objects must directly correspond to real objects. Especially if you perform grouping, you may want to have virtual groups you don't have in your real application. For example "Guest" is such a group: If you don't use it anywhere else, why should you create a corresponding object in your database?


You can create such groups using CGroup (ControlGroup => for AccessControlObjects) and RGroup(RequestGroup => for AccessRequestObjects); both are more or less regular models.
Note that you can assign aliases to every of your restricted or requesting objects, even if they don't represent a group.

Example:
~~~
[php] 
  
  $cGroup = new CGroup();
  $cGroup->alias = 'MyAlias';
  $cGroup->save();
  
  $aco = Image::model()->find(...);
  $aco->join($cGroup);
  // OR
  $aco->join('MyAlias');
~~~  
        
        
### Actions


Actions are represented by the Model "Action" and reside in {{action}}.
The shipped actions are "create", "read", "update", "delete" and "grant". They should neither be renamed nor removed, as the automatic functionality relies on them. In order to add a possible action, just add a row to your database. 
This extension supports the wildcard '*', which will automatically be converted into all available actions.
Please note that you can limit the actions which can be performed on a specific RestrictedActiveRecord by setting it's possibleActions (it's a static var!). 
Examples:
~~~
[php]

  $aro->grant($aco, array('read', 'update'));
  $aro->grant($aco, 'read, update');
  $aro->grant($aco, '* - create, delete'); //Corresponds to the preceding example if $aco::possibleActions = array('create', 'read', 'update', 'delete');
~~~

Please keep in mind that "*" is resolved immediately, so if you add actions later on, you will need to update your objects you granted "*" to.


### General Permissions


General Permissions come in different flavors:
Firstly there are the very general permissions defined in config.php: 'generalPermissions'. These are Permissions allowed indiscriminately for all aros on all acos. By default, this is only 'create'. 

Secondly, there are the Model-specific general permissions. Models are represented by (mostly) CGroups, whose alias equals the model name. 
This way, you can grant aros the permission to perform action X on all acos of Type Y, with for example:

~~~
[php]
$aro->grant('Post', 'comment');
~~~

Note that you must enable this behavior by setting 'enableGeneralPermissions' to true. 


### Business-Rules

 
There's an extra article about Business-Rules and their relation to RBAC: [Business-Rules and RBAC](http://www.yiiframework.com/wiki/346/acl-and-rbac/ "Business-Rules and RBAC").
If you use Business-Rules, please keep in mind that:

1. They are not applied on find-lookups. You*ll have to do it yourself. 

2) They don't show up whenever you perform actions on the hierarchy - for example searching all children of a given group.

There is one special Business-Rule: The "is"-Business rule: If we want to check if an Aro is a node of a given other Aro, the action is simply 'is', the passed Aro is the alleged child object and the aco is the alleged parent object.


### Grant-Restriction


There are two differently fine-grained options to restrict the grant-permission:

You can enable the general grant-restriction so that nobody can simply grant rights on random objects by setting 'enableGrantRestriction' to true. Instead, you'll have to grant some Aro the grant-permission before he can grant anything. 
Please be aware that nobody might have any access to a given object if the grant-Permission is not in the autoPermissions. 

The second, very fine-grained option is to grant specific grant-permissions. To use this, you have to activate it using 'enableSpecificGrantRestriction' => true first. The grant-permissions are in fact simply new actions following this scheme: "grant_MyAction" where MyAction is a regular Action. If you grant someone '*' as the autoPermissions, he will automatically have all permissions. However, he will have none if this behavior is activated and he has only the general "grant"-privilege. 
If you restrict the actions on a model using the Class::$possibleActions var, please be aware that you **must** mention the grant-actions there explicitely. Otherwise _nobody_ will be allowed to grant this specific action. Consequently, the Permission to grant somebody else to grant another one Action X is "grant_Xgrant_X".


3) A word on the configuration
------------------------------

The config.php is always located in the strategy's  directory, under "components/strategies/MyStrategy/../config.php"

~~~
[php]

return array(
    
    /**
     * The Prefix of your classes
     * This is specific to the strategy and must not be changed
     * @see Strategy::getClass 
     */
    'prefix' => 'Pm',
    
    /**
     * Enables or disables the strict Mode
     * If the strict mode is disabled, objects which are not found are simply created
     * on the fly (Access Control Objects as well as Access Request Objects) 
     * There's actually no reason to enable the strict Mode, besides testing.
     */
    'strictMode' => false,
    
    /**
     * The permissions of the Guest-Group will used whenever the requesting object
     * cannot be determined (for example if the user isn't logged in)
     * If you want to disable the usage of Guest-groups completely, just set ot to 
     * NULL 
     */
    'guestGroup' => 'Guest',
    /**
     * Enables the access-check in two layers: if enabled, the access will be firstly checked
     * against a general permission-system and only (and only if) if that returns false, the 
     * regular lookup will take place  
     */
    'enableGeneralPermissions' => false,
    /**
     * Enables the business rules for all actions (automatical lookup) _except_ the read-action
     */
    'enableBusinessRules' => false,
    /**
     * Sets the direction in which business-rules are applied. Default is to check
     * both sides 
     * possible values: "both", "aro" and "aco"
     */
    'lookupBusinessRules' => 'both',
    /**
     * Defines which permissions are automatically assigned to the creator of an object upon its creation
     * default: all
     * You can overwrite this using the autoPermissions-value of each object 
     */
    'autoPermissions' => '*',
    /**
     * Enables the restriction of the grant-action
     * Note: If you enable this and assign no autoPermissions at all, only general permissions will
     * grant something at all 
     */
    'enableGrantRestriction' => false,
    /**
     * Enables you to restrict WHAT an Aro can grant, if it can grant something at all
     */
    'enableSpecificGrantRestriction' => false,
    /**
     * Permissions which are allowed for all the users on all objects
     * default: only create 
     */
    'generalPermissions' => array('create'),
);

~~~

       

4) Possible Improvements
-------------------------
      
- introduce domains
Currently, all objects of all models (aco, aro) are stored in the same tables. If you have different types of objects changing more or less often (images change faster than groups or user), it would improve the performance drastically to store both in separate tables. 
      
- build a full-fledged ACP
This has already been done on my behalf, but I recoded the base and the graphical interface is therefore not usable any more. This wish is actually the reason why the extension is shipped as a module. 
      


5) Known Bugs
--------------
  
  Fortunately, this chapter is empty again :->


6) Versions
--------------

0.1:

+ proper removal of associated ACL-objects on deletion of the ActiveRecord


+ bugfix for errors in conjunction with DataProviders



0.2:

+ if strict-mode was disabled, automatic creation of Aro-Collections did not work 
properly in all cases. This has been fixed.

+ The Access Control Filter has been added

+ some minor changes (most important: CRestrictedActiveRecord) which do not effect exterior



0.3:

 + fully recoded 
 
 + added switchable strategies (shipped strategy: Path Materialization) 
 
 + abstracted actions 
 
 + enabled limitation of actions and a new syntax for action-definitions
 
 + added behaviors
 
 + introduced several more convenient interfaces



0.4:

 + Business Rules

 + General Permission Sets

 + Fine-grained Permission Restriction



0.41:

+ Bugfix for generalPermissions


0.42 (Current Version):

+ added extended AutoSave facility
+ usage of CActiveRecord in RestrictedActiveRecord::$inAttendance
+ fixed autoPermissions infinite loop
+ fixed missing methods in the behavior-adapter for the behavior-interface


