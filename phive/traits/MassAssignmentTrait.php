<?php

/**
 * Trait MassAssignmentTrait
 */
trait MassAssignmentTrait
{
    protected $fillable = [];
    protected $guarded = [];
    private $validator;
    private $caller;

    /**
     * Fill the model with an array of attributes.
     * @param array $property_values
     * @throws Exception
     */
    public function fill(array $property_values)
    {
        // need to check that the array sets the parameters of the fillable items
        foreach ($property_values as $property => $value){
            if($this->isFillable($property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * TODO: setValidator will be done at a later stage
     */
    protected function setCaller()
    {

    }

    /**
     * Checks if the property to be set can actually be set or not
     * @param $property
     * @return bool
     * @throws Exception
     */
    protected function isFillable($property): bool
    {
        // if both $fillable and guarded are filled up then an excemption is thrown as
        // cannot set up both at the same time
        if (count($this->fillable) > 0 && count($this->guarded) > 0){
            // throw error as cannot be both
            throw new Exception('Fillable and guarded cannot be both set.');
        }

        // if the $guarded is set then only the guarded attribute is not to be inserted, or if the
        // $guarded is set with * then nothing should be inserted
        if(count($this->guarded) > 0) {
            if (in_array($property, $this->guarded) || $this->guarded == ['*']) {
                return false;
            }
            // else everything else can be inserted
            return true;
        }

        // only the fillable attributes are to be inserted else insert nothing
        if(in_array($property, $this->fillable)) {
            return true;
        }

        // by default return false as nothing can be inserted
        return false;
    }

    /**
     * @return array
     */
    public function getFillables(): array
    {
        return $this->fillable;
    }
}