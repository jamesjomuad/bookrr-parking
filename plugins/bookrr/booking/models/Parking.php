<?php namespace Bookrr\Booking\Models;


use Model;
use \Carbon\Carbon;
use BackendAuth;
use Bookrr\User\Models\User;
use Bookrr\User\Models\BaseUser as AeroUser;
use Bookrr\Store\Models\Product;


class Parking extends Model
{
    use \October\Rain\Database\Traits\Validation;

    use \October\Rain\Database\Traits\SoftDelete;

    public $table = 'bookrr_booking';

    public $dates = ['park_in'];

    public $rules = [
        'customer' => 'required',
    ];

    protected $jsonable = ['items'];

    protected $guarded = ['created_at'];

    protected $hidden = ['updated_at','deleted_at'];

    protected $fillable = [
        'adult_going',
        'adult_returning',
        'agent_reference',
        'barcode',
        'child_going',
        'child_returning',
        'destination_in',
        'destination_out',
        'date_in',
        'date_out',
        'destination',
        'flight_number_arrive',
        'flight_number_depart',
        'note',
        'pickup_location',
        'guest_in',
        'guest_out',
        'status',
        'slot',
        'number',
        'ref_num'
    ];

    public $hasOne = [
        'cart' => ['Bookrr\Store\Models\Cart','key'=>'book_id']
    ];

    public $hasMany = [
        'movements' => [
            \Bookrr\Booking\Models\Movement::class,
            'key'    => 'booking_id',
            'delete' => true
        ],
    ];

    public $belongsTo = [
        'customer'  => ['Bookrr\User\Models\Customers','key' => 'user_id','otherKey'=>'user_id'],
        'vehicle'   => ['Bookrr\User\Models\Vehicle'],
        'bay'       => \Bookrr\Bay\Models\Bay::class,
        'ticket'    => \Bookrr\Booking\Models\Ticket::class
    ];

    protected $statusOption = [
        'pending',
        'forecourt',
        'parked',
        'declined',
        'canceled',
        'expired'
    ];


    /*
    *   OPTIONS
    */
    public function getVehicleOptions()
    {
        $options = [];
    
        foreach (AeroUser::auth()->user->vehicles as $key => $value)
        {
            $options[$value->id] = '(' . $value->plate . ') ' . $value->brand . ' ' . $value->model;
        }

        return $options;
    }


    /*
    *   ATTRIBUTES
    */
    public function getCreatedatAttribute($date)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('d/m/Y');
    }

    public function getBaseUserAttribute($value)
    {
        if(!$this->user)
        return;
        return $this->user->backendUser;
    }

    public function getStatusAttribute($value)
    {
        return strtolower($value);
    }

    public function getNameAttribute()
    {
        if($user = $this->customer()->first())
        {
            $user = $this->customer()->first()->user;
        
            return $user->first_name .' '. $user->last_name;
        }

        return null;
    }

    public function getDateInAttribute($value)
    {
        if(!$value)
        return Carbon::now($value)->format('Y-m-d');

        return $value;
    }

    public function getDateOutAttribute($value)
    {
        if(!$value)
        return Carbon::parse($value)->addDays(7)->format('Y-m-d');

        return $value;
    }

    public function getEmailAttribute($value)
    {
        if($this->user)
        {
            $user = $this->user->backendUser;
            return $user->email;
        }
    }

    public function getFullNameAttribute()
    {
        if($this->customer AND @$this->customer->user)
        {
            $customer = $this->customer->user;
            return $customer->first_name.' '.$customer->last_name;  
        }
        return null;
    }

    public function getVehiclePlateAttribute($value)
    {
        return @$this->vehicle->plate;
    }

    public function getVehicleBrandAttribute($value)
    {
        return @$this->vehicle->brand;
    }

    public function getVehicleModelAttribute($value)
    {
        return @$this->vehicle->model;
    }

    public function getVehicleSizeAttribute($value)
    {
        return @$this->vehicle->size ;
    }

    public function getSlotAttribute($value)
    {
        if(!$value)
        return 'Bay-'.$this->id * 100;

        return $value;
    }

    // public function getIsPaidAttribute($value)
    // {
    //     return Transaction::isPaid($this->ref_num);
    // }

    // public function getIsFailAttribute($value)
    // {
    //     return Transaction::isFail($this->ref_num);
    // }


    /*
    *   Filter Fields
    */
    public function filterFields($fields, $context = null)
    {
        if(isset($fields->bay) AND $this->status != 'pending' AND $this->status != '')
        {
            $fields->bay->disabled = true;
        }
    }


    /*
    *   EVENTS
    */
    public function beforeCreate()
    {
        $this->status = strtolower($this->statusOption[0]);
        $this->number = implode('-',str_split('#'.strftime("%Y%m%d%H%M%S"), 5));
    }

    public function beforeSave()
    {
        $this->status = strtolower($this->status);
    }

    // Update Bay status relation
    public function afterSave()
    {
        if(!empty($this->bay))
        {
            $this->bay->setReserve();
        }
        if($this->status=="checkout")
        {
            $this->bay->setAvailable();
        }
        if($this->status=='parked')
        {
            $this->bay->setOccupied();
        }
    }

    public function beforeDelete()
    {
        if($this->bay AND $this->bay->getOriginal('status')=='occupied')
        {
            $this->bay->setAvailable();
        }
    }

    /*
    *   SCOPES
    */
    public function scopeMonthOf($query,$timestamp)
    {
        $query
            ->whereMonth('date_in',$timestamp->month)
            ->whereYear('date_in',$timestamp->year)
        ;

        $query->orWhere(function($nest) use($timestamp) {
            $nest->whereMonth('date_out',$timestamp->month)
                ->whereYear('date_in',$timestamp->year)
            ;
        });
        return $query;
    }

    public function scopeGetBookings()
    {
        return $this->has('customer.user');
    }

    public function scopeVehicles()
    {
        if(!$this->user)
        return;
        return $this->user->backendUser->vehicles;
    }

    public function scopeFindByNumber($number)
    {
        return $this->where('number',$number);
    }

    
    /*
    *   Helpers
    */
    public function setCheckIn()
    {
        if($this->status != 'pending')
        {
            return false;
        }

        $this->status = 'parked';

        $this->park_in = Carbon::now()->format('Y-m-d H:i:s');

        return $this->save();
    }

    public function setCheckOut()
    {
        if($this->status != 'parked')
        {
            return false;
        }

        $this->status = 'checkout';

        $this->park_out = Carbon::now()->format('Y-m-d H:i:s');

        return $this->save();
    }

    public function clearRules()
    {
        $this->rules = [];
        return $this;
    }

    public function listParkBays()
    {
        $tmp = [];
        foreach(range(1000,4000) as $k=>$num){
            $bay = "Aeroparks - ".str_pad($num, 4, '0', STR_PAD_LEFT);
            $tmp[$bay] = $bay;
        }
        return $tmp;
    }

    public function setBay($bay)
    {
        $this->slot = $bay;
        $this->status = 'parked';
        return $this;
    }

    public function listProducts()
    {
        $options = Product::select('name')
            ->get()
            ->pluck('name')
            ->filter()
            ->toArray();

        return $options;
    }

}