import * as THREE from 'three';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';
import { makeGeometry } from './shapes.js';

/**
 * Cena principal da home: um objeto sendo "impresso" camada por camada,
 * com o bico da impressora percorrendo a camada atual.
 */
export function initHero(canvas) {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.75));
    renderer.localClippingEnabled = true;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.outputColorSpace = THREE.SRGBColorSpace;

    const scene = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;

    const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 100);
    camera.position.set(0, 1.4, 7.2);

    const rig = new THREE.Group();
    scene.add(rig);

    // ---------- Mesa de impressão
    const plate = new THREE.Mesh(
        new THREE.CylinderGeometry(1.75, 1.75, 0.08, 96),
        new THREE.MeshPhysicalMaterial({ color: 0x0b0b16, metalness: 0.2, roughness: 0.7, envMapIntensity: 0.25 })
    );
    plate.position.y = -1.44;
    rig.add(plate);

    const ring = new THREE.Mesh(
        new THREE.TorusGeometry(1.75, 0.012, 8, 160),
        new THREE.MeshBasicMaterial({ color: 0x22d3ee })
    );
    ring.rotation.x = Math.PI / 2;
    ring.position.y = -1.4;
    rig.add(ring);

    const grid = new THREE.PolarGridHelper(1.7, 16, 6, 64, 0x2b2b4a, 0x1a1a2e);
    grid.position.y = -1.395;
    rig.add(grid);

    // ---------- Objeto sendo impresso
    const geometry = makeGeometry('torusknot');
    geometry.scale(1.25, 1.25, 1.25);
    geometry.computeBoundingBox();
    const minY = geometry.boundingBox.min.y;
    const maxY = geometry.boundingBox.max.y;

    const clip = new THREE.Plane(new THREE.Vector3(0, -1, 0), minY);

    const printed = new THREE.Mesh(
        geometry,
        new THREE.MeshPhysicalMaterial({
            color: 0x22d3ee,
            roughness: 0.3,
            clearcoat: 0.8,
            iridescence: 0.6,
            iridescenceIOR: 1.4,
            clippingPlanes: [clip],
            clipShadows: true,
        })
    );
    // Face interna brilhante: simula a "camada quente" no corte
    const inner = new THREE.Mesh(
        geometry,
        new THREE.MeshBasicMaterial({ color: 0xf472b6, side: THREE.BackSide, clippingPlanes: [clip] })
    );
    const piece = new THREE.Group();
    piece.add(printed, inner);
    piece.position.y = 0.05;
    rig.add(piece);

    // ---------- Bico da impressora
    const nozzle = new THREE.Group();
    const metal = new THREE.MeshPhysicalMaterial({ color: 0xd4d4d8, metalness: 1, roughness: 0.25 });
    const block = new THREE.Mesh(new THREE.BoxGeometry(0.34, 0.24, 0.34), new THREE.MeshPhysicalMaterial({ color: 0x1e1b4b, metalness: 0.5, roughness: 0.4 }));
    block.position.y = 0.42;
    const tip = new THREE.Mesh(new THREE.ConeGeometry(0.09, 0.22, 24), metal);
    tip.rotation.x = Math.PI;
    tip.position.y = 0.2;
    const glow = new THREE.Mesh(new THREE.SphereGeometry(0.035, 16, 16), new THREE.MeshBasicMaterial({ color: 0xffb86b }));
    glow.position.y = 0.08;
    const tipLight = new THREE.PointLight(0xff9a3c, 3, 1.6, 2);
    tipLight.position.y = 0.08;
    nozzle.add(block, tip, glow, tipLight);
    rig.add(nozzle);

    // Linha de laser na camada atual
    const layerLine = new THREE.Mesh(
        new THREE.RingGeometry(1.6, 1.66, 96),
        new THREE.MeshBasicMaterial({ color: 0xa78bfa, transparent: true, opacity: 0.35, side: THREE.DoubleSide, blending: THREE.AdditiveBlending })
    );
    layerLine.rotation.x = -Math.PI / 2;
    rig.add(layerLine);

    // ---------- Partículas de fundo
    const count = 450;
    const pos = new Float32Array(count * 3);
    for (let i = 0; i < count; i++) {
        pos[i * 3] = (Math.random() - 0.5) * 16;
        pos[i * 3 + 1] = (Math.random() - 0.5) * 9;
        pos[i * 3 + 2] = (Math.random() - 0.5) * 10 - 3;
    }
    const dustGeo = new THREE.BufferGeometry();
    dustGeo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    const dust = new THREE.Points(dustGeo, new THREE.PointsMaterial({ color: 0x8b8bff, size: 0.025, transparent: true, opacity: 0.7, depthWrite: false }));
    scene.add(dust);

    // Poliedros flutuantes em wireframe
    const floaters = [];
    const wire = (c) => new THREE.MeshBasicMaterial({ color: c, wireframe: true, transparent: true, opacity: 0.35 });
    [[-3.6, 1.4, -1.5, 0x22d3ee], [3.7, -0.6, -2, 0xf472b6], [3.1, 1.9, -3, 0xa78bfa], [-3.2, -1.3, -2.5, 0xa78bfa]].forEach(([x, y, z, c], i) => {
        const m = new THREE.Mesh(i % 2 ? new THREE.OctahedronGeometry(0.42) : new THREE.IcosahedronGeometry(0.5, 0), wire(c));
        m.position.set(x, y, z);
        m.userData.base = y;
        scene.add(m);
        floaters.push(m);
    });

    // ---------- Luzes
    scene.add(new THREE.AmbientLight(0xffffff, 0.25));
    const l1 = new THREE.PointLight(0x22d3ee, 30, 20);
    l1.position.set(-4, 3, 3);
    const l2 = new THREE.PointLight(0xf472b6, 30, 20);
    l2.position.set(4, 1, 2);
    const key = new THREE.DirectionalLight(0xffffff, 1.2);
    key.position.set(2, 5, 4);
    scene.add(l1, l2, key);

    // ---------- Interação
    const mouse = { x: 0, y: 0, tx: 0, ty: 0 };
    window.addEventListener('pointermove', (e) => {
        mouse.tx = (e.clientX / window.innerWidth - 0.5) * 2;
        mouse.ty = (e.clientY / window.innerHeight - 0.5) * 2;
    });

    function resize() {
        const { clientWidth: w, clientHeight: h } = canvas.parentElement;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        // Em telas estreitas, afasta a câmera para caber o objeto
        camera.position.z = w < 768 ? 9.5 : 7.2;
        camera.updateProjectionMatrix();
    }
    resize();
    window.addEventListener('resize', resize);

    // ---------- Loop
    const PRINT = 7; // segundos imprimindo
    const HOLD = 2.5; // segundos exibindo pronto
    const timer = new THREE.Timer();
    let visible = true;
    new IntersectionObserver(([entry]) => (visible = entry.isIntersecting)).observe(canvas);

    function frame() {
        requestAnimationFrame(frame);
        if (!visible || document.hidden) return;

        timer.update();
        const t = timer.getElapsed();
        const cycle = reduced ? PRINT : t % (PRINT + HOLD);
        const progress = Math.min(cycle / PRINT, 1);
        const eased = 1 - Math.pow(1 - progress, 1.6);
        const height = minY + (maxY - minY) * eased + 0.0001;
        clip.constant = height + piece.position.y;

        const printing = progress < 1;
        const worldY = height + piece.position.y;
        const angle = t * 7;
        const radius = 0.9 + Math.sin(t * 3.1) * 0.25;
        nozzle.position.set(Math.cos(angle) * radius, worldY + (printing ? 0 : 0.6), Math.sin(angle) * radius);
        nozzle.visible = printing || cycle < PRINT + 0.6;
        glow.material.color.setHSL(0.08, 1, printing ? 0.6 + Math.sin(t * 30) * 0.1 : 0.3);
        tipLight.intensity = printing ? 3 : 0;

        layerLine.position.y = worldY;
        layerLine.material.opacity = printing ? 0.35 : Math.max(0, 0.35 - (cycle - PRINT) * 0.5);

        piece.rotation.y = t * 0.35;
        mouse.x += (mouse.tx - mouse.x) * 0.05;
        mouse.y += (mouse.ty - mouse.y) * 0.05;
        rig.rotation.y = mouse.x * 0.35;
        rig.rotation.x = mouse.y * 0.12;

        dust.rotation.y = t * 0.02;
        floaters.forEach((m, i) => {
            m.rotation.x = t * (0.2 + i * 0.05);
            m.rotation.y = t * (0.3 + i * 0.04);
            m.position.y = m.userData.base + Math.sin(t + i) * 0.2;
        });

        camera.position.x = mouse.x * 0.4;
        camera.lookAt(0, -0.25, 0);
        renderer.render(scene, camera);
    }
    frame();
}
