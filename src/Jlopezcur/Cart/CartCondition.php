<?php
namespace Jlopezcur\Cart;

use Exception;
use Jlopezcur\Cart\Helpers\Helpers;
use Validator;

class CartCondition {

    private $args;
    private $parsedRawValue;

    /**
     * @param array $args (name, type, target, value)
     * @throws InvalidConditionException
     */
    public function __construct(array $args) {
        $this->args = $args;
        if(Helpers::isMultiArray($args)) Throw new Exception('Multi dimensional array is not supported.');
        else $this->validate($this->args);
    }

    /**
     * Getters
     */

    public function getTarget() { return $this->args['target']; }
    public function getName() { return $this->args['name']; }
    public function getType() { return $this->args['type']; }
    public function getUnit() { return $this->args['unit']; }
    public function getAttributes() { return (isset($this->args['attributes'])) ? $this->args['attributes'] : array(); }
    public function getValue() { return $this->args['value']; }

    /**
     * apply condition to total or subtotal
     *
     * @param $totalOrSubTotalOrPrice
     * @return float
     */
    public function applyCondition($totalOrSubTotalOrPrice) {
        return $this->apply($totalOrSubTotalOrPrice, $this->getValue());
    }

    /**
     * get the calculated value of this condition supplied by the subtotal|price
     *
     * @param $totalOrSubTotalOrPrice
     * @return mixed
     */
    public function getCalculatedValue($totalOrSubTotalOrPrice) {
        $this->apply($totalOrSubTotalOrPrice, $this->getValue());
        return $this->parsedRawValue;
    }

    /**
     * apply condition
     *
     * @param $totalOrSubTotalOrPrice
     * @param $conditionValue
     * @return float
     */
    protected function apply($totalOrSubTotalOrPrice, $conditionValue) {
        // if value has a percentage sign on it, we will get first
        // its percentage then we will evaluate again if the value
        // has a minus or plus sign so we can decide what to do with the
        // percentage, whether to add or subtract it to the total/subtotal/price
        // if we can't find any plus/minus sign, we will assume it as plus sign
        if ($this->valueIsPercentage($conditionValue)) {
            if ($this->valueIsToBeSubtracted($conditionValue)) {
                $value = Helpers::normalizePrice($this->cleanValue($conditionValue));
                $this->parsedRawValue = $totalOrSubTotalOrPrice * ($value / 100);
                $result = floatval($totalOrSubTotalOrPrice - $this->parsedRawValue);
            } else if ($this->valueIsToBeAdded($conditionValue)) {
                $value = Helpers::normalizePrice($this->cleanValue($conditionValue));
                $this->parsedRawValue = $totalOrSubTotalOrPrice * ($value / 100);
                $result = floatval($totalOrSubTotalOrPrice + $this->parsedRawValue);
            } else {
                $value = Helpers::normalizePrice($conditionValue);
                $this->parsedRawValue = $totalOrSubTotalOrPrice * ($value / 100);
                $result = floatval($totalOrSubTotalOrPrice + $this->parsedRawValue);
            }
        } else {
            if($this->valueIsToBeSubtracted($conditionValue)) {
                $this->parsedRawValue = Helpers::normalizePrice($this->cleanValue($conditionValue));
                $result = floatval($totalOrSubTotalOrPrice - $this->parsedRawValue);
            } else if ($this->valueIsToBeAdded($conditionValue)) {
                $this->parsedRawValue = Helpers::normalizePrice($this->cleanValue($conditionValue));
                $result = floatval($totalOrSubTotalOrPrice + $this->parsedRawValue);
            } else {
                $this->parsedRawValue = Helpers::normalizePrice($conditionValue);
                $result = floatval($totalOrSubTotalOrPrice + $this->parsedRawValue);
            }
        }
        return $result;
    }

    /**
     * Tools
     */

    protected function valueIsPercentage($value) { return (preg_match('/%/', $value) == 1); }
    protected function valueIsToBeSubtracted($value) { return (preg_match('/\-/', $value) == 1); }
    protected function valueIsToBeAdded($value) { return (preg_match('/\+/', $value) == 1); }
    protected function cleanValue($value) { return str_replace(array('%','-','+'),'',$value); }

    /**
     * validates condition arguments
     *
     * @param $args
     * @throws InvalidConditionException
     */
    protected function validate($args) {
        $validator = Validator::make($args, [
            'name' => 'required',
            'type' => 'required',
            'unit' => 'required',
            'target' => 'required',
            'value' => 'required',
        ]);
        if($validator->fails()) throw new Exception($validator->messages()->first());
    }
}
