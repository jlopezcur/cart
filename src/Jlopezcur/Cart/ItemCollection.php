<?php namespace Jlopezcur\Cart;

/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/17/2015
 * Time: 11:03 AM
 */

use Illuminate\Support\Collection;

class ItemCollection extends Collection {

    /**
     * get the sum of price
     *
     * @return mixed|null
     */
    public function getPriceSum($type = 'price')
    {
        if ($type == 'price') return $this->price * $this->quantity;
        return $this->points * $this->quantity;
    }

    public function __get($name)
    {
        if( $this->has($name) ) return $this->get($name);
        return null;
    }
}
