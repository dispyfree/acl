<?php

Yii::import('acl.models.behaviors.*');
/**
 * RestrictedActiveRecordBehavior Class File
 * This class serves as a behavior for all the objects which have to control 
 * their access
 *
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.base
 */

/**
 * This class is intended tobe used as a behavior for objects which have restrictions on their access
 * It automatically checks, if the current user has the permissions to commit the regular CRUD-tasks
 */
class RestrictedActiveRecordBehavior extends AclObjectBehavior {

    /**
     * Overwrite this method to return the actual class Name
     * @return  either "Aro" or "Aco"
     */
    protected function getType() {
        return 'Aco';
    }

    /**
     * As there may be joins with several restricted models, we have to distinguish
     * the tables in the joins. That happens this way: tableName_$id
     * The id is unique to every RestrictedActiveRecordBehavior-Instance.
     * @var int
     */
    protected static $counter = 0;

    /**
     * Returns the static counter
     * @see $counter
     * @return  int the ID of this behavior-instance 
     */
    public function getId() {
        return self::$counter;
    }

    /**
     * generates a name unique to this Behavior-instance
     * @param   string  the tableName
     * @return  string  the unique tableName
     */
    public function getUniqueName($table) {
        return $table . '_' . $this->getId();
    }

    /**
     * Temporary CDbCriteria merged with the given one
     * this is used to pass certain things directly to this behavior
     * because the regular CDbCriteria isn't passed
     * @var array   array('relation' => array('key1' => ..., 'key2' => ...)) 
     */
    public static $criteria = array();

    /**
     * The following functions generates the CDbCriteria necessary to filter all accessable rows
     * The CDbCriteria is solely passsed to the wrapped methods
     * @param sql $conditions the conditions being passed to the real method
     * @param array $params the params being passed to the real method
     * @param   array   $options    options to be used by the method itself (keys: disableInheritance)
     * @return CDbCriteria the criteria assuring that the user only gets what he has access to
     */
    public function generateAccessCheck($conditions = '', $params = array(), $options = array()) {
        if (is_object($conditions) && get_class($conditions) == 'CDbCriteria') {
            $criteria = $conditions;
        } else {
            $criteria = new CDbCriteria;
            $criteria->mergeWith(array(
                'condition' => $conditions,
                'params' => $params
            ));
        }

        //Some things may be appended
        $criteria = $this->considerExtraCriteria($criteria);

        //If he's generally allowed, don't filter at all
        if (RestrictedActiveRecord::mayGenerally(get_class($this->getOwner()), 'read'))
            return $criteria;

        $options = array_merge(RestrictedActiveRecord::$defaultOptions, $options);

        //If the check is bypassed, return criteria without check
        if (RestrictedActiveRecord::$byPassCheck)
            return $criteria;

        //Generates tableNames
        $acoC = $this->getUniqueName('acoC');
        $aco = $this->getUniqueName('aco');
        $permissionTable = $this->getUniqueName('map');
        $modelTable = $this->getOwner()->getTableAlias(false, false);

        //Inner join to get the collection associated with this content
        $acoClass = Strategy::getClass('Aco');
        $collection = 'INNER JOIN `oldName` AS newName ON' .
                ' newName.model = RAR_model AND newName.foreign_key = modelTable.id';

        $rarModel = $this->getUniqueName(':RAR_model');
        $collection = Util::rep($collection, array(
                    'oldName' => $acoClass::model()->tableName(),
                    'newName' => $acoC,
                    'RAR_model' => $rarModel,
                    'modelTable' => $modelTable
                ));
        $criteria->params[$rarModel] = get_class($this->getOwner());


        //Inner join to the associated aco-nodes themselves to get the positions
        $acoNodeClass = Strategy::getClass('AcoNode');
        $nodes = 'INNER JOIN `oldTable` AS newTable ON'
                . ' newTable.collection_id = acoCollectionTable.id';

        $nodes = Util::rep($nodes, array(
                    'oldTable' => $acoNodeClass::model()->tableName(),
                    'newTable' => $aco,
                    'acoCollectionTable' => $acoC
                ));

        //But before: get the position-Check for the involved aro
        $aroPositionCheck = $this->generateAroPositionCheck($permissionTable);

        //Get our action :)
        $action = Util::enableCaching(Action::model(), 'action')->find('name = :name', array(':name' => 'read'));

        if ($action === NULL)
            throw new RuntimeException('Unable to find action read');

        //Now, join connecting table
        $acoCondition = $acoClass::buildTreeQueryCondition(
                        array('table' => $aco), array('table' => $permissionTable, 'field' => 'aco'), $options['disableInheritance']);

        //Take general Permissions into account: possibly the aro is permitted
        //the read-action in a general manner
        $generalAcoCondition = $this->generateGeneralPositionCheck($permissionTable);

        $actionId = $this->getUniqueName(':acl_action_id');
        $connection = 'INNER JOIN `oldTable` AS newTable ON ' .
                'newTable.action_id = actionId AND aroCondition '
                //Only one match is needed: either directly or indirectly
                . 'AND ( acoCondition OR generalAcoCondition)';
        $connection = Util::rep($connection, array(
                    'oldTable' => Permission::model()->tableName(),
                    'newTable' => $permissionTable,
                    'actionId' => $actionId,
                    'aroCondition' => $aroPositionCheck,
                    'acoCondition' => $acoCondition,
                    'generalAcoCondition' => $generalAcoCondition,
                ));

        $criteria->params[$actionId] = $action->id;

        $joins = array($collection, $nodes, $connection);

        //Detect relational queries: 
        //If a relational query is performed, it may be an optional relation
        //So we need to alter the condition and we shouldn't add more direct joins
        $owner = $this->getOwner();
        $isRelationalQuery = $owner->getTableAlias(false, false) != 't';

        if ($isRelationalQuery) {
            //Well - we'll need to replace the INNER JOIN from the first query
            //with a select
            $joins[0] = Util::rep($joins[0], array(
                        'INNER JOIN' => 'SELECT TRUE FROM',
                        'ON' => 'WHERE'
                    ));

            //  Now, we need to place the where at the end of all conditions
            $matches = array();
            preg_match('/^.*( WHERE.*)$/', $joins[0], $matches);
            $conditions = $matches[1];
            $joins[0] = str_replace($conditions, '', $joins[0]);

            //Now, we append it as a regular condition :)
            $criteria->condition .= ' EXISTS (';

            foreach ($joins as $join) {
                $criteria->condition .= ' ' . $join;
            }

            //Now, add the where-condition from the first select
            $criteria->condition .= $conditions;
            $criteria->condition.= ' ) ';
        } else {
            foreach ($joins as $join) {
                $criteria->mergeWith(array('join' => $join), true);
            }
        }

        //Increase the counter because each call can be a join-query
        self::$counter++;

        return $criteria;
    }

