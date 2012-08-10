<?php

/**
 * The specialization of the general AclObject.
 * This class implements the path-materialization-specific/optimized position- 
 * and rights-operations
 *
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.strategies.nestedSet.pathMaterialiization
 */
abstract class PmAclObject extends AclObject{
    
    
    /**
     * Returns all of the AclNodes of this object which do not have a parent yet
     *
     * @access public
     * @param  AclObject object
     * @return array[AclNode]
     */
    public function getFreeNodes(){
        $class = Util::getNodeNameOfObject($this);
     
        return $class::findAll('collection_id = :id AND path =""', array(':id' => $this->id));
    }
    
    
     /**
      * Fetches all Paths of the nodes of this object 
      * @return array[string] the paths of the nodes 
      */
     public function getPaths(){
        $nodeClass = Util::getNodeNameOfObject($this);
        $nodes = Util::enableCaching($nodeClass::model(), 'structureCache')->findAll('collection_id = :id', array(':id' => $this->id));
        $paths = array();
        
        foreach($nodes as $node){
            $paths[] = PmPathManager::appendToPath($node->path, $node->id);
        }
        unset($nodes);
        
        return $paths;
     }
     
    /**
     * Builds (string) condition which matches all destinations, which are children
     * of source
     * field should be either aco or aro
     * @param array $source array('field' => '', 'table' => '')
     * @param array $destination array('field' => '', 'table' => '')
     * @param boolean   $disableInheritance if set to true, no inheritance will be used, that means no node will acquire the rights of it's parent
     */ 
    public static function buildTreeQueryCondition($source, $destination, $disableInheritance = false){
        $source['field'] = 'path';
        $sourcePrefix = $source['table'].'.'.$source['field'];
        
        $pathExpression = 'CONCAT("^", '.$sourcePrefix.', '
                    .$source['table'].'.id, '
                .' "'.PmPathManager::getSeparator().'")';
        
        $method = !$disableInheritance ? 'REGEXP' : '=';
        
        //The object is accessible if the share-path is somewhere 
        // ABOVE the object itself
        
        return $pathExpression.' '.$method.' '.$destination['table'].'.'.$destination['field'].'_path'
                ;
    }
     
     /**
     * Fetches and returns positions of all nodes of this object 
     * which denote them
     * In this case, it's really easy because we've done that anyway :)
     * @return array[string] 
     */
    public function fetchComprisedPositions(){
        return $this->getPaths();
    }
    
     /**
     * $dir = 'asc':
     * Builds a single SQL-statement comprising all given positions and their parents
     * This SQL-statement will match all those rows being located above the given positions including themselves
      * 
      * $dir = 'desc'
      * The same, but with all the positions located below the given positions or 
      * equal to them.
     * @param array $positions All positions to include in our statement
     * @param string $type aco/aro
     * @param string $table the table comprising the map between objects and permissions
     * @param string $fieldPostfix  the postfix of the field (default: "_path" (suitable for the fields in the permission-table)
     * @return string the finished SQL-statement
     */
    public function addPositionCheck($positions, $type, $table = 't', $dir = 'asc', $fieldPostfix = '_path'){
        //Positions == paths in this case
        $preparedConditions = ' ( ';
        
        //The order is swapped depending on whether it's asc or desc directed
        $query = $dir == 'asc'?
            " '{position}' REGEXP CONCAT('^', {table}.{type}_path )" :
            " CONCAT('^', {table}.{type}{postfix} ) REGEXP '{position}'";
        
        foreach($positions as $key =>$position){
            if($key > 0)
                $preparedConditions .= ' OR ';
            $preparedConditions .= " ( ".Util::rep($query, array(
               '{position}' => $position,
               '{table}'    => $table,
               '{type}'     => $type,
               '{postfix}'  => $fieldPostfix
            ))
            ." ) ";
        }
        
        $preparedConditions .= ' ) ';
        
        return $preparedConditions;
    }
     
     /**
      * Creates a new node of this collection
      * This new node will be a children of the given AclNode 
      * @param AclNode $parent  parent of the new node, if NULL, it has no parent
      * @return AclNode the new node
      */
     protected function createNode($parent = NULL){
         
         $class = Util::getNodeNameOfObject($this);
          //First, create the node itself and place it in the tree
        $node = new $class();
        $node->collection_id = $this->id;
        
        if($parent !== NULL)
            $node->path = PmPathManager::appendToPath($parent->path, $parent->id);
        else
            $node->path = PmPathManager::getSeparator();

        
        if(!$node->save())
            throw new RuntimeException('Unable to create Node');
        
        return $node;
     }
     
