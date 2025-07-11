<?php

namespace App\Controllers;

use App\Helpers\DataFormatHelper;
use App\Models\BankCountry;
use App\Models\RegulatoryStats;
use App\Repositories\HalfYearReportRepository;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class HalfYearReportController implements ControllerProviderInterface
{

    /** @var HalfYearReportRepository $repo */
    private $repo;

    public function __construct()
    {
        $this->repo = new HalfYearReportRepository();
    }

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\HalfYearReportController::halfYearReport')
            ->bind('admin.reports.half-year')
            ->before(function () use ($app) {
                if (!p('admin.reports.half-year')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    public function halfYearReport(Application $app, Request $request)
    {
        $this->repo->setApp($app);

        $countries = ['SE'];
        $country = $request->get('country', $countries[0]);
        $intervals = $this->repo->getHalfYearDropdown();
        $currency = DataFormatHelper::getCurrencyFromCountry($country);
        $formatted_countries = BankCountry::select('iso', 'printable_name')->get()->keyBy('iso');
        $columns = [
            'jurisdiction' => 'Jurisdiction',
            'start_date' => 'Start date',
            'end_date' => 'End date',
            'category' => 'Category',
            'subcategory' => 'Subcategory',
            'value' => 'Value',
        ];

        list ($start, $end) = explode(':', $request->get('interval', $intervals[0]));

        $results = RegulatoryStats::query()
            ->where('start_date', $start)
            ->where('end_date', $end)
            ->where('jurisdiction', $country)
            ->get()
            ->map(function ($el) use ($currency) {
                if ($el->type === RegulatoryStats::TYPE_MONEY) {
                    $el->value .= " " . $currency;
                }
                if ($el->type === RegulatoryStats::TYPE_PERCENT) {
                    $el->value .= " %";
                }
                return $el;
            });

        if (!empty($request->get('export'))) {

            $results = $results->map(function ($el) use ($columns) {
                $aux = [];

                foreach ($columns as $column => $title) {
                    $aux[$column] = $el[$column];
                }

                return $aux;
            });
            return $this->repo->downloadCsv($results->toArray(), array_values($columns));
        }

        return $app['blade']->view()->make("admin.reports.halfyear.$country", compact(
            'app', 'country', 'countries', 'intervals', 'results', 'request', 'formatted_countries', 'currency'
        ))->render();
    }

}