    /**
     * Generates the check for the positions of the aro
     * @param   string  the name of the permission-table
     * @return  string  the finished condition
     */
    protected function generateAroPositionCheck($permissionTable) {

        //Get the aro-acl-object first
        $aroClass = Strategy::getClass('Aro');
        $aro = RestrictedActiveRecord::getUser();
        $aro = ACLObject::loadObjectStatic($aro, 'Aro');

        //If we are nobody... we are a guest^^
        $guest = Strategy::get('guestGroup');
        if (!$aro && $guest) {
            $aro = Util::enableCaching($aroClass::model(), 'aclObject')->find('alias = :alias', array(
                ':alias' => $guest));

            //If there's no guest group... we are nobody and we may nothing ;)
            if (!$aro)
                return array();
        }

        //Get the positions 
        $aroPositions = $aro->fetchComprisedPositions();

        //Form the check with them
        $aroPositionCheck = $aro->addPositionCheck($aroPositions, "aro", $permissionTable);

        return $aroPositionCheck;
    }

    /**
     * Generates a general permission check, applying also if the aco is 
     * somewhere between it's general equivalent
     * @param string $permissionTable
     * @return string the condition
     */
    protected function generateGeneralPositionCheck($permissionTable) {
        $owner = $this->getOwner();
        $generalAco = Util::enableCaching(CGroup::model(), 'aclObject')->find('alias = :alias', array(
            ':alias' => get_class($owner)
                ));

        if ($generalAco) {
            $positions = $generalAco->fetchComprisedPositions();
            return $generalAco->addPositionCheck($positions, 'aco', $permissionTable);
        }

        //If there's no aco, this option is always false
        return 'FALSE';
    }

