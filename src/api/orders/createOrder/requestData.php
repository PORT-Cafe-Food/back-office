<?php

/**
 * Class Customer
 *
 * Represents customer information.
 */
class Customer
{
    public $fullname;
    public $phonenumber;
    public $address;
    public $houseNumber;
    public $floorNumber;
    public $apartmentNumber;
    public $addressDescription;

    /**
     * Customer constructor.
     *
     * @param string $fullname           Customer's full name.
     * @param string $phonenumber        Customer's phone number.
     * @param string $address            Customer's address.
     * @param string $houseNumber        House number.
     * @param string $floorNumber        Floor number.
     * @param string $apartmentNumber    Apartment number.
     * @param string $addressDescription Additional address description.
     */
    public function __construct(
        string $fullname,
        string $phonenumber,
        string $address,
        string $houseNumber,
        string $floorNumber,
        string $apartmentNumber,
        string $addressDescription
    ) {
        $this->fullname = $fullname;
        $this->phonenumber = $phonenumber;
        $this->address = $address;
        $this->houseNumber = $houseNumber;
        $this->floorNumber = $floorNumber;
        $this->apartmentNumber = $apartmentNumber;
        $this->addressDescription = $addressDescription;
    }
}

/**
 * Class OrderItem
 *
 * Represents an item in the order.
 */
class OrderItem
{
    public $id;
    public $name;
    public $price;
    public $total_price;
    public $quantity;
    public $size;
    public $options;
    public $description;
    public $category_id;

    /**
     * OrderItem constructor.
     *
     * @param int    $id            Item ID.
     * @param string $name          Item name.
     * @param float  $price         Item price.
     * @param float  $total_price   Total price for the item.
     * @param int    $quantity      Item quantity.
     * @param string $size          Item size.
     * @param array  $options       Item options.
     * @param string $description   Item description.
     * @param int    $category_id   Category ID.
     */
    public function __construct(
        int $id,
        string $name,
        float $price,
        float $total_price,
        int $quantity,
        string $size,
        array $options,
        string $description,
        int $category_id
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->total_price = $total_price;
        $this->quantity = $quantity;
        $this->size = $size;
        $this->options = $options;
        $this->description = $description;
        $this->category_id = $category_id;
    }
}

/**
 * Class OrderCreate
 *
 * Represents the structure for creating a new order.
 */
class OrderCreate
{
    public $orderType;
    public $smsNotify;
    public $deliveryFee;
    public $timeToPrepare;
    public $customer;
    public $items;
    public $deliveryTime;

    /**
     * OrderCreate constructor.
     *
     * @param string   $orderType       Type of the order.
     * @param bool     $smsNotify       Whether to send an SMS notification.
     * @param float    $deliveryFee     Delivery fee for the order.
     * @param int      $timeToPrepare   Time required to prepare the order.
     * @param Customer $customer        Customer information.
     * @param array    $items           Items in the order.
     */
    public function __construct(string $jsonData)
    {
        // Decode the JSON data
        $orderData = json_decode($jsonData, true);

        // Validate the required fields
        if (!isset($orderData['orderType'], $orderData['smsNotify'], $orderData['deliveryFee'], $orderData['timeToPrepare'], $orderData['customer'], $orderData['items'])) {
            http_response_code(400);
            die('Invalid request data');
        }

        // Extract data from the JSON
        $this->orderType = $orderData['orderType'];
        $this->smsNotify = $orderData['smsNotify'];
        $this->deliveryFee = $orderData['deliveryFee'];
        $this->timeToPrepare = $orderData['timeToPrepare'];
        $this->customer = $orderData['customer'];
        $this->items = $orderData['items'];

        // Validate customer data
        if (!isset($this->customer['fullname'], $this->customer['phonenumber'], $this->customer['address'])) {
            http_response_code(400);
            die('Invalid customer data');
        }

        // Calculate delivery time
        $currentTimestamp = time();
        $deliveryTimestamp = $currentTimestamp + ($this->timeToPrepare * 60); // Convert minutes to seconds

        // Convert to DateTime object
        $this->deliveryTime = date('Y-m-d H:i:s', $deliveryTimestamp);
    }
}
