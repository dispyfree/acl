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
     * public static function isRule($aro, $aco, $action)
     */
    
    /*
    public static function ispicGroup($aro, $aco, $action){
        return true;
    }
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
}

?>
