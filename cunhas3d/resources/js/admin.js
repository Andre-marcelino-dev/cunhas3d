import Alpine from 'alpinejs';

window.toast = (message, type = 'success') => {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
};

// Pré-visualização 3D no formulário de produto do admin
document.querySelectorAll('[data-viewer]').forEach((el) => import('./three/viewer.js').then((m) => m.initViewer(el)));

window.Alpine = Alpine;
Alpine.start();
