import Alpine from 'alpinejs';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

document.documentElement.classList.add('js');
gsap.registerPlugin(ScrollTrigger);

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// ------------------------------------------------------------ Rolagem suave
if (!reduced) {
    const lenis = new Lenis({ lerp: 0.1 });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);
}

// ------------------------------------------------------------ Cenas 3D (carregadas sob demanda)
const hero = document.getElementById('hero-canvas');
if (hero) import('./three/hero.js').then((m) => m.initHero(hero));

document.querySelectorAll('[data-viewer]').forEach((el) => import('./three/viewer.js').then((m) => m.initViewer(el)));

const thumbs = document.querySelectorAll('img[data-thumb]');
if (thumbs.length) import('./three/thumbnails.js').then((m) => m.initThumbnails(thumbs));

// ------------------------------------------------------------ Animações de entrada
function reveal() {
    if (reduced) return;

    gsap.utils.toArray('[data-reveal]').forEach((el) => {
        const children = el.hasAttribute('data-stagger') ? el.children : [el];
        if (el.hasAttribute('data-stagger')) gsap.set(el, { opacity: 1 });
        gsap.fromTo(
            children,
            { opacity: 0, y: 40, filter: 'blur(8px)' },
            {
                opacity: 1,
                y: 0,
                filter: 'blur(0px)',
                duration: 0.9,
                ease: 'power3.out',
                stagger: 0.08,
                scrollTrigger: { trigger: el, start: 'top 88%' },
            }
        );
    });

    // Título da home: letras sobem uma a uma
    document.querySelectorAll('[data-split]').forEach((el) => {
        const words = el.textContent.trim().split(' ');
        el.innerHTML = words
            .map((w) => `<span class="inline-block overflow-hidden pb-1 align-bottom"><span class="inline-block" data-word>${w}</span></span>`)
            .join(' ');
        gsap.from(el.querySelectorAll('[data-word]'), { yPercent: 110, duration: 1.1, ease: 'expo.out', stagger: 0.07, delay: 0.15 });
    });

    // Parallax
    gsap.utils.toArray('[data-parallax]').forEach((el) => {
        gsap.to(el, {
            yPercent: parseFloat(el.dataset.parallax || -20),
            ease: 'none',
            scrollTrigger: { trigger: el, scrub: true },
        });
    });

    // Contadores numéricos
    document.querySelectorAll('[data-count]').forEach((el) => {
        const end = parseFloat(el.dataset.count);
        const obj = { v: 0 };
        gsap.to(obj, {
            v: end,
            duration: 2,
            ease: 'power2.out',
            scrollTrigger: { trigger: el, start: 'top 90%' },
            onUpdate: () => (el.textContent = Math.round(obj.v).toLocaleString('pt-BR') + (el.dataset.suffix || '')),
        });
    });
}
reveal();

// ------------------------------------------------------------ Cards com inclinação 3D
if (!reduced && window.matchMedia('(pointer: fine)').matches) {
    document.querySelectorAll('.tilt').forEach((card) => {
        card.addEventListener('pointermove', (e) => {
            const r = card.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width;
            const y = (e.clientY - r.top) / r.height;
            card.style.transform = `perspective(900px) rotateX(${(0.5 - y) * 10}deg) rotateY(${(x - 0.5) * 12}deg) translateZ(0)`;
            card.style.setProperty('--gx', `${x * 100}%`);
            card.style.setProperty('--gy', `${y * 100}%`);
        });
        card.addEventListener('pointerleave', () => (card.style.transform = ''));
    });
}

// ------------------------------------------------------------ Botões magnéticos
if (!reduced && window.matchMedia('(pointer: fine)').matches) {
    document.querySelectorAll('[data-magnetic]').forEach((btn) => {
        btn.addEventListener('pointermove', (e) => {
            const r = btn.getBoundingClientRect();
            gsap.to(btn, { x: (e.clientX - r.left - r.width / 2) * 0.25, y: (e.clientY - r.top - r.height / 2) * 0.35, duration: 0.3 });
        });
        btn.addEventListener('pointerleave', () => gsap.to(btn, { x: 0, y: 0, duration: 0.5, ease: 'elastic.out(1, 0.4)' }));
    });
}

// ------------------------------------------------------------ Toasts
window.toast = (message, type = 'success') => {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
};

// ------------------------------------------------------------ Adicionar ao carrinho sem recarregar
document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form[data-add-to-cart]');
    if (!form || e.submitter?.name === 'buy_now') return;
    e.preventDefault();

    const button = form.querySelector('button[type=submit]');
    button && (button.disabled = true);
    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message);
        document.querySelectorAll('[data-cart-count]').forEach((el) => {
            el.textContent = data.count;
            el.classList.remove('hidden');
            gsap.fromTo(el, { scale: 1.8 }, { scale: 1, duration: 0.6, ease: 'elastic.out(1, 0.4)' });
        });
        window.toast(data.message);
    } catch (err) {
        window.toast(err.message || 'Não foi possível adicionar. Tente novamente.', 'error');
    } finally {
        button && (button.disabled = false);
    }
});

// ------------------------------------------------------------ Busca de endereço pelo CEP (ViaCEP)
document.querySelectorAll('input[data-cep]').forEach((input) => {
    input.addEventListener('input', async () => {
        let v = input.value.replace(/\D/g, '').slice(0, 8);
        input.value = v.length > 5 ? `${v.slice(0, 5)}-${v.slice(5)}` : v;
        if (v.length !== 8) return;

        try {
            const res = await fetch(`https://viacep.com.br/ws/${v}/json/`);
            const data = await res.json();
            if (data.erro) return window.toast('CEP não encontrado.', 'error');
            const form = input.form;
            const set = (name, value) => form.elements[name] && value && (form.elements[name].value = value);
            set('address', data.logradouro);
            set('district', data.bairro);
            set('city', data.localidade);
            set('state', data.uf);
            form.elements.number?.focus();
        } catch {
            /* sem internet: o cliente preenche manualmente */
        }
    });
});

// Máscara simples de telefone
document.querySelectorAll('input[data-phone]').forEach((input) => {
    input.addEventListener('input', () => {
        const v = input.value.replace(/\D/g, '').slice(0, 11);
        input.value = v.length > 10
            ? v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3')
            : v.replace(/(\d{2})(\d{0,4})(\d{0,4})/, (m, a, b, c) => (b ? `(${a}) ${b}${c ? '-' + c : ''}` : a));
    });
});

window.Alpine = Alpine;
Alpine.start();
