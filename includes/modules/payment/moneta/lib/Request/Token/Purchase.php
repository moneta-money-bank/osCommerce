<?php

namespace MonetaPayments;

class RequestTokenPurchase extends RequestTokenAuth {

    public function __construct() {
        parent::__construct();
        $this->_data["action"] = Payments::ACTION_PURCHASE;
    }

}
