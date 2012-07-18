Contents:

1) Preliminaries: What does this extension do?
- How does ACL work?
- Features of this extension
- Limitations of this extension
- How does ACL differ from RBAC?
- Installation
2) How can I use this extension? 
3) Internals
4) Possible Improvements
5) Known Bugs**
6) Versions




1) What does this extension do?
--------------------------------
    
  This extension provides a full-fledged implementation of ACL-based access     control. It's ideas are heavily leaned on the way cakePHP implements ACL, but extended both in convenience and power.  
    
    
- How does ACL work?
---------------------
    
In the following I'll only elucidate the really important concepts inherent to ACL - and I'll try to be as brief as possible. For detailed description of how ACL works, delve into specialized texts.
    
Every ACL-system consists of at least two types: the ACOs (Access Control Objects) and AROs (Access Request Objects).
ACOs are all the objects which are accessed (=> passive behavior), in particular on which specific actions are performed. Their counterpart are the AROs. Those objects take the active part, they try to perform specific actions on the ACOs. 
                                                                                                   
Each single ARO-node can be linked to several ACOs. All connections between AROs and ACOs also indicate what actions may be performed by the involved ARO-node on the involved ACO-node.  In addition to that, both ACOs and AROs may be located in a hierarchy. Therefore they can have parents, inheriting the permissions of the parent node.
    
If an action is to be performed, a connection between the ACO and ARO is sought, which has to grant the specified action. If no such connection is found, or if the connection does not grant the desired action, the access is denied.
    
    
    
- Features
----------------------
    
- multiple groups for each node (both ACO and ARO) 
- Unlimited depth in hierarchy
- supports also access control filters with backward-compatible syntax
- non-recursive permission lookup 
- convenient, integrated interface
- transactions as well as exceptions upon crucial errors
    
- Limitations
----------------------
- Due to parametrized static method calls this extension is limited to PHP versions >= 5.3.0. 
- this extension supports solely positive rights-management
 => the access check only checks if you are explicitely allowed to do something,  it doesn't checkif you are explicitely _denied_ access

- A fellow told me that INNER JOINs have a different syntax (regarding `` and so on) on different DBMS. The extension was developed with MySQL and was never tested with competitors such as Oracle or PGSQL but it should be an easy task to adjust the things if I knew what to change.

- The system currently requires numerical primary keys which are all named "id" for both your ACOs and AROs. I keep my eyes peeled but nothing has shown up yet to remedy that in an easy way.
    
    
- How does ACL differ from RBAC?
-------------------------------
    
As the name already states, Role-based Access Control grants or denies permissions through roles. 
Access Control Lists uses a more general approach using generalized objects and their relations. In general, ACL is more suited if the access control has to be fine-grained at the object-/document-level. That is, if specific permissions to specific ACOs attached to AROs take precedence over specific permissions to types of ACOs attached to specific users. 
    
The gist is that the builtin Yii RBAC-system only allows so-called business-rules to check if a given object "belongs" to the given user (or one of his groups).
This has several drawbacks:

1) In order to check the business-rules Yii has to load every (possible suitable) single rule out of the database and execute it (it's plain PHP-code)

2) Those rules are fixed (PHP-expressions). Therefore one is not able to alter the permissions dynamically without rewriting all the rules.

3) It's a bad habit to store PHP-Code in the database. 

4) Those rules tend to be broader and thus there is no really convient "fine-grained" control possible. Of course it's possible to achieve the same things possible with ACL using RBAC. Even with a Smart you can reach 70 miles/h.
        
        
        
- Installation
-------------
    
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


Installation of the Access Control Filter: 
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

    
Configuration:
      
In order to use this feature, you have add the "RequestingActiveRecordBehavior" to your Model or you can derive your requesting model 
(usually the User) from "RequestingActiveRecord" instead of "CActiveRecord".
(see RestrictedActiveRecord::model)
 e.g.:       

~~~
[php]
class User extends RequestingActiveRecord{ ... }
~~~


Secondly, you do the same with the objects to restrict access on - you just use "RestrictedActiveRecordBehavior" as the behavior or "RestrictedActiveRecord" as the base class. 

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
- by default, every user can create a new object of every type
- by default, a user has all rights on the objects he himself created

- if the user is a guest, his rights are equal to the group named "Guest" (see the strategy-configuration to change this)
      	 => if you don't create such a group and assign rights, he has no rights
      	 => assign all rights for guests to this group and NOWHERE else
         => you can bypass the Access Check setting  RestrictedActiveRecord::byPassCheck to true.
         => you can "view" the database through the "glasses" of any other ARO than the current user: take a squint at RestrictedActiveRecord::inAttendance
         
This may be necessary in certain circumstances, for example if guests can create objects  and may update them (employing an authentication mechanism such as cookies) but he himself isn't represented by any dedicated ARO-object (because he is a guest). To anyway allow him to update his own objects, you can bypass the check easily.
		    
        
             
- Rights Management:
---------------------------
		    
		    

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

        
        
There are three possible syntaxes to denote an object :
1) Pass the object directly, e.g.: $user->grant($obj, $actions);
2) Use the unique identifier of the object, e.g.:
    $user->grant(array('model' => 'Image', 'foreign_key' => 'theID'), $actions);
3) Use an alias (if the object has an alias):
    $user->grant("myAlias", $actions);
    
Note: You can set the alias only if you access the ARO or ACO using RGroup/CGroup. If you use them only for grouping, you can use the implicit variant, which is probably the most convenient one.
    

Identification of objects:
-------------------------
Every ACL-Object (Aro or Aco) can be dinstinguished using three techniques:

a) The object itself (it's ID). This ID is automatically created when the object
   is instantiated. Most times you won't use this direct one. If you bother to         do, use RGroup or CGroup.

b)  Using the unique associated: Most ACL-Objects correspond to a real object in             your database. You can therefore identify the ACL-Objects by their associated objects: array('model' => 'yourModel', 'foreign_key' => 'aNumericalID');

c) Using an Alias. Not all ACL-Objects must directly correspond to real objects. Especially if you perform grouping, you may want to have virtual groups you don't have in your real application. For example "Guest" is such a group: If you don't use it anywhere else, why should you create a corresponding object in your database?
You can create such groups using CGroup (ControlGroup => for AccessControlObjects) and RGroup(RequestGroup => for AccessRequestObjects).
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
        
        
- Actions:
-------------------------

Actions are represented by the Model "Action" and reside in {{action}}.
The shipped actions are "create", "read", "update", "delete". They should neither be renamed nor removed, as the automatic functionality relies on them. In order to add a possible action, just add a row to your database. 
This extension supports the wildcard '*', which will automatically be converted into all available actions.
Please note that you can limit the actions which can be performed on a specific RestrictedActiveRecord by setting it's possibleActions (it's a static var!). 
Examples:
~~~
[php]

  $aro->grant($aco, array('read', 'update'));
  $aro->grant($aco, 'read, update');
  $aro->grant($aco, '* - create, delete'); //Corresponds to the preceding example if $aco::possibleActions = array('create', 'read', 'update', 'delete');
~~~

Please keep in mind that "*"" is resolved immediately, so if you add actions later on, you will need to update your objects you granted "*" to.

       

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



0.3 (Current Version):

 + fully recoded 
 
 + added switchable strategies (shipped strategy: Path Materialization) 
 
 + abstracted actions 
 
 + enabled limitation of actions and a new syntax for action-definitions
 
 + added behaviors
 
 + introduced several more convenient interfaces
 
 
