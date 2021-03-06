<?php namespace Bookrr\Store\Models;

use Model;

/**
 * Category Model
 */
class Category extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'bookrr_product_category';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];
    public $belongsToMany = [
        'product' => [
            'Bookrr\Store\Models\Product'
        ]
    ];

    public function filterFields($fields, $context = null)
    {
        $fields->slug->value = str_slug($fields->name->value);
    }
}
