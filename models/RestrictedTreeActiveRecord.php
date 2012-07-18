<?php

/**
 * This class is intended for objects which are located in a tree structure
 * themselves if the tree-structure should also use the permission system.
 * This way, the permission-tree is mirrored to the orginal tree.  
 *
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.base
 */
abstract class RestrictedTreeActiveRecord extends RestrictedActiveRecord{
   
   /**
    * @var int the parent-ID: we track changes and if they occur, we also move
    * the object in the ACL-tree
    */
    protected $oldParentId = NULL;
    
    public function afterFind() {
        parent::afterFind();
        $this->oldParentId = $this->parent_id;
        
        return true;
    }
    
    /**
     * If the parent has changed, adjust the permissions
     * @throws RuntimeException 
     */
    public function afterSave() {
        parent::afterSave();
        //If the Owner has changed - quit old parent in permission system and move to new!
        if ($this->oldParentId != $this->parent_id) {
           
            $acoClass = new CGroup();
            //First: find the aco-Object of yourself
            $aco = $acoClass->loadObject($this);
            if($aco === NULL)
                throw new RuntimeException('Aco-object does not exist');
            
            //If we moved to another parent  and we had an old one
            if($this->oldParentId !== NULL){
                if (!$aco->leave(array('model' => get_class($this), 'foreign_key' => $this->oldParentId)))
                    throw new RuntimeException('Unable to leave old parent-aco-object');
            }
            
            //Only choose a new parent if we have a new one - we don't necessarily have one :)
            if($this->parent_id !== NULL){
                $aco2 = $acoClass->loadObject(
                        array('model' => get_class($this), 'foreign_key' => $this->parent_id));
                if (!$aco->join($aco2))
                    throw new RuntimeException('Unable to choose new parent-aco');
            }
            
            //In the end, save the change
            $this->oldParentId = $this->parent_id;
        }
        
        return true;
    }
}

?>
