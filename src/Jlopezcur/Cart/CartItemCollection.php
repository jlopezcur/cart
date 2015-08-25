<?php namespace Jlopezcur\Cart;

use Illuminate\Support\Collection;
use Jlopezcur\Cart\Helpers\Helpers;
use Session;
use Event;

class CartItemCollection extends Collection {

    protected $session;
    protected $sessionKey;
    protected $events;
    protected $instanceName;

    public function __construct($session, $sessionKey = '', $events = null, $instanceName = '') {
        $this->session = $session;
        $this->sessionKey = $sessionKey;
        $this->events = $events;
        $this->instanceName = $instanceName;

        parent::__construct($this->session->get($this->sessionKey));
        Event::listen('session.started', function($user) {
            $items = Session::get($this->sessionKey);
            dd($items);
            if ($items != null) {
                foreach ($items as $item) {
                    $this->put($item->id, $item);
                }
            }
        });
    }

    public function addItem($params = []) {
        $this->load();
        if (Helpers::isMultiArray($params)) foreach($params as $item) $this->addItem($item);

        $item = new CartItem($params);
        $id = $params['id'];

        if($this->has($id)) $this->updateItem($id, $item);
        else {
            $this->events->fire($this->instanceName.'.adding', [$item, $this]);
            $this->put($id, $item);
            $this->save();
            $this->events->fire($this->instanceName.'.added', [$item, $this]);
        }
    }

    public function updateItem($id, $data) {
        $this->load();
        $item = $this->pull($id);

        $this->events->fire($this->instanceName.'.updating', [$item, $this]);
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

        $this->save();
        $this->events->fire($this->instanceName.'.updated', [$item, $this]);
    }

    public function remove($id) {
        $this->load();
        $this->events->fire($this->instanceName.'.removing', [$id, $this]);
        $this->forget($id);
        $this->save();
        $this->events->fire($this->instanceName.'.removed', [$id, $this]);
    }

    public function clear() {
        $this->load();
        $this->events->fire($this->instanceName.'.clearing', [$this]);
        foreach ($this->all() as $item) $this->forget($item->id);
        $this->save();
        $this->events->fire($this->instanceName.'.cleared', [$this]);
    }

    public function getTotalQuantity() {
        $this->load();
        if ($this->isEmpty()) return 0;
        $count = $this->sum(function($item) { return $item->quantity; });
        return $count;
    }

    public function getSubTotal($type = 'price') {
        $this->load();
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

    public function load() {

    }

    public function save() {
        $this->session->put($this->sessionKey, $this);
    }
}
