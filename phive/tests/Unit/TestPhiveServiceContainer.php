<?php

namespace Tests\Unit;
require_once __DIR__ . '/../../../phive/phive.php';

use BrandedConfig;
use IpBlock;
use Phive;
use PHPUnit\Framework\TestCase;




class TestPhiveServiceContainer extends TestCase
{


    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

    }

    public function testBrandedConfig()
    {
        $brandedConfig = phive('BrandedConfig');
        $this->assertSame('BrandedConfig', get_class($brandedConfig));

        /** @var BrandedConfig $brandedConfig */
        $brandedConfig = Phive::make('BrandedConfig');
        $this->assertSame('BrandedConfig', get_class($brandedConfig));

        $this->assertTrue($brandedConfig->newContainer());

        $cur_domain = phive()->getSetting('domain');
        $this->assertNotEmpty($cur_domain);
        $this->assertSame($cur_domain, $brandedConfig->getConfigValue('PHIVE.DOMAIN', 'nada'));
    }

    public function testPhiveEmpty()
    {
        $this->assertSame('Phive', get_class(phive()));
    }

    public function testPhiveClass()
    {
        $this->assertSame('IpBlock', get_class(phive('IpBlock')));
    }

    public function testPhiveCustomClass()
    {
        // this class is not defined in the modules.php, but we are able to add it to the container
        $jp_wheel = phive('DBUserHandler/JpWheel');
        $this->assertSame('JpWheel', get_class($jp_wheel));

        // we can get the same instance again
        $jp_wheel_hash = spl_object_hash($jp_wheel);
        $jp_wheel2 = phive('DBUserHandler/JpWheel');
        $jp_wheel2_hash = spl_object_hash($jp_wheel2);
        $this->assertSame($jp_wheel_hash, $jp_wheel2_hash);
    }

    public function testPhiveAlias()
    {
        $this->assertSame('DBUserHandler', get_class(phive('UserHandler')));
    }

    public function testNotFoundReturnsNull()
    {
        // This class is not in the container and can't be instantiated
        $this->assertNull(phive('NotFound'));
    }

    public function testPhiveSingleton()
    {
        $module = phive('UserHandler');
        // we can get the same instance again
        $module_hash = spl_object_hash($module);
        $module2 = phive('UserHandler');
        $module2_hash = spl_object_hash($module2);
        $this->assertSame($module_hash, $module2_hash);
    }

    public function testDependencyInjection()
    {
        $car = Phive::make(Car::class);
        $this->assertSame(Car::class, get_class($car));
        $output = $car->drive();
        $country = phive('IpBlock')->getCountry();
        $this->assertSame("Driving a car from $country", $output);

        // when we use make, we get a new instance
        $car2 = Phive::make(Car::class);
        $car_hash = spl_object_hash($car);
        $car2_hash = spl_object_hash($car2);
        $this->assertNotSame($car_hash, $car2_hash);
    }

    public function testBindingInterfaces()
    {
        phive()->bind(VehicleInterface::class, Truck::class, "", false); // bind Truck to VehicleInterface
        $truck = Phive::make(VehicleInterface::class); // this will return a Truck instance
        $this->assertSame(Truck::class, get_class($truck));
        $this->assertTrue(is_a($truck, VehicleInterface::class));

        $truck2 = phive(VehicleInterface::class); // this will return a Truck instance
        $this->assertSame(Truck::class, get_class($truck2));
        $this->assertNotSame(spl_object_hash($truck), spl_object_hash($truck2));

        $this->assertNull(phive(Car::class));
        phive()->bind(VehicleInterface::class, Car::class, "", true); // bind Car to VehicleInterface as singleton
        $car = phive(VehicleInterface::class);
        $this->assertSame(Car::class, get_class($car));
        $this->assertSame(spl_object_hash($car), spl_object_hash(phive(VehicleInterface::class)));

        // in this case we get the same instance as the previous call since it is a singleton
        $this->assertSame(spl_object_hash($car), spl_object_hash(Phive::make(VehicleInterface::class)));
    }
}

interface VehicleInterface
{
    public function drive();
}

class Car implements VehicleInterface
{

    private IpBlock $ipBlock;

    public function __construct(IpBlock $ipBlock)
    {
        $this->ipBlock = $ipBlock;
    }

    public function drive()
    {
        return "Driving a car from " . $this->ipBlock->getCountry();
    }
}

class Truck implements VehicleInterface
{
    public function drive()
    {
        return "Driving a truck";
    }
}
