<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/1
 * Time: 16:18
 */

namespace core\concurrent;

use core\concurrent\exception\UnCatchableException;

class Promise
{
    const PENDING = 0;
    const FULFILLED = 1;
    const REJECTED = 2;

    protected $state = Promise::PENDING;
    protected $value;
    protected $reason;
    protected $subscribers = array();

    public function __construct($computation = NULL) {
        if (is_callable($computation)) {
            try {
                $this->resolve(call_user_func($computation));
            }
            catch (UnCatchableException $e) {
                throw $e->getPrevious();
            }
            catch (\Exception $e) {
                $this->reject($e);
            }
            catch (\Error $e) {
                $this->reject($e);
            }
        }
    }

    /**
     * @param $callback
     * @param $next         Promise
     * @param $x
     * @throws \Exception
     */
    private function privateCall($callback, $next, $x) {
        try {
            $r = call_user_func($callback, $x);
            $next->resolve($r);
        }
        catch (UnCatchableException $e) {
            throw $e->getPrevious();
        }
        catch (\Exception $e) {
            $next->reject($e);
        }
        catch (\Error $e) {
            $next->reject($e);
        }
    }

    /**
     * @param $onfulfill
     * @param $next         Promise
     * @param $x
     * @throws \Exception
     */
    private function privateResolve($onfulfill, $next, $x) {
        if (is_callable($onfulfill)) {
            $this->privateCall($onfulfill, $next, $x);
        }
        else {
            $next->resolve($x);
        }
    }

    /**
     * @param $onreject
     * @param $next         Promise
     * @param $e
     * @throws \Exception
     */
    private function privateReject($onreject, $next, $e) {
        if (is_callable($onreject)) {
            $this->privateCall($onreject, $next, $e);
        }
        else {
            $next->reject($e);
        }
    }

    /**
     * @param $value
     * @throws \Exception
     */
    public function resolve($value)
    {
        if ($value === $this) {
            $this->reject(new TypeError('Self resolution'));
            return;
        }
        if ($value instanceof Promise) {
            $value->fill($this);
            return;
        }

        if ( (($value !== NULL) && is_object($value)) || is_string($value)) {
            if (method_exists($value, 'then')) {
                $then = array($value, 'then');
                $notrun = true;
                $self = $this;
                try {
                    call_user_func($then,
                        function($y) use (&$notrun, $self) {
                            if ($notrun) {
                                $notrun = false;
                                $self->resolve($y);
                            }
                        },
                        function($r) use (&$notrun, $self) {
                            if ($notrun) {
                                $notrun = false;
                                $self->reject($r);
                            }
                        }
                    );
                }
                catch (UnCatchableException $e) {
                    throw $e->getPrevious();
                }
                catch (\Exception $e) {
                    if ($notrun) {
                        $notrun = false;
                        $this->reject($e);
                    }
                }
                catch (\Error $e) {
                    if ($notrun) {
                        $notrun = false;
                        $this->reject($e);
                    }
                }
                return;
            }
        }
        if ($this->state === self::PENDING) {
            $this->state = self::FULFILLED;
            $this->value = $value;
            while (count($this->subscribers) > 0) {
                $subscriber = array_shift($this->subscribers);
                $this->privateResolve(
                    $subscriber['onfulfill'],
                    $subscriber['next'],
                    $value);
            }
        }
    }

    public function reject($reason)
    {
        if ($this->state === self::PENDING) {
            $this->state = self::REJECTED;
            $this->reason = $reason;
            while (count($this->subscribers) > 0) {
                $subscriber = array_shift($this->subscribers);
                $this->privateReject(
                    $subscriber['onreject'],
                    $subscriber['next'],
                    $reason);
            }
        }
    }

    protected function fill($future) {
        $this->then(array($future, 'resolve'), array($future, 'reject'));
    }

    public function then($onfulfill, $onreject = NULL) {
        if (!is_callable($onfulfill)) { $onfulfill = NULL; }
        if (!is_callable($onreject)) { $onreject = NULL; }
        $next = new Promise();
        if ($this->state === self::FULFILLED) {
            $this->privateResolve($onfulfill, $next, $this->value);
        }
        elseif ($this->state === self::REJECTED) {
            $this->privateReject($onreject, $next, $this->reason);
        }
        else {
            array_push($this->subscribers, array(
                'onfulfill' => $onfulfill,
                'onreject' => $onreject,
                'next' => $next
            ));
        }
        return $next;
    }