    /**
     * This method merges this criteria with additional criteria for this relation
     * provided by the user and takes care that table placeholders are replaced
     * @param CDbCriteria $criteria  the criteria to transform
     * @return  CDbCriteria the transformed criteria
     */
    protected function considerExtraCriteria($currentCriteria) {

        //Take into account only modifications for the currently processed relation
        $relation = $this->getOwner()->getTableAlias(false, false);
        $relevantCriteria = &self::$criteria[$relation];

        if (!is_array($relevantCriteria))
            $relevantCriteria = array();

        //Consider external changes
        $currentCriteria->mergeWith($relevantCriteria);

        //Processed => remove
        $relevantCriteria = array();

        //The programmer doesn't have to bother with the unique tables
        $currentCriteria = $this->translatePlaceholders($currentCriteria);

        return $currentCriteria;
    }

    /**
     * It's too clumsy for the developer to cope with the unique tables this
     * query will create. So we employ placeholders we'll simply replace
     * @param type $criteria 
     */
    protected function translatePlaceholders($criteria) {
        $tables = array('map', 'aco', 'acoC');

        //The fields of the criteria to change
        $fields = array('condition', 'order', 'join', 'select');

        foreach ($fields as $field) {
            foreach ($tables as $table) {
                $placeHolder = $table . '.';
                $replace = $this->getUniqueName($table) . '.';
                $criteria->$field = str_replace($placeHolder, $replace, $criteria->$field);
            }
        }

        return $criteria;
    }

    /**
     * This method will add a criteria to the next query, which limits the resultset
     * to a subset which fulfills the given group-conditions.
     * The relation is the name of the relation you use in your relations definition
     * in the model, if it's the accessed model itself, the relation is 't'.
     * 
     * Structure of the Restriction:
     * restriction := array('operator', [recursiveRestriction [,recursiveRestriction...]]);
     * recursiveRestriction := aclIdentifier 
     *    | array('operator', [recursiveRestriction [,recursiveRestriction ...]])
     * aclIdentifier    = 'alias' 
     *    | array('alias' => 'myAlias') 
     *    | array('model' => 'myModel', 'foreign_key' => 'foreignKey')
     *    | any Instance of CActiveRecord
     * operator := 'and' 
     *    |  'not'
     *    |  'or
     * 
     * This is quite a theoretical definition. Let's get down to the code:
     * array('and', 'Group1', array('not', 'Group2'));
     *  => This will yield all objects being in group1 but not group 2
     * 
     * @param string $relation  Name of the invoked relation
     * @param array  $restr     the group-restrictions
     */
    public static function addGroupRestriction($relation, $restr) {
        $relCriteria = self::$criteria[$relation];

        if (!is_array($relCriteria))
            $relCriteria = array();

        $condition = self::recursiveAddGroupRestriction($relation, $restr);

        if (!isset($relCriteria['condition']))
            $relCriteria['condition'] = $condition;
        else
            $relCriteria['condition'] .= ' AND ' . $condition;

        self::$criteria[$relation] = $relCriteria;
    }

    /**
     * Prepares the group-restriction recursively
     * @param string $relation name of the relation
     * @param array $restr  the restriction in its array-santax
     * @return string 
     */
    public static function recursiveAddGroupRestriction($relation, $restr) {
        $operators = array('and', 'or', 'not');

        //If that's a string, we do not need any further processing (it's an alias then)
        if(is_string($restr))
            return self::getPositionCheck($restr);
        
        //Fetch the operator
        $operator = current($restr);

        //If the array isn't something to put conditions together, consider it 
        //an identifier!
        if (!in_array($operator, $operators))
            return self::getPositionCheck($restr);

        //Ok it's really a concatenated condition. Process regularly
        //The not-operator has got a caveat: he needs to come first
        if ($operator == 'not')
            $condition = ' NOT (';
        else
            $condition = ' ( ';
        

        $alreadyAdded = 0;

        while (($next = next($restr)) !== false) {

            //If the not-operator is used, do not concatenate but just call recursively
            if ($operator == 'not') {
                $condition.= self::recursiveAddGroupRestriction($relation, $next);
                continue;
            }

            //Otherwise, use the normal concatenation
            //If we're not at the beginning
            if ($alreadyAdded++ != 0)
                $condition .= strtoupper($operator);

            //This can also used as a nested structure
            if (is_array($next)) {
                $condition .= self::addGroupRestriction($relation, $next);
            } else {
                $condition .= self::getPositionCheck($next);
            }
        }

        $condition .=' )';

        return $condition;
    }

