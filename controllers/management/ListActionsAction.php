<?php

class ListActionsAction extends CAction {

    public function run(){
        $this->controller->ajaxContainer(array($this, 'internalRun'));
    }
    
    public function internalRun() {
        $models = $_POST['models'];
        
        $actions = array();
        
        //The counter ensures that the first model's actions are the base
        $counter = 0;
        foreach($models as $model => $nImporteQuoi){
            $counter++;
            //The first letter _must_ always be uppercase
            $model = $this->controller->getModel($model, 'aco');

            $this->controller->checkModelValidity($model);

            //OK - fetch actions
            $modelActions = ActionMeta::finalActionMeta($model);
            
            
            //Now, remove all actions which are not present in all the models
            //But only if we are not in the first loop
            if($counter != 1){
                foreach($modelActions as $action => $actionDesc){
                    if(!isset($actions[$action]))
                        unset($actions[$action]);
                }
            }
            else
                //Otherwise the current model's actions server as the base
                $actions = $modelActions;
        }
        
        //If we have several models, we only want the most general descriptions
        if(count($models == 1)){
            //Fetch the general Action-Descriptions
            //(We don't want to filter again, so we firstly fetch the filtered ones)
            $generalActions = ActionMeta::getActionMeta();
            foreach($actions as $action=>$desc){
                if(isset($generalActions[$action]))
                    $actions[$action] = $generalActions[$action];
            }
        }
        
        
        return $actions;
    }

}

?>
