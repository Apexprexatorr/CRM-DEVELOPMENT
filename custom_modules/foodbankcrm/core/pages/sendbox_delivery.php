<?php
/**
 * SendBox Delivery Integration Helper
 */

class SendBoxDelivery {
    
    private $api_key;
    private $base_url = 'https://api.sendbox.co/shipping';
    
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }
    
    /**
     * Calculate delivery fee
     */
    public function getDeliveryFee($origin, $destination, $weight = 1) {
        $curl = curl_init();
        
        $data = array(
            'origin' => $origin,
            'destination' => $destination,
            'weight' => $weight,
            'shipment_type' => 'parcel'
        );
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->base_url . '/calculate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json'
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            return array('error' => $err);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Create delivery booking
     */
    public function createDelivery($data) {
        $curl = curl_init();
        
        // Prepare shipment data
        $shipment = array(
            'origin_name' => $data['origin_name'] ?? 'FoodBank Warehouse',
            'origin_phone' => $data['origin_phone'] ?? '08012345678',
            'origin_address' => $data['origin_address'] ?? 'Warehouse Address',
            'origin_state' => $data['origin_state'] ?? 'Lagos',
            'origin_country' => 'Nigeria',
            
            'destination_name' => $data['destination_name'],
            'destination_phone' => $data['destination_phone'],
            'destination_address' => $data['destination_address'],
            'destination_state' => $data['destination_state'],
            'destination_country' => 'Nigeria',
            
            'items' => $data['items'], // Array of items
            'weight' => $data['weight'] ?? 5,
            'shipment_type' => 'parcel',
            'pickup_date' => $data['pickup_date'] ?? date('Y-m-d'),
            'delivery_type' => $data['delivery_type'] ?? 'standard',
            
            'value' => $data['value'] ?? 0,
            'payment_type' => 'prepaid',
            'reference' => $data['reference'] ?? 'FBC-'.time(),
        );
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->base_url . '/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($shipment),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json'
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            return array('error' => $err);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Track delivery
     */
    public function trackDelivery($tracking_number) {
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->base_url . '/track/' . $tracking_number,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->api_key,
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            return array('error' => $err);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get delivery status
     */
    public function getDeliveryStatus($delivery_id) {
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->base_url . '/status/' . $delivery_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->api_key,
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            return array('error' => $err);
        }
        
        return json_decode($response, true);
    }
}
?>
