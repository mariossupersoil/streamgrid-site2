<?php
// Takes the cart from the browser, checks it against config.php, and sends
// the shopper to Stripe's secure checkout page. No card details ever touch
// this website: Stripe handles payment.

header('Content-Type: application/json');
$cfg = require __DIR__ . '/config.php';

function fail($msg, $code = 400) { http_response_code($code); echo json_encode(['error' => $msg]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Use POST.', 405);
if (!$cfg['secrets_found']) fail('The shop is not connected to Stripe yet (settings file not found).', 500);
if (!preg_match('/^sk_(test|live)_[A-Za-z0-9]{10,}$/', $cfg['stripe_secret_key'])) fail('The shop is not connected to Stripe yet (the Stripe key in the settings file looks incomplete).', 500);

$in   = json_decode(file_get_contents('php://input'), true);
$cart = is_array($in['items'] ?? null) ? $in['items'] : [];

// Rebuild the order from OUR prices only.
$params = [
  'mode' => 'payment',
  'success_url' => $cfg['site_url'] . '/success.html?session_id={CHECKOUT_SESSION_ID}',
  'cancel_url'  => $cfg['site_url'] . '/index.html#shop',
  'billing_address_collection' => 'auto',
  'phone_number_collection[enabled]' => 'true',
];
$subtotal = 0; $i = 0;
foreach ($cart as $item) {
  $id  = (string)($item['id'] ?? '');
  $qty = (int)($item['qty'] ?? 0);
  if (!isset($cfg['products'][$id]) || $qty < 1) continue;
  $qty = min($qty, 10);
  $p = $cfg['products'][$id];
  $params["line_items[$i][quantity]"] = $qty;
  $params["line_items[$i][price_data][currency]"] = $cfg['currency'];
  $params["line_items[$i][price_data][unit_amount]"] = $p['price'];
  $params["line_items[$i][price_data][product_data][name]"] = $p['name'];
  $params["line_items[$i][price_data][product_data][description]"] = $p['desc'];
  $subtotal += $p['price'] * $qty; $i++;
}
if ($i === 0) fail('Your cart is empty.');

foreach ($cfg['ship_to_countries'] as $k => $c) $params["shipping_address_collection[allowed_countries][$k]"] = $c;

$free = $subtotal >= $cfg['free_shipping_threshold'];
$params['shipping_options[0][shipping_rate_data][type]'] = 'fixed_amount';
$params['shipping_options[0][shipping_rate_data][display_name]'] = $free ? 'Free shipping' : 'Standard shipping';
$params['shipping_options[0][shipping_rate_data][fixed_amount][amount]'] = $free ? 0 : $cfg['shipping_flat'];
$params['shipping_options[0][shipping_rate_data][fixed_amount][currency]'] = $cfg['currency'];
$params['shipping_options[0][shipping_rate_data][delivery_estimate][minimum][unit]'] = 'business_day';
$params['shipping_options[0][shipping_rate_data][delivery_estimate][minimum][value]'] = 3;
$params['shipping_options[0][shipping_rate_data][delivery_estimate][maximum][unit]'] = 'business_day';
$params['shipping_options[0][shipping_rate_data][delivery_estimate][maximum][value]'] = 8;
if ($cfg['automatic_tax']) $params['automatic_tax[enabled]'] = 'true';

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query($params),
  CURLOPT_USERPWD => $cfg['stripe_secret_key'] . ':',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 20,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($res ?: '', true);
if ($code !== 200 || empty($data['url'])) {
  error_log('Stripe checkout error: ' . $res);
  fail('Checkout could not start. Please try again in a moment.', 502);
}
echo json_encode(['url' => $data['url']]);
