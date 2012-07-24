<?php

class DefaultController extends Controller
{
	public function actionIndex()
	{
		$this->render('index');
	}
        
        public function actionTest(){
            
           $files = File::model()->with('parent')->findAll();
            
           return true;
            
            $aliases = array(
                'RGroup' => array('a1', 'a2'),
                'CGroup' => array('b1', 'b2')
             );
            
            foreach($aliases as $class => $aliase){
                foreach($aliase as $alias){
                    $obj =$class::model()->find('alias = :alias', array(':alias' => $alias));
                    if($obj)
                        $obj->delete();
                }
            }
            
            $a1 = new RGroup();
            $a1->alias = 'a1';
            $a1->save();
            
            
            $a2 = new RGroup();
            $a2->alias = 'a2';
            $a2->save();
            
            $b1 = new CGroup();
            $b1->alias = 'b1';
            $b1->save();
            
            $b2 = new CGroup();
            $b2->alias = 'b2';
            $b2->save();
            
            $a1->grant($b1, '*');
            
            if($a2->is('a1'))
                    echo "double-sided business-rules in action!";
            else
                    echo "Access denied";
        }
       
}