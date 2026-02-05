<div align="center">
<img width="1200" height="475" alt="GHBanner" src="https://github.com/user-attachments/assets/0aa67016-6eaf-458a-adb2-6e31a0763ed6" />
</div>

# Run and deploy your AI Studio app

This contains everything you need to run your app locally.

View your app in AI Studio: https://ai.studio/apps/drive/15PL4_OxVGND8GVTVvGPdJFIpDgfYBE9K

## Run Locally

**Prerequisites:**  Node.js


1. Install dependencies:
   `npm install`
2. Set the `GEMINI_API_KEY` in [.env.local](.env.local) to your Gemini API key
3. Run the app:
   `npm run dev`


## Run with XAMPP (PHP backend)

This project was converted to use a **PHP backend** for Gemini so you can run it with **XAMPP**.
Your Gemini API key stays server-side (recommended by Google).

### 1) Build the frontend
Install Node (LTS), then from the project folder:

```bash
npm install
npm run build
```

This creates a `dist/` folder.

### 2) Copy into XAMPP htdocs
Create a folder like:

`C:\xampp\htdocs\dreadnought`

Copy these into it:
- the entire `dist/` folder **contents** (index.html + assets)
- the `php-api/` folder (from this repo)

Your folder should look like:
- `C:\xampp\htdocs\dreadnought\index.html`
- `C:\xampp\htdocs\dreadnought\assets\...`
- `C:\xampp\htdocs\dreadnought\php-api\move.php`
- `C:\xampp\htdocs\dreadnought\php-api\taunt.php`

### 3) Add your Gemini API key
Copy:

`php-api/secret.php.example` → `php-api/secret.php`

Then edit `secret.php` and paste your key.

### 4) Start Apache and test
In XAMPP Control Panel: **Start Apache**

Then open:
`http://localhost/dreadnought/`

### Notes
- If your key/model name changes, edit `php-api/config.php`.
- PHP requires the `curl` extension enabled (it is enabled by default in most XAMPP installs).
