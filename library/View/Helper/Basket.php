<?php

class Zkernel_View_Helper_Basket extends Zend_View_Helper_Abstract  {
	protected $_model_item = null;
	protected $_model_order = null;
	protected $_model_order_item = null;
	protected $_model_discount = null;
	protected $_field_order_item_id = 'itemid';

	function __construct() {
		if ($this->_model_item === null) $this->_model_item = new Default_Model_Catalogitem;
		if ($this->_model_order === null) $this->_model_order = new Default_Model_Order;
		if ($this->_model_order_item === null) $this->_model_order_item = new Default_Model_Orderitem;
		if ($this->_model_discount === null && class_exists('Default_Model_Discount')) $this->_model_discount = new Default_Model_Discount;
	}

	function basket() {
		return $this;
	}

	function basket2Sid($uid) {
		if (!$uid) return false;
		$sid = Zend_Session::getId();
		return $this->_model_order->update(array('author' => $sid), array(
			'`author` = ?' => $uid,
			'`finished` = 0',
			'`active` = 1'
		));
	}

	function basketId($create = false) {
		$uid = $this->view->user('id');
		$sid = Zend_Session::getId();
		if ($uid) {
			$id = (int)$this->_model_order->fetchOne('id', array(
				'`author` = ?' => $sid,
				'`finished` = 0',
				'`active` = 1'
			), 'date desc');
			if ($id) {
				$this->_model_order->update(array('author' => $uid), array('`id` = ?' => $id));
				$this->_model_order->update(array('active' => 0), array(
					'`author` = ?' => $uid,
					'`finished` = 0',
					'`active` = 1',
					'`id` != ?' => $id
				));
			}
		}
		else $uid = $sid;
		$id = (int)$this->_model_order->fetchOne('id', array(
			'`author` = ?' => $uid,
			'`finished` = 0',
			'`active` = 1'
		), 'date desc');
		if (!$id && $create) {
			$mp = new Default_Model_Pay();
			$ms = new Default_Model_Orderstatus();
			$mc = new Default_Model_Card();
			$d = array(
				'author' => $uid
			);
			if (method_exists($this, 'basketDefault')) {
				$dd = $this->basketDefault();
				if ($dd) $d = array_merge($d, $dd);
			}
			$id = $this->_model_order->insert($d);
		}
		return $id;
	}

	function basketQuant($id = null) {
		$oid = $this->basketId();
		$s = $this->_model_order->getAdapter()->select()
			->from(array('i' => $this->_model_order->info('name')), '')
			->joinLeft(array('m' => $this->_model_order_item->info('name')), 'i.id = m.parentid', array(
				'quant' => 'SUM(m.quant)'
			))
			->where('i.id = ?', $oid)
			->group('i.id');
		if ($id != null) $s->where('m.id = ?', $id);
		$ret = (int)$this->_model_order->getAdapter()->fetchOne($s, 'SUM(`quant`)');
		return $ret;
	}

	function basketPrice($id = null) {
		$oid = $this->basketId();
		$s = $this->_model_order->getAdapter()->select()
			->from(array('i' => $this->_model_order->info('name')), '')
			->joinLeft(array('m' => $this->_model_order_item->info('name')), 'i.id = m.parentid', array(
				'price' => 'SUM(m.price * m.quant)'
			))
			->where('i.id = ?', $oid)
			->group('i.id');
		if ($id != null) $s->where('m.id = ?', $id);
		$ret = (int)$this->_model_order->getAdapter()->fetchOne($s, 'SUM(`price`)');
		return $ret;
	}

