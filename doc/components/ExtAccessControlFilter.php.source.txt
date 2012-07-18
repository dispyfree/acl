<?php

//Import/Create the classes
Yii::app()->getModule('acl');

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
        $aro = RGroup::model()->find('foreign_key = :id AND model = :model',
                array(':id' => $user->id, ':model' => RequestingActiveRecord::$model)
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
