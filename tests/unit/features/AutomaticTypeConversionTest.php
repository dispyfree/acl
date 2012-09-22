<?php

Strategy::initialize();

/*
 * This unit test shows and tests the automatic type conversion
 * To run this test, you'll need this additional table:
 * 
  CREATE TABLE IF NOT EXISTS `tbl_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

 */

class testUser extends CActiveRecord{
    public function behaviors(){
            return array(
              'aro'  => 'acl.models.behaviors.RestrictedActiveRecordBehavior',
              'aco'  => 'acl.models.behaviors.RequestingActiveRecordBehavior'
            );
     }
     
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return '{{user}}';
    }
}

class AutomaticTypeConversionTest extends CTestCase{
    
    public function testConversion(){
        
        /**
         * Create objects in the first place 
         */
        $objects = array(
            'testUser' => array(
                'userA',
                'userB'
            ),
            'CGroup' => array(
                'group'
            )
        );
        
        foreach($objects as $type => $arr){
            foreach($arr as $alias){
                $$alias = new $type;
                $this->assertTrue($$alias->save());
            }
        }
        
        $group->alias = 'group';
        $this->assertTrue($group->save());
       
        /**
         *  Give B some rights on the group and let userA join that group
         */
        $userB->grant('group', '*');
        $this->assertFalse($userB->may($userA, 'read'));
        
        $userA->beAco();
        $this->assertTrue($userA->join('group'));
        $this->assertTrue($userB->may($userA, 'read'));
        $this->assertTrue($userA->leave('group'));
        $this->assertFalse($userB->may($userA, 'read'));
        
        //And once more, directly
        $userB->grant($userA, 'read');
        $this->assertTrue($userB->may($userA, 'read'));
        
        //Remove all objects
        foreach($objects as $type=> $arr)
            foreach($arr as $obj)
                $this->assertTrue($$obj->delete()); 
    }
    
}    
 ?>