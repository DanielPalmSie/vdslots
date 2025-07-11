<?php


namespace App\Classes\GoAML;

use Silex\Application;

class Brand
{
    /**
     * Define the brands and properties to use on the report
     *
     * @var array[]
     */
    private $brands;

    /**
     * Create a new brand instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->brands = $app['brands'];
    }

    /**
     * Return the Brands properties as an object
     *
     * @param $brand
     * @return object
     */
    public function getBrandInfos($brand)
    {
        return (object) $this->brands[$brand];
    }
}
