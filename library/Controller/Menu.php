<?php

/**
 * @zk_title   		Панель: меню
 * @zk_routable		0
 */
class Zkernel_Controller_Menu extends Zkernel_Controller_Action {
	function ctlinit() {
		$tt = array('' => '[ не указан ]');
    	$dir = $this->getFrontController()->getControllerDirectory();
    	$dir = @$dir['default'];
    	$handle = @opendir($dir);@readdir($handle);@readdir($handle);

    	while ($path = @readdir($handle)) {
			if (is_file($dir.'/'.$path)) {
				$n = $nn = strtolower(str_ireplace('Controller.php', '', $path));
				$c = ucfirst($n).'Controller';
				if (!class_exists($c)) include $dir.'/'.$path;
				$db = $this->_helper->util()->getDocblock($c);
				if (isset($db['zk_title'])) $nn = $db['zk_title'];
				$inner = array();
				$r = new Zend_Reflection_Class($c);
				$met = $r->getMethods();
				if (@$met) {
					$exist = false;
					foreach ($met as $el) {
						if ($el->name == '_getRoutes') {
							$rq = new Zend_Controller_Request_Http();
							$rq->setControllerName($n);
							$ci = new $c(
								$rq,
								new Zend_Controller_Response_Http()
							);
							$ci->init();
							$rs = $ci->_getRoutes();
							if ($rs) foreach ($rs as $k_1 => $el_1) {
								$inner[$n.'|index|'.$k_1] = $el_1;
							}
							break;
						}
					}
		    		if (!$exist) foreach ($met as $el) {
		    			$db1 = $this->_helper->util()->getDocblock($el, 'method');
		    			if (substr($el->name, -6) == 'Action') {
		    				$db1 = $this->_helper->util()->getDocblock($el, 'method');
		    				if ($db1 && array_key_exists('zk_routable', $db1) && !$db1['zk_routable']) continue;
		    				if (!isset($db1['zk_title'])) continue;
		    				$inner[$n.'|'.substr($el->name, 0, -6)] = $db1['zk_title'];
		    			}
		    		}
		    	}
				if ($db && array_key_exists('zk_routable', $db) && !$db['zk_routable']) {
					if ($inner) $tt[$nn] = $inner;
				}
				else {
					$tt[$n] = $nn;
					if ($inner) {
						foreach ($inner as $k => $v) $inner[$k] = '---'.$v;
						$tt = array_merge($tt, $inner);
					}
				}
			}
		}
		@closedir($handle);
		$this->_helper->control()->config->set(array(
			'tree' => 1,
			'field' => array(
				'title' => array(
					'title' => 'Название',
					'required' => true,
					'order' => 1
				),
				'key' => array(
					'title' => 'Ключ',
					'order' => 2,
					'active' => $this->_helper->user()->acl->isAllowed(
						$this->_helper->user()->role,
						$this->_helper->util()->getById(array(
							'model' => new Default_Model_Cresource(),
							'field' => 'id',
							'key' => 'key',
							'id' => 'admin'
						))
					)
				),
				'cap' => array(
					'title' => 'Раздел',
					'description' => 'Выберите либо раздел, либо введите URL в следующем поле. Если вы выберите раздел и введете URL одновременно, то будет использован URL',
					'type' => 'select',
					'param' => array(
						'multioptions' => $tt,
						'value' =>
'php_function:
$item = $control->config->model->fetchRow(array("`id` = ?" => (int)$control->getRequest()->getParam("id")));
return	$item->controller.
		($item->action ? "|".$item->action : "").
		($item->param ? "|".$item->param : "").
		($item->route ? "|".$item->route : "");'
					),
					'order' => 3
				),
				'url' => array(
					'title' => 'URL',
					'order' => 4
				),
				'controller' => array(
					'active' => false
				),
				'action' => array(
					'active' => false
				),
				'param' => array(
					'active' => false
				),
				'route' => array(
					'active' => false
				),
				'orderid' => array(
					'active' => false
				),
				'parentid' => array(
					'active' => false
				)
			),
			'func_override' =>
'php_function:
if ($control->config->data["cap"] && !$control->config->data["url"]) {
	$p = explode("|", $control->config->data["cap"]);
	$control->config->data["controller"] = @$p[0];
	$control->config->data["action"] = @$p[1];
	$control->config->data["param"] = @$p[2];
	$control->config->data["route"] = @$p[3];
}
else {
	$control->config->data["controller"] = "";
	$control->config->data["action"] = "";
	$control->config->data["param"] = "";
	$control->config->data["route"] = "";
	//if (!$control->config->data["url"]) $control->config->info[] = "Заполните поле \"Раздел\" или \"URL\"";
}
'
		));
	}

	public function ctlshowAction() {
    	$this->_helper->control()->config->set(array(
			'field' => array(
				'key' => array(
					'active' => false
				),
				'cap' => array(
					'active' => false
				),
				'url' => array(
					'active' => false
				)
			)
		));

    	$this->_helper->control()->routeDefault();
    }
}