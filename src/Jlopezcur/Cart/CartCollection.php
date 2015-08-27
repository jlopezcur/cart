<?php
namespace Jlopezcur\Cart;

use Illuminate\Support\Collection;
use Jlopezcur\Cart\Helpers\Helpers;
use Event;

class CartCollection extends Collection {

    protected $instance;

    public function __construct($instance = '') {
        $this->instance = $instance;
        parent::__construct([
            'items' => new CartItemCollection($this->instance),
            'conditions' => new CartConditionCollection($this->instance)
        ]);
        Event::fire($this->getInstance() . '.created', [$this]);
    }

    /**
     * Getters
     */

    protected function getInstance() { return 'cart.' . $this->instance; }
    public function getItems() { return $this->get('items'); }
    public function getConditions() { return $this->get('conditions'); }

    /**
     * Cart
     */

    public function getTotal($type = 'price') {
        $subTotal = $this->getItems()->getSubTotal($type);
        $newTotal = 0.00; if ($type == 'points') $newTotal = 0;
        $process = 0;

        // if no conditions were added, just return the sub total
        if(!$this->getConditions()->count()) return $subTotal;
        $this->getConditions()->each(function($cond) use ($subTotal, &$newTotal, &$process) {
            if($cond->getTarget() === 'subtotal') {
                ($process > 0) ? $toBeCalculated = $newTotal : $toBeCalculated = $subTotal;
                $newTotal = $cond->applyCondition($toBeCalculated);
                $process++;
            }
        });

        return $newTotal;
    }

    public function getTotalWithoutConditions($type = 'price') {
        return $this->getItems()->getSubTotalWithoutConditions($type);
    }

    public function clear() {
        $this->getItems()->clear();
        $this->getConditions()->clear();
        return $this;
    }

}
