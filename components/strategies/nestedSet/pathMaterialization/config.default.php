<?php

return array(
    
    /**
     * The Prefix of your classes
     * @see Strategy::getClass 
     */
    'prefix' => 'Pm',
    
    /**
     * Enables or disables the strict Mode
     * If the strict mode is disabled, objects which are not found are simly created
     * on the fly (Access Control Objects as well as Access Request Objects) 
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
     * If this is enabled, a new object will be created whenever a non-transient
     * operation (such as join, grant...) is performed on one of the objects listed
     * in the following. The created object will be a child of the old object.
     * @var array|callback an array of aliases for all virtual objects or \
     * a callback which receives the object and returns true/false depending on \
     * whether the given object should be considered to be virtual.
     * 
     * If you want to disable this feature, simply provide an empty array
     */
    'virtualObjects' => array(),
    
    /**
     * Whenever a new object is created for a virtual object, the alias
     * will be set to the given pattern, where {ident} is replaced by a unique 
     * identifier for the new object.
     * You have to make sure that any alias of this pattern is not used before.
     */
    'virtualObjectPattern' => '{ident}_virtual',
    
    /**
     * This callback is invoked whenever a new object is created due to the 
     * virtual object feature defined above. It gives you the ability to hook into
     * this process and fetch all the objects being created.
     * Most common use: Simply attach the newly created object to the current session
     */
    'virtualObjectCallback' => NULL,
    
    /**
     * Enables the business rules for all actions (automatical lookup) _except_ the read-action
     */
    'enableBusinessRules' => false,
    
    /**
     * Sets the direction in which business-rules are applied. Default is to check
     * both sides 
     * possible values: "all", "both", "aro" and "aco"
     *
     * The difference between "all" and "both" is that all evaluates _all_ rules 
     * whereas both only accepts rules where only one (of aro and aco) are 
     * determined using Business-Rules
     */
    'lookupBusinessRules' => 'all',
    
    
    /**
     * Enables the restriction of the grant/deny-action
     * Note: If you enable this and assign no autoPermissions at all, only general permissions will
     * grant something at all 
     */
    'enablePermissionChangeRestriction' => false,
    
    /**
     * Enables you to restrict WHAT an Aro can grant/deny, whether it can grant something at all
     */
    'enableSpecificPermissionChangeRestriction' => false,
    
    /**
     * Enables the check of join/deny operations based on permissions
     * the actions are "join" and "leave"
     */
    'enableRelationChangeRestriction' => false,
    
    /**
     * Permissions which are allowed for all the users on all objects
     * default: only create 
     */
    'generalPermissions' => array('create'),
    
    /**
     * Defines which permissions are automatically assigned to the creator of an object upon its creation
     * default: all
     * You can overwrite this using the autoPermissions-value of each object 
     */
    'autoPermissions' => '*',
    
    /**
     * Enables the access-check in two layers: if enabled, the access will be firstly checked
     * against a general permission-system and only (and only if) if that returns false, the 
     * regular lookup will take place  
     */
    'enableGeneralPermissions' => false,
    
    /**
     * Groups that will be automatically joined upon the creation of an object
     * You can overwrite this setting for each model in model::$autoJoinGroups
     * 
     * PLEASE NOTE that this does not abide by the join/leave restrictions, if
     * they are enabled.
     */
    'autoJoinGroups' => array(
        'aro' => array('All'),
        'aco' => array('All')
    ),
    
    'caching' => array(
      /**
       *  If enabled, the given number defines the number of seconds after which 
       * the cached values are invalidated. A value of 0 indicates that caching is disabled.
       */
        
      /**
       * The acl objects themselves are cached 
       */
      'collection' => 0,
        
      /**
        * The actions are cached
        */
      'action'     => 0,
        
       /**
        * The permissions are cached
        * Note that this does only apply for all checks where may() is involved. -
        * This is the case whenever you call it, or for the general permissions
        * Note: the regular find() method is _not_ affected unless you use
        * general permissions 
        */
      'permission' => 0,
        
      /**
       * This caches the objects associated with the acl objects (above: the collections)
       * This cache affects the REAL objects whenever this extension accesses them 
       */
      'aclObject' => 0,
        
      /**
       * Caches _only_ the objects used as the _current_ aro - not aros in general
       * if you do not enable this, the aro will be requested every single time from your database
       * whenever you do something with acl
       */
      'aroObject' => 0,
        
      /**
       * caches the structures of objects, e.g.:
       * relations (parent, child) and paths
       */
      'structureCache' => 0,
        
       /**
        * Allows you to use another cache component than the default one, solely for acl 
        */
      'cacheComponent' => 'cache'
    ),
);

?>