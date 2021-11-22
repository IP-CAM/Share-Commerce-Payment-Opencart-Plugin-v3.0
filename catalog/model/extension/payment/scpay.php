<?php 
class ModelExtensionPaymentSCPay extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/scpay');
		
		$method_data = array();
        $status = true;
        
        if($status)
        {
            $method_data = array(
                'code'       => 'scpay',
                'title'      => $this->language->get('text_title'),
                'terms'      => '',
                'sort_order' => $this->config->get('payment_scpay_sort_order')
            );
        }

        return $method_data;
	}
}