<?php
/**
 * This file contains all the Business-Rules
 *
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.base
 */
class BusinessRules {
    
    /**
     * If you have a rule named "Rule", you can define it in two ways:
     * a) a public static function isRule of this class 
     * b) an entry "isRule" => "myFunc" where myFunc is a static function in this class
     * c) an entry "isRule" => array($myObject, "myFunction"), a callback
     * @var array   ruleName => functionIdentifier 
     */
    public static $ruleMap = array();
    
    /**
     * prototype:
     * public static function isRule(arr('child' => .., 'father' => ..), 
     *                               arr('child' => .., 'father' => ..),
     *                               string)
     * public static function isRule($aro, $aco, $action)
     */
    
    
    public static function isa1($aro, $aco, $action){
        return $aro['child']->alias == 'a2';
    }
    
    public static function isb1($aro, $aco, $action){
        return $aco['child']->alias == 'b2';
    }
    
    
    /**
     * Checks whether the given aro is has the given rule regarding the given
     * aco and action
     * @param string $rule  an action (special action: "is" for inheritance-checks)
     * @param array $aro    an instance of the aro (either CActiveRecord or RGroup)   
     * @param array $aco    an instance of the aco (either CactiveRecord or CGroup) 
     * @param string $action    the action to be performed
     * @return boolean 
     */
    public static function fulfillsBusinessRule($rule, $aro, $aco, $action){
        $callback = isset(self::$ruleMap[$rule]) ? self::$ruleMap[$rule] : $rule;
        
        /**
         * Is this a callback? If yes, is it valid? 
         */
        if(is_array($callback)){
            $this->assureIsCallable($rule, $callback);        
            return $callback($aro, $aco, $action);
        }
        
        /**
         * If there's such a business rule, it has to be a member of this class now 
         */
        if(!method_exists(__CLASS__, $callback))
            //If there's no rule defined, simply return false
            return false;
        else{
            return self::$callback($aro, $aco, $action);
        }
    }
    
    /**
     * Registers the given callback for the given business rule
     * @param string $rule the rule to register for
     * @param array $callback the callback to register for the rule
     * @param boolean $force throws an exception if set false and the new rule would overwrite \
     * an existing one 
     */
    public static function registerBusinessRule($rule, $callback, $force = false) {  
        
        //Check if rule is valid
        $alias = self::getAliasFromRule($rule);
        if(!$alias)
            throw new RuntimeException(Yii::t('acl', 'The rule {rule} is not a valid business rule'),
                    array('{rule}' => $rule));
        
        //Assure that the callback is valid
        self::assureIsCallable($rule, $callback);
        
        if(!$force){
            $aliases = self::listBusinessAliases();
            
            if(in_array($alias, $aliases))
                    throw new RuntimeException(Yii::t('acl', 'The business rule is already in use'));
        }
        
        //Finally, register
        self::$ruleMap[$rule] = $callback;
    }
    
    /**
     * Unregisters the given business rule
     * @param string $rule the rule to unregister
     * @return boolean returns true if a business rule has been unregistered, false otherwise
     * Please note that business rules defined as methods in this class cannot be unregistered
     */
    public static function unRegisterBusinessRule($rule){
        if(isset(self::$ruleMap[$rule])){
            unset(self::$ruleMap[$rule]);
            return true;
        }
        return false;
    }
    
    /**
     * @return array    all the used aliases
     */
    public static function listBusinessAliases(){
        $methods    = get_class_methods(__CLASS__);
        $aliases    = array();
        
        //filter out the rules
        foreach($methods as $method){
            //Consider a mapping, if it's used
            if(in_array($method, self::$ruleMap))
                    $method = array_search($method, self::$ruleMap);

            $name = self::getAliasFromRule($method);
            if($name)
                $aliases[] = $name;
            
        }
        
        /**
         * Add the callbacks from the ruleMap 
         */
        foreach(self::$ruleMap as $rule => $callback){
            if(is_array($callback))
                self::assureIsCallable($rule, $callback);
            
            $name = self::getAliasFromRule($rule);
            if($name)
                $aliases = array_merge($aliases, array($name));
        }
        
        return $aliases;

    }
    
    /**
     * Extracts the alias from the given rule name
     * @param string $rule the name of the rule
     * @return string|null the name of the alias
     */
    protected static function getAliasFromRule($rule){
        $matches = array();
        //Extract the alias itself
        preg_match('/^is(.*)$/', $rule, &$matches);

        $name = NULL;
        //Only if he's valid
        if(isset($matches[1]))
            $name = $matches[1];
        
        if(strlen($name))
            return $name;
        return NULL;
    } 
    
    /**
     * Assures that the given callback for the given rule is callable, throws an exception
     * otherwise
     * @param string $rule the rule the callback is assigned to  
     * @param array|string $callback the callback to check whether it is a callback
     */
    protected static function assureIsCallable($rule, $callback){
        if(!is_callable($callback)){
            $msg = Yii::t('acl', 'The Business Rule {rule} has an invalid callback',
                    array('{rule}' => $rule));
            throw new RuntimeException($msg);
        }
    }
}

?>
