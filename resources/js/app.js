// Scroll-Driven Reveal Animations via IntersectionObserver
function initScrollReveals() {
    const reveals = document.querySelectorAll('.reveal-on-scroll:not(.is-visible)');
    if (!reveals.length) return;

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px -40px 0px',
            threshold: 0.1
        });

        reveals.forEach(el => observer.observe(el));
    } else {
        // Fallback for older browsers
        reveals.forEach(el => el.classList.add('is-visible'));
    }
}

document.addEventListener('DOMContentLoaded', initScrollReveals);
document.addEventListener('livewire:navigated', initScrollReveals);
window.addEventListener('load', initScrollReveals);
