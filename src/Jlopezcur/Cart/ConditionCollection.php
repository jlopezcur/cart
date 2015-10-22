<?php
namespace Jlopezcur\Cart;

use Illuminate\Support\Collection;
use Event;

class ConditionCollection extends Collection {

    protected $instance;

    public function __construct($instance = 'main') {
        $this->instance = $instance;
        parent::__construct();
    }

    public function addCondition($condition) {
        Event::fire($this->instance.'.adding-condition', [$this]);
        if (is_array($condition)) foreach($condition as $c) $this->addCondition($c);
        else $this->put($condition->getName(), $condition);
        Event::fire($this->instance.'.added-condition', [$this]);
        return $this;
    }

    public function getConditionsByType($type) {
        return $this->filter(function(Condition $condition) use ($type) {
            return $condition->getType() == $type;
        });
    }

    public function removeConditionsByType($type) {
        Event::fire($this->instance.'.removing-by-type-condition', [$type, $this]);
        $this->getConditionsByType($type)->each(function($condition) {
            $this->removeCartCondition($condition->getName());
        });
        Event::fire($this->instance.'.removed-by-type-condition', [$type, $this]);
    }

    public function removeCondition($condition_name) {
        Event::fire($this->instance.'.removing-condition', [$condition_name, $this]);
        $this->forget($condition_name);
        Event::fire($this->instance.'.removed-condition', [$condition_name, $this]);
    }

    public function clear() {
        Event::fire($this->instance.'.clearing-condition', [$this]);
        foreach ($this->all() as $condition) $this->forget($condition->id);
        Event::fire($this->instance.'.cleared-condition', [$this]);
    }

}
