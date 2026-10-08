function initProgress() {
    const bar = document.querySelector('[data-reading-progress] span');
    if (!bar) return;
    let scheduled = false;
    const update = () => {
        const range = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
        bar.style.transform = `scaleX(${Math.min(1, window.scrollY / range)})`;
        scheduled = false;
    };
    const schedule = () => {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(update);
    };
    update();
    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });
}

export function initMotion() {
    initProgress();
}
