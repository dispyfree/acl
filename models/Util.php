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
            case 'AGroup':
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
            $ret[] = $obj->id;
        }
        return $ret;
    }

    /**
     *  Retrieves the requested object by the given identifier (an object)
     *  Will throw an exception if the model-name is invalid but return NULL
     *  if the identifier doesn't match any instance.
     * @param AclObject identifier [model, foreign_key]
     */
    public static function getByIdentifier($identifier){
        if($identifier->model == NULL || $identifier->foreign_key == NULL)
                return NULL;

        if(!is_subclass_of($identifier->model, "CActiveRecord"))
                throw new RuntimeException('Invalid Identifier (model): '.$identifier->model);

        $class = $identifier->model;
        return $class::model()->findByPk($identifier->foreign_key);
    }
    
    /**
     * Behaves just as getByIdentifier but returns the object itself if no
     * associated record is found
     * @param AclObject $identifier
     * @return mixed 
     */
    public static function getByIdentifierGraceful($identifier){
        //If this is already the desired instance
        if($identifier instanceof CActiveRecord)
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

}

?>
