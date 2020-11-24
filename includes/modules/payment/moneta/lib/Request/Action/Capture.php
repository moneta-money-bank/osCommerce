<?php

namespace MonetaPayments;

class RequestActionCapture extends RequestActionRefund {

    public function __construct() {
        parent::__construct();
        $this->_data["action"] = Payments::ACTION_CAPTURE;
    }

}
