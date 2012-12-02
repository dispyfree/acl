<?php

/**
 * Implements path-materilization and node-specific logic for tree-operations
 *
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.strategies.nestedSet.pathMaterialiization
 */
abstract class PmAclNode extends AclNode{
    
    /**
     * Removes all child-nodes and their associated permissions
     * @throws RuntimeException 
     */
    protected function removeFullRecursively(){
        $path = PmPathManager::appendToPath($this->path, $this->id);
        
        //Firstly: remove nodes
        $num = static::model()->deleteAll('path REGEXP "^'.$path.'" ');

        if($num === false)
            throw new RuntimeException('Unable to remove child nodes');
        
        /**
         * Secondly: remove permissions
         * 1) Remove direct permissions of this node
         * 2) Remove permissions of all child nodes
         */
        $type = Util::getDataBaseType($this);
        $num = PmPermission::model()->deleteAll($type.'_id = :id', array(':id' => $this->id));
        if($num === false)
            throw new RuntimeException('Unable to remove directly associated permissions');
        
        $num = PmPermission::model()->deleteAll($type."_path REGEXP '^".$path."'");
        
        if($num === false)
            throw new RuntimeException('Unable to remove childrens\' associated permissions');
    }
    
    
    /**
     * This method copies all permissions assigned to another 
     * AclNode-Object of the same AclObject 
     * @param AclNode the node to take the permissions from
     */
    protected function takeOverPermissions($node){
        foreach($node->permissions as $permission){
            $permission = $permission->cloneObj();
            
            /**
             * Depending on the object type, we must overwrite one part or
             * the other one 
             */
            $type = Util::getDataBaseType($this);
            
            $permission->{$type.'_id'}    = $this->id;
            $permission->{$type.'_path'}   = PmPathManager::appendToPath($this->path, $this->id);
            if(!$permission->save())
                throw new RuntimeException('Unable to clone permission');
        }
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
    public function branchNodeSubTree( $source, $destination){
        $nodes = $source->getDirectChildren();
        $count = count($nodes);
         
        $newPath = PmPathManager::appendToPath($destination->path, $destination->id);
        foreach($nodes as $node){
            $newNode        = clone $node;
            $newNode->path  = $newPath;
            if(!$newNode->save())
                throw new RuntimeException('Unable to branch node '.$node->id);
            $count += $newNode->branchNodeSubtree($node, $newNode);
        }
        
        return $count;
    }
    
    /**
     * Generates the condition matching the direct AclNodes of this node
     * @return array(string, array) the first is the condition, the second one the params 
     */
    protected function generateDirectChildrenCondition(){
        $path = PmPathManager::appendToPath($this->path, $this->id);
        return array(
            'path = :path',
            array(':path' => $path)
        );
    }
    
     /**
     * Generates the condition matching the direct parent AclNodes of this node
     * @return array(string, array) the first is the condition, the second one the params 
     */
    protected function generateDirectParentCondition(){
        //Get Parent path and ID
        $parent = PmPathManager::getParentPath($this->path);
        return array(
            'path = :path AND id = :id',
            array(':path' => $parent['path'], 
                  ':id' => $parent['id']
            )
        );
    }
    
    /**
     * Gets the path of this node including itself
     * @return string the own path 
     */
    public function getOwnPath(){
        return PmPathManager::appendToPath($this->path, $this->id);
    }
    
    
    public function __clone(){
        //it should be a completely new node
        $this->id = NULL;
        $this->isNewRecord = true;
        $this->path = NULL;
    }
}

?>
