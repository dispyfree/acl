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
     * b) an entry "isRule" => "myFunc" where myFunc is a static function in 
     * this class
     * @var array   ruleName => functionName 
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
        $rule = isset(self::$ruleMap[$rule]) ? self::$ruleMap[$rule] : $rule;
        if(!method_exists(__CLASS__, $rule))
            //If there's no rule defined, simply return false
            return false;
        else{
            return self::$rule($aro, $aco, $action);
        }
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
            
            //Extract the alias itself
            preg_match('/^is(.*)$/', $method, &$matches);
            
            //Only if he's valid
            if(isset($matches[1]))
                $name = $matches[1];

            if(strlen($name))
                $aliases[] = $name;
            
        }
        
        return $aliases;

    }
}

?>
