
/**
 * Dreadnought AI — Battleship (Vanilla JS)
 * Mirrors the core rules from the original React/TS project:
 * - 10x10 grids
 * - Ship placement with orientation
 * - Hit/Miss/Sunk
 * - HARD mode calls PHP Gemini proxy for AI moves
 */

const GRID_SIZE = 10;
const DEFAULT_SHIPS = [
  { name: "Carrier", size: 5, color: "yellow" },
  { name: "Battleship", size: 4, color: "orange" },
  { name: "Cruiser", size: 3, color: "cyan" },
  { name: "Submarine", size: 3, color: "purple" },
  { name: "Destroyer", size: 2, color: "rose" },
];

const Phase = {
  SETUP: "SETUP",
  PLACEMENT: "PLACEMENT",
  PLAYER_TURN: "PLAYER_TURN",
  AI_TURN: "AI_TURN",
  GAME_OVER: "GAME_OVER",
};

function initialGrid() {
  return Array.from({ length: GRID_SIZE }, () => Array.from({ length: GRID_SIZE }, () => "empty"));
}

function deepCopyGrid(grid) {
  return grid.map(row => row.slice());
}

function canPlaceShip(grid, r, c, size, orient) {
  if (orient === "horizontal") {
    if (c + size > GRID_SIZE) return false;
    for (let i = 0; i < size; i++) if (grid[r][c + i] !== "empty") return false;
  } else {
    if (r + size > GRID_SIZE) return false;
    for (let i = 0; i < size; i++) if (grid[r + i][c] !== "empty") return false;
  }
  return true;
}

function coordsForPlacement(r, c, size, orient) {
  const coords = [];
  for (let i = 0; i < size; i++) {
    coords.push({
      r: orient === "vertical" ? r + i : r,
      c: orient === "horizontal" ? c + i : c,
    });
  }
  return coords;
}

function findShipAt(ships, r, c) {
  return ships.find(s => s.coordinates.some(p => p.r === r && p.c === c));
}

function coordLabel(r, c) {
  return `${String.fromCharCode(65 + c)}${r}`;
}

// --- Game state ---
let state;

function newGame() {
  state = {
    playerGrid: initialGrid(),
    aiGrid: initialGrid(),
    playerShips: [],
    aiShips: [],
    phase: Phase.SETUP,
    difficulty: document.getElementById("difficulty").value,
    configuredShips: DEFAULT_SHIPS.map(s => ({ ...s })),
    orientation: "horizontal",
    currentShipIndex: 0,
    winner: null,
    logs: ["Tactical Command Online. Fleet abilities synchronized."],
    isAiProcessing: false,
  };
  renderAll();
}

function log(line) {
  state.logs.push(line);
  renderLogs();
}

function setPhase(phase) {
  state.phase = phase;
  renderStatus();
}

function setupAI() {
  const aiGrid = initialGrid();
  const aiShips = [];

  state.configuredShips.forEach((ship, idx) => {
    let placed = false;
    while (!placed) {
      const r = Math.floor(Math.random() * GRID_SIZE);
      const c = Math.floor(Math.random() * GRID_SIZE);
      const orient = Math.random() > 0.5 ? "horizontal" : "vertical";
      if (canPlaceShip(aiGrid, r, c, ship.size, orient)) {
        const coords = coordsForPlacement(r, c, ship.size, orient);
        coords.forEach(p => (aiGrid[p.r][p.c] = "ship"));
        aiShips.push({ ...ship, id: `ai-${idx}`, coordinates: coords, hits: 0, orientation: orient, isSunk: false });
        placed = true;
      }
    }
  });

  state.aiGrid = aiGrid;
  state.aiShips = aiShips;
  setPhase(Phase.PLAYER_TURN);
  log("AI Fleet initialized. Engage at will.");
  renderAll();
}

// From original: checkShot updates grid and ships, returns result + sunk ship.
function checkShot(grid, ships, r, c) {
  const newGrid = deepCopyGrid(grid);
  const newShips = ships.map(s => ({ ...s, coordinates: s.coordinates.map(p => ({ ...p })) }));
  let result = "miss";
  let sunkShip = null;
  const logs = [];

  const target = findShipAt(newShips, r, c);
  if (target) {
    result = "hit";
    target.hits += 1;
    newGrid[r][c] = "hit";
    if (target.hits >= target.size) {
      target.isSunk = true;
      sunkShip = target;
      target.coordinates.forEach(p => (newGrid[p.r][p.c] = "sunk"));
      logs.push(`Target destroyed: ${target.name}.`);
    }
  } else {
    newGrid[r][c] = "miss";
  }

  return { newGrid, newShips, result, sunkShip, logs };
}

