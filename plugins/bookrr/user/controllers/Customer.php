<?php namespace Bookrr\User\Controllers;

use BackendMenu;
use Validator;
use ValidationException;
use BackendAuth;
use Backend\Classes\Controller;
use Bookrr\User\Models\BaseUser;
use Bookrr\User\Models\User;
use Bookrr\User\Models\Customer as CustomerModel;
use Bookrr\Booking\Models\Parking;
use Bookrr\user\Models\Vehicle;
use Bookrr\User\Models\Contact;
use \Carbon\Carbon;



class Customer extends Controller
{
    use \Bookrr\User\Traits\formatter;
    use \Bookrr\General\Traits\fflash;

    public $requiredPermissions = ['bookrr.users.customer'];

    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';

    protected $bookingFormWidget;
    protected $vehicleFormWidget;
    protected $contactFormWidget;

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Bookrr.User', 'user', 'customer');

        $this->bookingFormWidget = $this->createFormWidget([
            'alias'     => 'bookingForm',
            'arrayName' => 'Booking',
            'model'     => new Parking,
            'config'    => '$/bookrr/user/models/customer/booking_fields.yaml'
        ]);

        $this->vehicleFormWidget = $this->createFormWidget([
            'alias'     => 'vehicleForm',
            'arrayName' => 'Vehicle',
            'model'     => new Vehicle,
            'config'    => '$/bookrr/user/models/customer/vehicle_fields.yaml'
        ]);

