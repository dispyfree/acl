<?php

class DefaultController extends Controller
{
	public function actionIndex()
	{
		$this->render('index');
	}
        
        
       /* public function actionTest(){
            
            $aliases = array('a1', 'a2');
            
            foreach($aliases as $alias){
                $user =RGroup::model()->find('alias = :alias', array(':alias' => $alias));
                $user->delete();
            }
            
            $a1 = new RGroup();
            $a1->alias = 'a1';
            
            
            $a2 = new RGroup();
            $a2->alias = 'a2';
            
            
            $a2->join('a1');
            
            echo $a2->is('a1') ? 'is a1' : 'worlds\'s end is near';
            
            return true;
            
            $bla = CGroup::model()->find('alias = :alias', array(':alias' => 'pic'));
            if($bla)
                $bla->delete();
            
            $bla = new CGroup();
            $bla->alias = 'restricted';
            $bla->save();
            
            $pic = CGroup::model()->find('alias = :alias', array(':alias' => 'pic'));
            $cgroup = CGroup::model()->find('alias = :alias', array(':alias' => 'picGroup'));
            
            $user =RGroup::model()->find('alias = :alias', array(':alias' => 'user'));
            $group =RGroup::model()->find('alias = :alias', array(':alias' => 'bla'));
            
            
            echo (integer)$user->may('pic', 'read');
            echo (integer)$group->may('pic', 'read');
            echo $user->may('picGroup', 'read');
        }
       */
}