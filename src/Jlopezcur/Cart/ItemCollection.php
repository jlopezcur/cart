<?php
namespace Jlopezcur\Cart;

use Illuminate\Support\Collection;
use Jlopezcur\Cart\Helpers\Helpers;
use Event;

class ItemCollection extends Collection {

    protected $instance;

    public function __construct($instance = '') {
        $this->instance = $instance;
        parent::__construct();
    }

    public function addItem($params = []) {
        if (Helpers::isMultiArray($params)) foreach($params as $item) $this->addItem($item);

        $item = new Item($params);
        $id = $params['id'];

        if($this->has($id)) $this->updateItem($id, $item);
        else {
            Event::fire($this->instance.'.adding', [$item, $this]);
            $this->put($id, $item);
            Event::fire($this->instance.'.added', [$item, $this]);
        }
    }

    public function updateQuantity($id, $quantity) {
        $this->get($id)->quantity = $quantity;
        return $this;
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

    /**
     * Totals
     */

    public function getQuantity() {
        return $this->sum(function($item) { return $item->quantity; });
    }

    public function getSubTotal($type = 'price', $conditions = false) {
        return $this->sum(function($item) use ($type, $conditions) {
            return $item->getSum($type, $conditions);
        });
    }

    /**
     * Conditions
     */

    public function countItemConditionDifferentFromType($type) {
        $different = [];
        foreach ($this->all() as $item) {
            foreach($item->conditions as $condition) {
                if ($condition->getType() != $type) continue;
                if (!in_array($condition->getName(), $different)) array_push($different, $condition->getName());
            }
        }
        return count($different);
    }

    public function getSumConditionFromType($type) {
        $sum = 0;
        foreach ($this->all() as $item) {
            foreach($item->conditions as $condition) {
                if ($condition->getType() != $type) continue;
                $sum += $item->price * $condition->getValue() / 100;
            }
            $sum *= $item->quantity;
        }
        return $sum;
    }

}
