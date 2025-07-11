<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;
use Mockery\MockInterface;
use Rakit\Validation\ErrorBag;
use Rakit\Validation\Validation;
use Rakit\Validation\Validator;
use Tests\Unit\Phive\Modules\Licensed\IT\Support;

/**
 * Class AbstractTypeTest
 */
class AbstractTypeTest extends Support
{
    /**
     * @var AbstractEntity
     */
    protected $stub;

    /**
     * AbstractTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = $this->getMockForAbstractClass(AbstractEntity::class);
    }

    public function testAbstractType()
    {
        $this->assertInstanceOf(AbstractEntity::class, $this->stub);
    }

    public function testIsFillableWithoutProperties()
    {
        $is_fillable = self::getAccessibleMethod(AbstractEntity::class, 'isFillable');
        $this->assertNotTrue($is_fillable->invokeArgs($this->stub, ['test']));
    }

    public function testIsFillableWithProperties()
    {
        $is_fillable = self::getAccessibleMethod(AbstractEntity::class, 'isFillable');
        self::setValueInPrivateProperty(
            $this->stub,
            AbstractEntity::class,
            'fillable',
            ['test']
        );

        $this->assertTrue($is_fillable->invokeArgs($this->stub, ['test']));
    }

    public function testFill()
    {
        self::setValueInPrivateProperty(
            $this->stub,
            AbstractEntity::class,
            'fillable',
            ['test']
        );

        $this->stub->fill(['test' => true]);
        $this->assertTrue($this->stub->test);

        $this->stub->fill(['test' => 123]);
        $this->assertEquals(123, $this->stub->test);

        $this->stub->fill(['test' => 'test']);
        $this->assertEquals('test', $this->stub->test);

        $this->stub->fill(['test' => new \stdClass()]);
        $this->assertInstanceOf(\stdClass::class, $this->stub->test);
    }

    public function testExistsToArrayMethod()
    {
        $this->assertTrue(method_exists($this->stub, 'toArray'));
    }

    public function testValidatorType()
    {
        $validator = self::setPropertyAccessiblePublic(AbstractEntity::class, 'validator');
        $set_validator = self::getAccessibleMethod(AbstractEntity::class, 'setValidator');
        $set_validator->invoke($this->stub);
        $this->assertInstanceOf(Validator::class, $validator->getValue($this->stub));
    }

    public function testSuccessValidation()
    {
        $validation_mock = $this->getValidationMock();
        $validator_mock = $this->getValidatorMock($validation_mock);

        $this->stub = $this->getMockBuilder(AbstractEntity::class)
            ->onlyMethods(['setValidator'])
            ->getMockForAbstractClass();

        self::setValueInPrivateProperty($this->stub,
            AbstractEntity::class,
            'validator',
            $validator_mock
        );

        self::setValueInPrivateProperty($this->stub,
            AbstractEntity::class,
            'rules',
            $this->getRules()
        );

        $this->stub->validate(['test' => 'value']);
        $this->assertCount(0, $this->stub->errors);
    }

    public function testErrorValidation()
    {
        $error_message = 'Error test';
        $error_bag_mock = $this->getErrorBag($error_message);
        $validation_mock = $this->getValidationMock(true, $error_bag_mock);
        $validator_mock = $this->getValidatorMock($validation_mock);

        $this->stub = $this->getMockBuilder(AbstractEntity::class)
            ->onlyMethods(['setValidator'])
            ->getMockForAbstractClass();

        self::setValueInPrivateProperty($this->stub,
            AbstractEntity::class,
            'validator',
            $validator_mock
        );

        self::setValueInPrivateProperty($this->stub,
            AbstractEntity::class,
            'rules',
            $this->getRules()
        );

        $this->stub->validate([]);

        $this->assertCount(1, $this->stub->errors);

        $this->assertEquals($error_message, $this->stub->errors['test']['required']);
    }

    /**
     * @param bool $has_errors
     * @param ErrorBag $error_bag
     * @return MockInterface
     * @throws \Exception
     */
    private function getValidationMock(bool $has_errors = false, ErrorBag $error_bag = null): MockInterface
    {
        $validation_mock = \Mockery::mock(Validation::class);
        $validation_mock->shouldReceive('fails')
            ->andReturn($has_errors);

        if ($has_errors) {
            if (empty($error_bag)) {
                throw new \Exception('error bag is null');
            }

            $validation_mock->shouldReceive('errors')
                ->andReturn($error_bag);

        }

        return $validation_mock;
    }

    /**
     * @param Validation $validation
     * @return MockInterface
     */
    private function getValidatorMock(Validation $validation): MockInterface
    {
        $validator_mock = \Mockery::mock(Validator::class);
        $validator_mock->shouldReceive('validate')
            ->andReturn($validation);
        return $validator_mock;
    }

    /**
     * @param string $validation
     * @return array
     */
    private function getRules(string $validation = null): array
    {
        return [
            'test' => $validation ?? 'required'
        ];
    }

    /**
     * @param $message
     * @return MockInterface
     */
    private function getErrorBag(string $message): MockInterface
    {
        $error_bag_mock = \Mockery::mock(ErrorBag::class);
        $error_bag_mock->shouldReceive('toArray')
            ->andReturn(
                [
                    'test' => [
                        'required' => $message
                    ]
                ]
            );

        return $error_bag_mock;
    }

}