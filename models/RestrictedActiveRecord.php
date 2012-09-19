<?php

/**
 * RestrictedActiveRecord Class File
 * This class serves as a base-class for all the objects which have to control 
 * their access
 *
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.base
 */

/**
 * This class is intended as a base for objects which have restrictions on their access
 * It automatically checks, if the current user has the permissions to commit the regular CRUD-tasks
 */
abstract class RestrictedActiveRecord extends CActiveRecord {

    /**
     * This is used to temporarily disable the Access  Check
     * This may be needed if for example the user is a guest and has therefore no update-rights (he doesn't have an ARO representation) on his own objects
     * @var boolean
     */
    public static $byPassCheck = false;

    /**
     * This variable can be used to access the resource in attendance of another ARO-collection than the current user
     * default is to NULL which means that the user denoted by Yii::app()->user is used for access checking
     * @var mixed
     */
    public static $inAttendance = NULL;

    /**
     * @var string  The model to use for automatic access checing (as Aro-Collection). Default: "User" 
     */
    public static $model = 'User';

    /**
     * This contains all possible actions for the objects of this class. 
     * All other actions will never be granted and always denied upon inquiry. 
     * Example: array('update', 'read') << all other action are not available
     * @var array[string]
     */
    public static $possibleActions = NULL;
    public static $defaultOptions = array(
        'disableInheritance' => false, // If true, no aco will inherit the permissions of it's parent
    );

    /**
     * The following functions generates the CDbCriteria necessary to filter all accessable rows
     * The CDbCriteria is solely passsed to the wrapped methods
     * @param sql $conditions the conditions being passed to the real method
     * @param array $params the params being passed to the real method
     * @param   array   $options    options to be used by the method itself (keys: disableInheritance)
     * @return CDbCriteria the criteria assuring that the user only gets what he has access to
     */
    protected function generateAccessCheck($conditions = '', $params = array(), $options = array()) {
        if (is_object($conditions) && get_class($conditions) == 'CDbCriteria') {
            $criteria = $conditions;
        } else {
            $criteria = new CDbCriteria;
            $criteria->mergeWith(array(
                'condition' => $conditions,
                'params' => $params
            ));
        }

        //If he's generally allowed, don't filter at all
        if (self::mayGenerally(get_class($this), 'read'))
            return $criteria;

        $options = array_merge(RestrictedActiveRecord::$defaultOptions, $options);

        //If the check is bypassed, return criteria without check
        if (RestrictedActiveRecord::$byPassCheck)
            return $criteria;

        $criteria->distinct = true; //Important: there can be multiple locations which grant permission
        //Inner join to get the collection associated with this content
        $acoClass = Strategy::getClass('Aco');
        $collection = 'INNER JOIN `' . $acoClass::model()->tableName() . '` AS acoC ON acoC.model = :RAR_model AND acoC.foreign_key = t.id';
        $criteria->params[':RAR_model'] = get_class($this);

        //Inner join to the associated aco-nodes themselves to get the positions
        $acoNodeClass = Strategy::getClass('AcoNode');
        $nodes = ' INNER JOIN `' . $acoNodeClass::model()->tableName() . '` AS aco ON aco.collection_id = acoC.id';

        //But before: fetch the positions of the current user
        $aroClass = Strategy::getClass('Aro');
        $user = RestrictedActiveRecord::getUser();
        $aro  = $user;
        
        //If we are nobody... we are a guest^^
        $guest = Strategy::get('guestGroup');
        if (!$aro && $guest) {
            $aro = Util::enableCaching($aroClass::model(), 'aclObject')->find('alias = :alias', array(':alias' => $guest));

            //If there's no guest group... we are nobody and we may nothing ;)
            if (!$aro)
                return array();
        }


        $aroPositions = $aro->fetchComprisedPositions();
        $aroPositionCheck = $aro->addPositionCheck($aroPositions, "aro", "map");

        //Get our action :)
        $action = Util::enableCaching(Action::model(), 'action')->find('name = :name', array(':name' => 'read'));

        if ($action === NULL)
            throw new RuntimeException('Unable to find action read');

        //Now, join connecting table
        $acoCondition = $acoClass::buildTreeQueryCondition(
                        array('table' => 'aco'), array('table' => 'map', 'field' => 'aco'), $options['disableInheritance']
        );
        $connection = ' INNER JOIN `' . Permission::model()->tableName() . '` AS map ON ' . $acoCondition . ' AND ' . $aroPositionCheck . ' AND map.action_id = :acl_action_id';
        $criteria->params[':acl_action_id'] = $action->id;

        $joins = array($collection, $nodes, $connection);

        foreach ($joins as $join) {
            $criteria->mergeWith(array('join' => $join), true);
        }


        return $criteria;
    }

