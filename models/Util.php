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
    public function getByIdentifier($identifier){
        if($identifier->model == NULL || $identifier->foreign_key == NULL)
                return NULL;

        if(!is_subclass_of($identifier->model, "CActiveRecord"))
                throw new RuntimeException('Invalid Identifier (model): '.$identifier->model);

        $class = $idenetifier->model;
        return $class::model()->findByPk($identifier->foreign_key);
    }

}

?>
