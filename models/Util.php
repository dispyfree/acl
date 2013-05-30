<?php

/**
 * Utility Class 
 * @package acl.strategy.nestedSet.pathMaterialization
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 */
class Util {
    
    /**
     * Returns the database-type of the given object
     * @param AclObject $obj
     * @return string
     * @throws InvalidArgumentException 
     */
    public static function getDataBaseType($obj){
        $class = get_class($obj);
        
        switch($class){
            case Strategy::getClass('Aro'):
            case Strategy::getClass('AroNode'):
            case 'RGroup':
                return 'aro';
            case Strategy::getClass('Aco'):
            case Strategy::getClass('AcoNode'):
            case 'CGroup':
                return 'aco';
            default:
                throw new InvalidArgumentException('Unexpected Object');
        }
    }
    
    /**
     * Returns the class-name of the node belonging to the given Object
     * @param PmAclObject $obj
     * @return string
     * @throws InvalidArgumentException 
     */
    public static function getNodeNameOfObject(AclObject $obj){
        $class = get_class($obj);
        
        switch($class){
            case Strategy::getClass('Aro'):
            case 'RGroup':
                return Strategy::getClass('AroNode');
            case Strategy::getClass('Aco'):
            case 'CGroup':
                return Strategy::getClass('AcoNode');
            default:
                throw new InvalidArgumentException('Unexpected Object');
        }
    }
    
    /**
     * Generates a database-IN-statement out of the given options
     * @param array[string] $options
     * @return string the in-statement 
     */
    public static function generateInStatement($options){
        /**
         *  Special case: if there are no options (which is a valid possibility)
         *  the in statement is always false. 
         *  Example: a object may or may NOT have any nodes. If it doesn't need
         *  nodes, why should it bother to have some?
         */
        if(count($options) == 0)
            //IN-Clauses don't contain NULL anyway (no valid id or alias)
            //this is the most convenient way to keep a valid syntax
            return ' IS NOT NULL AND False ';
        
        $ret = ' IN ( ';
        
        foreach($options as $key =>$option){
            if($key > 0)
                $ret.= ' , ';

            $ret.= ctype_digit($option) ? $option : ' "'.$option.'" ';
        }
        $ret.= ' ) ';
        
        return $ret;
    }
    
    /**
     * Extracts the IDs of the given bunch of objects and returns them in
     * an indexed arry
     * @param array $objects
     * @return array[int]
     */
    public static function getIdsOfObjects($objects){
        $ret = array();
        foreach($objects as $obj){
            $ret[] = $obj->getPrimaryKey();
        }
        return $ret;
    }

    /**
     *  Retrieves the requested object by the given identifier (an object)
     *  Will throw an exception if the model-name is invalid but returns NULL
     *  if the identifier doesn't match any instance.
     * @param AclObject identifier [model, foreign_key]
     */
    public static function getByIdentifier($identifier){
        if($identifier->model == NULL || $identifier->foreign_key == NULL)
                return NULL;

        if(!is_subclass_of($identifier->model, "CActiveRecord"))
                throw new RuntimeException('Invalid Identifier (model): '.$identifier->model);

        $class = $identifier->model;
        return self::enableCaching($class::model(), 'aclObject')->findByPk($identifier->foreign_key);
    }
    
    /**
     * Behaves just as getByIdentifier but returns the object itself if no
     * associated record is found
     * @param AclObject $identifier
     * @return mixed 
     */
    public static function getByIdentifierGraceful($identifier){
        /**
         * If this is already the desired instance
         * Aros/Acos are also CActiveRecords, nevertheless they are not what we want here
         */
        if(($identifier instanceof CActiveRecord) 
                && !($identifier instanceof AclObject))
            return $identifier;
        
        $res = self::getByidentifier($identifier);
        if($res == NULL)
            return $identifier;
        return $res;
    }
    
    /**
     * Replaces all occurences of a key in $string with the keys value in $array 
     * @param string    $string    the string to replace in
     * @param array     $array     the keys/values
     */
    public static function rep($string, $array){
        foreach($array as $key=>$value){
            $string = str_replace($key, $value, $string);
        }
        
        return $string;
    }
    
    public static function addGroupRestr($relation, $restrictions){
        return RestrictedActiveRecordBehavior::addGroupRestriction($relation,
                $restrictions);
    }
    
    /**
     * Enables caching for the given object using the invalidation rules from the config
     * @param mixed $obj    the object to enable Caching for
     * @param string $type  the type of the object (collection, action or permission)
     * @param CCacheDependency $dependency
     * @param int $queryCount
     * @return mixed
     */
    public static function enableCaching($obj, $type, $dependency = NULL, $queryCount = 1){
        $types = array('collection', 'action', 'permission', 'aclObject', 'aroObject', 'structureCache');
        
        if(!in_array($type, $types))
                throw new RuntimeException('Invalid cache type:'.$type);
        
        $cachingSettings = Strategy::get('caching');
        
        //Set the cache component, if changed
        $dbConn = $obj->getDbConnection();
        $oldCache = NULL;
        $newCache = $cachingSettings['cacheComponent'];
        if($dbConn->queryCacheID != $newCache){
            $oldCache = $dbConn->queryCacheID;
            $dbConn->queryCacheID = $cachingSettings;
        }
        
        $results = $obj->cache($cachingSettings[$type], $dependency, $queryCount);
        
        //Reset cache if overwritten
        if($oldCache !== NULL)
            $dbConn->queryCacheID = $oldCache;
        
        return $results;
    }
    
    public static function flushCache(){
        if(isset(Yii::app()->cache))
            return Yii::app()->cache->flush();
        return true;
    }

}

?>
