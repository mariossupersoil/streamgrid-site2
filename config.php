<?php
// ===================================================================
//  StreamGrid shop settings (safe to keep in GitHub).
//  Prices here are the real ones: the checkout always uses these,
//  so nobody can change a price in their browser.
//
//  PRIVATE settings (Stripe key, email addresses) live in a separate
//  file that is NOT in GitHub: streamgrid-secrets.php, in your Hostinger
//  HOME folder (above "domains"). See streamgrid-secrets.example.php.
// ===================================================================

// Look for the private file in every folder above this site, so it is found
// even when Hostinger runs the site from a build folder. First match wins.
$secrets = []; $secrets_found = false; $seen = [];
$starts = array_filter([__DIR__, $_SERVER['DOCUMENT_ROOT'] ?? '', getenv('HOME') ?: '']);
if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
  $pw = @posix_getpwuid(posix_geteuid()); if (!empty($pw['dir'])) $starts[] = $pw['dir'];
}
foreach ($starts as $dir) {
  for ($i = 0; $i < 8 && $dir && !isset($seen[$dir]); $i++) {
    $seen[$dir] = true;
    $f = rtrim($dir, '/') . '/streamgrid-secrets.php';
    if (@is_file($f)) { $loaded = require $f; if (is_array($loaded)) { $secrets = $loaded; $secrets_found = true; } break 2; }
    $up = dirname($dir); if ($up === $dir) break; $dir = $up;
  }
}

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
    'refill'    => ['name' => 'StreamGrid refill mat',      'desc' => '1 replacement mat (uses your existing clip)', 'price' => 2700],
    'clips'     => ['name' => 'Spare clip set',             'desc' => '2 clips and mounting tape',            'price' => 900],

    // StepGrip (shower) and StreamPet (pets)
    'stepgrip'     => ['name' => 'StepGrip foot scrubbing mat', 'desc' => 'Silicone shower mat with 28 suction cups', 'price' => 3400],
    'scrubber'     => ['name' => 'StepGrip Scrubber',           'desc' => 'Handheld silicone scrubber with grip handle', 'price' => 1700],
    'stepgrip-set' => ['name' => 'StepGrip Set',                'desc' => 'Foot scrubbing mat and hand scrubber', 'price' => 4500],
    'streampet'    => ['name' => 'StreamPet lick mat',          'desc' => 'Food-grade silicone lick mat with suction cups', 'price' => 3200],

    // Subscribe & save: billed today, then every 3 months. Always ships free.
    // First box includes clips and tape; later boxes are fresh mats only.
    'single-sub'    => ['name' => 'StreamGrid subscription — Single',    'desc' => '1 fresh mat every 3 months (first box includes clip and tape)',  'price' => 3300, 'every_months' => 3],
    'pair-sub'      => ['name' => 'StreamGrid subscription — Pair',      'desc' => '2 fresh mats every 3 months (first box includes clips and tape)', 'price' => 5900, 'every_months' => 3],
    'household-sub' => ['name' => 'StreamGrid subscription — Household', 'desc' => '3 fresh mats every 3 months (first box includes clips and tape)', 'price' => 8100, 'every_months' => 3],
  ],

  // Customers manage or cancel subscriptions here. Get the link from
  // Stripe > Settings > Billing > Customer portal. You can also set it in
  // streamgrid-secrets.php as 'portal_url'.
  'portal_url' => '',

  // 4. Shipping zones (prices in cents). The customer picks a zone in the cart,
  //    and Stripe only accepts addresses inside that zone.
  //    flat:         one-time orders
  //    free_over:    one-time orders ship free above this (0 = never free)
  //    sub_shipping: added to each subscription box (0 = free)
  //    CHECK REAL POSTAGE: the US and world rates are starting estimates.
  'shipping_zones' => [
    'CA'    => ['label' => 'Canada',            'countries' => ['CA'], 'flat' => 1200, 'free_over' => 7500, 'sub_shipping' => 0],
    'US'    => ['label' => 'United States',     'countries' => ['US'], 'flat' => 1800, 'free_over' => 0,    'sub_shipping' => 1800],
    'WORLD' => ['label' => 'Rest of the world', 'countries' => '*',    'flat' => 2800, 'free_over' => 0,    'sub_shipping' => 2800],
  ],
  // Countries to leave out of "Rest of the world", e.g. ['RU', 'BY'].
  'world_exclude' => [],

  // 5. Sales tax. Leave false until you have set up Stripe Tax
  //    (Stripe Dashboard > Tax). Then change to true.
  'automatic_tax' => false,

  'secrets_found' => false,

], $secrets, ['secrets_found' => $secrets_found]);
