<?php

/**
 * Specialization of Permission providing some convenient functionality
 *
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.strategies.nestedSet.pathMaterialiization
 */
class PmPermission extends Permission{
    
    /**
     * Removes all permissions associated to the given object
     * @param AclObject $obj 
     * @param array[string] $path If given, the paths of the object ware not 
     * retrieved but taken from this parameter
     */
    public static function deleteByObject(AclObject $obj, array $paths = NULL){
        $type = Util::getDataBaseType($obj);
        
        if($paths === NULL)
            $paths = $obj->getPaths();
        
        $condition = PmPathManager::buildMultiplePathCondition($type.'_path', $paths);
        
        return PmPermission::model()->deleteAll($condition); 
    }
    
    public static function getPathFieldForType($type){
        return $type.'_path';
    }
}

?>
