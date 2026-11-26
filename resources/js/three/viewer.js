import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { STLLoader } from 'three/examples/jsm/loaders/STLLoader.js';
import { OBJLoader } from 'three/examples/jsm/loaders/OBJLoader.js';
import { ThreeMFLoader } from 'three/examples/jsm/loaders/3MFLoader.js';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';
import { makeGeometry, makeMaterial } from './shapes.js';

/**
 * Visualizador 3D interativo da página do produto.
 * <div data-viewer data-shape="vase" data-color="#22d3ee" data-model="/storage/models/x.glb">
 */
export function initViewer(el) {
    const canvas = document.createElement('canvas');
    canvas.className = 'h-full w-full cursor-grab active:cursor-grabbing';
    el.prepend(canvas);

    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.outputColorSpace = THREE.SRGBColorSpace;

    const scene = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;

    const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 100);
    camera.position.set(2.6, 1.4, 3.6);

    const controls = new OrbitControls(camera, canvas);
    controls.enableDamping = true;
    controls.autoRotate = true;
    controls.autoRotateSpeed = 1.6;
    controls.enablePan = false;
    controls.minDistance = 2.2;
    controls.maxDistance = 8;

    scene.add(new THREE.AmbientLight(0xffffff, 0.3));
    const l1 = new THREE.PointLight(0x22d3ee, 18, 15);
    l1.position.set(-3, 2, 2);
    const l2 = new THREE.PointLight(0xf472b6, 18, 15);
    l2.position.set(3, 1, -2);
    const key = new THREE.DirectionalLight(0xffffff, 1.4);
    key.position.set(2, 4, 3);
    scene.add(l1, l2, key);

    // Sombra suave no chão (gradiente radial)
    const shadowTex = (() => {
        const c = document.createElement('canvas');
        c.width = c.height = 128;
        const g = c.getContext('2d');
        const grad = g.createRadialGradient(64, 64, 0, 64, 64, 64);
        grad.addColorStop(0, 'rgba(0,0,0,0.65)');
        grad.addColorStop(1, 'rgba(0,0,0,0)');
        g.fillStyle = grad;
        g.fillRect(0, 0, 128, 128);
        return new THREE.CanvasTexture(c);
    })();
    const shadow = new THREE.Mesh(new THREE.PlaneGeometry(2.6, 2.6), new THREE.MeshBasicMaterial({ map: shadowTex, transparent: true, depthWrite: false }));
    shadow.rotation.x = -Math.PI / 2;
    shadow.position.y = -1.1;
    scene.add(shadow);

    let material = makeMaterial(el.dataset.color || '#22d3ee', el.dataset.shape);
    let object;

    function setColor(hex) {
        material.color.set(hex);
    }

    // Centraliza e ajusta o tamanho de qualquer modelo carregado
    function addModel(obj, zUp) {
        // Arquivos de impressão (STL/3MF) usam o eixo Z para cima; o Three.js usa Y
        if (zUp) obj.rotation.x = -Math.PI / 2;
        const wrap = new THREE.Group();
        wrap.add(obj);
        const box = new THREE.Box3().setFromObject(wrap);
        const size = box.getSize(new THREE.Vector3()).length() || 1;
        const center = box.getCenter(new THREE.Vector3());
        obj.position.sub(center);
        wrap.scale.setScalar(2.2 / size);
        object = new THREE.Group();
        object.add(wrap);
        scene.add(object);
        el.querySelector('[data-viewer-loading]')?.remove();
    }

    if (el.dataset.model) {
        const url = el.dataset.model;
        const format = (el.dataset.format || url.split('?')[0].split('.').pop()).toLowerCase();
        const fail = () => fallback();

        if (format === 'stl') {
            new STLLoader().load(url, (geo) => {
                geo.computeVertexNormals();
                addModel(new THREE.Mesh(geo, material), true);
            }, undefined, fail);
        } else if (format === 'obj') {
            new OBJLoader().load(url, (obj) => {
                obj.traverse((c) => { if (c.isMesh) c.material = material; });
                addModel(obj, false);
            }, undefined, fail);
        } else if (format === '3mf') {
            new ThreeMFLoader().load(url, (obj) => {
                // Sem cor no arquivo: usa a cor do produto
                obj.traverse((c) => {
                    if (c.isMesh && !c.geometry.attributes.color) c.material = material;
                });
                addModel(obj, true);
            }, undefined, fail);
        } else {
            new GLTFLoader().load(url, (gltf) => addModel(gltf.scene, false), undefined, fail);
        }
    } else {
        fallback();
    }

    function fallback() {
        object = new THREE.Mesh(makeGeometry(el.dataset.shape), material);
        scene.add(object);
        el.querySelector('[data-viewer-loading]')?.remove();
    }

    // Botões de cor (data-viewer-color="#hex")
    document.querySelectorAll('[data-viewer-color]').forEach((btn) => {
        btn.addEventListener('click', () => {
            setColor(btn.dataset.viewerColor);
            document.querySelectorAll('[data-viewer-color]').forEach((b) => b.classList.remove('ring-2'));
            btn.classList.add('ring-2');
        });
    });

    function resize() {
        const { clientWidth: w, clientHeight: h } = el;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
    }
    new ResizeObserver(resize).observe(el);
    resize();

    const timer = new THREE.Timer();
    let visible = true;
    new IntersectionObserver(([e]) => (visible = e.isIntersecting)).observe(el);

    (function frame() {
        requestAnimationFrame(frame);
        if (!visible || document.hidden) return;
        timer.update();
        const t = timer.getElapsed();
        if (object) object.position.y = Math.sin(t * 1.2) * 0.05;
        controls.update();
        renderer.render(scene, camera);
    })();
}
