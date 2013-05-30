<?php

/**
 * RequestingActiveRecordBehavior Class File
 *
 * This class serves as the behavior to be used for all "actors" in the program who have 
 * to per form actions on other objects 
 * 
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.base
 */
abstract class AclObjectBehavior extends CActiveRecordBehavior{
    
    /**
     * This will hold the actual aco - object this behavior is linked to
     * @var mixed the acl-object
     */
    protected $_obj = NULL;
    
    /**
     *  Stores the type if the user has explicitely specified one
     * @var string either "Aco" or "Aro"
     */
    protected $_type = NULL;
    
    /**
     * A few helper functions to set and retrieve the type of this behavior's 
     * object explicitely 
     */
    public function beAro(){ $this->setType('Aro'); }
    public function beAco(){ $this->setType('Aco'); }
    public function isAro(){ return $this->_type == 'Aro'; }
    public function isAco(){ return $this->_type == 'Aco';}
    
    /**
     * Overwrite this method to return the actual class Name
     * @return  either "Aro" or "Aco"
     */
    abstract protected function getType();
    
    /**
     * Loads the associated Object
     * @throws RuntimeException 
     */
    protected function loadObject(){
        $type = ($this->_type !== NULL) ? $this->_type : $this->getType();
        $this->_obj = AclObject::loadObjectStatic($this->getOwner(), $type);
        
        if(!$this->_obj)
            throw new RuntimeException(Yii::t('acl', 'Unable to load aro object'));
        
        return $this->_obj;
    }
    
    /**
     * Loads all (direct) parent objects
     * @param   boolean $convert  whether to convert parent objects back 
     * to any associated real world entities, if they exist. 
     * Take heed: it can occur that in the result set, acl objects and real 
     * world entities are mixed. 
     * It is the caller's responsibility to avoid endless recursion if nodes are
     * self-referencing (acl won't care).
     * @see getParentAroObjects
     * @see getParentAcoObjects
     * @return type
     */
    function getParentObjects($convert = false)
    {
        //chooses type automatically
        $this->loadObject();
        
        $objects = $this->_obj->getParentObjects();
        
        if(!$convert)
            return $objects;
        
        //Otherwise try to convert them and fall back in case there is no associated entity
        return array_map(array('Util', 'getByIdentifierGraceful'), $objects);
    }
    
    /**
      * Joins the given object (now called: group)
      * @param mixed $obj
      * @return boolean
      */
     public function join($obj){
         $this->loadObject();
         $suc = $this->_obj->join($obj);
         
         return $suc;
     }
     
     /**
      * Leaves the given group
      * @param mixed $obj
      * @return boolean
      */
     public function leave($obj){
        $this->loadObject();
        $suc = $this->_obj->leave($obj);

        return $suc;
     }
     
     /**
      * Checks whether this object is somehow a child of the given object
      * @param mixed $obj
      * @return boolean
      */
     public function is($obj){
        $this->loadObject();
        return $this->_obj->is($obj);
     }
     
     
     /**
      * This method takes care that autoJoins are done
      * @param type $evt 
      */
     public function afterSave($evt){
         parent::afterSave($evt);
         
         $owner = $this->getOwner();
         if($owner->isNewRecord){
             $this->performAutoJoins();
         }
     }
     
     /**
      * Performs the autoJoins defined in the configuration or by the model
      * NOTE: This method will bypass any restrictions on joins
      */
     protected function performAutoJoins(){
         $owner = $this->getOwner();
         $identifiers = $this->getAutoJoins($owner);

         foreach($identifiers as $type => $joins){
             
             //Assure that the object chooses the right type
             $method = 'be'.ucfirst($type);
             $owner->$method();
             foreach($joins as $join){
                //Bypass any checks
                if(!$owner->join($join, true))
                        throw new RuntimeException('Unable to join group');
             }
         }
     }
     
     /**
      * Returns a list of identifiers to join for the given object
      * @param CActiveRecord    $obj  the object to fetch the autojoins for
      * @return array   an array('aro' => ..., 'aco'=>...) of identifiers to join
      */
     protected function getAutoJoins($obj){
         $identifiers = array();
         
         foreach($this->getTypes() as $type){
            //Fetch the general config first
            $generalJoins         = Strategy::get('autoJoinGroups');
            $joins   = isset($generalJoins[$type]) ? $generalJoins[$type] : array();  

            //Now, let's look if it has been overwritten on a per class basis
            if($obj instanceof CActiveRecord){
                $class = get_class($obj);

                if(isset($class::$autoJoinGroups) 
                   && isset($class::$autoJoinGroups[$type]))
                    $joins = $class::$autoJoinGroups[$type];
            }
            $identifiers[$type] = $joins;
         }
            return $identifiers; 
     }
     
     /**
      * Sets the given type
      * @param string $type (either "Aro" or "Aco")
      * @param boolean $recursive if true, will set the type on all other behaviors too
      */
     protected function setType($type, $recursive = false){
         $this->_type = $type;
         if(!$recursive)
             return true;
         
         $owner = $this->getOwner();
         $aroBehavior = $owner->asa('aro');
         $acoBehavior = $owner->asa('aco');
         
         if($aroBehavior && $aroBehavior != $this)
             $aroBehavior->setType($type);
         if($acoBehavior && $acoBehavior != $this)
             $acoBehavior->setType($type);
     }
     
     /**
      * Returns an array containing all behaviors the owner of this behavior
      * can assume.
      * That are:
      * @return array array('aro'), array('aro', 'aco'), array('aco')
      */
     protected function getTypes(){
         $types = array();
         $owner = $this->getOwner();
         if($owner->asa('aro'))
             $types[] = 'aro';
         if($owner->asa('aco'))
             $types[] = 'aco';
         
         return $types;
     }
    
}
?>
