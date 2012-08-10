<?php

class ListArosAction extends CAction {
    
    
    public function run() {
        $this->controller->ajaxContainer(array($this, 'internalRun'));
    }
    

    public function internalRun() {
        //We'll produce an intersection-set of permitted objects
        $objects = $_POST['objects'];
        $overallAros = array();
        
        foreach($objects as $object){
            $model  = $object['model'];
            $id     = $object['id'];
            //The first letter _must_ always be uppercase
            $model = ucfirst($model);

            $this->controller->checkModelValidity($model);

            //Fetch the Model
            $obj = $model::model()->findByPk($id);

            if($obj === NULL)
                throw new RuntimeException(Yii::t('acl', 'Invalid Object'));

            //Ok - load the objects
            $overallAros[] = $obj->getDirectlyPermitted();
        }
        
        $overallAros = $this->intersectAros($overallAros);
        
        //We'll want to group this outputand get additional things - 
        // it itself isn't that helpful
        return $this->extendInformation($overallAros);
    }
    
    protected function intersectAros($aros){
        $finishedAros = array();

        if(count($aros) < 1)
            return $aros;
       
        $start = $aros[0];
        foreach($start as $currentCollection){
            $arosLeft = array_slice($aros, 1);
            $present = $this->collectionPermissionPresentIn(
                    $currentCollection['collection_id'],
                    $currentCollection['action_id'],
                    $arosLeft);
            
            if($present)
                $finishedAros[] = $currentCollection;
        }
        
        
        return $finishedAros;
    }
    
    protected function collectionPermissionPresentIn($collectionId, $actionId, $arosLeft){
       //If it has been present in every aroList 
        if(count($arosLeft) == 0)
            return true;
        
        $currentSet = $arosLeft[0];
        
        foreach($currentSet as $currentCollection){
            var_dump($currentCollection);
            
            if($currentCollection['collection_id'] == $collectionId
                    && $currentCollection['action_id'] == $actionId)
                
                return $this->collectionPermissionPresentIn(
                        $collectionId, $actionId,
                        array_slice($currentSet, 1));
        }
        return false;
    }
    
    protected function extendInformation($aros){
        $collections = array();

        foreach($aros as $aro){
            
            $actionId = $aro['action_id'];
            //Fetch Action
            $action = Util::enableCaching(Action::model(), 'action')->findByPk($actionId);
            
            if($action === NULL)
                throw new RuntimeException('Invalid Action (Database Integrity at risk)');
            
            if(isset($aro['model'])){
                $model  = $aro['model'];
                $id     = $aro['foreign_key'];
                //Fetch the Object itself
                $obj    = $model::model()->findByPk($id);
                
                if($obj === NULL)
                    throw new RuntimeException('Invalid Object (Database Integrity at risk)');
                
                $objTmp = &$collections[$model][$id];
                
                $objTmp['aro']     = $obj;
                $objTmp['actions'][]  = $action;
                
            }
            else{
                $collectionId = $aro['collection_id'];
                $objTmp = &$collections[$this->controller->virtualModel][$collectionId];
                
                //Simulate regular model
                
                $obj = new stdClass();
                $obj->id    = $collectionId;
                $obj->name  = $aro['alias'];
                
                $objTmp['aro'] = $obj;
                $objTmp['actions'][] = $action;
            }
        }
        
        //Assure that the models listed in ManagementController::aroForceList
        //are all displayed - regardless of objects being fetched
        foreach($this->controller->aroForceList as $model){
            if(!isset($collections[$model]))
                $collections[$model] = array();
        }
        
        return $collections;
    }

}

?>
