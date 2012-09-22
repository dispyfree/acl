<?php

Strategy::initialize();

/*
 * This unit test shows and tests the functionality of virtual objects
 */

class VirtualObjectTest extends CTestCase{
    
    /**
     * the created sandbox 
     * @var mixed
     */
    protected $sandbox = NULL;
    
    public function testVirtualObject(){
        //We overwrite the virtual objects firstly
        Strategy::set('virtualObjects', array('Guest'));
        
        //We want to know if the object has been created and which object it is
        Strategy::set('virtualObjectCallback', array($this, 'sandboxCallback'));
        
        /**
         * Let's see: If we create a new aco object, a new aro object will be created
         * as we're currently the guest 
         */
        $aco = new CGroup();
        $aco->alias ='myAco'; // << actually we do not care
        $this->assertTrue($aco->save());
        
        /**
         * Note that the auto-assignment of permissions does only apply for 
         * CActiveRecords. As we do not use them, we have to do it ourselves: 
         */
        $user = AclObject::loadObjectStatic('Guest', 'Aro');
    
        /**
         * Bypass check: as auto-assignment is not active, we actually don't have
         * any permission up to now :) 
         */
        $user->grant($aco, '*', true);
        
        $this->assertNotNull($this->sandbox);
       
        $this->assertTrue($this->sandbox->is('Guest'));

        //Remove all objects
        foreach(array($aco, $this->sandbox) as $obj)
                $this->assertTrue($obj->delete());
     
        Strategy::resetConfig();
    }
    
    /**
     * Callback invoked whenever a virtual object is resolved to a sandboxed one
     * @param mixed $virtualObject the object the action should be performed on
     * @param mixed $newObject     the object the action has been performed on
     */
    public function sandboxCallback($virtualObject, $newObject){
        $this->sandbox = $newObject;
    }
 }
?>
