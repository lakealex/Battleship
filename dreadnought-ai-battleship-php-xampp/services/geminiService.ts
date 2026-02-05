import { GRID_SIZE } from "../constants";

export interface AIMove {
  r: number;
  c: number;
  taunt: string;
}

/**
 * Calls the local PHP backend (XAMPP / Apache) which safely holds the GEMINI_API_KEY server-side.
 * Place the project in: C:\xampp\htdocs\<folder> and start Apache in XAMPP.
 */
export const getAIGameMove = async (
  playerGridVisibleToAI: string[][],
  history: string[]
): Promise<AIMove> => {
  try {
    const res = await fetch("/php-api/move.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ playerGridVisibleToAI, history }),
    });

    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const data = (await res.json()) as Partial<AIMove>;

    // Validation
    if (
      typeof data.r !== "number" ||
      data.r < 0 ||
      data.r >= GRID_SIZE ||
      typeof data.c !== "number" ||
      data.c < 0 ||
      data.c >= GRID_SIZE ||
      typeof data.taunt !== "string"
    ) {
      throw new Error("Invalid AI response payload");
    }

    return data as AIMove;
  } catch (error) {
    console.error("AI Error:", error);
    return {
      r: Math.floor(Math.random() * GRID_SIZE),
      c: Math.floor(Math.random() * GRID_SIZE),
      taunt: "Static interference... but I am still coming for you.",
    };
  }
};

export const getAITaunt = async (event: string): Promise<string> => {
  try {
    const res = await fetch("/php-api/taunt.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ event }),
    });

    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const data = (await res.json()) as { text?: string };
    return data.text || "Intriguing move.";
  } catch {
    return "I see what you are doing.";
  }
};
