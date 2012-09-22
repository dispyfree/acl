<?php


Strategy::initialize();

/**  
 * This test case addresses the following issue:
 * #3 Permission Insertion does not work properly
 * https://github.com/dispyfree/acl/issues/3
 * 
 * To run this test, you'll need this additional table:
  CREATE TABLE IF NOT EXISTS `tbl_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
 */

require_once(__DIR__.'/../common/testUser.php');
require_once(__DIR__.'/../common/testPerson.php');

class Issue_3_Test extends CTestCase
{
    
    public function testIssue(){
        /**
         * Note that for simplicity, we'll use only one CActiveRecord model here 
         */
        /**
         * Create objects in the first place 
         */
        $objects = array(
           'testUser' => array(
                'SuperUser',
            ),
            'testPerson' => array(
              'PersonA'  
            ),
            'RGroup' => array(
                'Admin',
                'SuperAdmin',
                'User'
            )
        );
        
        foreach($objects as $type => $arr){
            foreach($arr as $alias){
                $$alias = new $type;
                if(isset($$alias->alias))
                    $$alias->alias = $$alias;
                $this->assertTrue($$alias->save());
            }
        }
        
        /**
         * Build hierarchy 
         */
        $this->assertTrue($SuperAdmin->join('Admin'));
        $this->assertTrue($Admin->join('User'));
        $this->assertTrue($SuperUser->join('SuperAdmin'));
        
        
        $SuperUser->grant('User', '*');
        $SuperUser->grant('Person', '*');
        $SuperUser->grant($SuperUser, 'read,update');
        $SuperUser->grant($PersonA, 'read,update');
        
        //Crucial part
        $Test = new RGroup();
        $Test->alias = 'Test';
        $this->assertTrue($Test->save());
        
        $this->assertTrue($SuperUser->join('Test'));

        /**
         * Remove objects 
         */
         foreach($objects as $type=> $arr)
            foreach($arr as $obj)
                $this->assertTrue($$obj->delete()); 
         $this->assertTrue($Test->delete());
    }
            
}
?>