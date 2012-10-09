<?php

Strategy::initialize();

/**  
 * This test case addresses the following issue:
 * #1 Nested Access Requesting group does not work properly
 * https://github.com/dispyfree/acl/issues/1
 */
class Issue_1_Test extends CTestCase
{
    

    public function testIssue(){
        /**
         * Create all involved objects first
         */
        $objects = array(
            'user' => 'User', 
            'admin' => 'Admin', 
            'superAdmin' => 'superAdmin',
            'superUser' => 'superUser'
        );

        foreach($objects as $name => $alias){
            ${$name} = new RGroup();
            ${$name}->alias = $alias;
            $this->assertTrue(${$name}->save());
        }

        //Perform Actions
        $this->assertTrue($superUser->join('superAdmin'));
        $this->assertTrue($admin->join('User'));
        $this->assertTrue($superAdmin->join('Admin'));
        
        /**
         * Espected:
         * superUser is child of superAdmin
         * superAdmin ist child of admin
         * admin is child of User
         */
        $this->assertTrue($superUser->is('superAdmin'));
        $this->assertTrue($superAdmin->is('admin'));
        $this->assertTrue($admin->is('User'));

        $superAdmin->leave('Admin');
      
        /**
         * Espected:
         * superUser is child of superAdmin
         * superAdmin is NO child of admin
         * => superUser is NO child of User
         * admin is child of User
         */
        $this->assertTrue($superUser->is('superAdmin'));
        $this->assertFalse($superAdmin->is('Admin'));
        $this->assertFalse($superUser->is('User'));
        $this->assertTrue($admin->is('User'));
        
        //Remove objects from database
        foreach($objects as $name => $alias){
            $this->assertTrue(${$name}->delete());
        }
    }

    protected function is($child, $parent){
        return $child->is($parent);
    }
}
?>
