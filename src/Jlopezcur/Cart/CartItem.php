<?php
namespace Jlopezcur\Cart;

use Exception;
use Jlopezcur\Cart\Helpers\Helpers;
use Validator;

class CartItem {

    private $id;
    private $name;
    private $product;
    private $price;
    private $points;
    private $quantity;
    private $attributes = [];
    private $conditions;

    public function __construct(array $args) {
        if ($this->validate($args)) {
            $this->id = $args['id'];
            if (isset($args['name'])) $this->name = $args['name'];
            $this->product = $args['product'];
            if (isset($args['price'])) $this->price = Helpers::normalizePrice($args['price']);
            if (isset($args['points'])) $this->points = $args['points'];
            $this->quantity = $args['quantity'];
            if (isset($args['attributes'])) $this->attributes = new ItemAttributeCollection($args['attributes']);

            $this->conditions = new CartConditionCollection('item-conditions');
            if (isset($args['conditions'])) {
                foreach ($args['conditions'] as $condition) {
                    $this->conditions->addCondition($condition);
                }
            }
        }
    }

    protected function validate($args) {
        $validator = Validator::make($args, [
            'id' => 'required',
            'quantity' => 'required|numeric|min:1',
            'product' => 'required',
        ]);
        if ($validator->fails()) throw new Exception($validator->messages()->first());
        if (!isset($args['points']) && !isset($args['price'])) throw new Exception('No price or points');
        return true;
    }

    public function getPriceSum($type = 'price') {
        if ($type == 'price') return $this->price * $this->quantity;
        return $this->points * $this->quantity;
    }

    /**
     * Conditions
     */

    public function getConditions() { return $this->conditions; }

    public function hasConditions() { return ($this->conditions->count() > 0); }
    public function addItemCondition($condition) { $this->conditions->addCondition($condition); return $this; }
    public function removeItemCondition($condition_name) { $this->conditions->removeCondition($condition_name); return $this; }

    public function getSubTotal($type = 'price') {
        $value = $this->price; if ($type === 'points') $value = $this->points;
        if ($this->hasConditions()) {
            foreach($this->conditions->all() as $condition) {
                if($condition->getTarget() === 'item' && $condition->getUnit() === $type) {
                    $value = $condition->applyCondition($value);
                }
            }
        }
        return $value * $this->quantity;
    }

    /**
     * Getters & Setters
     */

     public function __get($property) {
         if (property_exists($this, $property)) {
             return $this->$property;
         }
     }

     public function __set($property, $value) {
         if (property_exists($this, $property)) {
             $this->$property = $value;
         }
         return $this;
     }

}
