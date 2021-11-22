<?php 
class ControllerExtensionPaymentSCPay extends Controller {
	private $error = array(); 

	public function index() {
		$this->load->language('extension/payment/scpay');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_scpay', $this->request->post);				
			
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token']. "&type=payment", 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

 		if (isset($this->error['merchant'])) {
			$data['error_merchant'] = $this->error['merchant'];
		} else {
			$data['error_merchant'] = '';
		}

 		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_password'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/scpay', 'user_token=' . $this->session->data['user_token'], 'SSL'),      		
		);

		$data['action'] = $this->url->link('extension/payment/scpay', 'user_token=' . $this->session->data['user_token'], 'SSL');
		
		$data['cancel'] = $this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], 'SSL');
		
		if (isset($this->request->post['payment_scpay_merchant'])) {
			$data['payment_scpay_merchant'] = $this->request->post['payment_scpay_merchant'];
		} else {
			$data['payment_scpay_merchant'] = $this->config->get('payment_scpay_merchant');
		}
		
		if (isset($this->request->post['payment_scpay_password'])) {
			$data['payment_scpay_password'] = $this->request->post['payment_scpay_password'];
		} else {
			$data['payment_scpay_password'] = $this->config->get('payment_scpay_password');
		}

		if (isset($this->request->post['payment_scpay_redirect'])) {
			$data['payment_scpay_redirect'] = $this->request->post['payment_scpay_redirect'];
		} else {
			$data['payment_scpay_redirect'] = HTTP_CATALOG . 'index.php?route=extension/payment/scpay/redirect';
		}

		if (isset($this->request->post['payment_scpay_hashtype'])) {
			$data['payment_scpay_hashtype'] = $this->request->post['payment_scpay_hashtype'];
		} else {
			$data['payment_scpay_hashtype'] = 'sha256';
		}

		if (isset($this->request->post['payment_scpay_environment'])) {
			$data['payment_scpay_environment'] = $this->request->post['payment_scpay_environment'];
		} else {
			$data['payment_scpay_environment'] = $this->config->get('payment_scpay_environment');
		}
		
		if (isset($this->request->post['payment_scpay_total'])) {
			$data['payment_scpay_total'] = $this->request->post['payment_scpay_total'];
		} else {
			$data['payment_scpay_total'] = $this->config->get('payment_scpay_total'); 
		} 
		
		$Config_scpay_order_status_id = $this->config->get('payment_scpay_order_status_id');
		if (isset($this->request->post['payment_scpay_order_status_id'])) {
			$data['payment_scpay_order_status_id'] = $this->request->post['payment_scpay_order_status_id'];
		} elseif(empty($Config_scpay_order_status_id)){
			$data['payment_scpay_order_status_id'] = 1;
		}else {
			$data['payment_scpay_order_status_id'] = $this->config->get('payment_scpay_order_status_id'); 
		}
		
		$Config_scpay_success_status_id = $this->config->get('payment_scpay_success_status_id');
		if (isset($this->request->post['payment_scpay_success_status_id'])) {
			$data['payment_scpay_success_status_id'] = $this->request->post['payment_scpay_success_status_id'];
		} elseif(empty($Config_scpay_success_status_id)){
			$data['payment_scpay_success_status_id'] = 2;
		}else {
			$data['payment_scpay_success_status_id'] = $this->config->get('payment_scpay_success_status_id'); 
		} 
		
		$Config_scpay_failed_status_id = $this->config->get('payment_scpay_failed_status_id');
		if (isset($this->request->post['payment_scpay_failed_status_id'])) {
			$data['payment_scpay_failed_status_id'] = $this->request->post['payment_scpay_failed_status_id'];
		} elseif(empty($Config_scpay_failed_status_id)){
			$data['payment_scpay_failed_status_id'] = 10;
		}else {
			$data['payment_scpay_failed_status_id'] = $this->config->get('payment_scpay_failed_status_id'); 
		} 
		
		$Config_scpay_cancel_status_id = $this->config->get('payment_scpay_cancel_status_id');
		if (isset($this->request->post['payment_scpay_cancel_status_id'])) {
			$data['payment_scpay_cancel_status_id'] = $this->request->post['payment_scpay_cancel_status_id'];
		} elseif(empty($Config_scpay_cancel_status_id)){
			$data['payment_scpay_cancel_status_id'] = 7;
		}else {
			$data['payment_scpay_cancel_status_id'] = $this->config->get('payment_scpay_cancel_status_id'); 
		} 
		
		$Config_scpay_pending_status_id = $this->config->get('payment_scpay_pending_status_id');
		if (isset($this->request->post['payment_scpay_pending_status_id'])) {
			$data['payment_scpay_pending_status_id'] = $this->request->post['payment_scpay_pending_status_id'];
		} elseif(empty($Config_scpay_pending_status_id)){
			$data['payment_scpay_pending_status_id'] = 1;
		}else {
			$data['payment_scpay_pending_status_id'] = $this->config->get('payment_scpay_pending_status_id'); 
		} 
		
		$this->load->model('localisation/order_status');
		
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		if (isset($this->request->post['payment_scpay_geo_zone_id'])) {
			$data['payment_scpay_geo_zone_id'] = $this->request->post['payment_scpay_geo_zone_id'];
		} else {
			$data['payment_scpay_geo_zone_id'] = $this->config->get('payment_scpay_geo_zone_id'); 
		} 

		$this->load->model('localisation/geo_zone');
										
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if (isset($this->request->post['payment_scpay_status'])) {
			$data['payment_scpay_status'] = $this->request->post['payment_scpay_status'];
		} else {
			$data['payment_scpay_status'] = $this->config->get('payment_scpay_status');
		}
		
		if (isset($this->request->post['payment_scpay_sort_order'])) {
			$data['payment_scpay_sort_order'] = $this->request->post['payment_scpay_sort_order'];
		} else {
			$data['payment_scpay_sort_order'] = $this->config->get('payment_scpay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/scpay', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/scpay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->request->post['payment_scpay_merchant']) {
			$this->error['merchant'] = $this->language->get('error_merchant');
		}
		
		if (!$this->request->post['payment_scpay_password']) {
			$this->error['password'] = $this->language->get('error_password');
		}
		
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}
}
?>