    public function find($conditions = '', $params = array()) {
        return parent::find($this->generateAccessCheck($conditions, $params));
    }

    public function findByAttributes($attributes, $conditions = '', $params = array()) {
        return parent::findByAttributes($attributes, $this->generateAccessCheck($conditions, $params));
    }

    public function findByPk($pk, $conditions = '', $params = array()) {
        return parent::findByPk($pk, $this->generateAccessCheck($conditions, $params));
    }

    public function findBySQL($sql, $params = array()) {
        return parent::find($this->generateAccessCheck($sql, $params));
    }

    public function findAll($conditions = '', $params = array()) {
        return parent::findAll($this->generateAccessCheck($conditions, $params));
    }

    public function findAllByAttributes($attributes, $conditions = '', $params = array()) {
        return parent::findAllByAttributes($attributes, $this->generateAccessCheck($conditions, $params));
    }

    public function findAllByPk($pk, $conditions = '', $params = array()) {
        return parent::findAllByPk($pk, $this->generateAccessCheck($conditions, $params));
    }

    public function findAllBySQL($sql, $params = array()) {
        return parent::findAll($this->generateAccessCheck($sql, $params));
    }

    /**
     * Gets the Aros who are directly (no inheritance!) permitted to perform
     * one of the specified actions on this object
     * @param mixed $actions the actions to be considered
     * @return array All of the objects which have one of the permissions
     */
    public function getDirectlyPermitted($actions = '*') {
        //First, fetch all of the action Ids
        $actions = Action::translateActions($this, $actions);
        $actionCondition = Util::generateInStatement($actions);
        $actions = Util::enableCaching(Action::model(), 'action')->findAll('name ' . $actionCondition);

        $actionIds = array();
        foreach ($actions as $action) {
            $actionIds[] = $action->id;
        }
        $actionIdCondition = Util::generateInStatement($actionIds);

        //Get the associated Aco first
        $aco = AclObject::loadObjectStatic($this, 'Aco');
        //Fetch all of the own positions and build condition
        $positions = $aco->fetchComprisedPositions();
        $acoCondition = Util::generateInStatement($positions);

        $aroNodeClass = Strategy::getClass('AroNode');

        $rGroupTable = RGroup::model()->tableName();
        $nodeTable = $aroNodeClass::model()->tableName();
        $permTable = Permission::model()->tableName();
        return Yii::app()->db->createCommand()
                        ->selectDistinct('t.id AS collection_id, t.foreign_key, t.model, t.alias, p.action_id')
                        ->from($rGroupTable . ' t')
                        ->join($nodeTable . ' n', 'n.collection_id = t.id')
                        ->join($permTable . ' p', 'p.aro_id = n.id AND p.aco_path ' . $acoCondition . ' AND p.action_id ' . $actionIdCondition)
                        ->queryAll()
        ;
    }

    /**
     * This method checks whether the user has the right to update the current record
     * By default, it's always allowed to create a new object. This object is automatically assigned to the user who created it with full permissions
     */
    public function beforeSave() {
        parent::beforeSave();
        $aro = self::getUser();

        //If there's no aro, don't assign any rights
        if ($aro === NULL)
            return true;

        //The Record is updated
        if (!$this->isNewRecord) {

            if (!$aro->may($this, 'update'))
                throw new AccessViolation('You are not allowed to update this record');
        }else {
            if (!$aro->may(get_class($this), 'create'))
                throw new AccessViolation('You are not allowed to create this object');
        }

        return true;
    }

    /**
     * This method checks whether the user has the right to delete the current record
     * 
     */
    public function beforeDelete() {
        parent::beforeDelete();

        if (self::mayGenerally(get_class($this), 'delete'))
            return true;

        $aro = self::getUser();
        if (!$aro->may($this, 'delete'))
            throw new AccessViolation('You are not allowed to delete this record');

        //Ok he has the right to do that - remove all the ACL-objects associated with this object
        $class = Strategy::getClass('Aco');
        $aco = $class::model()->find('model = :model AND foreign_key = :key', array(':model' => get_class($this), ':key' => $this->id));
        if (!$aco)
            throw new RuntimeException('No associated Aco!');

        if (!$aco->delete())
            throw new RuntimeException('Unable to delete associated Aco');

        return true;
    }

    /**
     * This method takes care to assign individual rights to newly created objects
     * 
     * @param CEvent $evt 
     */
    public function afterSave() {
        parent::afterSave();
        if ($this->isNewRecord) {
            $aro = self::getUser();
            //As the object is newly created, it needs a representation
            //If strict mode is disabled, this is not necessary
            $class = Strategy::getClass('Aco');
            $aco = new $class();
            $aco->model = get_class($this);
            $aco->foreign_key = $this->getPrimaryKey();

            if (!$aco->save()) {
                throw new RuntimeException('Unable to create corresponding Aco for new ' . get_class($this));
            }

            $aro->grant($aco, self::getAutoPermissions($this), true);
        }
    }

