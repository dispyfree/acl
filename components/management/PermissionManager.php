<?php

/**
 * This is the Backend for the ajax-baseed PermissionManager-Widget
 * 
 * @author dispy <dispyfree@googlemail.com>
 * @package acl.management
 * @license LGPLv2
 */
class PermissionManager extends CWidget{
    
    public $url = NULL;
    public function run(){
        $this->init();
        $this->render('__permissionDialog');
    }
    
    public function init(){
        parent::init();
        $am = Yii::app()->getAssetManager();
        //Publish the hole thing
        $this->url = $am->publish(
            Yii::getPathOfAlias('application.modules.acl.assets.*'));
        Yii::app()->clientScript->registerCssFile(
                $am->publish(Yii::getPathOfAlias(
                        'application.modules.acl.assets.css').'/permission.css')
                );

    }
}
?>
