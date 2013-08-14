<?php

/**
 * This class manages the strategy used for the tree as well as the includes
 * and provides the strategy-specific class-names
 * 
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.base
 */
class Strategy {
    
     /**
      * This defines the strategy to use. It's your duty to take care that all requirements
      * (e.g. database tables) exist
      * @var string
      */
     public static $strategy = 'nestedSet.pathMaterialization';
     

     protected static $initialized = false;
     
     /**
      * Assuming you put it there... change it otherwise ^^
      * @var string 
      */
     protected static $moduleName = 'acl';
     protected static $location = 'application.modules.acl';
     protected static $config   = NULL;
     protected static $configBackup = NULL;
     
     public static function initialize(){
         if(!static::$initialized){
             static::$initialized = true;
             
             //Try to get configuration out of the module configuration
             $module = Yii::app()->getModule(static::$moduleName);
             if ($module) {
                $strategy = $module->strategy;
                if (is_string($strategy)) {
                    static::$strategy = $strategy;
                }
                $strategy_config = $module->strategy_config;
             } else {
                $strategy_config = NULL;
             }

             $strategyPath = static::$location.'.components.strategies.'.static::$strategy;
             Yii::import($strategyPath.'.*');
             Yii::import($strategyPath.'.models.*');
            
             if (is_array($strategy_config) && !empty($strategy_config)) {
                 $config = $strategy_config;
             } else {
                 $config = require_once(Yii::getPathOfAlias($strategyPath.'.config').'.php');
             }
                          
             if(!$config)
                 throw new RuntimeException('Unable to load configuration');
             
             static::$configBackup = static::$config = $config;
             static::createShortcutClasses();
         }
     }
     
     /**
      * Gets the class-Name according to the chosen strategy
      * @param string $className 
      * @return string the resulting class-Name for the strategy
      */
     public static function getClass($className){
         static::initialize();
         //If this is a global class
         $globalClasses = array('AclObject', 'Action', 'RequestingActiveRecord', 
             'RestrictedActiveRecord', 'CGroup', 'RGroup');
         if(in_array($className, $globalClasses))
                 return $className;
         
         if(substr($className, 0, strlen(static::$config['prefix'])) == static::$config['prefix'])
                 return $className;
         
         return static::$config['prefix'].$className;
     }
     
     /**
      * Returns the given property of the strategy-config
      * @param string $propName they key of the property
      * @return mixed the value 
      */
     public static function get($propName){
         self::initialize();
         return @static::$config[$propName];
     }
     
     /**
      * Sets the given value of the strategy config
      * CAUTION: Changing these values can cause serious harm. Do only call 
      * this method if you know exactly what you are doing. 
      * Changing the config after the initialization of the application does virtually
      * always cause unexpected behavior.
      * 
      * @param string $propName they key of the property
      * @param mixed  $value    the new value
      * @return mixed the old value
      */
     public static function set($propName, $value){
         self::initialize();
         $oldValue = @static::$config[$propName];
         static::$config[$propName] = $value;
         return $oldValue;
     }
     
     /**
      * Resets the configuration to its initial state
      * @return array the current configuration 
      */
     public static function resetConfig(){
         $tmp = static::$config;
         static::$config = static::$configBackup;
         return $tmp;
     }
     
     /**
      * Just generates "AGroup" and "RGroup" aka
      * "AcessGroup" and "RequestGroup"  dynamically 
      */
     protected static function createShortcutClasses(){
         eval('class CGroup extends '.static::getClass('Aco').'{}');
         eval('class RGroup extends '.static::getClass('Aro').'{}');
     }
     
}

?>
