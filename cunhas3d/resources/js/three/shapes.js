import * as THREE from 'three';
import { mergeGeometries } from 'three/examples/jsm/utils/BufferGeometryUtils.js';

/**
 * Formas procedurais usadas quando o produto não tem um arquivo .glb.
 * Todas são centralizadas e escaladas para caber numa esfera de raio ~1.
 */

function vase() {
    const points = [];
    for (let i = 0; i <= 40; i++) {
        const t = i / 40;
        const r = 0.35 + 0.35 * Math.sin(t * Math.PI * 0.95 + 0.3) + 0.08 * Math.sin(t * 12);
        points.push(new THREE.Vector2(Math.max(r, 0.12), t * 2 - 1));
    }
    const geo = new THREE.LatheGeometry(points, 160);
    // Ondulação paramétrica na parede, como um vaso impresso em espiral
    const pos = geo.attributes.position;
    const v = new THREE.Vector3();
    for (let i = 0; i < pos.count; i++) {
        v.fromBufferAttribute(pos, i);
        const angle = Math.atan2(v.z, v.x);
        const k = 1 + 0.07 * Math.sin(angle * 10 + v.y * 3.5);
        pos.setXYZ(i, v.x * k, v.y, v.z * k);
    }
    geo.computeVertexNormals();
    return geo;
}

function gear() {
    const teeth = 14;
    const outer = 1;
    const inner = 0.82;
    const shape = new THREE.Shape();
    for (let i = 0; i < teeth * 4; i++) {
        const a = (i / (teeth * 4)) * Math.PI * 2;
        const r = i % 4 < 2 ? outer : inner;
        const x = Math.cos(a) * r;
        const y = Math.sin(a) * r;
        i === 0 ? shape.moveTo(x, y) : shape.lineTo(x, y);
    }
    shape.closePath();
    const hole = new THREE.Path();
    hole.absarc(0, 0, 0.28, 0, Math.PI * 2, true);
    shape.holes.push(hole);
    for (let i = 0; i < 5; i++) {
        const a = (i / 5) * Math.PI * 2;
        const p = new THREE.Path();
        p.absarc(Math.cos(a) * 0.56, Math.sin(a) * 0.56, 0.12, 0, Math.PI * 2, true);
        shape.holes.push(p);
    }
    const geo = new THREE.ExtrudeGeometry(shape, {
        depth: 0.32,
        bevelEnabled: true,
        bevelThickness: 0.04,
        bevelSize: 0.03,
        bevelSegments: 3,
        curveSegments: 24,
    });
    geo.rotateX(-Math.PI / 2.6);
    return geo;
}

function figure() {
    const parts = [
        new THREE.CylinderGeometry(0.55, 0.62, 0.18, 48).translate(0, -0.95, 0), // base
        new THREE.CapsuleGeometry(0.3, 0.7, 8, 32).translate(0, -0.3, 0), // corpo
        new THREE.SphereGeometry(0.24, 32, 24).translate(0, 0.45, 0), // cabeça
        new THREE.CapsuleGeometry(0.09, 0.55, 6, 16).rotateZ(0.5).translate(-0.38, -0.15, 0), // braço
        new THREE.CapsuleGeometry(0.09, 0.55, 6, 16).rotateZ(-1.1).translate(0.42, 0.05, 0), // braço erguido
        new THREE.CylinderGeometry(0.03, 0.03, 1.5, 12).rotateZ(-0.35).translate(0.62, 0.3, 0), // lança
        new THREE.ConeGeometry(0.08, 0.25, 16).rotateZ(-0.35).translate(0.89, 1.03, 0),
    ];
    return mergeGeometries(parts.map((g) => g.toNonIndexed()));
}

function lamp() {
    // Treliça: tubos nas arestas de um icosaedro subdividido (efeito "Voronoi")
    const base = new THREE.IcosahedronGeometry(1, 1);
    const edges = new THREE.EdgesGeometry(base, 1);
    const p = edges.attributes.position;
    const parts = [];
    const a = new THREE.Vector3();
    const b = new THREE.Vector3();
    const up = new THREE.Vector3(0, 1, 0);
    for (let i = 0; i < p.count; i += 2) {
        a.fromBufferAttribute(p, i);
        b.fromBufferAttribute(p, i + 1);
        const len = a.distanceTo(b);
        const tube = new THREE.CylinderGeometry(0.035, 0.035, len, 8, 1, true);
        const mid = a.clone().add(b).multiplyScalar(0.5);
        const q = new THREE.Quaternion().setFromUnitVectors(up, b.clone().sub(a).normalize());
        tube.applyMatrix4(new THREE.Matrix4().compose(mid, q, new THREE.Vector3(1, 1, 1)));
        parts.push(tube.toNonIndexed());
    }
    const verts = new THREE.IcosahedronGeometry(1, 1).attributes.position;
    const seen = new Set();
    for (let i = 0; i < verts.count; i++) {
        a.fromBufferAttribute(verts, i);
        const key = a.toArray().map((n) => n.toFixed(3)).join();
        if (seen.has(key)) continue;
        seen.add(key);
        parts.push(new THREE.SphereGeometry(0.06, 12, 8).translate(a.x, a.y, a.z).toNonIndexed());
    }
    parts.push(new THREE.CylinderGeometry(0.22, 0.3, 0.25, 32).translate(0, -1.05, 0).toNonIndexed());
    return mergeGeometries(parts);
}

const builders = {
    torusknot: () => new THREE.TorusKnotGeometry(0.72, 0.24, 260, 36, 2, 3),
    vase,
    gear,
    icosahedron: () => new THREE.IcosahedronGeometry(1, 0),
    figure,
    lamp,
};

export function makeGeometry(shape = 'torusknot') {
    const geo = (builders[shape] || builders.torusknot)();
    geo.computeBoundingSphere();
    geo.center();
    const s = 1 / geo.boundingSphere.radius;
    geo.scale(s, s, s);
    geo.computeBoundingBox();
    if (!geo.attributes.normal) geo.computeVertexNormals();
    return geo;
}

export function makeMaterial(color, shape) {
    return new THREE.MeshPhysicalMaterial({
        color: new THREE.Color(color),
        roughness: 0.38,
        metalness: 0.05,
        clearcoat: 0.6,
        clearcoatRoughness: 0.35,
        flatShading: shape === 'icosahedron',
        side: THREE.DoubleSide,
    });
}
