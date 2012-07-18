<?php

/**
 * 
 * The Node specializuation for path materialization
 *
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.strategies.nestedSet.pathMaterialiization
 */
class PmAcoNode extends PmAclNode{

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{aco}}';
    }
    
    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'aco' => array(static::BELONGS_TO, 'PmAco', 'collection_id'),
            'permissions' => array(static::HAS_MANY, 'Permission',  'aco_id')
        );
    }
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
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'collection_id' => 'Collection Id',
            'path' => 'path',
        );
    }
}

?>
