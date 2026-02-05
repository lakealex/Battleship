<?php
require __DIR__ . DIRECTORY_SEPARATOR . "config.php";

function read_json_body() {
  $raw = file_get_contents("php://input");
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function gemini_generate_content($modelName, $apiKey, $systemInstruction, $userText, $responseMimeType = null) {
  $url = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode($modelName) . ":generateContent?key=" . urlencode($apiKey);

  $payload = [
    "contents" => [
      [
        "role" => "user",
        "parts" => [
          ["text" => $userText]
        ]
      ]
    ]
  ];

  // System instruction (supported by the Gemini API)
  if ($systemInstruction) {
    $payload["systemInstruction"] = [
      "role" => "system",
      "parts" => [
        ["text" => $systemInstruction]
      ]
    ];
  }

  // Ask for JSON if desired (not all models support strict JSON schema in REST;
  // we keep it simple and just instruct the model in the prompt.)
  if ($responseMimeType) {
    $payload["generationConfig"] = [
      "responseMimeType" => $responseMimeType
    ];
  }

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

  $resp = curl_exec($ch);
  $errno = curl_errno($ch);
  $err = curl_error($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($errno) {
    return ["ok" => false, "status" => 0, "error" => $err];
  }

  $decoded = json_decode($resp, true);
  if ($status < 200 || $status >= 300) {
    return ["ok" => false, "status" => $status, "error" => $decoded ?: $resp];
  }

  return ["ok" => true, "status" => $status, "data" => $decoded];
}

function json_response($obj, $status = 200) {
  http_response_code($status);
  header("Content-Type: application/json");
  // Helpful for dev
  header("Cache-Control: no-store");
  echo json_encode($obj);
  exit;
}
