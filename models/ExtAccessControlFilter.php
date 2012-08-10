<?php

/**
 * This implements an extended Access Control Filter for integration with ACL
 * 
 * @package acl.base
 * @author dispy<dispyfree@googlemail.com>
 * @license LGPLv2
 */

class ExtAccessControlFilter extends CAccessControlFilter{
    
    /**
     * @param array $rules list of access rules.
     */
    public function setRules($rules) {
        foreach ($rules as $rule) {
            if (is_array($rule) && isset($rule[0])) {
                $r = new ExtAccessRule;
                $r->allow = $rule[0] === 'allow';
                foreach (array_slice($rule, 1) as $name => $value) {
                    if ($name === 'expression' || $name === 'roles' || $name === 'message')
                        $r->$name = $value;
                    else
                        $r->$name = array_map('strtolower', $value);
                }
                $this->_rules[] = $r;
            }
        }
    }
}

class ExtAccessRule extends CAccessRule{
    /**
     * @param IWebUser $user the user object
     * @return boolean whether the rule applies to the role
     */
    protected function isRoleMatched($user) {
        if (empty($this->roles))
            return true;
        
        //retrieve Collection
        $class = Strategy::getClass('Aro');
        $aro = Util::enableCaching($class::model(), 'aroObject')->find('foreign_key = :id AND model = :model',
                array(':id' => $user->id, ':model' => RestrictedActiveRecord::$model)
                );
        
        if(!$aro)
            return false;
        
        foreach ($this->roles as $role) {
            if($aro->is($role))
                return true;
        }
        return false;
    }
}
?>
