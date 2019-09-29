<?php namespace Aeroparks\General\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Aeroparks\User\Models\Customer;
use Carbon\Carbon;
use Faker\Factory as Faker;

/**
 * BookingStat Form Widget
 */
class BookingStat extends ReportWidgetBase
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
        $this->addCss('css/bookingstat.css', 'aeroparks.general');
        $this->addJs('js/bookingstat.js', 'aeroparks.general');
    }

    public function getWeekShortName()
    {
        return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][Carbon::now()->dayOfWeek];
    }

    public function rowClass($week=null)
    {
        if($week==$this->getWeekShortName())
        return 'active'; 
    }

}
