const STORAGE_KEY = "theme";
const MODES = ["system", "light", "dark"];

const LABELS = {
	system: "Colour scheme: System",
	light: "Colour scheme: Light",
	dark: "Colour scheme: Dark",
};

const THEME_COLORS = {
	light: "#f6f8fa",
	dark: "#161b22",
};

function getStoredTheme() {
	const stored = localStorage.getItem(STORAGE_KEY);
	return MODES.includes(stored) ? stored : "system";
}

function getEffectiveTheme(mode = getStoredTheme()) {
	if (mode === "light" || mode === "dark") {
		return mode;
	}

	return window.matchMedia("(prefers-color-scheme: dark)").matches
		? "dark"
		: "light";
}

function applyTheme(mode) {
	const root = document.documentElement;

	if (mode === "light" || mode === "dark") {
		root.dataset.theme = mode;
	} else {
		delete root.dataset.theme;
	}

	updateToggleButton(mode);
	updateMetaThemeColor(mode);
}

function updateToggleButton(mode) {
	const button = document.querySelector("[data-theme-toggle]");
	if (!button) {
		return;
	}

	button.dataset.themeMode = mode;
	button.setAttribute("aria-label", LABELS[mode]);
	button.setAttribute("title", LABELS[mode]);
}

function updateMetaThemeColor(mode) {
	const meta = document.querySelector('meta[name="theme-color"]');
	if (!meta) {
		return;
	}

	meta.setAttribute("content", THEME_COLORS[getEffectiveTheme(mode)]);
}

function cycleTheme() {
	const current = getStoredTheme();
	const next = MODES[(MODES.indexOf(current) + 1) % MODES.length];

	if (next === "system") {
		localStorage.removeItem(STORAGE_KEY);
	} else {
		localStorage.setItem(STORAGE_KEY, next);
	}

	applyTheme(next);
}

function initThemeToggle() {
	const button = document.querySelector("[data-theme-toggle]");
	if (!button) {
		return;
	}

	applyTheme(getStoredTheme());
	button.addEventListener("click", cycleTheme);

	window
		.matchMedia("(prefers-color-scheme: dark)")
		.addEventListener("change", () => {
			if (getStoredTheme() === "system") {
				updateMetaThemeColor("system");
			}
		});
}

initThemeToggle();
