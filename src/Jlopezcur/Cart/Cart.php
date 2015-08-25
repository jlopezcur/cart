<?php
namespace Jlopezcur\Cart;

use Exception;
use Jlopezcur\Cart\Helpers\Helpers;

class Cart {

    protected $session;
    protected $session_key;

    protected $cart;

    public function __construct($session) {
        $this->session = $session;
    }

    public function getCart($instance = 'main') { $this->cart = ($this->session->has($instance)) ? $this->session->get($instance) : new CartCollection($instance); return $this->cart; }
    public function saveCart($instance = 'main') { $this->session->put($instance, $this->cart); }

}