function allSunk(ships) {
  return ships.length > 0 && ships.every(s => s.isSunk);
}

// --- UI builders ---
function buildGrid(containerId, onCellClick, showShips) {
  const container = document.getElementById(containerId);
  container.innerHTML = "";

  const grid = containerId === "playerGrid" ? state.playerGrid : state.aiGrid;

  const wrapper = document.createElement("div");
  wrapper.className = "inline-block border border-slate-700 rounded-xl overflow-hidden";

  const table = document.createElement("div");
  table.className = "grid";
  table.style.gridTemplateColumns = `repeat(${GRID_SIZE}, 2.2rem)`;

  for (let r = 0; r < GRID_SIZE; r++) {
    for (let c = 0; c < GRID_SIZE; c++) {
      const cell = document.createElement("button");
      cell.className =
        "w-[2.2rem] h-[2.2rem] border border-slate-800 flex items-center justify-center text-xs " +
        "hover:bg-slate-800/70 active:bg-slate-700/70";
      cell.dataset.r = r;
      cell.dataset.c = c;

      const v = grid[r][c];
      let symbol = "⬛";
      if (v === "miss") symbol = "●";
      if (v === "hit") symbol = "✳️";
      if (v === "sunk") symbol = "💥";
      if (showShips && v === "ship") symbol = "🚢";

      cell.textContent = symbol;

      cell.addEventListener("click", () => onCellClick(r, c));
      table.appendChild(cell);
    }
  }

  wrapper.appendChild(table);
  container.appendChild(wrapper);
}

function renderShipList() {
  const shipList = document.getElementById("shipList");
  shipList.innerHTML = "";

  state.configuredShips.forEach((s, idx) => {
    const placed = state.playerShips.some(ps => ps.name === s.name);
    const isActive = state.phase === Phase.PLACEMENT && idx === state.currentShipIndex;

    const card = document.createElement("div");
    card.className =
      "flex items-center justify-between rounded-xl border px-3 py-2 " +
      (placed ? "border-emerald-700/60 bg-emerald-950/30" : isActive ? "border-indigo-500/60 bg-indigo-950/30" : "border-slate-800 bg-slate-900/40");

    card.innerHTML = `
      <div>
        <div class="text-sm font-semibold">${s.name}</div>
        <div class="text-xs text-slate-400">Size: ${s.size}</div>
      </div>
      <div class="text-xs ${placed ? "text-emerald-300" : isActive ? "text-indigo-300" : "text-slate-400"}">${placed ? "DEPLOYED" : isActive ? "NEXT" : "PENDING"}</div>
    `;
    shipList.appendChild(card);
  });

  const help = document.getElementById("placementHelp");
  if (state.phase === Phase.SETUP) {
    help.textContent = "Click “Start Placement”, then place ships by clicking your grid.";
  } else if (state.phase === Phase.PLACEMENT) {
    const s = state.configuredShips[state.currentShipIndex];
    help.textContent = s ? `Placing: ${s.name} (size ${s.size}) — Orientation: ${state.orientation}` : "";
  } else {
    help.textContent = "Placement locked.";
  }
}

function renderLogs() {
  const logs = document.getElementById("logs");
  logs.innerHTML = "";
  state.logs.slice().reverse().forEach((line) => {
    const p = document.createElement("p");
    p.className = "text-slate-200/90";
    p.textContent = line;
    logs.appendChild(p);
  });
}

function renderStatus() {
  document.getElementById("orientationLabel").textContent = state.orientation === "horizontal" ? "Horizontal" : "Vertical";
  document.getElementById("statusPill").textContent = state.phase;
  document.getElementById("turnLabel").textContent =
    state.phase === Phase.PLAYER_TURN ? "Your turn" :
    state.phase === Phase.AI_TURN ? "Enemy turn" :
    state.phase === Phase.GAME_OVER ? (state.winner === "player" ? "You win" : "AI wins") :
    "";
  document.getElementById("startBattle").disabled = !(state.phase === Phase.SETUP);
  document.getElementById("startBattle").classList.toggle("opacity-50", state.phase !== Phase.SETUP);
}

function renderAll() {
  buildGrid("playerGrid", onPlayerGridClick, true);
  buildGrid("aiGrid", onAiGridClick, false);
  renderShipList();
  renderLogs();
  renderStatus();
}

