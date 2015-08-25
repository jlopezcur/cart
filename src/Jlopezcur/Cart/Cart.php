<?php
namespace Jlopezcur\Cart;

use Exception;
use Jlopezcur\Cart\Helpers\Helpers;

class Cart {

    protected $session; // Item Storage
    protected $events; // The event dispatcher
    protected $instanceName; // The cart session key
    protected $sessionKeyCartItems; // the session key use to persist cart items
    protected $sessionKeyCartConditions; // the session key use to persist cart conditions

    /**
     * our object constructor
     *
     * @param $session
     * @param $events
     * @param $instanceName
     * @param $session_key
     */
    public function __construct($session, $events, $instanceName, $session_key) {
        $this->events = $events;
        $this->session = $session;
        $this->instanceName = $instanceName;
        $this->sessionKeyCartItems = $session_key.'_cart_items';
        $this->sessionKeyCartConditions = $session_key.'_cart_conditions';
        $this->events->fire($this->getInstanceName().'.created', array($this));
    }

    /**
     * Getters
     */

    public function getInstanceName() { return $this->instanceName; }
    public function get($itemId) { return $this->getContent()->get($itemId); }

    /**
     * Tools
     */

    public function has($itemId) { return $this->getContent()->has($itemId); }

    /**
     * add item to the cart, it can be an array or multi dimensional array
     *
     * @param array $params
     * @return $this
     * @throws InvalidItemException
     */
    public function add($params = []) {
        // the first argument is an array, now we will need to check if it is a multi dimensional
        // array, if so, we will iterate through each item and call add again
        if (Helpers::isMultiArray($params)) foreach($params as $item) $this->add($item);
        else {
            $cart = $this->getContent();
            $cart->addItem($params['id'], $params, $this->events, $this->getInstanceName());
            $this->save($cart);
        }
        return $this;
    }

    /**
     * update a cart
     *
     * @param $id
     * @param $data
     *
     * the $data will be an associative array, you don't need to pass all the data, only the key value
     * of the item you want to update on it
     */
    public function update($id, $data) {
        $cart = $this->getContent();
        $cart->updateItem($id, $data);
        $this->save($cart);
    }

    /**
     * add condition on an existing item on the cart
     *
     * @param int|string $productId
     * @param CartCondition $itemCondition
     * @return $this
     */
    public function addItemCondition($productId, $itemCondition) {
        if ($product = $this->get($productId)) {
            $conditionInstance = "\\Jlopezcur\\Cart\\CartCondition";
            if ($itemCondition instanceof $conditionInstance) {
                // we need to copy first to a temporary variable to hold the conditions
                // to avoid hitting this error "Indirect modification of overloaded element of Jlopezcur\Cart\ItemCollection has no effect"
                // this is due to laravel Collection instance that implements Array Access
                // // see link for more info: http://stackoverflow.com/questions/20053269/indirect-modification-of-overloaded-element-of-splfixedarray-has-no-effect
                $itemConditionTempHolder = $product['conditions'];

                if (is_array($itemConditionTempHolder)) {
                    array_push($itemConditionTempHolder, $itemCondition);
                } else {
                    $itemConditionTempHolder = $itemCondition;
                }

                $this->update($productId, array(
                    'conditions' => $itemConditionTempHolder // the newly updated conditions
                ));
            }
        }

        return $this;
    }

    /**
     * removes an item on cart by item ID
     *
     * @param $id
     */
    public function remove($id) {
        $cart = $this->getContent();
        $this->events->fire($this->getInstanceName().'.removing', array($id, $this));
        $cart->forget($id);
        $this->save($cart);
        $this->events->fire($this->getInstanceName().'.removed', array($id, $this));
    }

    /**
     * clear cart
     */
    public function clear() {
        $this->events->fire($this->getInstanceName().'.clearing', array($this));
        $this->session->put($this->sessionKeyCartItems, array());
        $this->events->fire($this->getInstanceName().'.cleared', array($this));
    }

    /**
     * add a condition on the cart
     *
     * @param CartCondition|array $condition
     * @return $this
     * @throws InvalidConditionException
     */
    public function condition($condition) {
        if (is_array($condition)) {
            foreach($condition as $c) {
                $this->condition($c);
            }
            return $this;
        }

        if(!$condition instanceof CartCondition) throw new InvalidConditionException('Argument 1 must be an instance of \'Jlopezcur\Cart\CartCondition\'');
        $conditions = $this->getConditions();
        $conditions->put($condition->getName(), $condition);
        $this->saveConditions($conditions);
        return $this;
    }

    /**
     * get conditions applied on the cart
     *
     * @return CartConditionCollection
     */
    public function getConditions() {
        return new CartConditionCollection($this->session->get($this->sessionKeyCartConditions));
    }

    /**
     * get condition applied on the cart by its name
     *
     * @param $conditionName
     * @return CartCondition
     */
    public function getCondition($conditionName) {
        return $this->getConditions()->get($conditionName);
    }

    /**
    * Get all the condition filtered by Type
    * Please Note that this will only return condition added on cart bases, not those conditions added
    * specifically on an per item bases
    *
    * @param $type
    * @return CartConditionCollection
    */
    public function getConditionsByType($type) {
        return $this->getConditions()->filter(function(CartCondition $condition) use ($type) {
            return $condition->getType() == $type;
        });
    }


