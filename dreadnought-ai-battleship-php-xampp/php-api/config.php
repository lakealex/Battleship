<?php
/**
 * XAMPP / Apache config for Gemini API.
 *
 * IMPORTANT:
 * - Put your API key in a local file OUTSIDE of Git (recommended), or set an Apache env var.
 * - For local class projects, simplest is to create: php-api/secret.php (NOT committed)
 *   that defines: $GEMINI_API_KEY = "YOUR_KEY";
 *
 * This file tries, in order:
 * 1) php-api/secret.php
 * 2) Environment variable GEMINI_API_KEY
 */

$GEMINI_MODEL_MOVE  = "gemini-3-flash-preview";
$GEMINI_MODEL_TAUNT = "gemini-3-flash-preview";

$GEMINI_API_KEY = null;

$secretPath = __DIR__ . DIRECTORY_SEPARATOR . "secret.php";
if (file_exists($secretPath)) {
  // secret.php should set $GEMINI_API_KEY
  require $secretPath;
}

if (!$GEMINI_API_KEY) {
  $env = getenv("GEMINI_API_KEY");
  if ($env) $GEMINI_API_KEY = $env;
}

if (!$GEMINI_API_KEY) {
  http_response_code(500);
  header("Content-Type: application/json");
  echo json_encode([
    "error" => "Missing GEMINI_API_KEY. Create php-api/secret.php or set an environment variable."
  ]);
  exit;
}
