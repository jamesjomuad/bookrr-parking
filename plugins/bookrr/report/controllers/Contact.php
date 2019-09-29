<?php namespace Aeroparks\Report\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Aeroparks\Report\Controllers\BaseController;

/**
 * Contact Back-end Controller
 */
class Contact extends BaseController
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Aeroparks.Report', 'report', 'contact');
    }
}