    public static function getAutoPermissions($obj) {
        if (!isset($obj->autoPermissions))
            return Strategy::get('autoPermissions');
        return $obj->autoPermissions;
    }

    /**
     * Checks whether the current ARO has the given permission on this object
     * @param string $permission 
     */
    public function grants($permission) {
        $aro = self::getUser();

        //If there's no aro, we don't even need to check
        if ($aro === NULL)
            return false;

        return $aro->may($this, $permission);
    }

    /**
     * Fetches the Access Request-Object to use (either the current user 
     * or an object from self::inAttendance.
     * @see inAttendance
     * @return AclObject
     * @throws RuntimeException 
     */
    public static function getUser() {
        /**
         * If there is an object in attendance, this takes precedence over all 
         * other objects 
         */
        if (self::$inAttendance !== NULL)
            return AclObject::loadObjectsStatic(self::$inAttendance, Strategy::getClass('Aro'));
        
        $aro = NULL;
        //We can only fetch the object if the user is really logged in
        if(!Yii::app()->user->isGuest()){
            $user = Yii::app()->user;
            $identifier = array('model' => static::$model, 'foreign_key' => $user->id);
            $aro = AclObject::loadObjectStatic($identifier, 'Aro');
        }
        /**
         * Why no if(!$aro){...} ? 
         * with strictMode = false, the object for the user being logged in is 
         * automagically created and as he's logged in, we don't want to treat him
         * as a guest anyway 
         */
        else{
            //If the user is not logged in, he's a guest
            $guest = Strategy::get('guestGroup');
            if($guest)
                $aro = AclObject::loadObjectStatic(array('alias' => $guest), 'Aro');
        }
        
         //If there's no guest...
        if(!$aro)
            throw new RuntimeException('Unable to determine aro: the user is not logged in and the guest group is disabled');

        return $aro;
    }

    /**
     * Checks whether the user is generally allowed to perform the given permission(s) on the given object
     * @see "enableGeneralPermissions" config.php of the currenctly active strategy
     * @param mixed $aco the object to perform the action on (either a string or a class)
     * @param mixed $perm´the permission to perform
     */
    public static function mayGenerally($aco, $perm) {

        // These ultra general permissions are not affected by the flag below
        $perm = Action::translateActions($aco, $perm);
        //If the permission is generally granted on all objects
        if (!is_array($perm)) {
            if(in_array($perm, Strategy::get('generalPermissions')))
                    return true;
        } else {
            //Only if all of the given actions are generally granted this returns true
            $generallyGranted = true;
            foreach ($perm as $permission) {
                if (!in_array($permission, Strategy::get('generalPermissions')))
                    $generallyGranted = false;
            }
            if ($generallyGranted)
                return true;
        }

        //Return always false if general permissions are disabled
        if (Strategy::get('enableGeneralPermissions') == false)
            return false;

        //We want to act on the general model, e.g. "Post" (using Aliases)
        if (!is_string($aco)) {
            if ($aco instanceof CActiveRecord)
                $aco = get_class($aco);
            else
                throw new RuntimeException('Invalid Aco-Argument');
            return self::getUser()->may($aco, $perm);
        }
    }

    /**
     * Defining them statically is quite a mess, but that's what PHP is. 
     * @see PmAco
     */

    /**
     * Checks if the given permission is granted - includes business-rules
     * @param string    $condition the query condition
     * @param array     $params  the attached parameters
     * @param array     An array of the original objects ('aro', 'aco')
     */
    public static function checkBirPermission($condition, $params, $originalObjects) {
        //First fetch all permission which _could_ grant the right
        $permissions = self::getPermissionsWithBiz($condition, $params);

        //Now, search them. 
        foreach ($permissions as $mode => $mode_permissions) {
            foreach ($mode_permissions as $permission) {
                if (self::areBusinessRulesFulfilled($mode, $permission, $originalObjects))
                    return true;
            }
        }
        return false;
    }

