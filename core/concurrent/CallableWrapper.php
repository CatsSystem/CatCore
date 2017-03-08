<?php
namespace core\concurrent;

class CallableWrapper extends Wrapper {
    public function __invoke() {
        $obj = $this->obj;
        return Promise::all(func_get_args())->then(function($args) use ($obj) {
            return call_user_func_array($obj, $args);
        });
    }
}
