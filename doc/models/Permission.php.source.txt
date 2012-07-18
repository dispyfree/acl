<?php

/**
 * This is the model class for table "{{permission}}".
 * 
 * Permissions are the link between Access Control Objects, Access Request Objects
 * and Actions. Permission define who can perform what on whom. 
 * Permissions (currently) work only positively, so if you deny rights you take
 * back already granted rights, but you don't explicitely deny rights. 
 * 
 * Especially if a user has a permission perform something on a specific object
 * because he is the child of a class which has the permission, denying the
 * permission to the user will _not_ affect the permissions of the group. 
 * 
 * @author dispy <dispyfree@googlemail.com>
 * @package acl.base
 * @license LGPLv2
 *
 * The followings are the available columns in table '{{permission}}':
 * @property integer $id
 * @property integer $aco_id
 * @property integer $aro_id
 * @property integer $aco_path
 * @property integer $aro_path
 * @property integer $action_id
 */
class Permission extends CActiveRecord
{
    
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Permission the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{permission}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        //
        return array(
            'acoNode' => array(static::BELONGS_TO, Strategy::getClass('AcoNode'), 'aco_id'),
            'aroNode' => array(static::BELONGS_TO, Strategy::getClass('AroNode'), 'aro_id'),
            'action' => array(static::BELONGS_TO, 'Action', 'action_id')
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'aco_id' => 'Aco',
            'aro_id' => 'Aro',
            'aco_path' => 'Aco Path',
            'aro_path' => 'Aro Path',
            'action_id' => 'Action',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria=new CDbCriteria;

        $criteria->compare('id',$this->id);
        $criteria->compare('aco_id',$this->aco_id);
        $criteria->compare('aro_id',$this->aro_id);
        $criteria->compare('action_id',$this->action_id);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
    
    /**
     * Clones the object - resets the ID so that it is in fact a new object 
     * in the database also
     */
    public function __clone(){
        $this->id = NULL;
        $this->isNewRecord = true;
    }
} 

?>