<?php
Strategy::initialize();

/*
 * This unit test shows at least some ways how business rules can be used
 */

class BusinessRulesTest extends CTestCase{
    
    public function testBusinessRules(){
        
        /**
         * Create our objects in the first place. We'll use
         * business rules for Aros in this example; it's also possible
         * to use them for Acos as well (and also both simultaneously)
         * 
         * Note that autoJoins do only apply to CActiveRecord instances. 
         * Most times you'll use them, but we'll refrain from them as they'd bloat
         * our test case 
         */
        $aco = new CGroup();
        $aco->alias = 'own';
        $this->assertTrue($aco->save());
        
        $aco1 = new CGroup();
        $aco1->alias = 'foreign';
        $this->assertTrue($aco1->save());
        
        $aco->join('All');
        $aco1->join('All');
        
        $aro = new RGroup();
        $aro->alias = 'Author';
        $this->assertTrue($aro->save());
        
        $aro1 = new RGroup();
        $aro1->alias = 'user';
        $this->assertTrue($aro1->save());
        
        /**
         * The author always has all permissions on whatever is his own
         */
        $this->assertTrue($aro->grant('All', '*'));
        
        /**
         * Now, the user is no author and therefore doesn't have any access 
         */
        $this->assertFalse($aro1->may($aco, 'read'));
        $this->assertFalse($aro1->may($aco1, 'read'));
        
        /**
         * Register the business rule and lo and behold - he has the permission! 
         */
        BusinessRules::registerBusinessRule('isAuthor', array($this, 'isOwn'));
        $this->assertTrue($aro1->may($aco, 'read'));
        //The user is no owner of the other one yet
        $this->assertFalse($aro1->may($aco1, 'read'));
        
        /**
         * Remove objects from database 
         */
        $objects = array($aro, $aro1, $aco, $aco1);
        
        foreach($objects as $obj){
            $this->assertTrue($obj->delete());
        }
        
    }
    
    /**
     * Our business rule is always true if the aco has the alias 'own'
     * Note that all objects passed in $aro and $aco are always the most "highest" 
     * objects - that means that if there is an associated CActiveRecord for 
     * any passed object, it will be passed instead. 
     * 
     * A regular use would be to compare user_id with the ID of the user or, for instance, 
     * check whether a guest is owner of an object (using the session information)
     */
    public function isOwn($aro, $aco, $action){
        return $aco['child']->alias == 'own';
    }
}
?>
