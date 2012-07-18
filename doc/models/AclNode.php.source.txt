<?php

/**
 * AclNode
 * This is the base class for all Nodes of the trees, providing basic functionality 
 *
 * @author dispy <dispyfree@googlemail.com>
 * @package acl.base
 * @license LGPLv2
 */
abstract class AclNode extends CActiveRecord{
    /**
     * This will take over the permissions of another node belonging to the same
     * AclObject, if the record is a new one
     */
    public function afterSave(){
        parent::afterSave();
        if($this->isNewRecord){           
            //First, find a random node of the same object
            $node = $this::model()->find('collection_id = :col_id AND id != :id',
                array(':col_id' => $this->collection_id, ':id' => $this->id));
            
            //If there exists a node... otherwise we don't have to overtake anything
            if($node !== NULL){
                    //Take over permissions of the the node
                    $this->takeOverPermissions($node);
                    $this->takeOverSubTree($node);
            }
        }
    }
    
    /**
     * Runs post-deletion errands 
     */
    public function afterDelete(){
        parent::afterDelete();
        
        //First of all: delete all child-nodes and permissions
        $this->removeFullRecursively();
    }
    
    /**
     * Removes all child-nodes and their associated permissions
     * @throws RuntimeException 
     */
    abstract protected function removeFullRecursively();
    
      /**
     * This method copies all permissions assigned to another 
     * AclNode-Object of the same AclObject 
     * @param AclNode the node to take the permissions from
     */
    abstract protected function takeOverPermissions($node);
    
    /**
     * Copies the subtree of the given other node of the same object to this
     * new node 
     * @param PmAclNode $node the node to take subtree from
     */
    protected function takeOverSubTree(PmAclNode $node){
        $this->branchNodeSubTree($node, $this);
    }
    
    /**
     * Copies all children of $source recursively into $destination
     * This branching is necessary because: If an AclObject is a child of another one, 
     * every AclNode of the parent object has to have one AclNode of the child AclObject
     * as it's child (this is due to the lookup-mechanism this extension uses)
     * If a new AclNode is created (for example because the parent object itself joins
     * another object), the subtree of an existing node is copied to the new node
     * 
     * In fact "copied" is the wrong term, because each node isn't cloned but 
     * a surrogate is created which is in fact another object - but a node of the same
     * AclObject having the same children as the original one.
     *
     * @access public
     * @param  AclNode source
     * @param  AclNode destination
     * @return int  the number of branched nodes (recursive!)
     */
    abstract public function branchNodeSubTree( $source, $destination);
    
     /**
     * Generates the condition matching the direct AclNodes of this node
     * @return array(string, array) the first is the condition, the second one the params 
     */
    abstract protected function generateDirectChildrenCondition();
    
     /**
     * Generates the condition matching the direct parent AclNodes of this node
     * @return array(string, array) the first is the condition, the second one the params 
     */
    abstract protected function generateDirectParentCondition();
    
    /**
     * Returns all the direct children of the given Node
     *
     * @access public
     * @return array[AclNode]
     */
    public function getDirectChildren(){
        list($condition, $params) = $this->generateDirectChildrenCondition();
        return $this->findAll($condition, $params);
    }
    
    /**
     * Returns the direct parent AclNodes of this node
     *
     * @access public
     * @param  AclNode node
     * @return array[AclNode]
     */
    public function getDirectParents(){
        list($condition, $params) = $this->generateDirectParentCondition();
        return $this->findAll($condition, $params);
    }
    
    abstract public function __clone();
}

?>
