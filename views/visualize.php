<?php
$page_title = '3D Visualizer';
$full_bleed = true;

$all_products = db()->query("SELECT p.id, p.name, p.price_cents,
        (SELECT pi.path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.sort_order, pi.id LIMIT 1) AS image_path
    FROM products p
    WHERE p.status = 'active' AND EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id)
    ORDER BY p.created_at DESC LIMIT 60")->fetchAll();

// Default to the newest product when no (valid) id is given.
$product = $all_products[0] ?? null;
foreach ($all_products as $candidate) {
    if ((int) $candidate['id'] === query_int('id')) {
        $product = $candidate;
    }
}
?>

<?php if (!$product): ?>
    <div class="mx-auto max-w-3xl px-4 py-16">
        <?php empty_state('cube', 'Nothing to visualise yet', 'Once sellers add products with photos, you can explore them in 3D here.',
            '<a href="' . e(url('shop')) . '" class="btn btn-outline">Browse the shop</a>'); ?>
    </div>
<?php else: ?>
<div class="flex flex-col-reverse bg-stone-100 lg:h-[calc(100vh-7.5rem)] lg:flex-row">
    <!-- Sidebar Controls -->
    <div class="z-10 flex w-full flex-col bg-white shadow-xl lg:h-full lg:w-80 lg:overflow-y-auto">
        <div class="border-b border-stone-100 p-6">
            <h1 class="font-display text-2xl font-semibold text-stone-900">3D Visualizer</h1>
            <p class="mt-1 text-sm text-stone-500">Drag to rotate · Scroll to zoom</p>
        </div>

        <div class="flex-grow space-y-8 p-6">
            <div>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-500">Selected item</h3>
                <div class="flex items-center rounded-xl bg-stone-50 p-3">
                    <img src="<?= e($product['image_path']) ?>" alt="" class="mr-3 h-12 w-12 rounded-lg bg-white object-cover shadow-sm">
                    <div class="min-w-0">
                        <p class="line-clamp-1 text-sm font-medium text-stone-800"><?= e($product['name']) ?></p>
                        <p class="text-xs font-semibold text-brand-700"><?= money($product['price_cents']) ?></p>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="mb-4 text-xs font-semibold uppercase tracking-wide text-stone-500">Environment</h3>
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-xs font-medium text-stone-500">Background colour</label>
                        <div class="flex space-x-2">
                            <?php foreach (['#f5f5f4' => 'bg-stone-100', '#1c1917' => 'bg-stone-900', '#faf7f2' => 'bg-cream', '#f6e8de' => 'bg-brand-100'] as $hex => $class): ?>
                                <button type="button" onclick="changeBg('<?= $hex ?>')" aria-label="Background <?= $hex ?>"
                                    class="h-8 w-8 rounded-full border-2 border-stone-200 <?= $class ?> outline-none transition hover:border-brand-500 focus:border-brand-500"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <label class="flex cursor-pointer items-center justify-between">
                        <span class="text-sm font-medium text-stone-700">Auto rotate</span>
                        <span class="relative inline-flex items-center">
                            <input type="checkbox" id="autoRotateCheck" checked class="peer sr-only" onchange="toggleAutoRotate()">
                            <span class="h-6 w-11 rounded-full bg-stone-200 transition after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-stone-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brand-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus-visible:ring-4 peer-focus-visible:ring-brand-200"></span>
                        </span>
                    </label>
                </div>
            </div>

            <div>
                <h3 class="mb-4 text-xs font-semibold uppercase tracking-wide text-stone-500">Select item</h3>
                <div class="grid grid-cols-5 gap-2 lg:grid-cols-4">
                    <?php foreach ($all_products as $p): ?>
                        <a href="<?= e(url('visualize', ['id' => $p['id']])) ?>" title="<?= e($p['name']) ?>"
                            class="relative block aspect-square overflow-hidden rounded-lg border-2 <?= ((int) $p['id'] === (int) $product['id']) ? 'border-brand-600' : 'border-transparent hover:border-stone-300' ?> transition">
                            <img src="<?= e($p['image_path']) ?>" alt="<?= e($p['name']) ?>" class="h-full w-full object-cover">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="border-t border-stone-100 bg-stone-50 p-6">
            <a href="<?= e(url('product', ['id' => $product['id']])) ?>" class="btn btn-dark w-full">View details</a>
        </div>
    </div>

    <!-- 3D Canvas -->
    <div id="canvas-container" class="relative h-[60vh] flex-grow cursor-move bg-stone-100 lg:h-auto">
        <div class="absolute right-6 top-6 z-10 rounded-full bg-white/80 px-4 py-2 shadow-sm backdrop-blur-sm">
            <p class="text-xs font-semibold text-stone-600">Interactive 3D view</p>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://unpkg.com/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

<script>
    const container = document.getElementById('canvas-container');
    const imageUrl = <?= json_encode($product['image_path'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    // Scene setup
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf5f5f4);

    const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
    camera.position.z = 2.5;

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.shadowMap.enabled = true;
    container.appendChild(renderer.domElement);

    // Controls
    const controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.autoRotate = true;
    controls.autoRotateSpeed = 2.0;

    // Lighting
    scene.add(new THREE.AmbientLight(0xffffff, 0.8));
    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(5, 5, 5);
    directionalLight.castShadow = true;
    scene.add(directionalLight);
    const pointLight = new THREE.PointLight(0xffffff, 0.5);
    pointLight.position.set(-5, -5, 5);
    scene.add(pointLight);

    const giftGroup = new THREE.Group();
    scene.add(giftGroup);

    new THREE.TextureLoader().load(imageUrl, function (texture) {
        // Fit the image within a 2x2 area, keeping its aspect ratio.
        const aspect = texture.image.width / texture.image.height;
        const planeWidth = aspect >= 1 ? 2 : 2 * aspect;
        const planeHeight = aspect >= 1 ? 2 / aspect : 2;

        // Chroma-key shader: makes a white studio background transparent.
        const material = new THREE.ShaderMaterial({
            uniforms: {
                textureMap: { value: texture },
                keyColor: { value: new THREE.Color(0xffffff) },
                similarity: { value: 0.15 },
                smoothness: { value: 0.05 }
            },
            vertexShader: `
                varying vec2 vUv;
                void main() {
                    vUv = uv;
                    gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
                }
            `,
            fragmentShader: `
                uniform sampler2D textureMap;
                uniform vec3 keyColor;
                uniform float similarity;
                uniform float smoothness;
                varying vec2 vUv;

                void main() {
                    vec4 texColor = texture2D(textureMap, vUv);
                    float diff = length(texColor.rgb - keyColor);
                    float alpha = smoothstep(similarity, similarity + smoothness, diff);
                    gl_FragColor = vec4(texColor.rgb, texColor.a * alpha);
                    if (alpha < 0.1) discard;
                }
            `,
            side: THREE.DoubleSide,
            transparent: true
        });

        const cutout = new THREE.Mesh(new THREE.PlaneGeometry(planeWidth, planeHeight), material);
        cutout.castShadow = true;
        cutout.receiveShadow = true;
        giftGroup.add(cutout);
    }, undefined, function () {
        console.error('The product image could not be loaded.');
    });

    window.addEventListener('resize', () => {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    });

    function animate() {
        requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    }
    animate();

    function changeBg(color) {
        scene.background = new THREE.Color(color);
    }

    function toggleAutoRotate() {
        controls.autoRotate = document.getElementById('autoRotateCheck').checked;
    }
</script>
<?php endif; ?>
