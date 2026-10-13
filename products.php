
<?php
require_once __DIR__ . '/../config.php';
require_admin();

$err = '';

/*
|--------------------------------------------------------------------------
| Product image upload settings
|--------------------------------------------------------------------------
*/
$uploadDir = dirname(__DIR__) . '/uploads/products/';
$imageUrl = '/powervault/uploads/products/';

$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $cat = trim($_POST['category'] ?? '');

        $price = trim($_POST['price'] ?? '');
        $priceVal = $price === ''
            ? null
            : (is_numeric($price) && (float)$price >= 0
                ? (float)$price : null);

        $stock = max(0, (int)($_POST['stock'] ?? 0));

        $cost = trim($_POST['cost_price'] ?? '');
        $costVal = $cost === ''
            ? null
            : (is_numeric($cost) && (float)$cost >= 0
                ? (float)$cost : null);

        // Keep existing image when editing without a new upload.
        $imageName = null;
        $newImagePath = null;

        if (!$name || ($price !== '' && $priceVal === null)
            || ($cost !== '' && $costVal === null)) {
            $err = 'Enter a product name and valid non-negative price/cost.';
        } else {
            try {
                // Validate and process the uploaded image.
                if (
                    isset($_FILES['product_image']) &&
                    $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE
                ) {
                    $file = $_FILES['product_image'];

                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException(
                            'Image upload failed. Please try again.'
                        );
                    }

                    if ($file['size'] > 5 * 1024 * 1024) {
                        throw new RuntimeException(
                            'Image must be 5 MB or smaller.'
                        );
                    }

                    if (!is_uploaded_file($file['tmp_name'])) {
                        throw new RuntimeException('Invalid uploaded file.');
                    }

                    $imageInfo = @getimagesize($file['tmp_name']);
                    $mime = $imageInfo['mime'] ?? '';

                    if (
                        !$imageInfo ||
                        !isset($allowedMimeTypes[$mime])
                    ) {
                        throw new RuntimeException(
                            'Only JPG, PNG and WEBP images are allowed.'
                        );
                    }

                    $extension = $allowedMimeTypes[$mime];
                    $imageName = bin2hex(random_bytes(16))
                        . '.' . $extension;

                    $newImagePath = $uploadDir . $imageName;

                    if (!move_uploaded_file(
                        $file['tmp_name'],
                        $newImagePath
                    )) {
                        throw new RuntimeException(
                            'Could not save image. Check folder permissions.'
                        );
                    }
                }

                if ($id > 0) {
                    $check = db()->prepare(
                        'SELECT image FROM products WHERE id = ?'
                    );
                    $check->execute([$id]);
                    $existing = $check->fetch();

                    if (!$existing) {
                        throw new RuntimeException('Product not found.');
                    }

                    $oldImage = $existing['image'] ?? null;

                    if ($imageName !== null) {
                        $s = db()->prepare(
                            'UPDATE products
                             SET name=?, description=?, category=?,
                                 price=?, stock=?, cost_price=?, image=?,
                                 updated_at=NOW()
                             WHERE id=?'
                        );

                        $s->execute([
                            $name, $desc, $cat, $priceVal,
                            $stock, $costVal, $imageName, $id
                        ]);
                    } else {
                        $s = db()->prepare(
                            'UPDATE products
                             SET name=?, description=?, category=?,
                                 price=?, stock=?, cost_price=?,
                                 updated_at=NOW()
                             WHERE id=?'
                        );

                        $s->execute([
                            $name, $desc, $cat, $priceVal,
                            $stock, $costVal, $id
                        ]);
                    }

                    // Delete previous image only after a successful update.
                    if (
                        $imageName !== null &&
                        !empty($oldImage) &&
                        basename($oldImage) === $oldImage
                    ) {
                        $oldPath = $uploadDir . $oldImage;

                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                } else {
                    $s = db()->prepare(
                        'INSERT INTO products
                         (name, description, category, price, stock,
                          cost_price, image, active, featured)
                         VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)'
                    );

                    $s->execute([
                        $name, $desc, $cat, $priceVal,
                        $stock, $costVal, $imageName
                    ]);
                }

                flash('Product saved successfully.');
                header('Location: /powervault/admin/products.php');
                exit;

            } catch (Throwable $ex) {
                // Remove a newly uploaded image if saving failed.
                if ($newImagePath && is_file($newImagePath)) {
                    @unlink($newImagePath);
                }

                $err = $ex instanceof RuntimeException
                    ? $ex->getMessage()
                    : 'Could not save product. Please check your database setup.';
            }
        }
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);

        db()->prepare(
            'UPDATE products SET active=1-active WHERE id=?'
        )->execute([$id]);

        flash('Product status updated.');
        header('Location: /powervault/admin/products.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Edit product
|--------------------------------------------------------------------------
*/
$edit = null;

if (isset($_GET['edit'])) {
    $s = db()->prepare('SELECT * FROM products WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

$products = db()->query(
    'SELECT * FROM products ORDER BY id DESC'
)->fetchAll();

$pageTitle = 'Products';
require __DIR__ . '/../includes/header.php';
?>

<style>
.product-upload-box {
    border: 1px dashed #b8c8bd;
    border-radius: 14px;
    padding: 20px;
    background: rgba(20, 40, 28, .04);
}
.product-upload-box input[type=file] {
    width: 100%;
    margin-top: 10px;
}
.product-preview {
    width: 140px;
    height: 120px;
    object-fit: contain;
    border-radius: 12px;
    background: #f5f7f5;
    border: 1px solid #e1e8e2;
    padding: 5px;
}
.product-table-image {
    width: 58px;
    height: 58px;
    object-fit: contain;
    border-radius: 9px;
    padding: 4px;
    background: #f5f7f5;
    border: 1px solid #e1e8e2;
}
</style>

<section class="section-pad">
    <div class="container-fluid admin-layout">

        <aside class="admin-sidebar">
            <div class="eyebrow">CONTROL CENTER</div>
            <a href="/powervault/admin/">▦ Dashboard</a>
            <a class="active" href="/powervault/admin/products.php">◇ Products</a>
            <a href="/powervault/admin/inventory.php">▤ Inventory</a>
            <a href="/powervault/admin/orders.php">⇄ Orders</a>
            <a href="/powervault/admin/profit-loss.php">⌁ Profit &amp; Loss</a>
            <a href="/powervault/admin/settings.php">⚙ Settings</a>
            <a href="/powervault/logout.php">↪ Logout</a>
        </aside>

        <div class="admin-content">

            <div class="section-heading">
                <div>
                    <div class="eyebrow">CATALOG MANAGEMENT</div>
                    <h1>Products</h1>
                    <p>Add products, upload photos and manage stock.</p>
                </div>
            </div>

            <?= get_flash() ?>

            <?php if ($err): ?>
                <div class="alert error"><?= e($err) ?></div>
            <?php endif; ?>

            <div class="dashboard-panel mb-4">
                <h3><?= $edit ? 'Edit Product' : 'Add Product' ?></h3>

                <form method="post"
                      enctype="multipart/form-data"
                      class="row g-3">

                    <input type="hidden" name="csrf"
                           value="<?= e(csrf_token()) ?>">

                    <input type="hidden" name="action" value="save">

                    <input type="hidden" name="id"
                           value="<?= e($edit['id'] ?? '') ?>">

                    <div class="col-md-6">
                        <label class="form-label">Product Name *</label>
                        <input class="form-control" name="name" required
                               value="<?= e($edit['name'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <input class="form-control" name="category"
                               value="<?= e($edit['category'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description"
                                  rows="3"><?= e($edit['description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Product Image</label>

                        <div class="product-upload-box">
                            <?php if (!empty($edit['image'])): ?>
                                <?php
                                $currentImage = basename($edit['image']);
                                $currentImagePath = $uploadDir . $currentImage;
                                ?>
                                <?php if (is_file($currentImagePath)): ?>
                                    <p class="mb-2">Current image</p>
                                    <img
                                        id="imagePreview"
                                        class="product-preview"
                                        src="<?= e($imageUrl . rawurlencode($currentImage)) ?>"
                                        alt="Current product image">
                                <?php else: ?>
                                    <img id="imagePreview"
                                         class="product-preview"
                                         alt="New image preview"
                                         style="display:none">
                                    <p class="muted">Current image file not found.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <img id="imagePreview"
                                     class="product-preview"
                                     alt="Product image preview"
                                     style="display:none">
                            <?php endif; ?>

                            <input
                                type="file"
                                class="form-control"
                                name="product_image"
                                id="productImage"
                                accept="image/jpeg,image/png,image/webp">

                            <small class="d-block mt-2 muted">
                                JPG, PNG or WEBP. Maximum size: 5 MB.
                                Leave empty to keep the current image.
                            </small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Price (blank = unset)</label>
                        <input class="form-control" type="number"
                               min="0" step=".01" name="price"
                               value="<?= e($edit['price'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Stock</label>
                        <input class="form-control" type="number"
                               min="0" name="stock"
                               value="<?= e($edit['stock'] ?? 0) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Purchase Cost</label>
                        <input class="form-control" type="number"
                               min="0" step=".01" name="cost_price"
                               value="<?= e($edit['cost_price'] ?? '') ?>">
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-neon">
                            Save Product
                        </button>
                        <a class="btn btn-outline-glow"
                           href="/powervault/admin/products.php">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <div class="dashboard-panel">
                <h3>Product Catalog (<?= count($products) ?>)</h3>

                <div class="table-responsive">
                    <table class="table pv-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <?php
                                    $productImage = basename($p['image'] ?? '');
                                    $productImagePath = $uploadDir . $productImage;
                                    ?>

                                    <?php if (
                                        $productImage !== '' &&
                                        is_file($productImagePath)
                                    ): ?>
                                        <img
                                            class="product-table-image"
                                            src="<?= e($imageUrl . rawurlencode($productImage)) ?>"
                                            alt="<?= e($p['name']) ?>">
                                    <?php else: ?>
                                        <span class="muted">No image</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <b><?= e($p['name']) ?></b>
                                    <small class="d-block muted">
                                        <?= e($p['category'] ?? '') ?>
                                    </small>
                                </td>

                                <td>
                                    <?= $p['price'] === null
                                        ? 'Not set'
                                        : money($p['price']) ?>
                                </td>

                                <td><?= (int)$p['stock'] ?></td>

                                <td>
                                    <?= $p['active'] ? 'Active' : 'Inactive' ?>
                                </td>

                                <td>
                                    <a class="text-link"
                                       href="?edit=<?= (int)$p['id'] ?>">
                                        Edit
                                    </a>

                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf"
                                               value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action"
                                               value="toggle">
                                        <input type="hidden" name="id"
                                               value="<?= (int)$p['id'] ?>">

                                        <button type="submit"
                                                class="table-action">
                                            <?= $p['active']
                                                ? 'Disable'
                                                : 'Enable' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
document.getElementById('productImage').addEventListener('change', function () {
    const file = this.files[0];
    const preview = document.getElementById('imagePreview');

    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Please select a JPG, PNG or WEBP image.');
        this.value = '';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        alert('Image size must be 5 MB or smaller.');
        this.value = '';
        return;
    }

    preview.src = URL.createObjectURL(file);
    preview.style.display = 'block';
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
