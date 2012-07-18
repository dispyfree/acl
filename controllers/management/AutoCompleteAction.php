<?php

class AutocompleteAction extends CAction {

    public function run(){
        $this->controller->ajaxContainer(array($this, 'internalRun'), false);
    }
    
    public function internalRun() {
        $model = $this->controller->getModel();
        $term  = $_GET['term'];
        
        $this->controller->checkModelValidity($model);
        $descriptiveValue = $this->getDescriptiveValue($model);
        
        //Try to find the objects
        $objs = $this->getObjects($model, $descriptiveValue, $term);

        $ret = array();
        foreach($objs as $obj){
            $tmp        = new stdClass();
            $tmp->id    = $obj->id;
            $tmp->label = $obj->$descriptiveValue;
            $tmp->value = $obj->$descriptiveValue;
            $ret[] = $tmp;
        }
        
        return $ret;
    }
    
    
    protected function getObjects($model, $descriptiveValue, $term){
        $objects = $model::model()->findAll($descriptiveValue.' LIKE :term',
                array(':term' => $term.'%'));
        
        return $objects;
    }
    
   protected function getDescriptiveValue($model){
       $values = array_keys(get_class_vars($model));
       
       $checklist = array('title', 'name', 'shortcut', 'alias', 'id');
       
       foreach($checklist as $entry){
           if(in_array($entry, $values))
               return $entry;
       }
       
       throw new RuntimeException('Unable to determine descriptive value');
       
   }
       

}

?>