    /**
     * Generates a condition-check for the given object, so that all of the 
     * objects matched by it are the object itself or its children
     * @param mixed $obj    the object to generate the condition for
     * @return string       the condition
     */
    protected static function getPositionCheck($obj) {
        //Load the object
        $obj = AclObject::loadObject($obj, 'Aco');
        
        //Add the path condition
        $positions = $obj->fetchComprisedPositions();

            //We'll need to make a new subquery for which we need a nother unique 
            //identifier
            static $counter = 0;
            $ident = 'not_subquery_'.($counter++);
            $subCondition = $obj->addPositionCheck($positions, 'path', $ident, 'desc', '');
            
            //Create Query
            $condition = "EXISTS ( ".Yii::app()->db->createCommand()
                ->select('*')
                ->from('{{aco}} AS '.$ident)
                ->where($subCondition.' AND '.$ident.'.collection_id = acoC.id')
                ->getText()." ) ";
        
        return $condition;
    }

    /**
     * Gets the Aros who are directly (no inheritance!) permitted to perform
     * one of the specified actions on this object
     * @param mixed $actions the actions to be considered
     * @return array All of the objects which have one of the permissions
     */
    public function getDirectlyPermitted($actions = '*') {
        //First, fetch all of the action Ids
        $owner = $this->getOwner();
        $actions = Action::translateActions($owner, $actions);
        $actionCondition = Util::generateInStatement($actions);
        $actions = Util::enableCaching(Action::model(), 'action')->findAll('name ' . $actionCondition);

        $actionIds = array();
        foreach ($actions as $action) {
            $actionIds[] = $action->id;
        }
        $actionIdCondition = Util::generateInStatement($actionIds);

        //Get the associated Aco first
        $aco = AclObject::loadObjectStatic($owner, 'Aco');
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
    public function beforeSave($event) {
        //The Record is updated
        $aro = RestrictedActiveRecord::getUser();

        //If there's no aro, don't assign any rights
        if ($aro === NULL)
            return true;

        if (!$this->getOwner()->isNewRecord) {
            if (!$aro->may($this->getOwner(), 'update'))
                throw new RuntimeException('You are not allowed to update this record');
        }
        else {
            if (!$aro->may(get_class($this->getOwner()), 'create'))
                throw new RuntimeException('You are not allowed to create this object');
        }

        return true && parent::beforeSave($event);
    }

    /**
     * This method checks whether the user has the right to delete the current record
     * 
     */
    public function beforeDelete($event) {
        $aro = RestrictedActiveRecord::getUser();
        $owner = $this->getOwner();

        //If he's generally allowed, don't filter at all
        if (RestrictedActiveRecord::mayGenerally(get_class($this->getOwner()), 'delete'))
            return true;

        if (!$aro->may($owner, 'delete'))
            throw new RuntimeException('You are not allowed to delete this record');

        //Ok he has the right to do that - remove all the ACL-objects associated with this object
        $class = Strategy::getClass('Aco');
        $aco = $class::model()->find('model = :model AND foreign_key = :key', array(':model' => get_class($owner), ':key' => $owner->id));
        if (!$aco)
            throw new RuntimeException('No associated Aco!');

        if (!$aco->delete())
            throw new RuntimeException('Unable to delete associated Aco');

        return true && parent::beforeDelete($event);
    }

    /**
     * This method takes care to assign individual rights to newly created objects
     * 
     * @param CEvent $evt 
     */
    public function afterSave($event) {
        $owner = $this->getOwner();
        if ($owner->isNewRecord) {
            $aro = RestrictedActiveRecord::getUser();
            //As the object is newly created, it needs a representation
            //If strict mode is disabled, this is not necessary
            $class = Strategy::getClass('Aco');
            $aco = new $class();
            $aco->model = get_class($owner);
            $aco->foreign_key = $owner->getPrimaryKey();

            if (!$aco->save()) {
                throw new RuntimeException('Unable to create corresponding Aco for new ' . get_class($owner));
            }

            $aro->grant($aco, RestrictedActiveRecord::getAutoPermissions($this->getOwner()), true);
        }
        
        return parent::afterSave($event);
    }

    /**
     * Checks whether the current ARO has the given permission on this object
     * @param string $permission 
     */
    public function grants($permission) {
        $aro = RestrictedActiveRecord::getUser();
        return $aro->may($this->getOwner(), $permission);
    }

}

?>
