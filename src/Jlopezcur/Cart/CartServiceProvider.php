<?php
namespace Jlopezcur\Cart;

use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider {

	protected $defer = false;

	public function register() {
		$this->app['cart'] = $this->app->share(function($app) {
			$storage = $app['session'];
			$events = $app['events'];
			$instanceName = 'cart';
			$session_key = '4yTlTeRu3oJOfzD';

			return new Cart(
				$storage,
				$events,
				$instanceName,
				$session_key
			);
		});
	}

	public function provides() {
		return array();
	}
}
