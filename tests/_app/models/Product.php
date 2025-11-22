<?php

namespace tests\_app\models;

use yii\db\ActiveRecord;
use eseperio\virtualfields\behaviors\VirtualFieldsBehavior;

/**
 * Product Model
 * 
 * @property int $id
 * @property string $name
 * @property float $price
 * @property int $created_at
 * @property int $updated_at
 */
class Product extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%product}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
            ],
            'virtualFields' => [
                'class' => VirtualFieldsBehavior::class,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'price'], 'required'],
            [['price'], 'number'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'price' => 'Price',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Get the entity type for virtual fields
     * 
     * @return int
     */
    public function getObjectType()
    {
        return 2;
    }
}
