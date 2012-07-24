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
        
    /**
     * Defines the model name used for bare acl collections
     * (that are virtual entities which do only exist in the acl system)
     * @var string
     */
    public $virtualModel = 'Collection';
    
    /*
     * Defines which models should show up always in the dialog
     * If the dialog is called on some acos, each type of aro gets a new tab.
     * However, if no aro of a certain type has got any permission on all
     * objects in the set, the type will not show up. 
     * The types you specify here will always show up - independent from the 
     * existence of any aros
     * @var array
     */
    public $aroForceList = array('User');
        
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
