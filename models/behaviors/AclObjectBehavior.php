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
    public function beAro(){ $this->_type = "Aro"; }
    public function beAco(){ $this->_type = "Aco"; }
    public function isAro(){ return $this->_type == 'Aro'; }
    public function isAco(){ return !$this->isAro();}
    
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
       return $this->_obj = AclObject::loadObjectStatic($this->getOwner(), $type);
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
         
         foreach($identifiers as $identifier){
             
             //Bypass any checks
             if(!$owner->join($identifier, true))
                     throw new RuntimeException('Unable to join group');
         }
     }
     
     /**
      * Returns a list of identifiers to join for the given object
      * @param CActiveRecord    $obj  the object to fetch the autojoins for
      * @return array   an array of identifiers to join
      */
     protected function getAutoJoins($obj){
         
         $identifiers = array();
         $type        = lcfirst($this->getType());
         
         //Fetch the general config first
         $joins         = Strategy::get('autoJoinGroups');
         $identifiers   = isset($joins[$type]) ? $joins[$type] : array();  
         
         //Now, let's look if it has been overwritten
         if($obj instanceof CActiveRecord){
             $class = get_class($obj);
             
             if(isset($class::$autoJoinGroups))
                 $identifiers = $class::$autoJoinGroups;
         }
         
         return $identifiers;
     }
    
}
?>
