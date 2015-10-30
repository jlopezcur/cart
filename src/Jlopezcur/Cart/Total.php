<?php
namespace Jlopezcur\Cart;

use Exception;
use Jlopezcur\Cart\Helpers\Helpers;
use Validator;

class Total {

    private $id;
    private $name;
    private $price;
    private $points;
    private $quantity;
    private $attributes = [];
    private $conditions;

    public function __construct(array $args) {
        if ($this->validate($args)) {
            $this->id = $args['id'];
            if (isset($args['name'])) $this->name = $args['name'];
            if (isset($args['price'])) $this->price = Helpers::normalizePrice($args['price']);
            if (isset($args['points'])) $this->points = $args['points'];
            $this->quantity = $args['quantity'];
            if (isset($args['attributes'])) $this->attributes = new ItemAttributeCollection($args['attributes']);

            $this->conditions = new ConditionCollection('total-conditions');
            if (isset($args['conditions'])) {
                foreach ($args['conditions'] as $condition) {
                    $this->conditions->addCondition($condition);
                }
            }
        }
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'points' => $this->points,
            'quantity' => $this->quantity,
            'attributes' => $this->attributes,
            'conditions' => $this->conditions,
        ];
    }

    protected function validate($args) {
        $validator = Validator::make($args, [
            'id' => 'required',
            'quantity' => 'required|numeric|min:1'
        ]);
        if ($validator->fails()) throw new Exception($validator->messages()->first());
        if (!isset($args['points']) && !isset($args['price'])) throw new Exception('No price or points');
        return true;
    }

    /**
     * Return price of the total width or without conditions
     * @param boolean $condition Return price with conditions or not
     * @return float Price of the total
     */
    public function getPrice($conditions = false) {
        $price = $this->price;
        if ($conditions) {
            foreach($this->conditions->all() as $condition) {
                if($condition->getUnit() === 'price') {
                    $price = $condition->applyCondition($price);
                }
            }
        }
        return $price;
    }

    /**
     * Return points of the total
     * @return integer Points of the total
     */
    public function getPoints($conditions = false) {
        $points = $this->points;
        if ($conditions) {
            foreach($this->conditions->all() as $condition) {
                if($condition->getUnit() === 'points') {
                    $points = $condition->applyCondition($points);
                }
            }
        }
        return $points;
    }

    /**
     * Return price (with or without conditions) or points multiplied by quantity
     * @param string $type 'points' or 'price', by default: 'price'
     * @param boolean $condition Return price with conditions or not
     * @return mixed Return price or points multiplied by quantity
     */
    public function getSum($type = 'price', $conditions = false) {
        if ($type == 'price') {
            return $this->getPrice($conditions) * $this->quantity;
        } else if ($type == 'points') {
            return $this->getPoints($conditions) * $this->quantity;
        }
        return 0;
    }

    /**
     * Conditions
     */

    public function getConditions() { return $this->conditions; }

    public function hasConditions() { return ($this->conditions->count() > 0); }
    public function addCondition($condition) { $this->conditions->addCondition($condition); return $this; }
    public function removeCondition($condition_name) { $this->conditions->removeCondition($condition_name); return $this; }

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
