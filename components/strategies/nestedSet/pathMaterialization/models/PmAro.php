<?php

/**
 *
 * The specific class for Access Request Objects providing the rights-
 * management specific to this strategy
 *
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.strategies.nestedSet.pathMaterialiization
 *
 * The followings are the available columns in table '{{aco}}':
 * @property string $id
 * @property integer $collection_id
 * @property string $path
 */
class PmAro extends PmAclObject
{
    
    /**
     * Grants the given actions to the given object
     * @param mixed $obj any valid identifier
     * @param mixed $actions the actions to grant
     * @param bool  $byPassCheck    Whether to bypass the additional grant-check
     * @return type 
     */
    public function grant($obj, $actions, $byPassCheck){
        $obj = $this->loadObject($obj, 'Aco');
        $actions = Action::translateActions($obj, $actions);
        
        //Check for the grant-Permission (if enabled)
        $enableGrantCheck = Strategy::get('enableGrantRestriction');
        if((!$byPassCheck) && $enableGrantCheck){
            if(!$this->may($obj, 'grant'))
                throw new RuntimeException(Yii::t('app', 'You are not permitted to grant on this object'));
            
            //Extended check, if enabled
            $enableSpecificCheck = Strategy::get('enableSpecificGrantRestriction');
            if($enableSpecificCheck){
                foreach($actions as $action){
                    $actionName = 'grant_'.$action;
                    if(!$this->may($obj, $actionName))
                        throw new RuntimeException(Yii::t('app', 'You are not permitted to grant this action on this object'));
                }
            }
        }
        
        //Load all nodes of this object
        $aroNodes = $this->getNodes();
        $acoNodes = $obj->getNodes();
        
        foreach($actions as $action){
            //First check: does that already exist?
            
            $aroIn = Util::generateInStatement(Util::getIdsOfObjects($aroNodes));
            $acoIn = Util::generateInStatement(Util::getIdsOfObjects($acoNodes));
            
            $action = Action::model()->find('name = :name', array(':name' => $action));
            
            if($action === NULL)
                throw new RuntimeException('Invalid action');
            
            $permission = Permission::model()->find('action_id = :action_id AND  aco_id '.$acoIn.' AND aro_id '.$aroIn,
                    array(':action_id' => $action->id));
            //Only grant if it's not yet granted
            if($permission === NULL){
                foreach($aroNodes as $aroNode){
                    foreach($acoNodes as $acoNode){
                        
                        $perm = new Permission();
                        $perm->aro_id = $aroNode->id;
                        $perm->aro_path = $aroNode->getOwnPath();
                        $perm->aco_id = $acoNode->id;
                        $perm->aco_path = $acoNode->getOwnPath();
                        $perm->action_id = $action->id;
                        
                        if(!$perm->save())
                            throw new RuntimeException('Unable to grant permission of '.$action->name.' from '
                                    .$aroNode->id.' to '.$acoNode->id);
                    }
                }
            }
            
        }
    }
    
    /**
     * Denies the given actions to the given object
     * @param mixed $obj any valid identifier
     * @param mixed $actions the actions to deny
     * @return type 
     */
    public function deny($obj, $actions){
        $obj = $this->loadObject($obj, 'Aco');
        $actions = Action::translateActions($obj, $actions);
        
        $aroNodes = $this->getNodes();
        $acoNodes = $obj->getNodes();
        
        $aroIn = Util::generateInStatement(Util::getIdsOfObjects($aroNodes));
        $acoIn = Util::generateInStatement(Util::getIdsOfObjects($acoNodes));
        
        foreach($actions as $action){
            
            $action = Action::model()->find('name = :name', array(':name' => $action));
            
            if($action === NULL)
                throw new RuntimeException('Invalid action');
            
            //Now, delete all the rows
            $suc = Permission::model()->deleteAll('aco_id '.$acoIn.' AND aro_id '.$aroIn.' AND action_id = :action_id',
                    array(':action_id' => $action->id));
            
            if($suc === false)
                throw new RuntimeException('Unabel to deny permission '.$action->id.' of '.$this->id.' to '.$obj->id);
        }
    }
    
    /**
     * Checks whether the this object may perform all of the given actions 
     * on the given object
     * @param mixed $obj
     * @param mixed $actions 
     * @param boolean true if it may, otherwise false
     */
    public function may($originalObj, $actions){

        $obj = $this->loadObject($originalObj, 'Aco');
        $actions = Action::translateActions($obj, $actions);
        
        $aroPaths = $this->getPaths();
        $aroCondition = $this->addPositionCheck($aroPaths, 'aro');
        
        $acoPaths = $obj->getPaths();
        $acoCondition = $this->addPositionCheck($acoPaths, 'aco');
        
        foreach($actions as $action){
            //First fetch the action
            $action = Action::model()->find('name = :name', array(':name' => $action));       
            if($action === NULL)
                throw new RuntimeException('Invalid action');
            
            //An action which is not possible is never allowed
            if(isset($obj::$possibleActions) && !in_array($action, $possibleActions))
                    return false;
            
            //Perform general check
            if(RestrictedActiveRecord::mayGenerally($originalObj, $action))
                return true;
        
        
            //Do we have to consider business-rules?
            $enableBir = Strategy::get('enableBusinessRules');
            
            $condition = 'action_id = :action_id AND '.$aroCondition.' AND '.$acoCondition;
            $params = array(':action_id' => $action->id);
            //First, check the regular ACL
                $perm = Permission::model()->find($condition,$params);

                if($perm === NULL && !$enableBir)
                    return false;
                
            //Only check if the permission has not already been granted by 
            //the regular ACL check
            if((!$perm) && $enableBir){
                $conditions = array(
                  'aroCondition' => $aroCondition,
                  'acoCondition' => $acoCondition,
                );
                if(!RestrictedActiveRecord::checkBirPermission($conditions, $params))
                    return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return PM_Aco the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{aro_collection}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'aroNodes' => array(static::HAS_MANY, 'PmAroNode', 'collection_id'),
            'permissions' => array(static::HAS_MANY, 'Permission', 'aro_id')
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'alias' => 'Alias',
            'model' => 'Model',
            'foreign_key' => 'Foreign Key',
            'created' => 'Created'
        );
    }
} 
?>