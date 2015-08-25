<?php
namespace Jlopezcur\Cart;

use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider {

	public function register() {
		$this->app['cart'] = $this->app->share(function($app) {
			$session = $app['session'];
			return new Cart($session);
		});
	}

}
