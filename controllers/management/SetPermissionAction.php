<?php

class SetPermissionAction extends CAction {

    public function run(){
        $this->controller->ajaxContainer(array($this, 'internalRun'));
    }
    
    protected $errors = array();
    
    public function internalRun() {
        
        $executed = array();
        
        list($model, $aros, $action, $acos, $mode) =  $this->getParameters();
        //OK - let's go
        foreach($aros as $aro){
                $executed[$aro->id]= $this->setPermission($aro, $action, $mode, $acos);    
        }
        $model = $this->controller->translateModel($model);
        return array('executed' => array($model => $executed), 'errors' => $this->errors);
    }
    
    protected function getParameters(){
        $mode       = $_POST['mode'];
        $permission = $_POST['permission'];
        $model      = $this->controller->getModel();
        $selection  = $_POST['selection'];
        $objects    = $_POST['acos'];
        
        $this->controller->checkModelValidity($model);
        
        //Check for Action
        $action = $this->getAction($model, $permission);
        $acos   = $this->loadAcos($objects);
        $aros   = $this->loadAros($model, $selection);
        
        
        return array($model, $aros, $action, $acos, $mode);
    }
    
    protected function loadAros($model, $selection){
        //Load the selection-objects
        $aros = array();
        foreach($selection as $objId){
            $obj = $model::model()->findByPk($objId);
            if($obj === NULL){
                $txt = 'The aro {model}::{id} doesn\'t exist and has been ignored';
                $this->error[] = Yii::t('acl', $txt, 
                        array('{model}' => $model, '{id}' => $objId));
            }
            else
                $aros[] = $obj;
        }
        
        return $aros;
    }
    
    protected function getAction($model, $permission){
        $action = Action::model()->find('name = :name', array(':name' => $permission));
        if($action == NULL)
            throw new RuntimeException(Yii::t('acl', '{action} is not a valid action',
             array('{action}' => $permission)));
        
        //Maybe it's also restricted by the model
        if(isset($model::$possibleActions) && !in_array($permission, $possibleActions)){
                $txt    = '{action} is not allowed on {model}';
                $params = array('{action}' => $permission, '{model}' => $model);
                throw new RuntimeException(Yii::t('acl', $txt, $params));
        }
                
        return $action;
    }
    
    protected function loadAcos($objects){
        $acos = array();
        
        foreach($objects as $object){
            try{
                $model = $object['model'];
                $id    = $object['id'];
                
                $this->controller->checkModelValidity($model);
                
                $aco = $model::model()->findByPk($id);
                
                if($aco === NULL){
                    $err = 'The aco {model}::{id] does not exist and has therefor'
                        .'been omitted in this operation';
                    $t =  Yii::t('acl', $err,  array('{model}' => $model, '{id}' => $id));
                    throw new RuntimeException($t);
                }
                
                $acos[] = $aco;
            }
            catch(Exception $e){
                $this->errors[] = $e->getMessage();
            }
        }
        
        return $acos;
    }
    
    protected function setPermission($aro, $action, $mode, $acos){
        $method = $mode == 'true' ? 'grant' : 'deny';
        
        //Collect the granted actions
        $executed = array();
        
        //Go ahead
        try{
            foreach($acos as $aco){
                $aro->$method($aco, $action);
            }
            //display permisison change only if we were able to apply it to al lacos
            $executed[] = array(
                'aro'       => $aro->id, 
                'action'    => $action->name, 
                'mode'      => $mode
              );
        }
        catch(Exception $e){
            $txt = 'Unable to grant {action} on {acoModel}::{acoId}'
                    .'to {aroModel}::{aroId}';
            $params = array(
              '{action}'    => $action->name,
              '{acoModel}'  => get_class($aco),
              '{acoId}'     => $aco->id,
              '{aroModel}'  => get_class($aro),
              '{aroId}'     => $aro->id
            );
            $this->error[] = Yii::t('acl', $txt, $params);
        }
     
        return $executed;
    }

}

?>
