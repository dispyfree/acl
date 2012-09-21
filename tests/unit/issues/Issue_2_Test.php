<?php


Strategy::initialize();

/**  
 * This test case addresses the following issue:
 * #2 Checking User in Group does not work properly
 * https://github.com/dispyfree/acl/issues/2
 */
class Issue_2_Test extends CTestCase
{
    
    public function testIssue(){
        /**
         * Create Objects in the first place; 
         * It doesn't matter which type they have 
         */
         $objects = array(
             /**
              * secondUser is child of parent1, user isn't
              * parent1 is child of parent2 (=> has >= 2 child nodes)
              */
            'user' => 'User', 
            'secondUser' => 'secondUser',
            'parent1' => 'Parent1',
            'parent2' => 'Parent2'
        );

        foreach($objects as $name => $alias){
            ${$name} = new RGroup();
            ${$name}->alias = $alias;
            $this->assertTrue(${$name}->save());
        }
        
        $this->assertTrue($parent1->join('parent2'));
        
        /**
         * Let the user join the parents
         */
        $this->assertFalse($secondUser->is('parent1'));
        $this->assertFalse($secondUser->is('parent2'));
        
        $this->assertTrue($secondUser->join('parent1'));
        
        $this->assertTrue($secondUser->is('parent1'));
        $this->assertTrue($secondUser->is('parent2'));
        
        /**
         * user isn't a child of any collection
         * The check for parent2 will work anyway, because only one node is involved
         */
        $this->assertFalse($user->is('parent2'));
        $this->assertFalse($user->is('parent1')); // << it will fail here

         //Remove objects from database
        foreach($objects as $name => $alias){
            $this->assertTrue(${$name}->delete());
        }
    }
   
}
?>
