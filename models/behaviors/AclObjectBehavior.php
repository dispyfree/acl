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
     * Overwrite this method to return the actual class Name
     * @return  either "Aro" or "Aco"
     */
    abstract protected function getType();
    
    /**
     * Loads the associated Object
     * @throws RuntimeException 
     */
    protected function loadObject(){
       return AclObject::loadObjectStatic($this->getOwner(), $this->getType());
    }
    
    /**
      * Joins the given object (now called: group)
      * @param mixed $obj
      * @return boolean
      */
     public function join($obj){
         $this->loadObject();
         return $this->_obj->join($obj);
     }
     
     /**
      * Leaves the given group
      * @param mixed $obj
      * @return boolean
      */
     public function leave($obj){
        $this->loadObject();
        return $this->_obj->leave($obj);
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
    
}
?>
