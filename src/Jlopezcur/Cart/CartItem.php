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
    private $conditions = [];

    public function __construct(array $args) {
        if ($this->validate($args)) {
            $this->id = $args['id'];
            if (isset($args['name'])) $this->name = $args['name'];
            $this->product = $args['product'];
            if (isset($args['price'])) $this->price = Helpers::normalizePrice($args['price']);
            if (isset($args['points'])) $this->points = $args['points'];
            $this->quantity = $args['quantity'];
            if (isset($args['attributes'])) $this->attributes = new ItemAttributeCollection($args['attributes']);
            if (isset($args['conditions'])) $this->conditions = $args['conditions'];
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

    public function hasConditions() {
        if (!isset($this->conditions)) return false;
        if (is_array($this->conditions)) return count($this->conditions) > 0;

        $conditionInstance = "Jlopezcur\\Cart\\CartCondition";
        if ($this->conditions instanceof $conditionInstance) return true;

        return false;
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
