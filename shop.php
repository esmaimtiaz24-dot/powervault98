
<?php
require_once __DIR__ . '/config.php';

$pageTitle = 'Shop';
$q = trim($_GET['q'] ?? '');

$stmt = db()->prepare(
    "SELECT * FROM products
     WHERE active=1
       AND (name LIKE ? OR description LIKE ? OR category LIKE ?)
     ORDER BY featured DESC, id ASC"
);

$like = '%' . $q . '%';
$stmt->execute([$like, $like, $like]);
$products = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<style>
/* PowerVault product images */
.shop-product-card .product-art {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 250px;
    overflow: hidden;
    background: #f5f7f6;
    border-radius: 18px;
    text-decoration: none;
}

.shop-product-card .shop-product-image {
    position: relative;
    z-index: 1;
    display: block;
    width: 100%;
    height: 250px;
    padding: 16px;
    object-fit: contain;
    transition: transform .35s ease;
}

.shop-product-card:hover .shop-product-image {
    transform: scale(1.06);
}

.shop-product-card .product-placeholder {
    position: relative;
    z-index: 1;
    margin: 40px auto;
}

.shop-product-card .product-badge {
    z-index: 2;
}

.shop-search-input {
    min-width: 0;
    flex: 1;
}

.shop-empty-image {
    color: #68746d;
    font-size: 14px;
}

@media (max-width: 575px) {
    .shop-product-card .product-art {
        min-height: 220px;
    }

    .shop-product-card .shop-product-image {
        height: 220px;
    }
}
</style>

<section class="page-hero">
    <div class="container">
        <div class="eyebrow">POWERVAULT COLLECTION</div>

        <h1>
            Find your <span class="gradient-text">next essential.</span>
        </h1>

        <p>
            Browse premium accessories, explore product images
            and find the right product for your needs.
        </p>

        <form class="search-form" method="get">
            <input
                class="shop-search-input"
                type="search"
                name="q"
                value="<?= e($q) ?>"
                placeholder="Search products..."
                aria-label="Search products">

            <button type="submit" class="btn btn-neon">
                Search
            </button>

            <?php if ($q !== ''): ?>
                <a href="/powervault/shop.php"
                   class="btn btn-outline-glow">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</section>

<section class="section-pad">
    <div class="container">

        <div class="section-heading">
            <div>
                <div class="eyebrow">OUR PRODUCTS</div>

                <h2>
                    Explore the <span class="gradient-text">Collection.</span>
                </h2>

                <p>
                    <?= count($products) ?>
                    product<?= count($products) === 1 ? '' : 's' ?>
                    found<?= $q !== '' ? ' for "' . e($q) . '"' : '' ?>.
                </p>
            </div>
        </div>

        <div class="row g-4">

            <?php foreach ($products as $p): ?>

                <?php
                // Uploaded images are stored as filenames in products.image.
                $imageName = basename($p['image'] ?? '');

                $imagePath = __DIR__
                    . '/uploads/products/'
                    . $imageName;

                $imageUrl = '/powervault/uploads/products/'
                    . rawurlencode($imageName);

                $hasImage = $imageName !== ''
                    && is_file($imagePath);
                ?>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="product-card shop-product-card">

                        <a
                            class="product-art"
                            href="/powervault/product.php?id=<?= (int)$p['id'] ?>"
                            aria-label="View <?= e($p['name']) ?>">

                            <div class="art-glow"></div>

                            <?php if ($hasImage): ?>

                                <img
                                    class="shop-product-image"
                                    src="<?= e($imageUrl) ?>"
                                    alt="<?= e($p['name']) ?>"
                                    loading="lazy">

                            <?php else: ?>

                                <div class="product-placeholder">
                                    <span class="device-screen"></span>
                                    <span class="device-port"></span>
                                    <span class="device-bolt">ϟ</span>
                                </div>

                            <?php endif; ?>

                            <span class="product-badge">
                                PV COLLECTION
                            </span>
                        </a>

                        <div class="product-info">

                            <div class="product-category">
                                <?= e($p['category'] ?: 'ACCESSORY') ?>
                            </div>

                            <h3><?= e($p['name']) ?></h3>

                            <p>
                                <?= e(
                                    $p['description']
                                    ?: 'Product details will be updated soon.'
                                ) ?>
                            </p>

                            <div class="product-bottom">
                                <strong>
                                    <?= $p['price'] === null
                                        || $p['price'] === ''
                                        ? 'Price not set'
                                        : money($p['price']) ?>
                                </strong>

                                <a
                                    class="btn-mini"
                                    href="/powervault/product.php?id=<?= (int)$p['id'] ?>">
                                    Details ↗
                                </a>
                            </div>

                            <?php if (
                                $p['price'] !== null
                                && (float)$p['price'] > 0
                                && (int)$p['stock'] > 0
                            ): ?>

                                <form
                                    method="post"
                                    action="/powervault/cart.php"
                                    class="mt-3">

                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?= e(csrf_token()) ?>">

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="add">

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int)$p['id'] ?>">

                                    <button
                                        type="submit"
                                        class="btn btn-neon w-100">
                                        Add to Cart
                                    </button>
                                </form>

                            <?php else: ?>

                                <button
                                    disabled
                                    class="btn btn-disabled w-100 mt-3">
                                    <?= (int)$p['stock'] <= 0
                                        ? 'Out of Stock'
                                        : 'Price Not Set' ?>
                                </button>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

            <?php if (!$products): ?>
                <div class="col-12">
                    <div class="empty-state">
                        <?php if ($q !== ''): ?>
                            No products found for
                            "<?= e($q) ?>". Try another search.
                        <?php else: ?>
                            No products are available yet.
                            Add products from your admin panel.
                        <?php endif; ?>

                        <div class="mt-3">
                            <a href="/powervault/shop.php"
                               class="btn btn-outline-glow">
                                View All Products
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
