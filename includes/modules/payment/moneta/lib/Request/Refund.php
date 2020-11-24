<?php

namespace MonetaPayments;

class RequestRefund extends Request {

    public function __construct($values = array()) {
        parent::__construct();
        $this->_token_request = new RequestTokenRefund($values);
        $this->_action_request = new RequestActionRefund($values);
    }

}
