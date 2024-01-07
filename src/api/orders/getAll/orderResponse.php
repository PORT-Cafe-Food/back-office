<?php

/**
 * Class OrderResponse
 *
 * Represents the response structure for an order.
 */
class OrderResponse
{
    public $orderType;
    public $smsNotify;
    public $deliveryFee;
    public $timeToPrepare;
    public $isReady;
    public $customer;
    public $items;

    /**
     * OrderResponse constructor.
     *
     * @param array $orderData The data array representing an order.
     */
    public function __construct(array $orderData)
    {
        $this->orderType = $orderData['orderType'];
        $this->smsNotify = (bool)$orderData['smsNotify'];
        $this->deliveryFee = $orderData['deliveryFee'];
        $this->timeToPrepare = $orderData['timeToPrepare'];
        $this->isReady = (bool)$orderData['isReady'];

        $this->customer = [
            'fullname' => $orderData['customerFullname'],
            'phonenumber' => $orderData['customerPhonenumber'],
            'address' => $orderData['customerAddress'],
            'houseNumber' => $orderData['customerHouseNumber'],
            'floorNumber' => $orderData['customerFloorNumber'],
            'apartmentNumber' => $orderData['customerApartmentNumber'],
            'addressDescription' => $orderData['customerAddressDescription'],
        ];

        $this->items = [
            [
                'id' => $orderData['itemId'],
                'name' => $orderData['itemName'],
                'price' => $orderData['itemPrice'],
                'total_price' => $orderData['orderDetailsTotalPrice'],
                'quantity' => $orderData['quantity'],
                'size' => $orderData['size'],
                'options' => json_decode($orderData['options']),
                'description' => $orderData['description'],
                'category_id' => $orderData['category_id'],
            ],
        ];
    }

    /**
     * Converts the OrderResponse instance to a JSON string.
     *
     * @return string The JSON string representing the OrderResponse instance.
     */
    public function toJson()
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
