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
    public function grant($obj, $actions, $byPassCheck = false){
        $obj = $this->loadObject($obj, 'Aco');
        $actions = Action::translateActions($obj, $actions);
        
        //Check for the grant-Permission (if enabled)
        if(!$byPassCheck)
            $this->checkPermissionChange ('grant', $obj, $actions);
        
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
     * @param bool  $byPassCheck    Whether to bypass the additional deny-check
     * @return type 
     */
    public function deny($obj, $actions, $byPassCheck = false){
        $obj = $this->loadObject($obj, 'Aco');
        $actions = Action::translateActions($obj, $actions);
        
        //Check for the deny-Permission (if enabled)
        if(!$byPassCheck)
            $this->checkPermissionChange('deny', $obj, $actions);
        
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
     * This function checks whether the permission-change of the given type is permitted
     * with respect to the given object and the actions
     * If not, it throws an exception: otherwise it returns true
     * @throws RuntimeException
     * 
     * @param   string  $type   either "grant" or "deny"
     * @param   mixed   $obj    the object to change the permission on 
     * @param   array   $actions    the actions to grant/deny
     * @return  boolean true if succeeded
     */
    protected function checkPermissionChange($type, $obj, $actions){
        
        if(!in_array($type, array('grant', 'deny')))
                throw new RuntimeException('Invalid permission-change type');
        $ltype  = $type;
        $type   = ucfirst($type);
        
        //The object who wants to perform this change must be permitted to do so
        $aro = RestrictedActiveRecord::getUser();
        
        //Check if the change is restricted
        $generalConfigEntry     = 'enablePermissionChangeRestriction';
        
        //If that's checked, there can be also specific checks
        $specificConfigEntry    = 'enableSpecificPermissionChangeRestriction';
        $enableCheck = Strategy::get($generalConfigEntry);
        
        if($enableCheck){
            
            //This aro may generally not do this
            if(!$aro->may($obj, $ltype))
                throw new RuntimeException(Yii::t('app', 
                        'You are not permitted to {type} on this object',
                        array('{type}' => $ltype)));
            
            //Extended check, if enabled
            $enableSpecificCheck = Strategy::get($specificConfigEntry);
            if($enableSpecificCheck){
                
                foreach($actions as $action){
                    
                    $actionName = $ltype.'_'.$action;
                    if(!$aro->may($obj, $actionName))
                        throw new RuntimeException(Yii::t('app', 
                                'You are not permitted to {type} {action} on this object',
                                array('{type}' => $ltype,
                                      '{action}' => $actionName)));
                }
            }
        }
        
        return true;
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
                    throw new AccessViolation('Action '.$action.' is not allowed on '
                            .get_class($obj));
            
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
                
                $original = array(
                  'aro' => Util::getByIdentifierGraceful($this),
                  'aco' => Util::getByIdentifierGraceful($originalObj),
                );
                
                if(!RestrictedActiveRecord::checkBirPermission($conditions, $params, $original))
                    return false;
            }
        }
        
        return true;
    }
    
    /**
      * This takes care of the aro/aco specifis for calling business-rules
      * @param  string  the Rule
      * @param  arr     array('child' and 'father')
      * @param  string  the action
      */
     protected function callSpecificBusinessRule($rule, $arr, $action){
         return BusinessRules::fulfillsBusinessRule($rule, $arr, NULL, $action);
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