     /**
     * Returns all of the (direct) AclNodes whose parent AclNode is a node of this 
     * AclObject. 
     * 
     * If the $child AclObject is specified, only nodes having the given AclObject
     * as owner will be returned.
     *
     * @access public
     * @param  AclObject child
     * @param  Integer
     * @return array[AclNode]
     */
    public function getDirectChildNodes(AclObject $child = NULL){
        $nodeName   = Util::getNodeNameOfObject($this);
        $type       = Util::getDataBaseType($this);
        
        //It's easy: fetch all nodes and get the paths their childs will have
        $nodes = $this->getNOdes();
        $resPaths = array();
        
        foreach($nodes as $node){
            $resPaths[] = PmPathManager::appendToPath($node->path, $node->id);
        }

        $condition = Util::generateInStatement($resPaths);
        $params = array();
        
        if($child !== NULL){
            $condition .= ' AND t.collection_id = :id ';
            $params[':id'] = $parent->id;
        }
        
        return $nodeName::model()->with($type)->findAll(' t.path '.$condition);
    }
    
    /**
     * Returns all of the (direct) AclNodes whose child AclNode is a node of this 
     * AclObject. 
     * 
     * If the $child AclObject is specified, only nodes having the given AclObject
     * as owner will be returned.
     *
     * @access public
     * @param  AclObject child
     * @param  Integer
     * @return array[AclNode]
     */
    public function getDirectParentNodes(AclObject $parent = NULL){
        $nodeName   = Util::getNodeNameOfObject($this);
        $type       = Util::getDataBaseType($this);
        
        //This time it's easy: fetch all paths and search only by the IDs
        $paths = $this->getPaths();
        $ids = array();
        foreach($paths as $path){
            //We have to apply it twice - the getPaths() returns full paths
            $info = PmPathManager::getParentPath($path);
            $info  = PmPathManager::getParentPath($info['path']);
            //If it has a parent ^^
            if($info['id'])
                $ids[] = $info['id'];
        }

        $condition = Util::generateInStatement($ids);
        $params = array();
        
        if($parent !== NULL){
            $condition .= ' AND collection_id = :id ';
            $params[':id'] = $parent->id;
        }
        
        return $nodeName::model()->with($type)->findAll(' t.id '.$condition);
    }
     
     /**
      * Processes post-deletion tasks 
      */
     public function beforeDelete(){ 
         
         //Delete all associated AclNodes
        $class = Util::getNodeNameOfObject($this);
        $paths = $this->getPaths();
        
        //Now, deletes nodes including their subnodes
        $condition = PmPathManager::buildMultiplePathCondition("path", $paths);
        $num = $class::model()->deleteAll($condition);
        
        if($num === false)
            throw new RuntimeException('Unable to delete all nodes of '.$this->id);
        
        //Finally, delete all associated permissions
        if(PmPermission::deleteByObject($this, $paths) === false)
                throw new RuntimeException('Unable to delete associated permissions of '.$this->id);
        
        return $num !== false && parent::beforeDelete();
     }
     
     /**
      * Joins the given object (now called: group)
      * @param mixed $obj
      * @param boolean  $byPassCheck whether to bypass the permission-check
      * @return boolean
      */
     public function join($obj, $byPassCheck = false){
         
         $obj = AclObject::loadObjectStatic($obj, $this->getType());
         
         if(!$byPassCheck)
            $this->checkRelationChange('join', $obj);
         
         //Get all nodes of the object
         $objNodes = $obj->getNodes();
         
         $transaction = Yii::app()->db->beginTransaction();
         try{
            foreach($objNodes as $objNode){
                $this->createNode($objNode);
            }
            $transaction->commit();
            
            //If everything worked - flush the cache
            Util::flushCache();
         }
         catch(Exception $e){
             $transaction->rollback();
             throw $e;
         }
         
         return true;
     }
     