    /*********** Static Function Begin ******************/

    /**
     * @param $obj  mixed
     * @return bool
     */
    public static function isPromise($obj)
    {
        return $obj instanceof Promise;
    }

    public static function value($v) {
        $future = new Promise();
        $future->resolve($v);
        return $future;
    }

    public static function toPromise($obj) {
        if ($obj instanceof Promise) {
            return $obj;
        }
        if ($obj instanceof \Generator) {
            return self::co($obj);
        }
        return self::value($obj);
    }

    public static function all($array) {
        return self::toPromise($array)->then(
            function($array) {
                $keys = array_keys($array);
                $n = count($array);
                $result = array();
                if ($n === 0) {
                    return self::value($result);
                }
                $future = new Promise();
                $resolve = function() use ($future, &$result, $keys) {
                    $array = array();
                    foreach($keys as $key) {
                        $array[$key] = $result[$key];
                    }
                    $future->resolve($array);
                };
                $reject = array($future, "reject");
                foreach ($array as $index => $element) {
                    self::toPromise($element)->then(
                        function($value) use ($index, &$n, &$result, $resolve) {
                            $result[$index] = $value;
                            if (--$n === 0) {
                                $resolve();
                            }
                        },
                        $reject
                    );
                }
                return $future;
            }
        );
    }

    public static function any($array) {
        return self::toPromise($array)->then(
            function($array) {
                $keys = array_keys($array);
                $n = count($array);
                if ($n === 0) {
                    throw new \Exception('any(): $array must not be empty');
                }
                $reasons = array();
                $future = new Promise();
                $resolve = array($future, "resolve");
                $reject = function() use ($future, &$reasons, $keys) {
                    $array = array();
                    foreach($keys as $key) {
                        $array[$key] = $reasons[$key];
                    }
                    $future->reject($array);
                };
                foreach ($array as $index => $element) {
                    self::toPromise($element)->then(
                        $resolve,
                        function($reason) use ($index, &$reasons, &$n, $reject) {
                            $reasons[$index] = $reason;
                            if (--$n === 0) {
                                $reject();
                            }
                        }
                    );
                }
                return $future;
            }
        );
    }

    public static function wrap($handler) {
        if (is_callable($handler)) {
            if (is_array($handler)) {
                $m = new \ReflectionMethod($handler[0], $handler[1]);
            }
            else {
                $m = new \ReflectionFunction($handler);
            }
            if ($m->isGenerator()) {
                return function() use ($handler) {
                    return self::all(func_get_args())->then(
                        function($args) use ($handler) {
                            return self::co(call_user_func_array($handler, $args));
                        }
                    );
                };
            }
        }
        if (is_object($handler)) {
            if (is_callable($handler)) {
                return new CallableWrapper($handler);
            }
            return new Wrapper($handler);
        }
        return $handler;
    }

    public static function co($generator/*, arg1, arg2...*/) {
        if (is_callable($generator)) {
            $args = array_slice(func_get_args(), 1);
            $generator = call_user_func_array($generator, $args);
        }
        if (!($generator instanceof \Generator)) {
            return self::toPromise($generator);
        }
        $future = new Promise();
        $onfulfilled = function($value) use (&$onfulfilled, &$onrejected, $generator, $future) {
            try {
                $next = $generator->send($value);
                if ($generator->valid()) {
                    self::toPromise($next)->then($onfulfilled, $onrejected);
                }
                else {
                    if (method_exists($generator, "getReturn")) {
                        $ret = $generator->getReturn();
                        $future->resolve($ret);
                    }
                    else {
                        $future->resolve($value);
                    }
                }
            }
            catch(\Exception $e) {
                $future->reject($e);
            }
            catch(\Error $e) {
                $future->reject($e);
            }
        };
        $onrejected = function($err) use (&$onfulfilled, $generator, $future) {
            try {
                $onfulfilled($generator->throw($err));
            }
            catch(\Exception $e) {
                $future->reject($e);
            }
            catch(\Error $e) {
                $future->reject($e);
            }
        };
        self::toPromise($generator->current())->then($onfulfilled, $onrejected);
        return $future;
    }

    /*********** Static Function End ******************/
}