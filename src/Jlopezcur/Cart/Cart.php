<?php
namespace Jlopezcur\Cart;

use Exception;
use Jlopezcur\Cart\Helpers\Helpers;

class Cart {

    protected $session;         // Item Storage
    protected $events;          // The event dispatcher
    protected $instance_name;    // The cart session key

    protected $items;           // CartItemCollection
    protected $conditions;      // CartConditionCollection

    /**
     * our object constructor
     *
     * @param $session
     * @param $events
     * @param $instance_name
     * @param $session_key
     */
    public function __construct($session, $events, $instance_name, $session_key) {
        $this->events = $events;
        $this->session = $session;
        $this->instance_name = $instance_name;

        $this->items = new CartItemCollection($session, $session_key.'_cart_items', $this->events, $this->instance_name);
        $this->conditions = new CartConditionCollection($session, $session_key.'_cart_conditions', $this->events, $this->instance_name);

        $this->events->fire($this->instance_name.'.created', [$this]);
    }

    /**
     * Getters
     */

    public function getInstanceName() { return $this->instance_name; }

    /**
     * Items
     */

    public function getContent() { return $this->items; }
    public function has($id) { return $this->items->has($id); }
    public function get($id) { return $this->items->get($id); }
    public function add($params = []) { $this->items->addItem($params); return $this; }
    public function update($id, $data) { $this->items->updateItem($id, $data); return $this; }
    public function remove($id) { $this->items->remove($id); return $this; }
    public function clear() { $this->items->clear(); return $this; }
    public function isEmpty() { return $this->items->isEmpty(); }

    public function addItemCondition($id, $condition) {
        if ($item = $this->get($id)) {
            $conditions = $item->conditions;
            array_push($conditions, $condition);
            $this->update($id, ['conditions' => $conditions]);
        }
        return $this;
    }
    public function removeItemCondition($id, $condition_name) {
        if($item = $this->get($id)) {
            $conditions = $item->conditions;
            foreach($conditions as $key => $condition) {
                if($condition->getName() == $condition_name) unset($conditions[$key]);
            }
            $this->update($id, ['conditions' => $conditions]);
        }
        return $this;
    }

    public function getSubTotal($type = 'price') { return $this->items->getSubTotal($type); }
    public function getTotalQuantity() { return $this->items->getTotalQuantity(); }

    /**
     * Conditions
     */

    public function addCondition($condition) { $this->conditions->addCondition($condition); return $this; }
    public function getCondition($condition_name) { return $this->conditions->get($conditionName); }
    public function getConditionsByType($type) { return $this->conditions->getConditionsByType($type); }
    public function removeConditionsByType($type) { $this->conditions->removeConditionsByType($type); return $this; }
    public function removeCondition($condition_name) { $this->conditions->removeCondition($condition_name); return $this; }
    public function clearConditions() { $this->conditions->clear(); return $this; }

    /**
     * Cart
     */

    public function getTotal($type = 'price') {
        $subTotal = $this->getSubTotal($type);
        $newTotal = 0.00; if ($type == 'points') $newTotal = 0;
        $process = 0;

        // if no conditions were added, just return the sub total
        if(!$this->conditions->count()) return $subTotal;
        $this->conditions->each(function($cond) use ($subTotal, &$newTotal, &$process) {
            if($cond->getTarget() === 'subtotal') {
                ($process > 0) ? $toBeCalculated = $newTotal : $toBeCalculated = $subTotal;
                $newTotal = $cond->applyCondition($toBeCalculated);
                $process++;
            }
        });

        return $newTotal;
    }

}
