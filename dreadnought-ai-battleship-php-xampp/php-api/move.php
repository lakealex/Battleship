<?php
require __DIR__ . DIRECTORY_SEPARATOR . "_common.php";

$body = read_json_body();
$grid = $body["playerGridVisibleToAI"] ?? null;
$history = $body["history"] ?? [];

if (!is_array($grid)) {
  json_response(["error" => "playerGridVisibleToAI must be a 2D array"], 400);
}
if (!is_array($history)) $history = [];

$historyText = "";
$recent = array_slice($history, -5);
foreach ($recent as $line) {
  $historyText .= $line . "\n";
}

$prompt = "Current game board (Player's side):\n" .
          json_encode($grid) . "\n\n" .
          "Key: 'empty' = untouched, 'hit' = you hit a ship, 'miss' = you missed.\n\n" .
          "Recent history of dialogue:\n" . $historyText . "\n" .
          "Strategic rules:\n" .
          "1. Pick a coordinate (r, c) between 0 and 9 that is 'empty'.\n" .
          "2. If you have a 'hit' nearby, try adjacent cells to sink the ship.\n" .
          "3. Be cunning.\n" .
          "4. Provide a short, menacing taunt.\n\n" .
          "Return ONLY valid JSON with keys r (int), c (int), taunt (string).";

$sys = "You are 'Admiral Obsidian', a cold, calculating, and slightly arrogant naval strategist playing a high-stakes game of Battleship.";

$result = gemini_generate_content($GEMINI_MODEL_MOVE, $GEMINI_API_KEY, $sys, $prompt, "application/json");

if (!$result["ok"]) {
  json_response(["error" => "Gemini API error", "details" => $result["error"]], 502);
}

$data = $result["data"];
// Try to pull the text part out:
$text = $data["candidates"][0]["content"]["parts"][0]["text"] ?? "";

$decoded = json_decode($text, true);
if (!is_array($decoded) || !isset($decoded["r"], $decoded["c"], $decoded["taunt"])) {
  // fall back: return raw for debugging
  json_response(["error" => "Model did not return valid JSON", "raw" => $text, "apiResponse" => $data], 502);
}

json_response([
  "r" => intval($decoded["r"]),
  "c" => intval($decoded["c"]),
  "taunt" => strval($decoded["taunt"]),
]);
