<?php

class CommentTest extends CTestCase
{
    
    public function testCreateUser(){
        $this->joe = new User();
        $this->joe->name = 'joe';
        
        $this->assertTrue($joe->save());
        $this->assertNotNull(User::model()->findByPk($this->id));
        
        $this->pete = new User();
        $this->pete->name = 'pete';
        
        $this->assertTrue($joe->save());
        $this->assertNotNull(User::model()->findByPk($this->pete->id));
    }
    
    public function testCreateGroup(){
        $this->family = new RGroup();
        $this->family->alias = 'family';
        $this->assertTrue($family->save());
        $this->assertNotNull(User::model()->findByPk($this->family));
        
        $this->village = new RGroup();
        $this->village->alias = 'village';
        $this->assertTrue($this->village->save());
        $this->assertNotNull(User::model()->findByPk($this->village));
        
        $this->foreign = new RGroup();
        $this->foreign->alias = 'foreign';
        $this->assertTrue($this->foreign->save());
        $this->assertNotNull(User::model()->findByPk($this->foreign));
    }
    
    public function testJoinGroup(){
        $this->assertTrue($this->family->join($this->village));
        $this->assertTrue($this->family->is('village'));
        
        $this->assertTrue($this->joe->join('family'));
        $this->assertTrue($this->joe->is('family'));
        
        $this->assertTrue($this->joe->is('village'));
        $this->assertFalse($this->joe->is('foreign'));
        
        //NOw, pete's turn
        $this->assertFalse($this->pete->is('village'));
        $this->assertFalse($this->pete->is('familty'));
        
        //He cannot leave a group in which he's not directly
        $this->assertFalse($this->joe->leave('village'));
        $this->assertTrue($this->joe->leave('family'));
        
        //Now, everything's reversed
       $this->assertFalse($this->joe->is('family'));
       $this->assertFalse($this->joe->is('village'));
        
    }
    
    function testDeletion(){
        $this->assertTrue($this->village->delete());
        $this->assertTrue($this->family->delete());
        $this->assertTrue($this->foreign->delete());
        
        $this->assertTrue($this->joe->delete());
        $this->assertTrue($this->pete->delete());
    }
}
?>
