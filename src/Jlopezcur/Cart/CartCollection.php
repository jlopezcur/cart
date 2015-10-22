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
            'items' => new ItemCollection($this->instance),
            'conditions' => new ConditionCollection($this->instance),
            'totals' => new TotalCollection($this->instance),
        ]);
        Event::fire($this->getInstance() . '.created', [$this]);
    }

    /**
     * Getters
     */

    protected function getInstance() { return 'cart.' . $this->instance; }
    public function getItems() { return $this->get('items'); }
    public function getConditions() { return $this->get('conditions'); }
    public function getTotals() { return $this->get('totals'); }

    /**
     * Cart
     */

    public function getTotal($type = 'price', $conditions = false, $totals = false) {
        $subTotal = $this->getItems()->getSubTotal($type, $conditions);
        if ($totals) $subTotal += $this->getTotals()->getSubTotal($type, $conditions);

        if(!$this->getConditions()->isEmpty()) {
            $this->getConditions()->each(function($cond) use ($subTotal) {
                $subTotal = $cond->applyCondition($subTotal);
            });
        }

        return $subTotal;
    }

    public function clear() {
        $this->getItems()->clear();
        $this->getConditions()->clear();
        return $this;
    }

}
