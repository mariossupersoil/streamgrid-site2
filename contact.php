<?php
// StreamGrid enquiry form. Sends each enquiry to your inbox, then shows thank-you.html.

$cfg  = require __DIR__ . '/config.php';
$to   = $cfg['contact_to'];      // set in streamgrid-secrets.php (private, not in GitHub)
$from = $cfg['contact_from'];

if ($_SERVER["REQUEST_METHOD"] !== "POST") { header("Location: index.html"); exit; }
if ($to === "" || $from === "") { header("Location: index.html#enquire"); exit; }

// Spam trap: real people never fill in the hidden "website" box.
if (!empty($_POST["website"])) { header("Location: thank-you.html"); exit; }

$name    = trim(strip_tags($_POST["name"] ?? ""));
$email   = trim($_POST["email"] ?? "");
$message = trim(strip_tags($_POST["message"] ?? ""));

if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header("Location: index.html#enquire"); exit;
}

$name = str_replace(["\r", "\n"], " ", $name);
$body = "New StreamGrid enquiry\n\nName: $name\nEmail: $email\n\nMessage:\n" . ($message !== "" ? $message : "(none)") . "\n";
$headers = "From: StreamGrid <$from>\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8\r\n";

mail($to, "New StreamGrid enquiry from $name", $body, $headers);
header("Location: thank-you.html");
exit;
