<?php
// ===================================================================
//  StreamGrid shop settings (safe to keep in GitHub).
//  Prices here are the real ones: the checkout always uses these,
//  so nobody can change a price in their browser.
//
//  PRIVATE settings (Stripe key, email addresses) live in a separate
//  file that is NOT in GitHub: streamgrid-secrets.php, one folder ABOVE
//  public_html on Hostinger. See streamgrid-secrets.example.php.
// ===================================================================

$secrets_file = dirname(__DIR__) . '/streamgrid-secrets.php';
$secrets = is_file($secrets_file) ? (require $secrets_file) : [];

return array_merge([

  // Defaults, used only until the private file exists.
  'stripe_secret_key' => 'sk_test_PASTE_YOUR_KEY_HERE',
  'site_url'          => 'https://yourdomain.com',
  'contact_to'        => '',
  'contact_from'      => '',

  'currency' => 'cad',

  // 3. Products. Prices are in CENTS: 3900 = $39.00
  'products' => [
    'single'    => ['name' => 'StreamGrid — The Single',    'desc' => '1 StreamGrid, 1 clip, mounting tape',  'price' => 3900],
    'pair'      => ['name' => 'StreamGrid — The Pair',      'desc' => '2 StreamGrid, 2 clips, mounting tape', 'price' => 6900],
    'household' => ['name' => 'StreamGrid — The Household', 'desc' => '3 StreamGrid, 3 clips, mounting tape', 'price' => 9500],
    'clips'     => ['name' => 'Spare clip set',             'desc' => '2 clips and mounting tape',            'price' => 900],
  ],

  // 4. Shipping (in cents). Free when the order reaches the threshold.
  'shipping_flat'           => 1200,   // $12.00
  'free_shipping_threshold' => 7500,   // $75.00
  'ship_to_countries'       => ['CA'], // add 'US' to ship to the United States

  // 5. Sales tax. Leave false until you have set up Stripe Tax
  //    (Stripe Dashboard > Tax). Then change to true.
  'automatic_tax' => false,

], $secrets);
