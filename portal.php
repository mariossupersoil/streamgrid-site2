<?php
// Sends subscribers to Stripe's page where they can update their card or cancel.
$cfg = require __DIR__ . '/config.php';
if (!empty($cfg['portal_url']) && preg_match('#^https://billing\.stripe\.com/#', $cfg['portal_url'])) {
  header('Location: ' . $cfg['portal_url']); exit;
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage your subscription — StreamGrid</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#F7F5F0;color:#1F2427;font-family:system-ui,sans-serif;text-align:center;padding:24px}p{color:#5C6468;max-width:30em;line-height:1.7}a{color:#233A40}</style></head>
<body><main><h1 style="font-weight:500">Manage your subscription</h1><p>To change or cancel your subscription, reply to any StreamGrid receipt email and we will take care of it right away.</p><p><a href="index.html">Back to StreamGrid</a></p></main></body></html>
