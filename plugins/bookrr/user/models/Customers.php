<?php namespace Bookrr\User\Models;

use Model;

/**
 * Customers Model
 */
class Customers extends Model
{
    use \October\Rain\Database\Traits\Validation;

    use \October\Rain\Database\Traits\SoftDelete;

    public $table = 'bookrr_user';

    public $rules = [
        'phone' => 'required|regex:/^([-a-z0-9_ ])+$/i|min:6'
    ];

    protected $guarded = ['*'];

    protected $fillable = ['address','phone','birth_date'];

    #
    #   Relation
    #
    public $belongsTo = [
        'user' => [
            \Backend\Models\User::class,
            'key'    => 'user_id',
            'delete' => true
        ]
    ];
    public $hasMany = [
        'parkings' => ['Bookrr\Booking\Models\Parking','key' => 'user_id','otherKey'=>'user_id','delete' => true],
        'vehicles' => ['Bookrr\User\Models\Vehicle','key' => 'user_id','otherKey'=>'user_id','delete' => true],
        'vehicle_count' => ['Bookrr\User\Models\Vehicle','key' => 'user_id','otherKey'=>'user_id','delete' => true,'count'=>true],
        'contacts' => ['Bookrr\User\Models\Contact','key' => 'user_id','delete' => true]
    ];

    #
    #   Set Default Query
    #
    public function newQuery($excludeDeleted = true)
    {
        $query = parent::newQuery($excludeDeleted);
        $query->isCustomer();
        return $query;
    }

    #
    #   Scopes
    #
    public function scopeIsCustomer($query)
    {
        return $query->with('user')
        ->whereHas('user.role',function($q){
            $q->where('code','customer');
        });
    }

    public function getVehicle()
    {
        return $this->vehicles()->where('primary',1)->first();
    }

    public function hasVehicle()
    {
        return $this->vehicles->isEmpty() ? !true : !false;
    }

    #
    #   Events
    #
    public function afterDelete()
    {
        $this->user()->delete();
    }

    public function getFullNameAttribute()
    {
        return $this->user->first_name.' '.$this->user->last_name;
    }

    public function getActivePlateAttribute()
    {
        return $this->vehicles->where('primary',1)->first()->plate;
    }

}
