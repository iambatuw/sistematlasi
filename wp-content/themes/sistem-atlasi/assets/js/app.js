import { initTheme } from './theme.js?ver=1.0.5';
import { initMotion } from './motion.js?ver=1.0.5';

function initialize() {
    initTheme();
    initMotion();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
} else {
    initialize();
}
