<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/7/14
 * Time: 16:12
 * Desc: -lkk 对象类
 */

namespace Lkk;

use Closure;
use Exception;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;


class LkkObject implements JsonSerializable {

    /**
     * @var ReflectionClass
     */
    private $__class;


    /**
     * Object constructor.
     *
     * @param array $vars
     */
    public function __construct($vars = []) {
        if(!is_array($vars)) {
            $vars = (array)$vars;
        }

        $this->__class = new ReflectionClass($this);
        foreach ($vars as $field => $value) {
            $this->set($field, $value);
        }
    }


    /**
     * Get value with getter
     *
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function get($name) {
        $methodName = 'get' . ucfirst($name);
        if ($this->__class->hasMethod($methodName) && ($method = $this->__class->getMethod($methodName)) && $method->isPublic() && !$method->isStatic()) {
            return $method->invoke($this);
        }
        if ($this->__class->hasProperty($name) && ($property = $this->__class->getProperty($name)) && $property->isPublic() && !$property->isStatic()) {
            return $property->getValue($this);
        }
        return $this->__getUndefined($name);
    }


    /**
     * Get value with getter via magic method
     *
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name) {
        return $this->get($name);
    }


    /**
     * Set value with setter
     *
     * @param string $name
     * @param mixed $value
     * @throws Exception
     */
    public function set($name, $value=null) {
        $methodName = 'set' . ucfirst($name);
        if ($this->__class->hasMethod($methodName) && ($method = $this->__class->getMethod($methodName)) && $method->isPublic() && !$method->isStatic()) {
            $method->invoke($this, $value);
            return;
        }
        if ($this->__class->hasProperty($name) && ($property = $this->__class->getProperty($name)) && $property->isPublic() && !$property->isStatic()) {
            $property->setValue($this, $value);
            return;
        }
        $this->__setUndefined($name, $value);
    }


    /**
     * Set value with setter via magic method
     *
     * @param string $name
     * @param mixed $value
     * @throws Exception
     */
    public function __set($name, $value) {
        $this->set($name, $value);
    }


    /**
     * Set undefined property
     *
     * @param string $name
     * @param mixed $value
     * @throws Exception
     */
    public function __setUndefined($name, $value) {
        throw new Exception('Undefined writable property ' . $this->__class->name . '::' . $name);
    }


    /**
     * Get undefined property
     *
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __getUndefined($name) {
        throw new Exception('Undefined readable property: ' . $this->__class->name . '::' . $name);
    }


    public function __isset($name){
        return isset($this->$name);
    }


    public function __unset($name){
        unset($this->$name);
    }

    public function __toString(){
        return get_class($this);
    }


    /**
     * Get json fields
     *
     * @return array[]
     */
    protected function jsonFields() {
        return [];
    }


    /**
     * @inheritdoc
     */
    public function jsonSerialize() {

        $fields = $this->jsonFields();
        if (count($fields) === 0) {
            $json = array_map(function (ReflectionProperty $field) {
                return $field->getValue($this);
            }, array_filter($this->__class->getProperties(ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC), function (ReflectionProperty $field) {
                return !$field->isStatic();
            }));
        } else {
            $json = array_map(function ($field) {
                if ($field instanceof Closure) {
                    return $field();
                }
                return $this->{$field};
            }, $fields);
        }
        return $json;
    }


    /**
     * 获取类的短名(不包含命名空间)
     * @return mixed
     */
    public function getClassShortName() {
        $fullname = get_class($this);
        $arr = explode('\\', $fullname);
        return end($arr);
    }


}