     /**
      * Leaves the given group
      * @param mixed    $obj
      * @param boolean  $byPassCheck whether to bypass the permission-check
      * @return boolean
      */
     public function leave($obj, $byPassCheck = false){
         
         $obj = AclObject::loadObjectStatic($obj, $this->getType());
          
         if(!$byPassCheck)
            $this->checkRelationChange('leave', $obj);
         
         //Get all nodes of the object
         $paths = $obj->getPaths();
         $nodeClass = Util::getNodeNameOfObject($this);

         //We only want to leave usign the DIRECT child-nodes of this collection
         $oneLevelCondition = 'path = ":path"';
         $pathCondition = PmPathManager::buildMultiplePathCondition('path', $paths, $oneLevelCondition);
         
         $transaction = Yii::app()->db->beginTransaction();
         try{
            $nodes = $nodeClass::model()->findAll('collection_id = :id AND '.$pathCondition,
                 array(':id' => $this->id));
            
            foreach($nodes as $node){
                if(!$node->delete())
                    throw new RuntimeException('Unable to delete node');
            }
            $transaction->commit();
            Util::flushCache();
            
            return true;
         }
         catch(Exception $e){
             $transaction->rollback();
             throw $e;
         }
     }
     
    /**
     * This function checks if the current aro-object may let this object join
     * or leave the given object
     * If not, it throws an exception: otherwise it returns true
     * @throws RuntimeException
     * 
     * @param   string  $type   either "join" or "leave"
     * @param   mixed   $obj    the object to join/leave
     * @return  boolean true if succeeded
     */
    protected function checkRelationChange($type, $obj){
        
        if(!in_array($type, array('join', 'leave')))
                throw new RuntimeException('Invalid relation-change type');
        $ltype  = $type;
        $type   = ucfirst($type);
        
        //The object who wants to perform this change
        $aro = RestrictedActiveRecord::getUser();
        
        //Check if the change is restricted
        $generalConfigEntry     = 'enableRelationChangeRestriction';
        
        $enableCheck = Strategy::get($generalConfigEntry);
        
        if($enableCheck){ 
            //This aro may generally not do this
            if(!$aro->may($obj, $ltype))
                throw new RuntimeException(Yii::t('app', 
                        'You are not permitted to let this object {type} {obj}',
                        array('{type}' => $type,
                              '{obj}' => get_class($this).'::'.$this->id)));
        }
        
        return true;
    }
     
     /**
      * Checks whether this object is somehow a child of the given object
      * @param mixed $obj
      * @return boolean
      */
     public function is($obj){
         
         $obj = AclObject::loadObjectStatic($obj, $this->getType());
          
        //Get all nodes of the object
         $paths = $obj->getPaths();
         $nodeClass = Util::getNodeNameOfObject($this);

         //We only want to use the DIRECT child-nodes of this collection
         $pathCondition = PmPathManager::buildMultiplePathCondition('path', $paths);

         $enableBusinessRules = Strategy::get('enableBusinessRules');

         $regularly = Util::enableCaching($nodeClass::model(), 'structureCache')->find('collection_id = :id  AND'.$pathCondition,
                 array(':id' => $this->id)) !== NULL;

         if(!$enableBusinessRules){
             return $regularly;
         }
         else{
             //If we're done with regular ACL
             if($regularly)
                 return true;

             //Go down to the nitty-gritty
             $type = Util::getDataBaseType($obj);
             //Select only aliases which are in fact business-rules
             $aliases = BusinessRules::listBusinessAliases();
             $in   = Util::generateInStatement($aliases);
             $nodes = Util::enableCaching($nodeClass::model(), 'structureCache')->with($type)->findAll(
                     $type.'.alias '.$in
                 );

             //Now, check all the Business-rules
             foreach($nodes as $node){
                 $collection = $node->{$type};

                 if($collection->alias != NULL){
                     $val = $this->callBusinessRule('is'.$collection->alias, $obj, 'is');
                     if($val)
                         return true;
                 }
             }

             return false;
         }

     }
     
     /**
      * This packs the object and this object itself in the proper array
      * and calls the specific business-rule taking care of aro/aco specific
      * syntax
      * @param  string  the Rule
      * @param  mixed   the given object
      * @param  string  the action
      */
     protected function callBusinessRule($rule, $obj, $action){
         $arr = array(
           'father' => Util::getByIdentifierGraceful($obj),
           'child'=> Util::getByIdentifierGraceful($this)
         );
         return $this->callSpecificBusinessRule($rule, $arr, $action);
     }
     
     /**
      * This takes care of the aro/aco specifis for calling business-rules
      * @param  string  the Rule
      * @param  arr     array('child' and 'father')
      * @param  string  the action
      */
     abstract protected function callSpecificBusinessRule($rule, $arr, $action);
     
     /**
      * @return string  the type of this object ('Aro' or 'Aco')
      */
     public function getType(){
         return ucfirst(Util::getDataBaseType($this));
     }
}

?>
