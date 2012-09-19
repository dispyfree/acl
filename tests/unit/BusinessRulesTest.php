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
        
        $author = new RGroup();
        $author->alias = 'Author';
        $this->assertTrue($author->save());
        
        $user = new RGroup();
        $user->alias = 'user';
        $this->assertTrue($user->save());
        
        /**
         * If guest mode is enabled and nobody's logged in (what's the default case
         * in unit tests), newly created objects are by default assigned to the
         * "Guest" group (see default configuration). If we act as some random user, we 
         * don't run into this hassle.
         */
        $randomUser = new RGroup();
        $randomUser->alias = 'someOtherUser';
        $this->assertTrue($randomUser->save());
        RestrictedActiveRecord::$inAttendance = $randomUser;
        
        $own = new CGroup();
        $own->alias = 'own';
        $this->assertTrue($own->save());
        
        $foreign = new CGroup();
        $foreign->alias = 'foreign';
        $this->assertTrue($foreign->save());
        
        $own->join('All');
        $foreign->join('All');
        
        /**
         * The author always has all permissions on whatever is his own
         */
        $author->grant('All', '*');
        
        /**
         * Now, the user is no author and therefore doesn't have any access 
         */
        $this->assertFalse($user->may($own, 'read'));
        $this->assertFalse($user->may($foreign, 'read'));
        
        /**
         * Register the business rule and lo and behold - he has the permission! 
         */
        BusinessRules::registerBusinessRule('isAuthor', array($this, 'isOwn'));
        $this->assertTrue($user->may($own, 'read'));
        //The user is no owner of the other one yet
        $this->assertFalse($user->may($foreign, 'read'));
        
        /**
         * Remove objects from database 
         */
        $objects = array($author, $user, $randomUser, $own, $foreign);
        
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
