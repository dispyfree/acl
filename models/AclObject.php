<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * AclObject Interface File
 * This is the base interfasce for AclObjects defining the most basic operations 
 *
 * @author dispy <dispyfree@googlemail.com>
 * @package acl.base
 * @license LGPLv2
 */
abstract class AclObject extends CActiveRecord{
    
     /**
      * Joins the given object (now called: group)
      * @param mixed $obj
      * @return boolean
      */
     abstract public function join($obj);
     
     /**
      * Leaves the given group
      * @param mixed $obj
      * @return boolean
      */
     abstract public function leave($obj);
     
     /**
      * Checks whether this object is somehow a child of the given object
      * @param mixed $obj
      * @return boolean
      */
     abstract public function is($obj);
     
     /**
     * Returns all of the AclNodes of this object which do not have a parent yet
     *
     * @access public
     * @param  AclObject object
     * @return array[AclNode]
     */
    abstract public function getFreeNodes();
    
    /**
     * Fetches and returns positions of all nodes of this object 
     * which denote them
     * In this case, it's really easy because we've done that anyway :)
     * @return array[string] 
     */
    abstract public function fetchComprisedPositions();
    
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
     * @param string $fieldPostfix  the postfix of the field (default: "_path" (suitable for the fields in the permission-table)
     * @param string $table the table comprising the map between objects and permissions
     * @return string the finished SQL-statement
     */
    abstract public function addPositionCheck($positions, $type, $table = 't', $dir = 'asc', $fieldPostFix = '_path');
    
    /**
      * Creates a new node of this collection
      * This new node will be a children of the given AclNode 
      * @param AclNode $parent  parent of the new node, if NULL, it has no parent
      * @return AclNode the new node
      */
     abstract protected function createNode($parent = NULL);
     
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
    abstract public function getDirectChildNodes(AclObject $child = NULL);
    
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
    abstract public function getDirectParentNodes(AclObject $parent = NULL);
    
    /**
     * Fetches all child-objects and returns them in an array
     * @return array[AclObject] the child-objects 
     */
    public function getChildObjects(){
        $childNodes = $this->getDirectChildNodes(NULL);
        $objects = array();
        
        $type = Util::getDataBaseType($this);
        foreach($childNodes as $node){
            $obj = $node->{$type};
            //Why this way? Several nodes may be associated to the same object
            $objects[$obj->id] = $obj;
        }
        
        return $objects;
    }
    
    /**
     * Fetches all parent-objects and returns them in an array
     * @return array[AclObject] the parent-objects 
     */
    public function getParentObjects(){
        $parentNodes = $this->getDirectParentNodes(NULL);
        $objects = array();
        
        $type = Util::getDataBaseType($this);
        foreach($parentNodes as $node){
            $obj = $node->{$type};
            //Why this way? Several nodes may be associated to the same object
            $objects[$obj->id] = $obj;
        }
        
        return $objects;
    }
     
    /**
     * Just a convenient wrapper to loadObjects
     * @param mixed $identifier The Identifier denoting the associated row in the ACL-system. 
     * @param string $model - the class-Name of the expected object (Aro or Aco)
     */
    public static function loadObjectStatic($identifier, $model){
        return self::loadObjectsStatic($identifier, $model, true);
    }
    
     /**
     * Just a convenient wrapper to loadObjects
     * @param mixed $identifier The Identifier denoting the associated row in the ACL-system. 
     * @param string $model - the class-Name of the expected object (Aro or Aco)
     */
    public function loadObject($identifier, $model = NULL){
        if(!$model)
            $model = get_class($this);
        return self::loadObjectsStatic($identifier, $model, true);
    }
    
    /**
     * Just a convenient wrapper to loadObjects
     * @param mixed $identifier The Identifier denoting the associated row in the ACL-system. 
     * @param string $model - the class-Name of the expected object (Aro or Aco)
     */
    public function loadObjects($identifier, $model = NULL, $onlyFirst = true){
        if(!$model)
            $model = get_class($this);
        return self::loadObjects($identifier, $model, $onlyFirst);
    }
    
    
    /**
     * This method is used to load Objects (either Aco or Aro) using convenient identifiers. 
     * 
     * @param mixed $identifier The Identifier denoting the associated row in the ACL-system. 
     * Supported identifiers:
     * 1)   Array syntax: array('model' => 'MyModel', 'foreign_key' => myId)
     *          e.g.: model => User, foreign_key => the ID of the user (presumably AUTO_INCREMENT INT from the user row)
     * 2)   alias syntax: "MyAlias"
     *          e.g.: "Visitors", "Admins", "Authors"
     * 3)   direct syntax: pass your object derived from CActiveRecord directly
     *          e.g.: loadObject(User::model()->find(....))
     *          This will be automatically resolved to the first syntax. 
     *          Please be aware that auto-creation of associated ACL-objects only happens if the strict-mode is disabled 
     *          So if you pass a new object which has no corresponding aco/aro-row, this will lead to an exception if the strict-mode is
     *          enabled.
     * 4)   Of course, you can pass the finished object directly. As many methods call this method without check, this is natural.
     * 
     * @param string $model - the class-Name of the expected object (Aro or Aco)
     * @param boolean $onlyFirst    Determines whether to fetch only the first matching object, or all of them. 
     * @return type 
     */
    public static function loadObjectsStatic($identifier, $model = NULL, $onlyFirst = true){

        //There are several ways to define the object in question
        if(is_string($model)){
            $class = Strategy::getClass($model);
            $model = new $class();
        }
        
        if($onlyFirst)
                $method = 'find';
            else
                $method = 'findAll';
            
        //An alias is being used
        if(is_string($identifier)){
            
            $objects = $model->$method('alias=:alias', array('alias' => $identifier));
            
            if(!$objects){
                if(Strategy::get('strictMode')){
                    throw new Exception('Unknown alias for ACL-ObjectCollection');
                }
                else{
                    $obj = new $model;
                    $obj->alias   = $identifier;
                    $obj->save();
                    
                    $objects = $onlyFirst ? $obj : array($obj);
               }
            }
            
        }
        
        //The object is searched by its model
        elseif(
                is_array($identifier) && 
                (isset($identifier['foreign_key']) && isset($identifier['model']))
            ){
                
            $objects = $model->$method('foreign_key = :foreign_key AND model = :model', 
                    array(':foreign_key' => $identifier['foreign_key'], ':model' => $identifier['model']));
            if(!$objects){
                if(Strategy::get('strictMode')){
                    throw new Exception('Unknown foreign key and/or model for ACL-ObjectCollection');
                }
                else{
                    $obj = new $model;
                    $obj->foreign_key   = $identifier['foreign_key'];
                    $obj->model         = $identifier['model'];
                    $obj->save();
                    
                    $objects = $onlyFirst ? $obj : array($obj);
                }
                
            }
        }
        
        //The object is passed directly - do not do anything
        elseif(is_a($identifier, "AclObject") || (
                is_a($identifier, "HiddenClass") 
                && is_subclass_of($identifier->pretends(), "Aclobject")
        )){
            $objects = $identifier;
            
            //If the object has another type than the requested type - transform
            //We can only check if a model is given at all
            if($model != NULL){
                $className = get_class($model);
                
                $bareType   = ucfirst(Util::getDataBaseType($objects));
                $realType = Strategy::getClass($bareType);
                //If the types don't match
                if($realType != $className){
                    /** Try to get the "highest" object
                     * Why? objects are firstly always resolved using their 
                     * CActiveRecord - properties. To retain consistency, 
                     * we have to use this record, if possible ( otherwise, we'd
                     * probably end up with a database entry of model "RGroup"
                     * and another one with the real model name!
                     * 
                     * If there's no associated model, this will always be resolved
                     * to the real class Name and no model name => consistency 
                     * retained
                     */
                    $newObjects = Util::getByIdentifierGraceful($objects);
                    
                    //Only return this if we've got an associated model
                    //Otherwise, fall back to the regular class
                    //Otherwise => will lead to model => "RGroup"
                    if($newObjects instanceof CActiveRecord)
                        return self::loadObjectsStatic($newObjects, $model, $onlyFirst);
                }
            }
            
            return $objects;
        }
        elseif(is_a($identifier, "CActiveRecord")){
            return self::loadObjectsStatic( array('model' => get_class($identifier), 'foreign_key' => $identifier->id), $model, $onlyFirst);
        }
        else{
            throw new Exception('Unknown ACL-Object specification');
        }
        
        return $objects;   
    }
    
    /**
     * This method returns all AclNodes being associated with this
     *
     * @access public
     * @param  AclObject object
     * @return array[AclNode]
     */
    public function getNodes(){
        $class = Util::getNodeNameOfObject($this);
        
        return $class::model()->findAll('collection_id = :id', array(':id' => $this->id));
    }
    
    /**
      * Processes post-saving tasks 
      */
     public function afterSave(){
         parent::afterSave();
         
         //If we're new here, we also need a new node for the permissions :)
         if($this->isNewRecord){
             $this->createNode();
         }
     }
     
     /**
      * Loads the associated object, if possible, and returns it
      * @return mixed   NULL or a child of CActiveREcord
      */
     public function getAssociatedObject(){
         if(is_subclass_of($this->model, "CActiveRecord")){
             $model = $this->model;
             return $model::model()->findByPk($this->foreign_key);
         }
         return NULL;
     }
    
     
     
}

?>
