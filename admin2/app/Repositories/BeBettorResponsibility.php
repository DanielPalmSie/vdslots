<?php
namespace App\Repositories;

use App\Models\User;
use Silex\Application;

class BeBettorResponsibility
{
    /**
     * Return self instance
     *
     * @return BeBettorResponsibility
     */
    public static function instance()
    {
        return new self();
    }

    /**
     * Return view for BeBettor checks
     *
     * @param Application $app
     * @param string $section
     * @param int $user_id
     * @return mixed
     */
    public function getView(Application $app,string $section,int $user_id)
    {
        $this->app = $app;
        $user = User::find($user_id);

        $data = $user->beBettor->where('type', $section)->toArray();

        $columns = [
            'user_id' => 'ID',
            'fullname'=>'Full name',
            'country'=>'Country',
            'brand'=>'Brand call requested',
            'requested_at'=>'Call Date',
            'status'=>'Score',
        ];

        $remote = phive('Distributed')->getSetting('remote_brand');
        $remote_brand_id = phive('Distributed')->getBrandIdByName($remote);
        $user_id_remote = $user->getSetting("c{$remote_brand_id}_id");

        $method = 'remoteAffordabilityCheck';
        if ($section == 'vulnerability') {
            $method = 'remoteVulnerabilityCheck';
        }
        $response = toRemote($remote, $method, [$user_id_remote], 2);

        if(!isset($response["result"]) && !empty($response)) {
            $data = array_merge($data, $response);
        }

        return $app['blade']->view()->make(
            "admin.rg.partials.{$section}-table",
            compact('app', 'section', 'data', 'user', 'columns')
        )->render();
    }
}
