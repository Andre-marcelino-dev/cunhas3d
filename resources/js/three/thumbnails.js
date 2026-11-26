import * as THREE from 'three';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';
import { makeGeometry, makeMaterial } from './shapes.js';

/**
 * Gera as miniaturas 3D dos cards de produto usando UM único contexto WebGL
 * (muito mais leve do que um canvas por card). Cada card vira uma imagem.
 * <img data-thumb data-shape="gear" data-color="#ff0000">
 */
let renderer, scene, camera;
const cache = new Map();

function setup() {
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, preserveDrawingBuffer: true });
    renderer.setPixelRatio(1);
    renderer.setSize(512, 512, false);
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.outputColorSpace = THREE.SRGBColorSpace;

    scene = new THREE.Scene();
    scene.environment = new THREE.PMREMGenerator(renderer).fromScene(new RoomEnvironment(), 0.04).texture;
    camera = new THREE.PerspectiveCamera(32, 1, 0.1, 50);
    camera.position.set(2.4, 1.3, 3.4);
    camera.lookAt(0, 0, 0);

    scene.add(new THREE.AmbientLight(0xffffff, 0.3));
    const l1 = new THREE.PointLight(0x22d3ee, 16, 15);
    l1.position.set(-3, 2, 2);
    const l2 = new THREE.PointLight(0xf472b6, 16, 15);
    l2.position.set(3, 1, -2);
    const key = new THREE.DirectionalLight(0xffffff, 1.5);
    key.position.set(2, 4, 3);
    scene.add(l1, l2, key);
}

function render(shape, color) {
    const k = `${shape}|${color}`;
    if (cache.has(k)) return cache.get(k);

    const mesh = new THREE.Mesh(makeGeometry(shape), makeMaterial(color, shape));
    mesh.rotation.y = -0.4;
    scene.add(mesh);
    renderer.render(scene, camera);
    const url = renderer.domElement.toDataURL('image/png');
    scene.remove(mesh);
    mesh.geometry.dispose();
    mesh.material.dispose();

    cache.set(k, url);
    return url;
}

export function initThumbnails(imgs) {
    if (!renderer) setup();
    const queue = [...imgs];

    // Renderiza aos poucos para não travar a página
    (function next() {
        const img = queue.shift();
        if (!img) return;
        img.src = render(img.dataset.shape, img.dataset.color);
        img.classList.remove('opacity-0');
        requestAnimationFrame(next);
    })();
}
