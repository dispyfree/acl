<?php

/**
 * ActionMeta Class File
 *
 * This class stores general MetaData for the actions
 * This metaData includes a human-readable name for each action, a short and 
 * possibly also a long Description. In addition to that you can specify
 * two images for the active and disabled style. The metaData can be invididually
 * overwritten in each model
 * 
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.management
 */
class ActionMeta extends CComponent{
    
    /**
     * The global Action-Metadata
     * required:
     *          name
     * optional:
     *          short_desc
     *          long_desc
     *          enabled_img
     *          disabled_img
     * @return array 
     */
    public static function getActionMeta(){
        $am = Yii::app()->getAssetManager();
        $path = $am->publish('images/crystal_project/16x16/actions');
          
       return 
          array(
            'create' => array(
                'name' => Yii::t('acl', 'Create Object'),
                'enabled_img' => $path.'/save_all.png',
                'disabled_img' => $path.'/save_all_disabled.png',
                /*
                'short_des' => Yii::t('acl', 'Creates an object of type X'),
                'long_desc' => Yii::t('acl', 'Creates an object of type X'
                .' If you do this, x, y and z will happen and you have b choices')
                */
            ),
            'read' => array(
                'name' => Yii::t('acl', 'View this Object'),
                'enabled_img' => $path.'/viewmag.png',
                'disabled_img' => $path.'/viewmag_disabled.png',
            ),
            'update' => array(
                'name' => Yii::t('acl', 'Update this object'),
                'enabled_img' => $path.'/edit.png',
                'disabled_img' => $path.'/edit_disabled.png',
            ),
            'delete' => array(
                'name' => Yii::t('acl', 'Delete this object'),
                'enabled_img' => $path.'/delete.png',
                'disabled_img' => $path.'/delete_disabled.png'
            ),

    ); 
   }
   
   /**
    * The global listActions
    * @see all allowedActions
    * @return string 
    */
   public static function listActions(){
       return 'create, read, update, delete';
   }
   
   /**
    * The global allowed Actions
    * Please note that this restricts the actions which are being accessible
    * using the management tools at all. The list Actions only indicate what the 
    * client-side will display, but as you can do your own, manual ajax-requests
    * this will stonewall any attempts to violate permissions
    * 
    * Note that it doesn't make sense to list actions which are not permitted 
    * anyway, that's why they're automatically removed
    * @see listActions
    * @return string 
    */
   public static function allowedActions(){
       return 'create, read, update, delete';
   }
   
   /**
    * This returns the Action-Meta, taking into account all the three settings 
    * above (you can disable the listing, see second parameter)
    * @param    string  Name of the CActiveRerd-Model
    * @param    boolean Whether to return only the listable actions
    * @return array 
    */
   public static function finalActionMeta($model, $applyList = true){
       $data = array(
         'listActions'      => self::listActions(),
         'allowedActions'   =>  self::allowedActions()
       );
       $getActionMeta = self::getActionMeta();
       
       if(method_exists($model, 'getActionMeta')){
           $getActionMeta = array_merge($getActionMeta, $model::getActionMeta());
       }
       
       foreach($data as $method => $actions){
           
           // Run this only if there's a model equivalent at all
           if(method_exists($model, $method)){
            $data[$method]  = $model::$method();
           }
           else{
               //Simply translate them
               $data[$method] = Action::translateActions($model, $actions);
           }
       }
       
       //Now, list only certain actions and don't list forbidden actions
       
       foreach($getActionMeta as $key=>$actionData){
           //If it's forbidden, remove it
           if(!in_array($key, $data['allowedActions']))
                   unset($getActionMeta[$key]);
           
           //Take Listing into account, if requested
           elseif($applyList && !in_array($key, $data['listActions']))
                   unset($getActionMeta[$key]);
       }
       
       return $getActionMeta;
       
   }
   
   
   
    
}
?>
