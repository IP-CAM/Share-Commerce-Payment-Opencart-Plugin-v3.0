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
		
		$this->language->load('payment/scpay');
		$data = array();
		if (!empty($this->request->request)){
			$urlType = $this->request->request['urlType'];
		}
		
		if($urlType == 'return'){
			$data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

			if (!isset($this->request->server['HTTPS']) || ($this->request->server['HTTPS'] != 'on')) {
				$data['base'] = $this->config->get('config_url');
			} else {
				$data['base'] = $this->config->get('config_ssl');
			}
			$data['language'] = $this->language->get('code');
			$data['direction'] = $this->language->get('direction');
		
			$data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

			$data['text_response'] = $this->language->get('text_response');
			$data['text_success'] = $this->language->get('text_success');
			$data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), $this->url->link('checkout/success', '', $this->isSSL()));
			$data['text_failure'] = $this->language->get('text_failure');
			$data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/checkout', '', $this->isSSL()));
		}
		
		if (!empty($this->request->post)){
			$TransactionType = $this->request->post['TransactionType'];
			$PaymentID = $this->request->post['PaymentID'];
			$ServiceID = $this->request->post['ServiceID'];
			$OrderNumber = $this->request->post['OrderNumber'];
			$Amount = $this->request->post['Amount'];
			$CurrencyCode = $this->request->post['CurrencyCode'];
			$TxnID = $this->request->post['TxnID'];
			$PymtMethod = $this->request->post['PymtMethod'];
			$TxnStatus = $this->request->post['TxnStatus'];
			$AuthCode = (!empty($this->request->post['AuthCode'])) ? $this->request->post['AuthCode'] : "";
			$TxnMessage = $this->request->post['TxnMessage'];
			$IssuingBank = (!empty($this->request->post['IssuingBank'])) ? $this->request->post['IssuingBank'] : "";
			$HashValue = $this->request->post['HashValue'];
			$HashValue2 = $this->request->post['HashValue2'];
		} else {
			$TransactionType = $this->request->get['TransactionType'];
			$PaymentID = $this->request->get['PaymentID'];
			$ServiceID = $this->request->get['ServiceID'];
			$OrderNumber = $this->request->get['OrderNumber'];
			$Amount = $this->request->get['Amount'];
			$CurrencyCode = $this->request->get['CurrencyCode'];
			$TxnID = $this->request->get['TxnID'];
			$PymtMethod = $this->request->get['PymtMethod'];
			$TxnStatus = $this->request->get['TxnStatus'];
			$AuthCode = (!empty($this->request->get['AuthCode'])) ? $this->request->get['AuthCode'] : "";
			$TxnMessage = $this->request->get['TxnMessage'];
			$IssuingBank = (!empty($this->request->get['IssuingBank'])) ? $this->request->get['IssuingBank'] : "";
			$HashValue = $this->request->get['HashValue'];
			$HashValue2 = $this->request->get['HashValue2'];
		}
	
		$this->load->model('checkout/order');
		$order = $this->model_checkout_order->getOrder($OrderNumber);

		if($TransactionType == "SALE" && ( $order['order_status_id']==$this->config->get('payment_scpay_order_status_id') || $order['order_status_id']==$this->config->get('payment_scpay_pending_status_id') )){
			$verify = hash( 'sha256', $this->config->get('payment_scpay_password') . $TxnID . $ServiceID . $PaymentID . $TxnStatus . $Amount . $CurrencyCode . $AuthCode . $OrderNumber );

			if( $HashValue2 != $verify ){
				$TxnStatus = 3;
			}
			
			if($urlType == 'callback'){
				// echo appropriate acknolegement urlType = callback
				if($TxnStatus == 3){
					echo "ERROR";
				}
				else{
					echo "OK";
				}
			}
					
			if ( $TxnStatus == 0 ) {
				$message = "Successful payment (escpay $urlType Response) $CurrencyCode $Amount [PaymentID: $PaymentID] [TxnStatus:$TxnStatus] [Payment method:$PymtMethod]";
				$this->model_checkout_order->addOrderHistory($OrderNumber, $this->config->get('payment_scpay_success_status_id'), $message, true);
				
				// Dont deduct the stock if order status was set pending by bank
				// because stock was already reserved when status was set pending
				/* if( !$this->isOrderStatusPendingByBank($order) ){
					//$this->deduct_order_Stock($order);
				} */
			}
			elseif($TxnStatus == 1){
				if($TxnMessage == "Buyer cancelled"){
					$message = "Payment Cancelled by Shopper(escpay $urlType Response)";
					$this->model_checkout_order->addOrderHistory($OrderNumber, $this->config->get('payment_scpay_cancel_status_id'), $message, true);
				}
				else{
					$message = "Failed Payment (escpay $urlType Response) $CurrencyCode $Amount [PaymentID: $PaymentID] [TxnStatus:$TxnStatus] [Payment method:$PymtMethod]";
					$this->model_checkout_order->addOrderHistory($OrderNumber, $this->config->get('payment_scpay_failed_status_id'), $message, true);
					
					// Return back the stock if order status was set pending by bank
					// because stock was reserved when status was set pending, therefore need to release stock now
					
					/* if( $this->isOrderStatusPendingByBank($order) ){
						//$this->return_order_Stock($order);
					} */
				}
			}
			elseif($TxnStatus == 2){
				$message = "Pending Payment (escpay $urlType Response) $CurrencyCode $Amount [PaymentID: $PaymentID] [TxnStatus:$TxnStatus] [Payment method:$PymtMethod]";
				$this->model_checkout_order->addOrderHistory($OrderNumber, $this->config->get('payment_scpay_pending_status_id'), $message, true);
				//$this->deduct_order_Stock($order);
			}
		}
		
		if($urlType == 'return'){
			// redirection to appropriate page when urlType = return
			if($TxnStatus == 0){
				$this->response->redirect($this->url->link('extension/checkout/scpayresponse/success', '', $this->isSSL()));
			}
			elseif($TxnStatus == 1){
				if($TxnMessage == "Buyer cancelled"){
					$this->response->redirect($this->url->link('extension/checkout/scpayresponse/fail', '', $this->isSSL()));
				}
				else{
					$this->response->redirect($this->url->link('extension/checkout/scpayresponse/fail', '', $this->isSSL()));
				}
			}
			elseif($TxnStatus == 2){
				$this->response->redirect($this->url->link('extension/checkout/scpayresponse/pending', '', $this->isSSL()));
			}
			else{
				$this->response->redirect($this->url->link('extension/checkout/scpayresponse/fail', '', $this->isSSL()));
			}
			exit;
		}
		elseif($urlType == 'callback'){
			// echo appropriate acknolegement urlType = callback
			if($TxnStatus == 3){
				echo "ERROR";
			}
			else{
				echo "OK";
			}
		}
	}

	public function redirect(){
		$var = $_GET;

		echo "<PRE>";
		print_r($var);

		if (isset($var['RespCode']) && isset($var['RespDesc']) && isset($var['MerchantOrderNo']) && isset($var['MerchantRef1']) && isset($var['TxnRefNo']) && isset($var['SCSign'])) {
            global $woocommerce;

			$this->load->model('checkout/order');
			$order = $this->model_checkout_order->getOrder($var['MerchantRef1']);

            $order_id = $order['order_id'];

            if ($order && $order_id != 0) {
                # Check Sign
                $signstr = "";
                foreach ($var as $key => $value) {
                    if ($key == 'SCSign') {
                        continue;
                    }

                    $signstr .= $value;
                }
                $sign = "";
                if ($this->config->get('payment_scpay_hashtype') == 'sha256') {
                    $sign = hash_hmac('sha256', $signstr, $this->config->get('payment_scpay_password'));
                }

                if ($sign == $var['SCSign']) {
                    if ($var['RespCode'] == '00' || $var['RespDesc'] == 'Success') {
                        if ($order['order_status_id'] == '1' || $order['order_status_id'] == '2') { 
                            # only update if order is pending
                            if ($order['order_status_id'] == '1') {
                                $message = "Successful payment From Share Commerce, Amount: ".$var['amount']." ,TxnRefNo:$TxnRefNo ";
								$this->model_checkout_order->addOrderHistory($OrderNumber, $this->config->get('payment_scpay_success_status_id'), $message, true);
                            }

                            //redirect to success page
							$this->response->redirect($this->url->link('extension/checkout/scpayresponse/success', '', $this->isSSL()));
                            exit();
                        }
                    } else {

                        // if (strtolower($order->get_status()) == 'pending') {
                            
                        //     // $order->update_status('failed');
                        //     $order->add_order_note('Payment was unsuccessful');
                        //     add_filter('the_content', 'scpay_payment_declined_msg');
                        // }
                        // // $woocommerce->cart->empty_cart();

                        // wp_redirect($woocommerce->cart->get_checkout_url());
                        // exit();
                    }
                } else {
                    add_filter('the_content', 'scpay_hash_error_msg');
                }
            }
        }
	}

}