<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-13
 * Time: 01:41
 */

namespace Akari\system\util;

use Countable;
use ArrayAccess;
use Traversable;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable {

    protected $items = [];

    public function __construct($items = []) {
        $this->items = $this->getArrayableItems($items);
    }

    public static function make($items = []) {
        return new static($items);
    }

    public function all() {
        return $this->items;
    }

    public function times($number, callable $callback = null) {
        if ($number < 1) {
            return new static();
        }

        if (is_null($callback)) {
            return new static(range(1, $number));
        }

        return (new static(range(1, $number)))->map($callback);
    }

    public function diff($items) {
        return new static(array_diff($this->items, $this->getArrayableItems($items)));
    }

    public function isEmpty() {
        return empty($this->items);
    }

    public function isNotEmpty() {
        return ! $this->isEmpty();
    }

    public function last() {
        return ArrayUtil::last($this->items);
    }

    public function first() {
        return ArrayUtil::first($this->items);
    }

    public function flatten(string $columnKey, ?string $indexKey, $allowRepeat = FALSE) {
        return new static(ArrayUtil::flatten($this->items, $columnKey, $indexKey, $allowRepeat));
    }

    public function indexByKey(string $indexKey) {
        return new static(ArrayUtil::index($this->items, $indexKey));
    }

    public function sortByKeys(array $keys) {
        return new static(ArrayUtil::reindex($this->items, $keys));
    }

    public function map(callable $callback) {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $items));
    }

    public function filter(callable $callback) {
        $result = [];
        foreach ($this->items as $key => $value) {
            if (!call_user_func_array($callback, [$key, $value])) {
                $result[$key] = $value;
            }
        }

        return new static($result);
    }

    public function values() {
        return new static(array_values($this->items));
    }

    public function keys() {
        return new static(array_keys($this->items));
    }

    protected function getArrayableItems($items) {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->all();
        } elseif ($items instanceof JsonSerializable) {
            return $items->jsonSerialize();
        } elseif ($items instanceof Traversable) {
            return iterator_to_array($items);
        }
        return (array) $items;
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator() {
        return new ArrayIterator($this->items);
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset) {
        return array_key_exists($offset, $this->items);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset) {
        return $this->items[$offset];
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset) {
        unset($this->items[$offset]);
    }

    public function count() {
        return count($this->items);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize() {
        return json_encode($this->items);
    }

}