    /**
     * Remove all the condition with the $type specified
     * Please Note that this will only remove condition added on cart bases, not those conditions added
     * specifically on an per item bases
     *
     * @param $type
     * @return $this
     */
    public function removeConditionsByType($type) {
        $this->getConditionsByType($type)->each(function($condition) {
            $this->removeCartCondition($condition->getName());
        });
    }

    /**
     * removes a condition on a cart by condition name,
     * this can only remove conditions that are added on cart bases not conditions that are added on an item/product.
     * If you wish to remove a condition that has been added for a specific item/product, you may
     * use the removeItemCondition(itemId, conditionName) method instead.
     *
     * @param $conditionName
     * @return void
     */
    public function removeCartCondition($conditionName) {
        $conditions = $this->getConditions();
        $conditions->pull($conditionName);
        $this->saveConditions($conditions);
    }

    /**
     * remove a condition that has been applied on an item that is already on the cart
     *
     * @param $itemId
     * @param $conditionName
     * @return bool
     */
    public function removeItemCondition($itemId, $conditionName) {
        if(!$item = $this->getContent()->get($itemId)) return false;

        if ($item->hasConditions()) {
            // NOTE:
            // we do it this way, we get first conditions and store
            // it in a temp variable $originalConditions, then we will modify the array there
            // and after modification we will store it again on $item['conditions']
            // This is because of ArrayAccess implementation
            // see link for more info: http://stackoverflow.com/questions/20053269/indirect-modification-of-overloaded-element-of-splfixedarray-has-no-effect

            $tempConditionsHolder = $item['conditions'];

            // if the item's conditions is in array format
            // we will iterate through all of it and check if the name matches
            // to the given name the user wants to remove, if so, remove it
            if(is_array($tempConditionsHolder)) {
                foreach($tempConditionsHolder as $k => $condition) {
                    if($condition->getName() == $conditionName) {
                        unset($tempConditionsHolder[$k]);
                    }
                }
                $item['conditions'] = $tempConditionsHolder;
            }

            // if the item condition is not an array, we will check if it is
            // an instance of a Condition, if so, we will check if the name matches
            // on the given condition name the user wants to remove, if so,
            // lets just make $item['conditions'] an empty array as there's just 1 condition on it anyway
            else {
                $conditionInstance = "Jlopezcur\\Cart\\CartCondition";
                if ($item['conditions'] instanceof $conditionInstance) {
                    if ($tempConditionsHolder->getName() == $conditionName) {
                        $item['conditions'] = array();
                    }
                }
            }
        }

        $this->update($itemId, array(
            'conditions' => $item['conditions']
        ));

        return true;
    }

    /**
     * clears all conditions on a cart,
     * this does not remove conditions that has been added specifically to an item/product.
     * If you wish to remove a specific condition to a product, you may use the method: removeItemCondition($itemId, $conditionName)
     *
     * @return void
     */
    public function clearCartConditions() {
        $this->session->put($this->sessionKeyCartConditions, []);
    }

    /**
     * get cart sub total
     *
     * @return float
     */
    public function getSubTotal($type = 'price') {
        $cart = $this->getContent();

        $sum = $cart->sum(function($item) use ($type) {
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

    /**
     * the new total in which conditions are already applied
     *
     * @param string $type 'price' or 'points' (default: 'price')
     * @return float|int
     */
    public function getTotal($type = 'price') {
        $subTotal = $this->getSubTotal($type);
        $newTotal = 0.00; if ($type == 'points') $newTotal = 0;
        $process = 0;
        $conditions = $this->getConditions();

        // if no conditions were added, just return the sub total
        if(!$conditions->count()) return $subTotal;
        $conditions->each(function($cond) use ($subTotal, &$newTotal, &$process) {
            if($cond->getTarget() === 'subtotal') {
                ($process > 0) ? $toBeCalculated = $newTotal : $toBeCalculated = $subTotal;
                $newTotal = $cond->applyCondition($toBeCalculated);
                $process++;
            }
        });

        return $newTotal;
    }

    /**
     * get total quantity of items in the cart
     *
     * @return int
     */
    public function getTotalQuantity() {
        $items = $this->getContent();
        if ($items->isEmpty()) return 0;
        $count = $items->sum(function($item) {
            return $item['quantity'];
        });
        return $count;
    }

    /**
     * get the cart
     *
     * @return CartCollection
     */
    public function getContent() {
        return (new CartCollection($this->session->get($this->sessionKeyCartItems)));
    }

    /**
     * check if cart is empty
     *
     * @return bool
     */
    public function isEmpty() {
        $cart = new CartCollection($this->session->get($this->sessionKeyCartItems));
        return $cart->isEmpty();
    }

    /**
     * add row to cart collection
     *
     * @param $id
     * @param $item
     */
    protected function addRow($id, $item) {
        $cart = $this->getContent();
        $cart->put($id, $item);
        $this->save($cart);
    }

    /**
     * save the cart
     *
     * @param $cart CartCollection
     */
    protected function save($cart) {
        $this->session->put($this->sessionKeyCartItems, $cart);
    }

    /**
     * save the cart conditions
     *
     * @param $conditions
     */
    protected function saveConditions($conditions) {
        $this->session->put($this->sessionKeyCartConditions, $conditions);
    }

}
