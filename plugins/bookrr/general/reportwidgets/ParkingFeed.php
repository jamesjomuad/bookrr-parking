<?php namespace Bookrr\General\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Bookrr\User\Models\Customer;
use Carbon\Carbon;
use Faker\Factory as Faker;

/**
 * BookingStat Form Widget
 */
class ParkingFeed extends ReportWidgetBase
{

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('widget');
    }

    public function prepareVars()
    {
        $this->vars['customers'] = Customer::whereDate('created_at', Carbon::today())->get();
        $this->vars['faker']     = Faker::create();
    }

    public function loadAssets()
    {
        // $this->addCss('css/bookingstat.css', 'bookrr.general');
        // $this->addJs('js/bookingstat.js', 'bookrr.general');
    }


}
