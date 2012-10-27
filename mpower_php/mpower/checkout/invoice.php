<?php
class MPower_Checkout_Invoice extends MPower_Checkout {

  private $items = array();
  private $total_amount = 0.0;
  private $taxes = array();
  private $description;
  private $currency = "ghs";
  private $cancel_url;
  private $return_url;
  private $invoice_url;
  private $custom_data;
  private $receipt_url;

  private $customer = array();

  function __construct(){
    $this->cancel_url = MPower_Checkout_Store::getCancelUrl();
    $this->return_url = MPower_Checkout_Store::getReturnUrl();
    $this->custom_data = new MPower_CustomData();
  }

  public function addItem($name,$quantity,$price,$totalPrice,$description="") {
    $this->items['item_'.count($this->items)] = array(
      'name' => $name,
      'quantity' => intval($quantity),
      'unit_price' => round($price,2),
      'total_price' => round($totalPrice,2),
      'description' => $description
    );
  }

  public function pushItems($data=array()) {
    $this->items = $data;
  }

  public function pushTaxes($data=array()) {
    $this->taxes = $data;
  }

  public function setTotalAmount($amount) {
    $this->total_amount = round($amount,2);
  }

  public function setDescription($description) {
    $this->description = $description;
  }

  public function setCancelUrl($url) {
    if(filter_var($url, FILTER_VALIDATE_URL)){
      $this->cancel_url = $url;
    }
  }

  public function setReturnUrl($url) {
    if(filter_var($url, FILTER_VALIDATE_URL)){
      $this->return_url = $url;
    }
  }

  public function addTax($name,$amount) {
    $this->taxes['tax_'.count($this->taxes)] = array(
      'name' => $name,
      'amount' => $amount
    );
  }

  public function getInvoiceUrl() {
    return $this->invoice_url;
  }

  public function getItems() {
    return json_encode($this->items, JSON_FORCE_OBJECT);
  }

  public function getTaxes() {
    return json_encode($this->taxes, JSON_FORCE_OBJECT);
  }

  public function setCustomData($name,$value) {
    $this->custom_data->set($name,$value);
  }

  public function pushCustomData($data=array()) {
    $this->custom_data->push($data);
  }

  public function getCustomData($name) {
    return $this->custom_data->get($name);
  }

  public function showCustomData() {
    return $this->custom_data->show();
  }

  public function getTotalAmount() {
    return $this->total_amount;
  }

  public function getDescription() {
    return $this->description;
  }

  public function getReceiptUrl() {
    return $this->receipt_url;
  }

  public function getStatus() {
    return $this->status;
  }

  public function confirm($parsetoken="") {
    $token = isset($token) ? $parsetoken : $_GET['token'];
    $result = MPower_Utilities::httpGetRequest("http://0.0.0.0:3000/sandbox-api/v1/checkout-invoice/confirm/test_4fd6a70b07");
    if(count($result) > 0) {
      $this->status = $result['status'];
      $this->pushCustomData($result["custom_data"]);
      $this->pushItems($result["invoice"]['items']);
      $this->pushTaxes($result["invoice"]['taxes']);
      $this->customer = $result['customer'];
      $this->setTotalAmount($result['invoice']['total_amount']);
      $this->receipt_url = $result['receipt_url'];
      return true;
    }else{
      $this->status = "fail";
      $this->response_code = 1002;
      $this->response_text = "Invoice Not Found";
      return false;
    }
  }

  public function getCustomerInfo($info_type) {
    return $this->customer[$info_type];
  }

  public function create() {
    $checkout_payload = array(
      'invoice' => array(
        'items' => $this->items,
        'taxes' => $this->taxes,
        'total_amount' => $this->getTotalAmount(),
        'description' => $this->getDescription()
      ),
      'store' => array(
        'name' => MPower_Checkout_Store::getName(),
        'tagline' => MPower_Checkout_Store::getTagline(),
        'postal_address' => MPower_Checkout_Store::getPostalAddress(),
        'phone' => MPower_Checkout_Store::getPhoneNumber(),
        'logo_url' => MPower_Checkout_Store::getLogoUrl(),
        'website_url' => MPower_Checkout_Store::getWebsiteUrl()
      ),
      'custom_data' => $this->showCustomData(),
      'actions' => array(
        'cancel_url' => $this->cancel_url,
        'return_url' => $this->return_url
      )
    );

    $result = MPower_Utilities::httpJsonRequest(MPower_Setup::getCheckoutBaseUrl(),$checkout_payload);

    switch ($result["response_code"]) {
      case 00:
        $this->status = "success";
        $this->response_code = $result["response_code"];
        $this->response_text = $result["description"];
        $this->invoice_url = $result["response_text"];
        return true;
        break;
      default:
        $this->invoice_url = "";
        $this->status = "fail";
        $this->response_code = $result["response_code"];
        $this->response_text = $result["response_text"];
        return false;
        break;
    }
  }
}