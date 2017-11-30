<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/11/30
 * Time: 17:44
 * Desc:
 */

namespace Lkk\Concurrent;

class CallableWrapper extends Wrapper {

    public function __invoke() {
        $obj = $this->obj;
        return Promise::all(func_get_args())->then(function($args) use ($obj) {
            return call_user_func_array($obj, $args);
        });
    }

}