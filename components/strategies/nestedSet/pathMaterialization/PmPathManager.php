<?php

/**
 * The Path-Manager provides basic functionality for working with paths such as:
 * - building paths
 * - splitting the paths up into their ids
 * - building Query-conditions
 * 
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.strategies.nestedSet.pathMaterialiization 
 */
class PmPathManager {
   
    /**
     * Appends the given ID (denoting an element) to the given path 
     * 
     * @param string $path
     * @param string $id
     * @return string   the full path including the new element 
     */
    public static function appendToPath($path, $id){
        $separator = PmPathManager::getSeparator();
        if(strlen($path) > 0 && $path[strlen($path) -1] != $separator)
            $path .= $separator;
        return $path.$id.self::getSeparator();
    }
    
    /**
     * Returns the parent path of the given path and the ID of the parent
     * @param array[string path, string ID] $path 
     */
    public static function getParentPath($path){
        $pos = strrpos($path, PmPathManager::getSeparator());
        
        //If it's like: /blablubb/dong/
        //But consider path "4" ^^
        if($pos == strlen($path) - 1 && strlen($path) > 1 ){
            $path = substr($path, 0, -1);
            $pos = strrpos($path, PmPathManager::getSeparator());
        }
        //Why? If the separator hasn't been found, we don't need to omit him!
        $id = substr($path, $pos + ($pos !== false ? 1 : 0));
        $newPath = substr($path, 0, $pos);
        
        return array('path' => $newPath,'id' => $id);
    }
    
    /**
     * Builds an sql-condition like: ($field LIKE [...] [AND condition]) OR ($field LIKE [...] [AND condition])
     * for all the given paths
     * @param string    $field  the field to match (for example: path)
     * @param array[string] $paths
     * @param string $additionalCondition If given, this will be used as an additional condition
     * to every single path-like-condition (bound with and)
     * Occurences of :path will be replaced with the provided path
     * 
     * @todo: to be checked if the order is correct in all uses (a regexp b => hierarchy?)
     * @return string   the condition 
     */
    public static function buildMultiplePathCondition($field, $paths, $additionalCondition = ''){
        /**
         * Caveat: if there are no paths, there's nothing to match
         * => always false 
         */
        if(count($paths) == 0)
            return ' False ';
        
        $condition = ' ( ';
        
        foreach($paths as $index => $path){
            //If we aren'T at the beginning
            if($index != 0)
                $condition .= ' OR ';
            
            $condition .= " (".$field." REGEXP CONCAT('^', '".$path."') ". 
                        ($additionalCondition ? ' AND '.str_replace(':path', $path, $additionalCondition) : '')
                        ." ) ";
        }

        return $condition.' ) ';
    }
    
    public static function getSeparator(){
        return '/';
    }
    
}

?>
