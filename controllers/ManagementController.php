<?php

/**
 * Management Controller Class File
 *
 * This class serves as the backend for ajax-powered GUI rights management
 * 
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.management
 */
class ManagementController extends CController{
    
    public function actions(){
            return array(
                'listActions'   => 'application.modules.acl.controllers.management.ListActionsAction',
                'listAros'      => 'application.modules.acl.controllers.management.ListArosAction',
                'setPermission' => 'application.modules.acl.controllers.management.SetPermissionAction',
                'autoComplete'  => 'application.modules.acl.controllers.management.AutoCompleteAction'
                
            );
        }
        
    public $virtualModel = 'Collection';
    public $aroForceList = array('User', 'Group');
        
    public function getModel($model = NULL, $type = 'aro'){
        if($model == NULL){
            $model = ucfirst(isset($_GET['model']) ? $_GET['model'] : $_POST['model']);
            $this->checkModelValidity($model);
        }
            
        if($model == $this->virtualModel)
            $model =  $type == 'aro' ? 'RGroup' : 'CGroup';
        
        return $model;
    }
    
    /**
     * getModel vice versa
     */
    public function translateModel($model, $type = 'aro'){
        if(in_array($model, array('aro', 'aco')))
            return $this->virtualModel;
        return $model;
    }
        
    public function checkModelValidity($model){
            if(!is_subclass_of($model, 'CActiveRecord'))
                    throw new RuntimeException(Yii::t('acl', 'Invalid Model'));
            
            //If it's not activated
            if(!isset($model::$enableAjaxPermissionManagement) 
                    || !$model::$enableAjaxPermissionManagement)
                throw new RuntimeException(Yii::t('acl', 'Management disabled for this model'));
    }
    
    public function ajaxContainer($callback, $wrap = true){
        $response = array(
            'error' => false
        );
        $data = NULL;
        try{
           $data = call_user_func($callback);
           
           if(!$wrap){
                echo json_encode($data); 
                return true;
           }
        }
        catch(Exception $e){
            $response['error'] = true;
            $response['err_msg'] = $e->getMessage();
        }
        $response['data'] = $data;
        
        echo json_encode($response);
    }
}
?>
