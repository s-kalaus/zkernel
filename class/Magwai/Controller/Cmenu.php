<?php

class Magwai_Controller_Cmenu extends Magwai_Controller_Action {
	function ctlinit() {
		$m = new Site_Model_Cresource();
		$ttt = array('0' => '[ без ограничений ]');
		$tt = $m->fetchPairs();
		if ($tt) $ttt += $tt;

		$this->_helper->control()->config->set(array(
			'tree' => true,
			'field' => array(
				'title' => array(
					'title' => 'Название',
					'required' => true
				),
				'orderid' => array(
					'active' => false
				),
				'parentid' => array(
					'active' => false
				),
				'resource' => array(
					'title' => 'Ресурс',
					'type' => 'select',
					'param' => array(
						'multioptions' => $ttt
					)
				),
				'controller' => array(
					'title' => 'Контроллер',
					'unique' => true
				),
				'action' => array(
					'title' => 'Действие'
				),
				'param' => array(
					'title' => 'Параметры'
				),
			),
			'orderby' => 'orderid',
			'func_success' => 'php_function:Zend_Controller_Action_HelperBroker::getStaticHelper("js")->addEval("c.load_menu();");'
		));
	}

	public function ctlshowAction()
    {
    	$this->_helper->control()->config->set(array(
    		'field' => array(
    			'resource' => array(
    				'active' => false
    			)
    		)
		));

    	$this->_helper->control()->routeDefault();
    }

	public function ctldragAction()
    {
    	$this->_helper->control()->config->set(array(
    		'func_success' => 'php_function:Zend_Controller_Action_HelperBroker::getStaticHelper("js")->addEval("c.load_menu();");'
		));

    	$this->_helper->control()->routeDefault();
    }
}