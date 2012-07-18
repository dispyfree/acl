<?php

/**
 * 
 * The Access Control Object specialization for path materilization
 *
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.strategies.nestedSet.pathMaterialiization
 */
class PmAco extends PmAclObject
{
    
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return PM_Aco the static model class
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
        return '{{aco_collection}}';
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
        return array(
            'acoNodes' => array(static::HAS_MANY, 'PmAcoNode', 'collection_id'),
            'permissions' => array(static::HAS_MANY, 'Permission', 'aco_id')
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'alias' => 'Alias',
            'model' => 'Model',
            'foreign_key' => 'Foreign Key',
            'creaed' => 'Created'
        );
    }

} 
?>