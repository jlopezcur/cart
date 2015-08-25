<?php
namespace Jlopezcur\Cart;

use Illuminate\Support\Collection;
use Jlopezcur\Cart\Helpers\Helpers;
use Event;

class CartItemCollection extends Collection {

    protected $events;
    protected $instance;

    public function __construct($instance = '') {
        $this->instance = $instance;
        parent::__construct();
    }

    public function addItem($params = []) {
        if (Helpers::isMultiArray($params)) foreach($params as $item) $this->addItem($item);

        $item = new CartItem($params);
        $id = $params['id'];

        if($this->has($id)) $this->updateItem($id, $item);
        else {
            Event::fire($this->instance.'.adding', [$item, $this]);
            $this->put($id, $item);
            Event::fire($this->instance.'.added', [$item, $this]);
        }
    }

    public function updateItem($id, $data) {
        $item = $this->pull($id);
        Event::fire($this->instance.'.updating', [$item, $this]);
        foreach($data as $key => $value) {
            // if the key is currently "quantity" we will need to check if an arithmetic
            // symbol is present so we can decide if the update of quantity is being added
            // or being reduced.
            if ($key == 'quantity') {
                if(preg_match('/\-/', $value) == 1) {
                    $value = (int) str_replace('-','',$value);

                    // we will not allowed to reduced quantity to 0, so if the given value
                    // would result to item quantity of 0, we will not do it.
                    if(($item[$key] - $value) > 0) {
                        $item[$key] -= $value;
                    }
                } elseif( preg_match('/\+/', $value) == 1 ) {
                    $item[$key] += (int) str_replace('+','',$value);
                } else {
                    $item[$key] += (int) $value;
                }
            } else {
                $item[$key] = $value;
            }
        }
        $this->put($id, $item);
        Event::fire($this->instance.'.updated', [$item, $this]);
    }

    public function remove($id) {
        Event::fire($this->instance.'.removing', [$id, $this]);
        $this->forget($id);
        Event::fire($this->instance.'.removed', [$id, $this]);
    }

    public function clear() {
        Event::fire($this->instance.'.clearing', [$this]);
        foreach ($this->all() as $item) $this->forget($item->id);
        Event::fire($this->instance.'.cleared', [$this]);
    }

    public function getTotalQuantity() {
        if ($this->isEmpty()) return 0;
        $count = $this->sum(function($item) { return $item->quantity; });
        return $count;
    }

    public function getSubTotal($type = 'price') {
        $sum = $this->sum(function($item) use ($type) {
            $originalPrice = $item->price;
            $newPrice = 0.00;

            if ($type === 'points') { $originalPrice = $item->points; $newPrice = 0; }

            $processed = 0;
            if ($item->hasConditions()) {
                if(!is_array($item->conditions)) $item->conditions = [$item->conditions];

                foreach($item->conditions as $condition) {
                    if($condition->getTarget() === 'item' && $condition->getUnit() === $type) {
                        ($processed > 0) ? $toBeCalculated = $newPrice : $toBeCalculated = $originalPrice;
                        $newPrice = $condition->applyCondition($toBeCalculated);
                        $processed++;
                    } else {
                        $newPrice = $originalPrice;
                    }
                }
                return $newPrice * $item->quantity;
            } else return $originalPrice * $item->quantity;
        });

        $out = floatval($sum);
        if ($type == 'points') $out = floor($sum);

        return $out;
    }

    /**
     * Conditions
     */

    public function addItemCondition($id, $condition) {
        if ($item = $this->get($id)) {
            Event::fire($this->instance.'.adding-item-condition', [$id, $condition, $this]);
            $item->addItemCondition($condition);
            Event::fire($this->instance.'.added-item-condition', [$id, $condition, $this]);
        }
        return $this;
    }

    public function removeItemCondition($id, $condition_name) {
        if($item = $this->get($id)) {
            Event::fire($this->instance.'.removing-item-condition', [$id, $condition_name, $this]);
            $item->removeItemCondition($condition_name);
            Event::fire($this->instance.'.removed-item-condition', [$id, $condition_name, $this]);
        }
        return $this;
    }

}
