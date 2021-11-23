<?php
class ControllerExtensionPaymentSCPay extends Controller {
	public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$order_id = $order_info['order_id'];
		$amount = sprintf("%.02f", $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false));
		$name = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
		$phone = $order_info['telephone'];
		$email = $order_info['email'];
		$billingaddress1 = $order_info['payment_address_1'];
		$billingaddress2 = $order_info['payment_address_2'];
		$billingcountry = $this->db->query("select iso_code_2 from country where name = '".$order_info['payment_country']."'")->row['iso_code_2'];
		$billingstate = $order_info['payment_zone'];
		$billingcity = $order_info['payment_city'];
		$redirecturl = $this->config->get('payment_scpay_redirect');

		$data = array(
            'MerchantID' => $this->config->get('payment_scpay_merchant'),
            'CurrencyCode' => 'MYR',
            'TxnAmount' => $amount,
            'MerchantOrderNo' => $order_id . '_' . time(),
            'MerchantOrderDesc' => "Payment for Order No. : " . $order_id,
            'MerchantRef1' => $order_id,
            'MerchantRef2' => '',
            'MerchantRef3' => '',
            'CustReference' => '',
            'CustName' => $name,
            'CustEmail' => $email,
            'CustPhoneNo' => $phone,
            'CustAddress1' => $billingaddress1,
            'CustAddress2' => $billingaddress2,
            'CustCountryCode' => $billingcountry,
            'CustAddressState' => $billingstate,
            'CustAddressCity' => $billingcity,
            'RedirectUrl' => $redirecturl,
        );

		# make sign
        $signstr = "";
        foreach ($data as $key => $value) {
            $signstr .= $value;
        }

		if ($this->config->get('payment_scpay_hashtype') == 'sha256') {
            $data['SCSign'] = hash_hmac('sha256', $signstr, $this->config->get('payment_scpay_password'));
        }

		if ($this->config->get('payment_scpay_environment')=='test'){
			$data['action'] = 'https://staging.payment.share-commerce.com/payment'; 
		}else{
			$data['action'] = 'https://payment.share-commerce.com/payment';
		}

		if (file_exists(DIR_TEMPLATE . $this->config->get('theme_default_directory') . '/template/extension/payment/scpay.twig')) {
			return $this->load->view('extension/payment/scpay', $data);
		} else {
			return $this->load->view('extension/payment/scpay', $data);
		}	
	}
	
	public function isSSL()
    {
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == '1')) { 
			return true;
		}
		return false;
    }
	
	public function callback(){

		$json = file_get_contents('php://input');
        $var = json_decode($json);

		$log = new Log('SCPaymentCallback.log');
		$log->write($json);
		
		if (isset($var['RespCode']) && isset($var['RespDesc']) && isset($var['MerchantOrderNo']) && isset($var['MerchantRef1']) && isset($var['TxnRefNo']) && isset($var['SCSign'])) {
            //get order
			$this->load->model('checkout/order');
			$order = $this->model_checkout_order->getOrder($var['MerchantRef1']);

            $order_id = $order['order_id'];

            if ($order && $order_id != 0) {
                # Check Sign
                $signstr = "";
                foreach ($var as $key => $value) {
                    if ($key == 'SCSign' || $key == 'route') {
                        continue;
                    }

                    $signstr .= $value;
                }
				
                $sign = "";
                if ($this->config->get('payment_scpay_hashtype') == 'sha256') {
                    $sign = hash_hmac('sha256', $signstr, $this->config->get('payment_scpay_password'));
                }

				// echo $sign . ' | ' . $var['SCSign'] ; 
				// exit();

				// echo "<PRE>";
				// print_r($order);
				// exit();

                if ($sign == $var['SCSign']) {
                    if ($var['RespCode'] == '00' || $var['RespDesc'] == 'Success') { // success

                        if ($order['order_status_id'] == '1' || $order['order_status_id'] == '2' || $order['order_status_id'] == '10') { // check order is pending or proccessing
                            
							$message = "Payment successfully made through Share Commerce Payment, Transaction Reference " . $var['TxnRefNo'];
							$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_scpay_success_status_id'), $message, true);

							echo "OK";
                        	exit();
                        }
                    } else {

                        $message = "Payment was unsuccessful";
						$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_scpay_pending_status_id'), $message, true);

                        echo "OK";
                        exit();
                    }
                } 
				else{
					// echo "Signning not match";
					$message = "Signing Not Match";
					$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_scpay_pending_status_id'), $message, true);

					echo "OK";
					exit();
				}
            }
        }
	}

	public function redirect(){
		$var = $_GET;

		// echo "<PRE>";
		// print_r($var);
		// exit();

		// die($this->url->link('extension/checkout/scpayresponse/success', '', $this->isSSL()));

		if (isset($var['RespCode']) && isset($var['RespDesc']) && isset($var['MerchantOrderNo']) && isset($var['MerchantRef1']) && isset($var['TxnRefNo']) && isset($var['SCSign'])) {
            //get order
			$this->load->model('checkout/order');
			$order = $this->model_checkout_order->getOrder($var['MerchantRef1']);

            $order_id = $order['order_id'];

            if ($order && $order_id != 0) {
                # Check Sign
                $signstr = "";
                foreach ($var as $key => $value) {
                    if ($key == 'SCSign' || $key == 'route') {
                        continue;
                    }

                    $signstr .= $value;
                }
				
                $sign = "";
                if ($this->config->get('payment_scpay_hashtype') == 'sha256') {
                    $sign = hash_hmac('sha256', $signstr, $this->config->get('payment_scpay_password'));
                }

				// echo $sign . ' | ' . $var['SCSign'] ; 
				// exit();

				// echo "<PRE>";
				// print_r($order);
				// exit();

                if ($sign == $var['SCSign']) {
                    if ($var['RespCode'] == '00' || $var['RespDesc'] == 'Success') { // success

                        if ($order['order_status_id'] == '1' || $order['order_status_id'] == '2' || $order['order_status_id'] == '0') { // check order is pending or proccessing
                            
							$message = "Payment successfully made through Share Commerce Payment, Transaction Reference " . $var['TxnRefNo'];
							$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_scpay_success_status_id'), $message, true);

							$this->response->redirect($this->url->link('checkout/success', '', $this->isSSL()));
                            exit();
                        }
                    } else {

                        $message = "Payment was unsuccessful";
						$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_scpay_pending_status_id'), $message, true);

                        $this->response->redirect($this->url->link('checkout/checkout', '', $this->isSSL()));
                        exit();
                    }
                } 
				else{
					// echo "Signning not match";
					$message = "Signing Not Match";
					$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_scpay_pending_status_id'), $message, true);

					$this->response->redirect($this->url->link('checkout/checkout', '', $this->isSSL()));
					exit();
				}
            }
        }
	}

}