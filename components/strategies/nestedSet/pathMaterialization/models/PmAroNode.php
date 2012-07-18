<?php

/**
 * The Node for path materialization
 *
 * @author dispy <dispyfree@googlemail.com>
 * @license LGPLv2
 * @package acl.strategies.nestedSet.pathMaterialiization
 */
class PmAroNode extends PmAclNode{
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
        return '{{aro}}';
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
    
     /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'aro' => array(static::BELONGS_TO, 'PmAro', 'collection_id'),
            'permissions' => array(static::HAS_MANY, 'Permission',  'aro_id')
        );
    }
}

?>
