<?php
/**
 * Simple Paystack Key Tester
 * Loads your account balance to verify the Secret Key works.
 */

// 🔴 PASTE YOUR SECRET KEY HERE EXACTLY AS COPIED
$secret_key = 'sk_test_24845eca974e163568aa6dd497590551e1ad2260'; 

// Clean the key just in case
$secret_key = trim($secret_key);

echo "<h1>Paystack Connection Test</h1>";
echo "Testing Key: <strong>" . substr($secret_key, 0, 10) . "..." . substr($secret_key, -5) . "</strong><br><br>";

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.paystack.co/balance", // Simple endpoint to test auth
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer " . $secret_key,
    "Cache-Control: no-cache",
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
  echo "<div style='color:red; font-weight:bold;'>cURL Error: " . $err . "</div>";
} else {
  $result = json_decode($response);
  
  if ($result && $result->status === true) {
      echo "<div style='color:green; font-weight:bold; font-size:20px;'>✅ SUCCESS! Key is Valid.</div>";
      echo "<pre>" . print_r($result->data, true) . "</pre>";
  } else {
      echo "<div style='color:red; font-weight:bold; font-size:20px;'>❌ FAILED. Paystack Rejected the Key.</div>";
      echo "<strong>Paystack Message:</strong> " . ($result->message ?? 'Unknown Error') . "<br>";
      echo "<strong>Raw Response:</strong> " . $response;
  }
}
?>