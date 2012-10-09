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
     * Looks up if the user is granted a specific action to the given object
     * @param   string|array    $obj    The object to be checked   
     * @param   string          $action the action to be performed
     * @return bool true if access is granted, false otherwise
     */
    public function may($obj, $action){
        $this->_type = NULL;
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
        $this->_type = NULL;
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
        $this->_type = NULL;
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
            /**
             * Take care that an acl object is created
             * If it has already been created (some internals, joins whatever),
             * this won't lead to an error  
             */
            $identifier = array(
              'model' => get_class($owner),
              'foreign_key' => $owner->getPrimaryKey()
            );
            $obj = AclObject::loadObjectStatic($identifier, 'Aro');
            if(!$obj)
                throw new RuntimeException("Unable to save aro collection");
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
            $suc =$aro->delete()&& parent::beforeDelete($event);
            $transaction->commit();
            return $suc;
        }
        catch(Exception $e){
            $transaction->rollback();
            throw $e;
        }
        
        return parent::beforeDelete($event);
        
    }
    
}
?>
