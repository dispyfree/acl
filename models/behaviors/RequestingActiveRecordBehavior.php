<?php

Yii::import('acl.models.behaviors.*');

/**
 * RequestingActiveRecordBehavior Class File
 *
 * This class serves as the behavior to be used for all "actors" in the program who have 
 * to per form actions on other objects 
 * 
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.base
 */
class RequestingActiveRecordBehavior extends AclObjectBehavior{
    
    /**
     * Overwrite this method to return the actual class Name
     * @return  either "Aro" or "Aco"
     */
    protected function getType(){
        return 'Aro';
    }
    
    /**
     * Loads the associated Object
     * this method must be overwritten because we have additional checks here
     * @throws RuntimeException 
     */
    protected function loadObject(){
        $class = Strategy::getClass($this->getType());
        
        $owner = $this->getOwner();
        
        if($this->_obj === NULL){
            $this->_obj = Util::enableCaching($class::model(), 'aroObject')->find('model = :model AND foreign_key = :foreign_key', 
                    array(':model' => get_class($owner), 'foreign_key' => $owner->id));
            
            //If there's no such Aro-Collection... use Guest ^^
            $guest = Strategy::get('guestGroup');
            if(!$this->_obj && $guest){
                $this->_obj = Util::enableCaching($class::model(), 'aroObject')->find('alias = :alias', array(':alias' => $guest));
                
                //If there's no guest...
                if(!$this->_obj)
                    throw new RuntimeException('There is no associated Aro nor a guest-group');
            }
        }
    }
       
    
    /**
     * Looks up if the user is granted a specific action to the given object
     * @param   string|array    $obj    The object to be checked   
     * @param   string          $action the action to be performed
     * @return bool true if access is granted, false otherwise
     */
    public function may($obj, $action){
        $this->loadObject();
        return $this->_obj->may($obj, $action);
    }
    
    /**
     * Grants the object denoted by the $obj-identifier the given actions
     * @param type $obj the object identifier
     * @param array $actions        the actions to grant
     * @param bool  $byPassCheck    Whether to bypass the additional grant-check
     * @return bool 
     */
    public function grant($obj, $actions, $byPassCheck = false){
        $this->loadObject();
        $suc = $this->_obj->grant($obj, $actions, $byPassCheck);
        
        return $suc;
    }
    
    /**
     * Denies the object denoted by the $obj-identifier the given actions
     * @param type $obj the object identifier
     * @param array $actions the actions to deny
     * @return bool 
     */
    public function deny($obj, $actions){
        $this->loadObject();
        $suc = $this->_obj->deny($obj, $actions);
        
        return $suc;
    }
    
    /**
     * This method takes care to associate an ARO-collection with this one
     * 
     * @param CEvent $evt 
     */
    public function afterSave($event){
        $owner = $this->getOwner();
        if($owner->isNewRecord){
            $class = Strategy::getClass('Aro');
            $aro = new $class();
            $aro->model = get_class($owner);
            $aro->foreign_key = $owner->getPrimaryKey();
            if(!$aro->save())
                throw new RuntimeError("Unable to save Aro-Collection");
        }
        
        parent::afterSave($event);
    }
    
    /**
     * Flushes the cache
     */
    public function afterDelete($event){
        return parent::afterDelete($event);
    }
    
    /**
     * This method takes care that all associated ACL-objects are properly removed
     */
    public function beforeDelete($event){
        $owner = $this->getOwner();
        //Ok he has the right to do that - remove all the ACL-objects associated with this object
        $class = Strategy::getClass('Aro');
        $aro = $class::model()->find('model = :model AND foreign_key = :key', array(':model' => get_class($owner), ':key' => $owner->id));
        
        if(!$aro)
            throw new RuntimeException('No associated Aro-Collection!');
        
        $transaction = Yii::app()->db->beginTransaction();
        try{
            $suc =$aro->delete()&& parent::beforeDelete();
            $transaction->commit();
            return $suc;
        }
        catch(Exception $e){
            $transaction->rollback();
            throw $e;
        }
        
    }
    
}
?>
