<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$category = sanitize($_GET['category'] ?? '');
$search = sanitize($_GET['q'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'newest');
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * PRODUCTS_PER_PAGE;

$filters = [
    'category' => $category,
    'search' => $search,
    'sort' => $sort,
    'limit' => PRODUCTS_PER_PAGE,
    'offset' => $offset,
];

$products = get_products($filters);
$total = count_products($filters);
$total_pages = max(1, (int) ceil($total / PRODUCTS_PER_PAGE));
$categories = get_categories();

$cat_name = 'All Products';
if ($category) {
    foreach ($categories as $c) {
        if ($c['slug'] === $category) {
            $cat_name = $c['name'];
            break;
        }
    }
}

$page_title = ($search ? "Search: $search" : $cat_name) . ' — ' . APP_NAME;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container">
    <div class="page-header">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= APP_URL ?>/index.php">Home</a> &rsaquo;
            <span><?= e($search ? "Search: $search" : $cat_name) ?></span>
        </nav>
        <h1><?= e($search ? "Results for \"$search\"" : $cat_name) ?></h1>
        <p><?= $total ?> product<?= $total !== 1 ? 's' : '' ?> found</p>
    </div>

    <div class="products-layout">
        <aside class="filters-sidebar" aria-label="Filters">
            <form method="get" action="<?= APP_URL ?>/products.php">
                <?php if ($search): ?>
                <input type="hidden" name="q" value="<?= e($search) ?>">
                <?php endif; ?>

                <div class="filter-group">
                    <h4>Categories</h4>
                    <label>
                        <input type="radio" name="category" value="" <?= $category === '' ? 'checked' : '' ?> onchange="this.form.submit()">
                        All Categories
                    </label>
                    <?php foreach ($categories as $cat): ?>
                    <label>
                        <input type="radio" name="category" value="<?= e($cat['slug']) ?>" <?= $category === $cat['slug'] ? 'checked' : '' ?> onchange="this.form.submit()">
                        <?= e($cat['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h4>Sort By</h4>
                    <select name="sort" class="sort-select" style="width:100%" onchange="this.form.submit()">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                        <option value="discount" <?= $sort === 'discount' ? 'selected' : '' ?>>Biggest Discount</option>
                    </select>
                </div>
            </form>
        </aside>

        <div class="products-main">
            <div class="products-toolbar">
                <span class="products-count">Showing <?= count($products) ?> of <?= $total ?> products</span>
                <select id="sortSelect" class="sort-select" aria-label="Sort products">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                    <option value="discount" <?= $sort === 'discount' ? 'selected' : '' ?>>Biggest Discount</option>
                </select>
            </div>

            <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h2>No Products Found</h2>
                <p>Try adjusting your search or browse all categories.</p>
                <a href="<?= APP_URL ?>/products.php" class="btn btn-primary">Browse All Products</a>
            </div>
            <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?= render_product_card($product) ?>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo;</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                    <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">&raquo;</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
