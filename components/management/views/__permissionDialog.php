<?php
   $this->beginWidget('zii.widgets.jui.CJuiDialog', array(
        'id'=>'permissionDialog',
        // additional javascript options for the dialog plugin
        'options'=>array(
            'title'=>Yii::t('app', 'Permissions'),
            'autoOpen'=>false,
            'width' => '400px',
        ),
    ));
   
   echo CHtml::tag('div', array('id' => 'permissionTabs'), '<ul></ul>');
    
   echo "<p style='clear:both'></p><br />";
    $this->widget('bootstrap.widgets.BootButton', array(
        'label'=> Yii::t('app', 'Done'), 
        'type'=>'primary', // '', 'primary', 'info', 'success', 'warning', 'danger' or 'inverse'
        'size'=>'small', // '', 'large', 'small' or 'mini',
        'htmlOptions' => array(
            'id' => 'done',
            'class' => 'pull-left',
            'onclick' => 'js: function(){ $("permissionDialog").dialog("close");}'
        )
    ));
    
    $this->endWidget('zii.widgets.jui.CJuiDialog');
    
    $cs = Yii::app()->getAssetManager();
 ?>

<script type='text/javascript'>

    var fileName = "<?php 
                echo $this->url.'/js/PermissionManager.js';
      ?>";
    var permissionBackendUrl = "<?php
        echo $this->controller->createAbsoluteUrl('/acl/Management/');
    ?>";
    
    var pmanager = null;
    
    function showPermissionDialog(objects){
        var tmpobjects = objects;
        var func = function(){

           pmanager = new PermissionManager(tmpobjects);
           pmanager.startUp();
        };
        
        $.getScript(fileName, func);
    }
    
    $('#permissionDialog').bind('dialogclose', closePermissionDialog);
    
    function closePermissionDialog(){
        pmanager.destroy();
        pmanager = null;
    }
</script>

<script type='text/javascript' src='<?php 
                echo $this->url.'/js/PermissionManager.js';
      ?>'></script>