        $this->contactFormWidget = $this->createFormWidget([
            'alias'     => 'contactForm',
            'arrayName' => 'Contact',
            'model'     => new Contact,
            'config'    => '$/bookrr/user/models/customer/contact_fields.yaml'
        ]);
    }

    public function create($id = null)
    {
        $this->pageTitle = "New Customer";
        
        $config = $this->makeConfig('$/bookrr/user/models/customer/fields.yaml');

        $config->model = new User;

        $widget = $this->makeWidget('Backend\Widgets\Form',$config);

        $this->vars['widget'] = $widget;
    }

    public function onCreate()
    {
        $data = post();

        $data['backendUser']['role_id'] = CustomerModel::roleID();
        
        $data['backendUser']['login']   = post('login');

        $data['birthdate'] = Carbon::parse(post('birthdate'));

        $validation = Validator::make($data, [
            'backendUser.first_name'    => 'required',
            'backendUser.last_name'     => 'required',
            'backendUser.login'         => 'required',
            'backendUser.email'         => 'required|email'
        ]);

        if ($validation->fails())
        {
            throw new ValidationException($validation);
        }

        $backendUser = BackendAuth::register($data['backendUser']);

        $backendUser->role_id = CustomerModel::roleID();

        $backendUser->save();

        $backendUser->profile()->save(User::create($data));

        if(post('close'))
        return \Redirect::to('/backend/bookrr/user/customer');

        return \Redirect::to('/backend/bookrr/user/customer/update/'.$backendUser->profile->id);

        \Flash::success('User Successfully Created!');
    }

    public function onCheckEmail()
    {
        /*
        * Serve jquery validator
        */
        $model = BaseUser::select('email')->where('email',request('email'));

        if($model->count())
        {
            return response()->json(false);
        }

        return response()->json(true);
    }

    protected function createFormWidget($options)
    {
        $config = $this->makeConfig($options['config']);

        $config->model = $options['model'];

        $config->alias = $options['alias'];

        $config->arrayName = $options['arrayName'];

        $widget = $this->makeWidget('Backend\Widgets\Form', $config);

        $widget->bindToController();

        return $widget;
    }

    protected function getCustomerModel()
    {
        $custId = post('customer_id') ? : post('id');

        $customer = $custId
            ? CustomerModel::find($custId)
            : new CustomerModel
        ;

        return $customer;
    }

    public function onLoadCreateBookingForm()
    {
        $this->vars['bookingFormWidget'] = $this->bookingFormWidget;

        $this->vars['custID'] = post('id');

        $this->addJs('/plugins/bookrr/user/assets/js/customer.js');

        return $this->makePartial('booking_create_form');
    }

    public function onCreateBooking()
    {
        $data = $this->bookingFormWidget->getSaveData();

        $model = new Parking;

        $model->rules = [];

        $model->fill($data);

        $model->save();

        $customer = $this->getCustomerModel();

        $customer->bookings()->save($model);

        return $this->refreshBookingList();
    }

    protected function refreshBookingList()
    {
        $items = $this->getCustomerModel()
            ->bookings()
            ->withDeferred($this->bookingFormWidget->getSessionKey())
            ->get()
        ;

        $this->vars['items'] = $items;

        return ['#itemList' => $this->makePartial('booking_list')];
    }

    public function onLoadCreateVehicleForm()
    {
        $this->vars['formWidget'] = $this->vehicleFormWidget;

        $this->vars['custID'] = post('id');

        return $this->makePartial('vehicle_create_form');
    }

    public function onLoadEditVehicleForm()
    {
        $this->vars['formWidget'] =  $this->createFormWidget([
            'alias'     => 'vehicleForm',
            'arrayName' => 'Vehicle',
            'model'     => new Vehicle,
            'model'     => Vehicle::find(post('vehicleId')),
            'config'    => '$/bookrr/user/models/customer/vehicle_fields.yaml'
        ]);

        $this->vars['custID'] = post('id');

        return $this->makePartial('vehicle_update_form');
    }

    public function onCreateVehicle()
    {
        $data = $this->vehicleFormWidget->getSaveData();

        $customer = $this->getCustomerModel();

        $model = new Vehicle;

        $model->rules = [
            'plate' => 'required',
            'brand' => 'required',
            'model' => 'required',
            'size'  => 'required'
        ];

        if(count($customer->vehicles)==0)
        {
            $data['primary'] = 1;
        }

        $model->fill($data);

        if($data['primary'])
        {
            $customer->vehicles()->update(['primary' => 0]);
        }

        $model->save();

        $customer = $this->getCustomerModel();

        $customer->vehicles()->save($model);

        return $this->refreshVehicleList();
    }

    public function onUpdateVehicle()
    {
        $data = post('Vehicle');

        $customer = $this->getCustomerModel();

        $model = Vehicle::find(post('vehicleId'));

        $model->fill($data);

        if($data['primary'])
        {
            $customer->vehicles()->update(['primary' => 0]);
        }

        $model->save();

        $customer = $this->getCustomerModel();

        $customer->vehicles()->save($model);

        return $this->refreshVehicleList();
    }

    public function onDeleteVehicle()
    {
        $recordId = post('vehicleId');

        $model = Vehicle::find($recordId);

        $model->delete();

        return $this->refreshVehicleList();
    }

    protected function refreshVehicleList()
    {
        $items = $this->getCustomerModel()
            ->vehicles()
            ->withDeferred($this->bookingFormWidget->getSessionKey())
            ->get()
        ;

        $this->vars['items'] = $items;

        return ['#vehicleList' => $this->makePartial('vehicle_list')];
    }

    public function onLoadCreateContactForm()
    {
        $this->vars['formWidget'] = $this->contactFormWidget;

        return $this->makePartial('contact_create_form');
    }

    public function onCreateContact()
    {
        $data = $this->contactFormWidget->getSaveData();

        $customer = $this->getCustomerModel();

        $model = new Contact;

        $model->rules = [
            'first_name' => 'required',
            'last_name'  => 'required',
            'phone'      => 'required'
        ];

        $model->fill($data);

        if($data['primary'])
        {
            $customer->contacts()->update(['primary' => 0]);
        }

        $model->save();

        $customer->contacts()->save($model);

        return $this->refreshContactList();
    }

    public function onLoadEditContactForm()
    {
        $this->vars['formWidget'] =  $this->createFormWidget([
            'alias'     => 'contactForm',
            'arrayName' => 'contact',
            'model'     => new Contact,
            'model'     => Contact::find(post('contactId')),
            'config'    => '$/bookrr/user/models/customer/contact_fields.yaml'
        ]);

        return $this->makePartial('contact_update_form');
    }

    public function onUpdateContact()
    {
        $data = post('contact');

        $customer = $this->getCustomerModel();

        $model = Contact::find(post('contactId'));

        $model->fill($data);

        if($data['primary'])
        {
            $customer->contacts()->update(['primary' => 0]);
        }

        $model->save();

        $customer->contacts()->save($model);

        return $this->refreshContactList();
    }

    public function onDeleteContact()
    {
        $recordId = post('contactId');

        $model = Contact::find($recordId);

        $model->delete();

        return $this->refreshContactList();
    }

    protected function refreshContactList()
    {
        $items = $this->getCustomerModel()
            ->contacts()
            ->withDeferred($this->contactFormWidget->getSessionKey())
            ->get()
        ;

        $this->vars['items'] = $items;

        return ['#contactList' => $this->makePartial('contact_list')];
    }

}