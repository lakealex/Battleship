<?php
// Dreadnought AI Battleship (XAMPP-ready)
// 1) Put this folder inside: C:\xampp\htdocs\dreadnought
// 2) Set your Gemini API key in config.php
// 3) Open: http://localhost/dreadnought/
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dreadnought AI — Battleship (PHP/XAMPP)</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100">
  <div class="max-w-6xl mx-auto p-4 md:p-8">
    <header class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
      <div>
        <h1 class="text-2xl md:text-3xl font-semibold tracking-tight">Dreadnought AI — Battleship</h1>
        <p class="text-slate-300 text-sm">Vanilla JS front-end + PHP proxy (works on XAMPP). No Node/Vite required.</p>
      </div>
      <div class="flex items-center gap-3">
        <label class="text-sm text-slate-300">Difficulty</label>
        <select id="difficulty" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
          <option value="EASY">Easy (random AI)</option>
          <option value="MEDIUM" selected>Medium (random AI)</option>
          <option value="HARD">Hard (Gemini AI)</option>
        </select>
        <button id="newGame" class="bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 rounded-lg px-4 py-2 text-sm font-medium">
          New Game
        </button>
      </div>
    </header>

    <main class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
      <section class="lg:col-span-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4">
            <div class="flex items-center justify-between mb-3">
              <h2 class="font-semibold">Your Fleet</h2>
              <div class="flex items-center gap-2">
                <button id="toggleOrientation" class="bg-slate-800 hover:bg-slate-700 rounded-lg px-3 py-2 text-xs font-medium">
                  Orientation: <span id="orientationLabel">Horizontal</span>
                </button>
                <button id="startBattle" class="bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 rounded-lg px-3 py-2 text-xs font-medium">
                  Start Placement
                </button>
              </div>
            </div>
            <div id="playerGrid" class="select-none"></div>
            <div class="mt-4">
              <h3 class="text-sm font-semibold text-slate-200">Place ships (click cells)</h3>
              <p id="placementHelp" class="text-xs text-slate-400 mt-1"></p>
              <div id="shipList" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2"></div>
            </div>
          </div>

          <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4">
            <div class="flex items-center justify-between mb-3">
              <h2 class="font-semibold">Enemy Waters</h2>
              <span id="turnLabel" class="text-xs text-slate-300"></span>
            </div>
            <div id="aiGrid" class="select-none"></div>
            <p class="text-xs text-slate-400 mt-3">
              During battle: click enemy cells to fire.
              <span class="block mt-1">Hard mode uses Gemini via <code class="text-slate-200">/api/ai_move.php</code>.</span>
            </p>
          </div>
        </div>
      </section>

      <aside class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold">Tactical Log</h2>
          <span id="statusPill" class="text-xs px-2 py-1 rounded-full bg-slate-800 text-slate-200">SETUP</span>
        </div>
        <div id="logs" class="mt-3 h-[520px] overflow-auto text-xs leading-relaxed space-y-2 pr-2"></div>
        <div class="mt-4 text-xs text-slate-400">
          <p><span class="font-semibold text-slate-200">Legend:</span> ⬛ empty, 🚢 your ship, ✳️ hit, ● miss, 💥 sunk</p>
        </div>
      </aside>
    </main>

    <footer class="mt-8 text-xs text-slate-500">
      Tip: If you don't want to use Gemini, keep difficulty on Easy/Medium.
    </footer>
  </div>

  <script src="assets/app.js"></script>
</body>
</html>