	function basketAdd($id, $quant = 1) {
		$oid = $this->basketId(true);
		$item = $this->_model_item->fetchBasketCard($id);
		if (!$item || !$item->price || !$quant) return false;
		$ex = $this->_model_order_item->fetchRow(array(
			'`parentid` = ?' => $oid,
			'`'.$this->_field_order_item_id.'` = ?' => $id
		));
		if ($ex) {
			$ok = $this->_model_order_item->update(array(
				'quant' => $ex->quant + $quant
			), array(
				'`id` = ?' => $ex->id
			));
		}
		else {
			$ok = $this->_model_order_item->insert(array(
				'parentid' => $oid,
				'quant' => $quant,
				$this->_field_order_item_id => $id,
				'price' => $item->price,
				'orderid' => (int)$this->_model_order_item->fetchMax('orderid') + 1
			));
		}
		return $ok;
	}

	function basketDelete($id) {
		$oid = $this->basketId();
		if (!$oid) return false;
		$ok = $this->_model_order_item->delete(array(
			'`parentid` = ?' => $oid,
			'`'.$this->_field_order_item_id.'` = ?' => $id
		));
		return $ok;
	}

	function basketList() {
		$oid = $this->basketId();
		$list = $this->_model_order_item->fetchAll(array('`parentid` = ?' => $oid), 'orderid');
		$ret = array();
		if ($list) {
			foreach ($list as $el) {
				$d = new Zkernel_View_Data($el);
				$item = $this->_model_item->fetchBasketCard($el->{$this->_field_order_item_id});
				$ret[] = new Zkernel_View_Data(array_merge($d->toArray(), $item->toArray()));
			}
		}
		return $ret;
	}

	function basketCard() {
		$oid = $this->basketId();
		$res = $this->_model_order->fetchRow(array('`id` = ?' => (int)$oid));
		return $res ? new Zkernel_View_Data($res) : null;
	}

	function basketFinish($data = array()) {
		$data['finished'] = 1;
		return $this->basketSave($data);
	}

	function basketSave($data) {
		$oid = $this->basketId();
		if (!$oid) return false;
		$ok = $this->_model_order->update($data, array('`id` = ?' => $oid));
		return $ok ? $oid : false;
	}

	function basketDiscount($uid = null) {
		$uid = $uid ? $uid : $this->view->user('id');
		$total = $this->finishedPrice(null, null, $uid, true);
		return $this->_model_discount->fetchBasketDiscount($total);
	}

	function finishedCard($oid) {
		$res = $this->_model_order->fetchRow(array('`id` = ?' => (int)$oid, '`finished` = ?' => 1));
		return $res ? new Zkernel_View_Data($res) : null;
	}

	function finishedPrice($oid = null, $id = null, $uid = null, $payed = null) {
		$s = $this->_model_order->getAdapter()->select()
			->from(array('i' => $this->_model_order->info('name')), '')
			->joinLeft(array('m' => $this->_model_order_item->info('name')), 'i.id = m.parentid', array(
				'price' => 'SUM(m.price * m.quant)'
			))
			->where('i.active = ?', 1)
			->where('i.finished = ?', 1)
			->group('i.id');
		if ($payed != null) $s->where('i.payed = ?', (int)$payed);
		if ($uid != null) $s->where('i.author = ?', $uid);
		if ($oid != null) $s->where('i.id = ?', $oid);
		if ($id != null) $s->where('m.id = ?', $id);
		$ret = (int)$this->_model_order->getAdapter()->fetchOne($s, 'SUM(`price`)');
		return $ret;
	}

	function finishedList($oid) {
		$list = $this->_model_order_item->fetchAll(array('`parentid` = ?' => $oid), 'orderid');
		$ret = array();
		if ($list) {
			foreach ($list as $el) {
				$d = new Zkernel_View_Data($el);
				$item = $this->_model_item->fetchBasketCard($el->{$this->_field_order_item_id});
				$ret[] = new Zkernel_View_Data(array_merge($d->toArray(), $item->toArray()));
			}
		}
		return $ret;
	}

	function finishedSave($oid, $data) {
		$ok = $this->_model_order->update($data, array('`id` = ?' => $oid, '`finished` = ?' => 1));
		return $ok ? $oid : false;
	}
}
