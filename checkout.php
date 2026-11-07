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
$zone_id = (string)($in['zone'] ?? 'CA');
if (!isset($cfg['shipping_zones'][$zone_id])) $zone_id = 'CA';
$zone = $cfg['shipping_zones'][$zone_id];
if ($zone['countries'] === '*') {
  $others = [];
  foreach ($cfg['shipping_zones'] as $z) if (is_array($z['countries'])) $others = array_merge($others, $z['countries']);
  $zone['countries'] = array_values(array_diff(require __DIR__ . '/stripe-countries.php', $others, $cfg['world_exclude']));
}

// Rebuild the order from OUR prices only.
$params = [
  'mode' => 'payment',
  'success_url' => $cfg['site_url'] . '/success.html?session_id={CHECKOUT_SESSION_ID}',
  'cancel_url'  => $cfg['site_url'] . '/' . (in_array($in['page'] ?? '', ['index.html', 'stepgrip.html', 'streampet.html'], true) ? $in['page'] : 'index.html') . '#shop',
  'billing_address_collection' => 'auto',
  'phone_number_collection[enabled]' => 'true',
];
$subtotal = 0; $i = 0; $has_sub = false;
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
  if (!empty($p['every_months'])) {                 // a subscription item
    $params["line_items[$i][price_data][recurring][interval]"] = 'month';
    $params["line_items[$i][price_data][recurring][interval_count]"] = (int)$p['every_months'];
    $has_sub = true;
  }
  $subtotal += $p['price'] * $qty; $i++;
}
if ($i === 0) fail('Your cart is empty.');

foreach ($zone['countries'] as $k => $c) $params["shipping_address_collection[allowed_countries][$k]"] = $c;

if ($has_sub) {
  // Subscription checkout: billed today and every 3 months. Anything else in
  // the same order is charged once. Shipping, if any, is added to each box.
  $params['mode'] = 'subscription';
  if ($zone['sub_shipping'] > 0) {
    $params["line_items[$i][quantity]"] = 1;
    $params["line_items[$i][price_data][currency]"] = $cfg['currency'];
    $params["line_items[$i][price_data][unit_amount]"] = $zone['sub_shipping'];
    $params["line_items[$i][price_data][product_data][name]"] = 'Shipping to ' . $zone['label'];
    $params["line_items[$i][price_data][recurring][interval]"] = 'month';
    $params["line_items[$i][price_data][recurring][interval_count]"] = 3;
    $i++;
  }
} else {
$free = $zone['free_over'] > 0 && $subtotal >= $zone['free_over'];
$params['shipping_options[0][shipping_rate_data][type]'] = 'fixed_amount';
$params['shipping_options[0][shipping_rate_data][display_name]'] = ($free ? 'Free shipping' : 'Standard shipping') . ' — ' . $zone['label'];
$params['shipping_options[0][shipping_rate_data][fixed_amount][amount]'] = $free ? 0 : $zone['flat'];
$params['shipping_options[0][shipping_rate_data][fixed_amount][currency]'] = $cfg['currency'];
$params['shipping_options[0][shipping_rate_data][delivery_estimate][minimum][unit]'] = 'business_day';
$params['shipping_options[0][shipping_rate_data][delivery_estimate][minimum][value]'] = 3;
$params['shipping_options[0][shipping_rate_data][delivery_estimate][maximum][unit]'] = 'business_day';
$params['shipping_options[0][shipping_rate_data][delivery_estimate][maximum][value]'] = $zone_id === 'CA' ? 8 : ($zone_id === 'US' ? 12 : 25);
}
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
