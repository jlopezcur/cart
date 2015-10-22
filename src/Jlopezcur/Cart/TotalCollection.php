<?php
namespace Jlopezcur\Cart;

use Illuminate\Support\Collection;
use Jlopezcur\Cart\Helpers\Helpers;
use Event;

class TotalCollection extends Collection {

    protected $instance;

    public function __construct($instance = '') {
        $this->instance = $instance;
        parent::__construct();
    }

    public function addTotal($params = []) {
        if (Helpers::isMultiArray($params)) foreach($params as $total) $this->addTotal($total);

        $total = new Total($params);
        $id = $params['id'];

        if($this->has($id)) $this->updateTotal($id, $total);
        else {
            Event::fire($this->instance.'.adding', [$total, $this]);
            $this->put($id, $total);
            Event::fire($this->instance.'.added', [$total, $this]);
        }
    }

    public function updateQuantity($id, $quantity) {
        $this->get($id)->quantity = $quantity;
        return $this;
    }

    public function updateTotal($id, $data) {
        $total = $this->pull($id);
        Event::fire($this->instance.'.updating', [$total, $this]);
        foreach($data as $key => $value) {
            // if the key is currently "quantity" we will need to check if an arithmetic
            // symbol is present so we can decide if the update of quantity is being added
            // or being reduced.
            if ($key == 'quantity') {
                if(preg_match('/\-/', $value) == 1) {
                    $value = (int) str_replace('-','',$value);

                    // we will not allowed to reduced quantity to 0, so if the given value
                    // would result to item quantity of 0, we will not do it.
                    if(($total[$key] - $value) > 0) {
                        $total[$key] -= $value;
                    }
                } elseif( preg_match('/\+/', $value) == 1 ) {
                    $total[$key] += (int) str_replace('+','',$value);
                } else {
                    $total[$key] += (int) $value;
                }
            } else {
                $total[$key] = $value;
            }
        }
        $this->put($id, $total);
        Event::fire($this->instance.'.updated', [$total, $this]);
    }

    public function remove($id) {
        Event::fire($this->instance.'.removing', [$id, $this]);
        $this->forget($id);
        Event::fire($this->instance.'.removed', [$id, $this]);
    }

    public function clear() {
        Event::fire($this->instance.'.clearing', [$this]);
        foreach ($this->all() as $total) $this->forget($total->id);
        Event::fire($this->instance.'.cleared', [$this]);
    }

    /**
     * Totals
     */

    public function getQuantity() {
        return $this->sum(function($total) { return $total->quantity; });
    }

    public function getSubTotal($type = 'price', $conditions = false) {
        return $this->sum(function($total) use ($type, $conditions) {
            return $total->getSum($type, $conditions);
        });
    }

    /**
     * Conditions
     */

    public function countTotalConditionDifferentFromType($type) {
        $different = [];
        foreach ($this->all() as $total) {
            foreach($total->conditions as $condition) {
                if ($condition->getType() != $type) continue;
                if (!in_array($condition->getName(), $different)) array_push($different, $condition->getName());
            }
        }
        return count($different);
    }

    public function getSumConditionFromType($type) {
        $sum = 0;
        foreach ($this->all() as $total) {
            foreach($total->conditions as $condition) {
                if ($condition->getType() != $type) continue;
                $sum += $total->price * $condition->getValue() / 100;
            }
            $sum *= $total->quantity;
        }
        return $sum;
    }

}
