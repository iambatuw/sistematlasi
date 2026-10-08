export function initRecoveryConsole() {
    const consoleView = document.querySelector('[data-recovery-console]');
    if (!consoleView || consoleView.dataset.bound) return;
    consoleView.dataset.bound = 'true';
    const button = consoleView.querySelector('[data-recovery-action]');
    const status = consoleView.querySelector('[data-recovery-status]');
    const media = [...consoleView.querySelectorAll('[data-recovery-media]')];
    if (!button || !status || media.length === 0) return;

    let index = 0;
    const activate = () => {
        index = (index + 1) % media.length;
        consoleView.classList.remove('is-changing');
        void consoleView.offsetWidth;
        consoleView.classList.add('is-changing');
        media.forEach((item, itemIndex) => item.classList.toggle('is-active', itemIndex === index));
        const ready = index > 0;
        status.textContent = ready ? status.dataset.ready : status.dataset.missing;
        consoleView.dataset.state = ready ? 'ready' : 'missing';
    };

    button.addEventListener('click', activate);
    window.setTimeout(() => {
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) activate();
    }, 900);
}
