# Laravel 5 Shopping Cart

A Shopping Cart Implementation for Laravel Framework (Based on the impressive work of darryldecode [https://github.com/darryldecode])

##INSTALLATION

Install the package through [Composer](http://getcomposer.org/). Edit your project's `composer.json` file by adding:

### Laravel 5

```php
"require": {
	"laravel/framework": "5.0.*",
	"jlopezcur/cart": "dev-master"
}
```

Next, run the Composer update command from the Terminal:

    composer update

    or

    composer update "jlopezcur/cart"

##CONFIGURATION

1. Open config/app.php and addd this line to your Service Providers Array
  ```php
  Jlopezcur\Cart\CartServiceProvider::class
  ```

2. Open config/app.php and addd this line to your Aliases

```php
  'Cart' => Jlopezcur\Cart\Facades\CartFacade::class
  ```

## HOW TO USE
* [Usage](#usage)
* [Conditions](#conditions)
* [Instances](#instances)
* [Exceptions](#exceptions)
* [Events](#events)
* [Examples](#examples)
* [Changelogs](#changelogs)
* [License](#license)

## Usage

TODO

## Conditions

TODO

## Instances

TODO

## Exceptions

TODO

## Events

TODO

## Examples

TODO

## Changelogs

TODO

## License

The Laravel Shopping Cart is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

### Disclaimer

THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR, OR ANY OF THE CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
