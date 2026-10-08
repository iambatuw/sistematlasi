const STORAGE_KEY = 'atlas-theme-v3';
const THEMES = ['light', 'dark'];

export function currentTheme() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (THEMES.includes(stored)) return stored;
    } catch (_) {
        // Use the page theme when storage is unavailable.
    }
    const attr = document.documentElement.getAttribute('data-theme');
    return THEMES.includes(attr) ? attr : 'light';
}

export function applyTheme(theme, persist = true) {
    const next = THEMES.includes(theme) ? theme : 'light';
    document.documentElement.setAttribute('data-theme', next);
    document.documentElement.dataset.theme = next;
    document.documentElement.style.colorScheme = next;
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', next === 'dark' ? '#121513' : '#f1f2ee');

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', next === 'light' ? 'true' : 'false');
    });

    if (persist) {
        try {
            localStorage.setItem(STORAGE_KEY, next);
        } catch (_) {
            // Keep the selected theme for this page even if storage is unavailable.
        }
    }
}

export function toggleTheme() {
    const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(next, true);
    return next;
}

window.toggleTheme = toggleTheme;
window.applyTheme = applyTheme;

export function initTheme() {
    applyTheme(currentTheme(), false);

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-theme-toggle], .theme-toggle');
        if (!button) return;
        event.preventDefault();
        toggleTheme();
    });

    window.addEventListener('storage', (event) => {
        if (event.key === STORAGE_KEY && THEMES.includes(event.newValue)) {
            applyTheme(event.newValue, false);
        }
    });
}