// --- Event handlers ---
function onPlayerGridClick(r, c) {
  if (state.phase !== Phase.PLACEMENT) return;

  const ship = state.configuredShips[state.currentShipIndex];
  if (!ship) return;

  if (!canPlaceShip(state.playerGrid, r, c, ship.size, state.orientation)) {
    log(`Invalid placement for ${ship.name} at ${coordLabel(r, c)}.`);
    return;
  }

  const coords = coordsForPlacement(r, c, ship.size, state.orientation);
  const newGrid = deepCopyGrid(state.playerGrid);
  coords.forEach(p => (newGrid[p.r][p.c] = "ship"));

  state.playerGrid = newGrid;
  state.playerShips.push({ ...ship, id: `p-${state.playerShips.length}`, coordinates: coords, hits: 0, orientation: state.orientation, isSunk: false });

  log(`${ship.name} deployed at ${coordLabel(r, c)}.`);
  state.currentShipIndex += 1;

  if (state.playerShips.length === state.configuredShips.length) {
    log("All ships deployed. Initializing AI...");
    setupAI();
    return;
  }

  renderAll();
}

async function onAiGridClick(r, c) {
  if (state.phase !== Phase.PLAYER_TURN) return;
  if (state.isAiProcessing) return;

  const v = state.aiGrid[r][c];
  if (v === "hit" || v === "sunk" || v === "miss") return;

  // Player fires
  const shot = checkShot(state.aiGrid, state.aiShips, r, c);
  state.aiGrid = shot.newGrid;
  state.aiShips = shot.newShips;

  log(`Fire mission ${coordLabel(r, c)}: [${shot.result.toUpperCase()}]`);
  shot.logs.forEach(log);

  if (allSunk(state.aiShips)) {
    state.winner = "player";
    setPhase(Phase.GAME_OVER);
    renderAll();
    return;
  }

  setPhase(Phase.AI_TURN);
  renderAll();
  await processAiMove();
}

async function processAiMove() {
  if (state.phase !== Phase.AI_TURN) return;
  state.isAiProcessing = true;

  let move;
  if (state.difficulty === "HARD") {
    // Hide ships from AI: convert "ship" -> "empty"
    const visible = state.playerGrid.map(row => row.map(cell => (cell === "ship" ? "empty" : cell)));
    try {
      const res = await fetch("api/ai_move.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ playerGrid: visible, history: state.logs.slice(-10) }),
      });
      const data = await res.json();
      move = { r: data.r, c: data.c, taunt: data.taunt || "..." };
      if (typeof move.r !== "number" || typeof move.c !== "number") throw new Error("Bad AI response");
      log(`ADMIRAL OBSIDIAN: "${move.taunt}"`);
    } catch (e) {
      move = randomUnshotCell(state.playerGrid);
      log(`ADMIRAL OBSIDIAN: "Signal corrupted... firing anyway."`);
    }
  } else {
    move = randomUnshotCell(state.playerGrid);
  }

  const shot = checkShot(state.playerGrid, state.playerShips, move.r, move.c);
  state.playerGrid = shot.newGrid;
  state.playerShips = shot.newShips;

  log(`INCOMING FIRE: ${coordLabel(move.r, move.c)}: [${shot.result.toUpperCase()}]`);
  shot.logs.forEach(log);

  if (allSunk(state.playerShips)) {
    state.winner = "ai";
    setPhase(Phase.GAME_OVER);
    state.isAiProcessing = false;
    renderAll();
    return;
  }

  // Return control
  setPhase(Phase.PLAYER_TURN);
  state.isAiProcessing = false;
  renderAll();
}

function randomUnshotCell(grid) {
  const candidates = [];
  for (let r = 0; r < GRID_SIZE; r++) {
    for (let c = 0; c < GRID_SIZE; c++) {
      if (grid[r][c] !== "hit" && grid[r][c] !== "sunk" && grid[r][c] !== "miss") candidates.push({ r, c });
    }
  }
  return candidates[Math.floor(Math.random() * candidates.length)];
}

// --- Wire up controls ---
document.getElementById("newGame").addEventListener("click", newGame);
document.getElementById("difficulty").addEventListener("change", (e) => {
  if (!state) return;
  state.difficulty = e.target.value;
  log(`Difficulty set to ${state.difficulty}.`);
});
document.getElementById("toggleOrientation").addEventListener("click", () => {
  if (state.phase !== Phase.PLACEMENT) return;
  state.orientation = state.orientation === "horizontal" ? "vertical" : "horizontal";
  renderStatus();
  renderShipList();
});
document.getElementById("startBattle").addEventListener("click", () => {
  if (state.phase !== Phase.SETUP) return;
  setPhase(Phase.PLACEMENT);
  log("Begin ship deployment. Place your fleet.");
  renderAll();
});

// init
newGame();
