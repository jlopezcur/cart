<?php namespace Jlopezcur\Cart;

use Illuminate\Support\Collection;

class CartCollection extends Collection {

    public function addItem($id, $params, $events, $instanceName) {
        $item = new CartItem($params);

        if($this->has($id)) {
            $events->fire($instanceName.'.updating', [$item, $this]);
            $this->update($id, $item);
            $events->fire($instanceName.'.updated', [$item, $this]);
        } else {
            $events->fire($instanceName.'.adding', [$item, $this]);
            $this->addRow($id, $item);
            $events->fire($instanceName.'.added', [$item, $this]);
        }
    }

    public function updateItem($id, $data) {
        $item = $this->pull($id);

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
    }

}
