<?php
/*
 * To run this test, you'll need this additional table:
 * 
  CREATE TABLE IF NOT EXISTS `tbl_person` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

 */

class testPerson extends CActiveRecord{
    public function behaviors(){
            return array(
              //'aro'  => 'acl.models.behaviors.RestrictedActiveRecordBehavior',
              'aco'  => 'acl.models.behaviors.RequestingActiveRecordBehavior'
            );
     }
     
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return '{{person}}';
    }
}
?>
