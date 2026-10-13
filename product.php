
<?php
require_once __DIR__ . '/config.php';

$id = (int) ($_GET['id'] ?? 0);

$s = db()->prepare(
    'SELECT * FROM products WHERE id=? AND active=1'
);
$s->execute([$id]);
$p = $s->fetch();

if (!$p) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/includes/header.php';

    echo '<div class="container section-pad">
        <div class="empty-state">Product not found.
        <a href="/powervault/shop.php">Return to shop</a></div>
    </div>';

    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $p['name'];

// Resolve the uploaded product image safely.
$imageName = basename($p['image'] ?? '');
$imageFile = __DIR__ . '/uploads/products/' . $imageName;
$imageUrl = '/powervault/uploads/products/' . rawurlencode($imageName);

$hasImage = $imageName !== '' && is_file($imageFile);

require __DIR__ . '/includes/header.php';
?>

<style>
.product-detail .detail-art {
    position: relative;
    min-width: 0;
    overflow: hidden;
    border-radius: 24px;
}

.product-detail .detail-product-image {
    position: relative;
    z-index: 1;
    display: block;
    width: 100%;
    height: clamp(300px, 42vw, 520px);
    object-fit: contain;
    padding: 24px;
    border-radius: 24px;
    background: #fff;
}

.product-detail .detail-art .product-placeholder {
    position: relative;
    z-index: 1;
    margin: 70px auto;
}

.product-detail .detail-copy {
    min-width: 0;
}

.product-detail .detail-price {
    margin: 20px 0;
}

.product-detail .quantity-input {
    max-width: 130px;
}

@media (max-width: 767px) {
    .product-detail .detail-product-image {
        height: 320px;
        padding: 16px;
    }
}
</style>

<section class="section-pad">
    <div class="container product-detail">

        <div class="detail-art">
            <div class="art-glow"></div>

            <?php if ($hasImage): ?>
                <img
                    class="detail-product-image"
                    src="<?= e($imageUrl) ?>"
                    alt="<?= e($p['name']) ?>"
                    loading="eager">
            <?php else: ?>
                <div class="product-placeholder large">
                    <span class="device-screen"></span>
                    <span class="device-port"></span>
                    <span class="device-bolt">ϟ</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="detail-copy">
            <div class="eyebrow">
                <?= e($p['category'] ?: 'POWERVAULT COLLECTION') ?>
            </div>

            <h1><?= e($p['name']) ?></h1>

            <p class="muted">
                <?= e(
                    $p['description']
                    ?: 'Product description can be updated from the admin panel.'
                ) ?>
            </p>

            <div class="detail-price">
                <?= $p['price'] === null
                    ? 'Price not set'
                    : money($p['price']) ?>
            </div>

            <p class="muted">
                Stock:
                <?= (int)$p['stock'] > 0
                    ? (int)$p['stock'] . ' available'
                    : 'Currently unavailable' ?>
            </p>

            <?php if (
                $p['price'] !== null &&
                (float)$p['price'] > 0 &&
                (int)$p['stock'] > 0
            ): ?>
                <form method="post" action="/powervault/cart.php">
                    <input type="hidden" name="csrf"
                           value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id"
                           value="<?= (int)$p['id'] ?>">

                    <label class="form-label" for="quantity">
                        Quantity
                    </label>

                    <input
                        id="quantity"
                        type="number"
                        class="form-control quantity-input"
                        name="quantity"
                        min="1"
                        max="<?= (int)$p['stock'] ?>"
                        value="1"
                        required>

                    <button type="submit" class="btn btn-neon mt-3">
                        Add to Cart
                    </button>
                </form>
            <?php else: ?>
                <button disabled class="btn btn-disabled">
                    Price not set or out of stock
                </button>
            <?php endif; ?>

            <div class="detail-note">
                Payments: Cash on Delivery, Easypaisa and JazzCash.
                Eligible online payments receive a 3% discount.
            </div>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
