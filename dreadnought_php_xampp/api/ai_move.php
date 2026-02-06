<?php
// POST /api/ai_move.php
// Body: { "playerGrid": string[][], "history": string[] }
// Returns: { r: number, c: number, taunt: string }

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Use POST']);
  exit;
}

require_once __DIR__ . '/../config.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON']);
  exit;
}
$grid = $data['playerGrid'] ?? null;
$history = $data['history'] ?? [];

if (!is_array($grid) || count($grid) !== 10) {
  http_response_code(400);
  echo json_encode(['error' => 'playerGrid must be 10x10']);
  exit;
}

// Build prompt (keep it simple & robust)
$prompt = "Current game board (Player side):\n" . json_encode($grid) . "\n\n"
        . "Key: 'empty' = untouched, 'hit' = you hit a ship, 'miss' = you missed, 'sunk' = sunk ship.\n\n"
        . "Recent dialogue history:\n" . implode("\n", array_slice($history, -6)) . "\n\n"
        . "Rules:\n"
        . "1) Pick coordinates (r,c) with 0<=r,c<=9 where the grid cell is 'empty'.\n"
        . "2) If you have a 'hit', prefer adjacent cells to finish a ship.\n"
        . "3) Reply ONLY with valid JSON: {\"r\":<int>,\"c\":<int>,\"taunt\":\"...\"}";

$body = [
  "contents" => [[
    "role" => "user",
    "parts" => [["text" => $prompt]]
  ]],
  // Try to bias toward JSON-only output.
  "generationConfig" => [
    "temperature" => 0.6,
    "responseMimeType" => "application/json"
  ],
  "systemInstruction" => [
    "parts" => [[
      "text" => "You are 'Admiral Obsidian', a cold, calculating, slightly arrogant naval strategist playing Battleship. Be concise."
    ]]
  ]
];

$model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-1.5-flash';
$url = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode($model) . ":generateContent";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Content-Type: application/json",
  "x-goog-api-key: " . GEMINI_API_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
curl_setopt($ch, CURLOPT_TIMEOUT, 25);

$response = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($response === false || $http < 200 || $http >= 300) {
  // Fallback random move, but return a helpful diagnostic string too.
  $fallback = random_empty_cell($grid);
  echo json_encode([
    "r" => $fallback[0],
    "c" => $fallback[1],
    "taunt" => "Static interference... but I am still coming for you.",
    "_debug" => [
      "http" => $http,
      "curl_error" => $err ?: null
    ]
  ]);
  exit;
}

// Parse Gemini response. In practice the JSON comes back in candidates[0].content.parts[0].text
$decoded = json_decode($response, true);
$text = null;

if (isset($decoded["candidates"][0]["content"]["parts"][0]["text"])) {
  $text = $decoded["candidates"][0]["content"]["parts"][0]["text"];
} elseif (isset($decoded["candidates"][0]["content"]["parts"][0]["inlineData"])) {
  $text = $decoded["candidates"][0]["content"]["parts"][0]["inlineData"];
}

$move = null;
if (is_string($text)) {
  $move = json_decode($text, true);
}

if (!is_array($move) || !isset($move["r"], $move["c"], $move["taunt"])) {
  $fallback = random_empty_cell($grid);
  echo json_encode([
    "r" => $fallback[0],
    "c" => $fallback[1],
    "taunt" => "Your defenses are... disappointing.",
    "_debug" => ["parse_failed" => true]
  ]);
  exit;
}

$r = intval($move["r"]);
$c = intval($move["c"]);
$taunt = strval($move["taunt"]);

if ($r < 0 || $r > 9 || $c < 0 || $c > 9) {
  $fallback = random_empty_cell($grid);
  echo json_encode([
    "r" => $fallback[0],
    "c" => $fallback[1],
    "taunt" => "Coordinates corrected. Your end remains inevitable."
  ]);
  exit;
}
if (!isset($grid[$r][$c]) || $grid[$r][$c] !== "empty") {
  $fallback = random_empty_cell($grid);
  echo json_encode([
    "r" => $fallback[0],
    "c" => $fallback[1],
    "taunt" => $taunt
  ]);
  exit;
}

echo json_encode(["r" => $r, "c" => $c, "taunt" => $taunt]);

function random_empty_cell($grid) {
  $empties = [];
  for ($r = 0; $r < 10; $r++) {
    for ($c = 0; $c < 10; $c++) {
      if (isset($grid[$r][$c]) && $grid[$r][$c] === "empty") $empties[] = [$r,$c];
    }
  }
  if (count($empties) === 0) return [rand(0,9), rand(0,9)];
  return $empties[array_rand($empties)];
}
