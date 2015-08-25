<?php
namespace Jlopezcur\Cart;

use Illuminate\Support\Collection;

class CartConditionCollection extends Collection {

    protected $session;
    protected $sessionKey;
    protected $events;
    protected $instanceName;

    public function __construct($session, $sessionKey = '', $events = null, $instanceName = '') {
        $this->session = $session;
        $this->sessionKey = $sessionKey;
        $this->events = $events;
        $this->instanceName = $instanceName;

        $items = $this->session->get($this->sessionKey);
        parent::__construct($items);
    }

    public function addCondition() {
        if (is_array($condition)) foreach($condition as $c) $this->condition($c);
        else {
            $this->conditions->put($condition->getName(), $condition);
            $this->save();
        }
        return $this;
    }

    public function getConditionsByType($type) {
        return $this->filter(function(CartCondition $condition) use ($type) {
            return $condition->getType() == $type;
        });
    }

    public function removeConditionsByType($type) {
        $this->getConditionsByType($type)->each(function($condition) {
            $this->removeCartCondition($condition->getName());
        });
    }

    public function removeCondition($conditionName) {
        $this->forget($conditionName);
        $this->save();
    }

    public function clear() {
        $this->events->fire($this->instanceName.'.clearing-condition', [$this]);
        foreach ($this->all() as $condition) $this->forget($condition->id);
        $this->save();
        $this->events->fire($this->instanceName.'.cleared-condition', [$this]);
    }

    public function save() {
        $this->session->put($this->sessionKey, $this);
    }

}
