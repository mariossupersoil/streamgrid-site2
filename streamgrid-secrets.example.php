<?php
// TEMPLATE ONLY — this copy holds no real secrets.
//
// On Hostinger, create a file named  streamgrid-secrets.php  in your HOME
// folder (the top folder in File Manager, above "domains"), paste this in,
// and fill in your real values. It sits outside every website folder, so
// nobody can open it in a browser, and GitHub deploys never touch it.

return [
  'stripe_secret_key' => 'sk_test_...',            // Stripe > Developers > API keys
  'site_url'          => 'https://yourdomain.com', // no slash at the end
  'contact_to'        => 'you@example.com',        // where enquiries go
  'contact_from'      => 'hello@yourdomain.com',   // a mailbox on your Hostinger domain
];
