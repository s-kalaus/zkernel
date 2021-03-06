<?php
/**
 * Zkernel
 *
 * Copyright (c) 2010 Magwai Ltd. <info@magwai.ru>, http://magwai.ru
 * Licensed under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */

class Zkernel_Config_Control implements Countable, Iterator, ArrayAccess {
    protected $_data = array();
    protected $_index;
    protected $_count;
    protected $_key;
    protected $_skipNextIteration;

    /**
     * Конструктор
     * Можно передать ассоциативный массив или несколько массивов. Все будет склеено
     */
	public function __construct() {
		$a = func_get_args();
        if ($a) foreach ($a as $e) $this->set($e);
    }

    /**
     * Запись конфига
     *
     * @param string $k Ключ
     * @param array $k Ассоциативный массив
     * @param string $v Значение
     * @param array $v Значение
     * @return Zkernel_Config_Control
     */
    function set($k, $v = null) {
		if (is_array($k) || $k instanceof Zkernel_Config_Control) {
			if ($k) foreach ($k as $_k => $_v) {
				if (isset($this->_data[$_k]) && $this->_data[$_k] instanceof Zkernel_Config_Control && (is_array($_v) || $_v instanceof Zkernel_Config_Control)) $this->_data[$_k]->set($_v);
				else $this->set($_k, $_v);
			}
		}
		else {
			if (is_array($v)) {
				$v = new Zkernel_Config_Control($v);
				$v->_key = $k;
			}
			$this->correct($k, $v);
			if ($v instanceof Zkernel_Config_Control) $v->_key = $k;
			$this->_data[$k] = $v;
			$this->_count = count($this->_data);
		}
		return $this;
	}

	function correct($k, &$v) {
		if ($this->_key === 'field') {
			$t = $v;
			$v = new Zkernel_Config_Control(array(
				'stype' => '',
				'editoptions' => '',
				'required' => false,
				'type' => 'text',
				'align' => '',
				'width' => '',
				'description' => '',
				'sortable' => false,
				'active' => $k != 'lang' && $k != 'parentid' && $k != 'orderid' && $k != 'id' && !preg_match('/^ml\_[^\_]+\_[\d]+$/i', $k),
				'name' => $k,
				'title' => $k == 'title' ? 'Название' : $k,
				'hidden' => false,
				'live' => false,
				'formatter' => '',
				'formatoptions' => '',
				'param' => array(),
				'order' => 1000
			));

			$v->set($t);
		}
		else if (($this->_key === 'button_top' || $this->_key === 'button_bottom')) {
			if (is_string($v)) {
				$action = 'ctl'.$v;
				$default = 0;
				$inner = 0;
				$confirm = (int)($v == 'delete');
				$param = '';
				$field = '';
				$cl = 't';
			}
			else {
				$action = $v->action;
				$default = (int)$v->default;
				$inner = (int)$v->inner;
				$confirm = (int)$v->confirm;
				$param = $v->param;
				$cl = @$v->cl ? $v->cl : 't';
				$controller = $v->controller;
				$field = $v->field;
			}
			if (!@$controller) $controller = Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
			if (is_object($v) && $v->title) $title = $v->title;
			else switch ($action) {
				case 'ctladd':
					$title = '_lang_add';
					break;
				case 'ctledit':
					$title = '_lang_edit';
					break;
				case 'ctldelete':
					$title = '_lang_delete';
					break;
			}
			$v = new Zkernel_Config_Control(array(
				'cl' => $cl,
				'title' => @$title,
				'controller' => $controller,
				'action' => $action,
				'param' => $param,
				'default' => $default,
				'inner' => $inner,
				'confirm' => $confirm,
				'field' => $field
			));
		}
		else if ($k === 'static_field' && $v) {
			$t = $v;
			$v = new Zkernel_Config_Control(array(
				'field_src' => 'title',
				'field_dst' => 'stitle',
				'unique' => true,
				'length' => 20
			));
			if ($t instanceof Zkernel_Config_Control) $v->set($t);
		}
	}

	function get($k) {
		if (isset($this->_data[$k])) {
			$ret = $this->_data[$k];
			if (is_string($ret) && substr($ret, 0, 13) == 'php_function:') {
				$f = create_function('$control', substr($ret, 13));
				$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
				$control = $viewRenderer->view->control();
				$ret = $f($control);
				if ($ret) {
					$this->set($k, $ret);
					if ($this->_data[$k] instanceof Zkernel_Config_Control) $this->_data[$k]->set($ret);
					$ret = $this->_data[$k];
				}
			}
		}
		else $ret = null;
		return $ret;
	}

	public function toArray() {
        $array = array();
        foreach ($this as $key => $value) {
            if ($value instanceof Zkernel_Config_Control) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $this->$key;
            }
        }
        return $array;
    }

	function __get($k) {
		return $this->get($k);
	}

	function __set($k, $v = null) {
		/*if (is_array($v) && is_array($this->_data[$k])) {
			$this->_data[$k] = $this->array_merge($this->_data[$k], $v);
		}
		else $this->_data[$k] = $v;
		$this->_count = count($this->_data);*/
		$this->set(array($k => $v));
	}

	public function __clone() {
      $array = array();
      foreach ($this->_data as $key => $value) {
          if ($value instanceof Zkernel_Config_Control) {
              $array[$key] = clone $value;
          } else {
              $array[$key] = $value;
          }
      }
      $this->_data = $array;
    }

	public function __isset($name) {
        return isset($this->_data[$name]);
    }

    public function __unset($name) {
		unset($this->_data[$name]);
		$this->_count = count($this->_data);
		$this->_skipNextIteration = true;
    }

    public function count() {
        return $this->_count;
    }

    public function current() {
        $this->_skipNextIteration = false;
        return current($this->_data);
    }

    public function key() {
        return key($this->_data);
    }

    public function next() {
        if ($this->_skipNextIteration) {
            $this->_skipNextIteration = false;
            return;
        }
        next($this->_data);
        $this->_index++;
    }

    public function rewind() {
        $this->_skipNextIteration = false;
        reset($this->_data);
        $this->_index = 0;
    }

    public function valid() {
        return $this->_index < $this->_count;
    }

    public function offsetExists($k) {
    	return isset($this->_data[$k]);
    }

	public function offsetGet($k) {
		return $this->get($k);
	}

	public function offsetSet($k, $v) {
		if ($k === null) {
			$k = 0;
			if ($this->_data) foreach ($this->_data as $_k => $_v) if ($k <= $_k) $k = $_k + 1;
		}
		$this->set(array($k => $v));
	}

	public function offsetUnset($k) {
		unset($this->_data[$k]);
		$this->_count = count($this->_data);
		$this->_skipNextIteration = true;
	}

	private function is_assoc($array) {
		if (!is_array($array)) return false;
		foreach (array_keys($array) as $k => $v) {
			if ($k !== $v) return true;
		}
  		return false;
	}

	function array_merge(array &$array1, array &$array2){
		$merged = $array1;
		foreach ( $array2 as $key => &$value )
		{
    		if (is_array($value) && isset ($merged[$key]) && is_array($merged [$key]))
    			$merged[$key] = $this->array_merge($merged[$key], $value);
			else
				$merged[$key] = $value;
		}
		return $merged;
	}
}