    /**
     * Finds all permissions including their associated objects (nodes and collections)
     * Uses the configuration to determine in which direction(s) to fetch objects
     * @param array $conditions ("aroCondition" and "acoCondition")
     * @param array $params
     * @return array ["aro" =>Permissions, "aco" => Permissions, "both" => Permissions]  
     * Where aro and aco indicate in which direction to check for business rules
     */
    protected static function getPermissionsWithBiz($conditions, $params) {
        //Whether we should examine all directions - or which subset of them
        $mode = Strategy::get('lookupBusinessRules');

        //We need to step through each mode because a lookup can only be
        //applied in one direction at once (=> merge the results)
        $modes = self::translateMode($mode);
        $finishedPermissions = array();

        foreach ($modes as $mode) {
            //tmp copy for changes
            $modeCondition = $conditions;

            //Now, set the relations accordingly
            //Depending on what business-rules are enabled, we need to exclude
            //some paths (and thus conditions)
            $modeCondition = self::excludePaths($mode, $modeCondition);

            //We need the actions and associated collections anyway
            $with = array(
                'action',
                'aroNode.aro',
                'acoNode.aco'
            );

            //Finally, build the condition
            $condition = " action_id = :action_id ";
            foreach ($modeCondition as $tmpCondition) {
                $condition.= ' AND ' . $tmpCondition;
            }

            //Finally, exclude all conditions in which neither (business alias) is set
            $aliases        = BusinessRules::listBusinessAliases();
            $inStatement    = Util::generateInStatement($aliases);
            $condition.=' AND ('
                    . 'aco.alias ' . $inStatement
                    . ' OR aro.alias ' . $inStatement . ' )';

            $permissions = Util::enableCaching(Permission::model(), 'permission')->with($with)->findAll(
                    array('condition' => $condition, 'params' => $params));
            $finishedPermissions[$mode] = $permissions;
        }

        return $finishedPermissions;
    }

    /**
     * Translates the given string to a list of modes to examine
     * @param string $mode  'all', 'both', 'aro', 'aco'
     * @return array
     * @throws RuntimeException 
     */
    protected static function translateMode($mode) {
        $modes = array();
        switch ($mode) {
            case 'all':
                $modes = array('aro', 'aco', 'both');
                break;

            case 'both':
                $modes = array('aro', 'aco');
                break;

            case 'aro':
                $modes = array('aro');
                break;

            case 'aco':
                $modes = array('aco');
                break;

            default:
                throw new RuntimeException('Invalid mode for business-rules');
        }

        return $modes;
    }

    /**
     * This excludes the paths from the search criteria ($condition) according
     * to the mode
     * @param string $mode  'both', 'aro' or 'aco'
     * @param array $condition the $condition ('aroCondition', 'acoCondition')
     * @return array    the finished condition with excluded paths
     * @throws RuntimeException 
     */
    protected static function excludePaths($mode, $condition) {
        /**
         * If we search in one direction, we delete the paths and thus are 
         * able to check for business-rules making the path-entitlement up 
         */
        switch ($mode) {
            case 'aro':
                unset($condition['aroCondition']);
                break;

            case 'aco':
                unset($condition['acoCondition']);
                break;

            //We're looking in both directions simultaneously
            case 'both':
                unset($condition['aroCondition']);
                unset($condition['acoCondition']);
                break;

            default:
                throw new RuntimeException('Specified invalid Business-Rule mode');
        }

        return $condition;
    }

    /**
     * Checks whether the business-rules attached to this permission are fulfilled
     * @param string    $mode (either "aro" or "aco"
     * @param Permission $permission  the permission to check
     * @param array     An array of the original objects ('aro', 'aco')
     * @return boolean  true if they are fulfilled, false otherwise
     */
    protected static function areBusinessRulesFulfilled($mode, $permission, $originalObjects) {

        //If we check for both directions simultaneously, simply split it up
        if ($mode == 'both') {
            return self::areBusinessRulesFulfilled('aro', $permission, $originalObjects)
                    && self::areBusinessRulesFulfilled('aco', $permission, $originalObjects);
        }

        $suffix = 'Node';
        $node = $mode . $suffix;
        //Is the direction loaded at all?
        if (!isset($permission->{$node}))
            throw new RuntimeException('Direction not loaded: ' . $mode);

        $collection = $permission->{$node}->{$mode};
        //This should _not_ happen as it's filtered above
        if (!$collection->alias)
            throw new RuntimeException('Algorithm inconsistency revealed');

        //We - we have to check
        //build the rule
        $rule = 'is' . $collection->alias;

        //Fetch Objects if possible (otherwise, you have the bare CGroup/RGroup)
        //Those objects are the parents, which are directly own the permission
        $aro = Util::getByIdentifierGraceful($permission->aroNode->aro);
        $aco = Util::getByidentifierGraceful($permission->acoNode->aco);
        
        //Pack the parameters for the Business-Rule
        $aroArr = array(
            'child' => $originalObjects['aro'],
            'father'=> $aro
        );
        
        $acoArr = array(
            'child' => $originalObjects['aco'],
            'father'=> $aco
        );

        //Finally, check against the business-rule
        return BusinessRules::fulfillsBusinessRule($rule, $aroArr, $acoArr, $permission->action);
        
    }

}

?>
