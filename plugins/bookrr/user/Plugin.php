<?php namespace Aeroparks\User;

use Backend;
use Event;
use System\Classes\PluginBase;
use Backend\Models\User as UserModel;
use Backend\Controllers\Users as UserController;
use BackendAuth;
use Aeroparks\User\Models\User;
use Aeroparks\User\Models\Loyalty;



class Plugin extends PluginBase
{
    public $elevated = true;

    public function pluginDetails()
    {
        return [
            'name'        => 'Aero User',
            'description' => 'Manage Aeroparks Users',
            'author'      => 'Jomuad',
            'icon'        => 'icon-car'
        ];
    }

    public function boot()
    {
        # Middleware
        $this->app['Illuminate\Contracts\Http\Kernel']
        ->pushMiddleware('Aeroparks\User\Middleware\FrontendMiddleware');
        // ->pushMiddleware('Aeroparks\User\Middleware\CustomerMiddleware');


        # Overide backend user model
        UserModel::extend(function($model){
            // Relation
            $model->hasOne = [
                'aeroUser'  => ['Aeroparks\User\Models\User'],
                'points'    => ['Aeroparks\User\Models\Loyalty']
            ];

            // isCustomer method
            $model->addDynamicMethod('isCustomer',function() use($model) {
                $user = $model->aeroUser;
                return ($model->aeroUser && strtolower($user->type)=='customer') ? true : false;
            });

            // isActive method
            $model->addDynamicMethod('isActive',function() use($model) {
                return (@$model->aeroUser->is_active) ? true : false;
            });
        });

        UserController::extendFormFields(function($form, $model, $context){

            if(!$model->aeroUser){
                return;
            }

            $form->addTabFields([
                'aeroUser[title]' => [
                    'label' => 'Title',
                    'span'  => 'auto',
                    'tab'   => 'Profile'
                ],
                'aeroUser[phone]' => [
                    'label' => 'Phone Number',
                    'span'  => 'auto',
                    'tab'   => 'Profile'
                ],
                'aeroUser[company]' => [
                    'label' => 'Company',
                    'span'  => 'auto',
                    'tab'   => 'Profile'
                ],
                'aeroUser[age]' => [
                    'label' => 'Age',
                    'type'  => 'number',
                    'span'  => 'auto',
                    'tab'   => 'Profile'
                ],
                'aeroUser[gender]' => [
                    'label' => 'Gender',
                    'span'  => 'auto',
                    'tab'   => 'Profile'
                ],
                'aeroUser[birthdate]' => [
                    'label' => 'Birthdate',
                    'type'  => 'datetimepicker',
                    'mode'  => 'date',
                    'span'  => 'auto',
                    'tab'   => 'Profile'
                ]
            ]);
        });

        # Create/Update Loyalty Reward for customer
        Event::listen('backend.user.login', function ($user) {
            if(BackendAuth::getUser()->isCustomer())
            {
                if($user->points)
                {
                    $plusPoints = Loyalty::$loginPoints;
                    $user->points->points += $plusPoints;
                    $user->points->save();
                }
                else
                {
                    $loyalty = Loyalty::create(['points' => Loyalty::$loginPoints]);
                    $user->points()->save($loyalty);
                }
            }
        });

        # Set User as Active
        Event::listen('backend.user.login', function ($user) {
            if($user->aeroUser)
            {
                $user->aeroUser->is_active = true;
                $user->aeroUser->save();
            }
        });

        #Set User as not Active
        Event::listen('backend.user.logout', function ($user) {
            if($user->aeroUser)
            {
                $user->aeroUser->is_active = false;
                $user->aeroUser->save();
            }
        });

        Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
            if(BackendAuth::getUser() && BackendAuth::getUser()->isCustomer())
            {
                $controller->addCss('/plugins/aeroparks/user/assets/css/user.css',str_random(5));  
            }
        });
    }

    public function registerPermissions()
    {
        return [
            'aeroparks.user.*' => [
                'tab' => 'Aeroparks',
                'label' => 'Manage Aeropark Users.'
            ],
        ];
    }

    public function registerNavigation()
    {
        if(BackendAuth::getUser()->isCustomer())
        {
            return [
                'loyalty' => [
                    'label' => 'Rewards',
                    'url'   => Backend::url('aeroparks/user/loyalty'),
                    'icon'  => 'icon-star',
                    'order' => 1000
                ]
            ];
        }


        return [
            'user' => [
                'label'       => 'Users',
                'url'         => Backend::url('aeroparks/user/customer'),
                'icon'        => 'icon-users',
                'permissions' => ['aeroparks.user.*'],
                'order'       => 920,

                'sideMenu' => [
                    'customer' => [
                        'label'       => 'Customers',
                        'url'         => Backend::url('aeroparks/user/customer'),
                        'icon'        => 'icon-user-circle-o',
                        'permissions' => ['aeroparks.user.*'],
                    ],
                    'staff' => [
                        'label'       => 'Staff',
                        'url'         => Backend::url('aeroparks/user/staff'),
                        'icon'        => 'icon-user-secret',
                        'permissions' => ['aeroparks.user.*'],
                    ],
                    // 'agent' => [
                    //     'label'       => 'Agents',
                    //     'url'         => Backend::url('aeroparks/user/agent'),
                    //     'icon'        => 'icon-handshake-o',
                    //     'permissions' => ['aeroparks.user.*'],
                    // ],
                    // 'affiliate' => [
                    //     'label'       => 'Affiliate',
                    //     'url'         => Backend::url('aeroparks/user/affiliate'),
                    //     'icon'        => 'icon-user-plus',
                    //     'permissions' => ['aeroparks.user.*'],
                    // ],
                    // 'loyalty' => [
                    //     'label'       => 'Loyalty',
                    //     'url'         => Backend::url('aeroparks/user/loyalty'),
                    //     'icon'        => 'icon-star',
                    //     'permissions' => ['aeroparks.user.*'],
                    // ]
                ]
            ]
        ];
    }
}
