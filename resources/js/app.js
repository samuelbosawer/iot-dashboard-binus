import './bootstrap';

/* ---------- Progressive Web App ---------- */

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

const installButtons = document.querySelectorAll('[data-pwa-install]');
let deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    installButtons.forEach((button) => { button.hidden = false; });
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    installButtons.forEach((button) => { button.hidden = true; });
});

installButtons.forEach((button) => {
    button.addEventListener('click', async () => {
        if (!deferredInstallPrompt) return;

        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        installButtons.forEach((item) => { item.hidden = true; });
    });
});

/* ---------- Bottom tab bar: highlight the section in view ---------- */

const sectionTabs = [...document.querySelectorAll('[data-bottom-nav] a[data-section]')];
const sections = sectionTabs.map((tab) => document.getElementById(tab.dataset.section)).filter(Boolean);

if (sections.length) {
    const activate = (id) => {
        sectionTabs.forEach((tab) => {
            const isActive = tab.dataset.section === id;
            tab.toggleAttribute('data-active', isActive);
            isActive ? tab.setAttribute('aria-current', 'location') : tab.removeAttribute('aria-current');
        });
    };

    // A section counts as "current" while it crosses a thin band just below the sticky header.
    const observer = new IntersectionObserver((entries) => {
        entries.filter((entry) => entry.isIntersecting).forEach((entry) => activate(entry.target.id));
    }, { rootMargin: '-30% 0px -65% 0px' });

    activate(sections[0].id);
    sections.forEach((section) => observer.observe(section));
    sectionTabs.forEach((tab) => tab.addEventListener('click', () => activate(tab.dataset.section)));
}
