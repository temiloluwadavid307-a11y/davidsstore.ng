<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - David's Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/platform.css">
    <style>
        :root {
            --primary: #F68B1E;
            --primary-hover: #e07b1a;
            --dark: #282828;
            --light: #F5F5F5;
            --white: #FFFFFF;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        * {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--light);
        }

        /* Sidebar */
        .sidebar {
            background: var(--dark);
            height: 100vh;
            color: var(--white);
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            z-index: 1050;
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar a {
            color: rgba(255,255,255,0.7);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            transition: var(--transition);
        }

        .sidebar a:hover, .sidebar a.active {
            background: var(--primary);
            color: var(--white);
        }

        .sidebar a i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease-in-out;
        }

        /* Top Bar */
        .top-bar {
            background: var(--white);
            padding: 12px 20px;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1040;
        }

        /* Stat Cards */
        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        /* Table Container */
        .table-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        /* Product Preview */
        .product-preview {
            width: 56px;
            height: 56px;
            object-fit: contain;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 4px;
        }

        /* Buttons */
        .btn-primary-custom {
            background: var(--primary);
            border: none;
        }

        .btn-primary-custom:hover {
            background: var(--primary-hover);
        }

        /* Overlay for mobile */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive Adjustments */
        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 260px;
            }
            .menu-toggle {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-store"></i>
            VENDOR PANEL
        </div>
        <nav class="nav flex-column">
            <a class="active" href="vendor.html">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="vendor-my-products.html">
                <i class="fa-solid fa-boxes-stacked"></i> My Products
            </a>
            <a href="vendor-add-product.html">
                <i class="fa-solid fa-circle-plus"></i> Add Product
            </a>
            <a href="vendor-orders.html">
                <i class="fa-solid fa-basket-shopping"></i> Orders
            </a>
            <a href="vendor-earnings.html">
                <i class="fa-solid fa-sack-dollar"></i> Earnings
            </a>
            <a href="vendor-analytics.html">
                <i class="fa-solid fa-chart-simple"></i> Analytics
            </a>
            <a href="vendor-account-settings.html">
                <i class="fa-solid fa-gear"></i> Account Settings
            </a>
            <a href="../index.html">
                <i class="fa-solid fa-house"></i> Back to Store
            </a>
            <a href="#" id="logoutBtn">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-outline-secondary menu-toggle" id="menuToggle">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div>
                            <h4 class="mb-0">Vendor Dashboard</h4>
                            <p class="text-muted small mb-0">Welcome back, TechHub Nigeria!</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="input-group d-none d-md-flex" style="max-width: 280px;">
                            <span class="input-group-text bg-transparent">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" placeholder="Search products...">
                        </div>
                        <button class="btn btn-outline-secondary position-relative">
                            <i class="fa-solid fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="width:10px;height:10px;"></span>
                        </button>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-gradient bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:44px;height:44px;">
                                <i class="fa-solid fa-store"></i>
                            </div>
                            <div class="text-start d-none d-sm-block">
                                <div class="fw-bold" id="vendorName">TechHub NG</div>
                                <small class="text-muted" id="vendorSubtitle">Verified Vendor</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="p-4">
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-0 text-muted">Total Earnings</h5>
                            </div>
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-sack-dollar"></i>
                            </div>
                        </div>
                        <h3 class="mb-1" id="vendorTotalEarnings">₦ 4,580,000</h3>
                        <small class="text-success d-flex align-items-center gap-1">
                            <i class="fa-solid fa-arrow-up"></i>
                            +18.2% this month
                        </small>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-0 text-muted">Total Products</h5>
                            </div>
                            <div class="stat-icon bg-info bg-opacity-10 text-info">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>
                        </div>
                        <h3 class="mb-1" id="vendorTotalProducts">85</h3>
                        <small class="text-muted d-flex align-items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-warning"></i>
                            5 low in stock
                        </small>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-0 text-muted">Orders</h5>
                            </div>
                            <div class="stat-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-basket-shopping"></i>
                            </div>
                        </div>
                        <h3 class="mb-1" id="vendorTotalOrders">624</h3>
                        <small class="text-success d-flex align-items-center gap-1">
                            <i class="fa-solid fa-arrow-up"></i>
                            +12.5% this month
                        </small>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-0 text-muted">Rating</h5>
                            </div>
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-star"></i>
                            </div>
                        </div>
                        <h3 class="mb-1" id="vendorAverageRating">4.8</h3>
                        <small class="text-muted d-flex align-items-center gap-1">
                            <i class="fa-solid fa-users"></i>
                            Based on 234 reviews
                        </small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Product Management -->
                <div class="col-lg-8">
                    <div class="table-container">
                        <div class="p-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <h5 class="mb-0 fw-semibold">My Products</h5>
                            <button class="btn btn-primary-custom btn-sm">
                                <i class="fa-solid fa-plus"></i> Add New Product
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Sales</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="vendorDashboardProducts">
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="../assets/images/14pm.jpg" class="product-preview" alt="iPhone">
                                                <div>
                                                    <h6 class="mb-0 fw-semibold">iPhone 14 Pro Max - 256GB Gold</h6>
                                                    <small class="text-muted">SKU: DS-IP-14PM-256G</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-semibold">₦ 950,000</td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20">In Stock (12)</span></td>
                                        <td><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-20">Active</span></td>
                                        <td>28 units</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1"><i class="fa-solid fa-pen-to-square"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="../assets/images/ps5.jpg" class="product-preview" alt="PS5">
                                                <div>
                                                    <h6 class="mb-0 fw-semibold">Sony PlayStation 5 Console</h6>
                                                    <small class="text-muted">SKU: DS-PS5-CON</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-semibold">₦ 520,000</td>
                                        <td><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-20">Low Stock (3)</span></td>
                                        <td><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-20">Active</span></td>
                                        <td>15 units</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1"><i class="fa-solid fa-pen-to-square"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="../assets/images/s23u.jpg" class="product-preview" alt="Samsung">
                                                <div>
                                                    <h6 class="mb-0 fw-semibold">Samsung Galaxy S23 Ultra</h6>
                                                    <small class="text-muted">SKU: DS-SAM-S23U</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-semibold">₦ 850,000</td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20">In Stock (8)</span></td>
                                        <td><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-20">Active</span></td>
                                        <td>21 units</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1"><i class="fa-solid fa-pen-to-square"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="assets/images/macbook.jpg" class="product-preview" alt="MacBook">
                                                <div>
                                                    <h6 class="mb-0 fw-semibold">MacBook Air M2 Chip - 13.6"</h6>
                                                    <small class="text-muted">SKU: DS-MAC-M2-13</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-semibold">₦ 720,000</td>
                                        <td><span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20">Out of Stock</span></td>
                                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-20">Inactive</span></td>
                                        <td>12 units</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1"><i class="fa-solid fa-pen-to-square"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="col-lg-4">
                    <div class="table-container">
                        <div class="p-4 border-bottom">
                            <h5 class="mb-0 fw-semibold">Recent Orders</h5>
                        </div>
                        <div class="p-3" id="vendorRecentOrders">
                            <div class="mb-3 p-3 border rounded-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="fw-semibold">#DS-7845</div>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20">Delivered</span>
                                </div>
                                <div class="text-muted small mt-1">iPhone 14 Pro Max</div>
                                <div class="mt-2 d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><i class="fa-regular fa-calendar-days"></i> 2026-06-15</small>
                                    <div class="fw-bold text-dark">₦ 950,000</div>
                                </div>
                            </div>
                            <div class="mb-3 p-3 border rounded-3">
                                <div class="d-flex justify-between align-items-start">
                                    <div class="fw-semibold">#DS-7844</div>
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-20">Shipped</span>
                                </div>
                                <div class="text-muted small mt-1">PS5 Console</div>
                                <div class="mt-2 d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><i class="fa-regular fa-calendar-days"></i> 2026-06-14</small>
                                    <div class="fw-bold text-dark">₦ 520,000</div>
                                </div>
                            </div>
                            <div class="mb-3 p-3 border rounded-3">
                                <div class="d-flex justify-between align-items-start">
                                    <div class="fw-semibold">#DS-7843</div>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-20">Processing</span>
                                </div>
                                <div class="text-muted small mt-1">Samsung Galaxy S23 Ultra</div>
                                <div class="mt-2 d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><i class="fa-regular fa-calendar-days"></i> 2026-06-14</small>
                                    <div class="fw-bold text-dark">₦ 850,000</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/marketplace-core.js"></script>
    <script>
        // Session Check
        window.MarketplaceApp.requireAuth(['vendor']);

        // Logout
        document.getElementById('logoutBtn').addEventListener('click', (e) => {
            e.preventDefault();
            window.MarketplaceApp.logout();
            window.location.href = '../login.php';
        });

        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
        }

        menuToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);

        // Close sidebar on link click for mobile
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    toggleSidebar();
                }
            });
        });

        function renderVendorDashboard() {
            const metrics = window.MarketplaceApp.getVendorMetrics();
            const state = window.MarketplaceApp.getState();
            const vendor = state.users.vendors.find((item) => item.id === 'vendor-techhub');

            document.getElementById('vendorName').textContent = vendor?.name || 'Vendor';
            document.getElementById('vendorSubtitle').textContent = vendor?.verified ? 'Verified Vendor' : 'Vendor Account';
            document.getElementById('vendorTotalEarnings').textContent = window.MarketplaceApp.formatCurrency(metrics.totalEarnings);
            document.getElementById('vendorTotalProducts').textContent = metrics.totalProducts;
            document.getElementById('vendorTotalOrders').textContent = metrics.totalOrders;
            document.getElementById('vendorAverageRating').textContent = metrics.averageRating.toFixed(1);

            document.getElementById('vendorDashboardProducts').innerHTML = metrics.products.map((product) => `
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <img src="${window.MarketplaceApp.resolveImage(product.image)}" class="product-preview" alt="${product.name}">
                            <div>
                                <h6 class="mb-0 fw-semibold">${product.name}</h6>
                                <small class="text-muted">SKU: ${product.sku}</small>
                            </div>
                        </div>
                    </td>
                    <td class="fw-semibold">${window.MarketplaceApp.formatCurrency(product.price)}</td>
                    <td><span class="badge bg-${product.stock > 5 ? 'success' : product.stock > 0 ? 'warning' : 'danger'} bg-opacity-10 text-${product.stock > 5 ? 'success' : product.stock > 0 ? 'warning' : 'danger'} border border-${product.stock > 5 ? 'success' : product.stock > 0 ? 'warning' : 'danger'} border-opacity-20">${product.stock > 0 ? `In Stock (${product.stock})` : 'Out of Stock'}</span></td>
                    <td><span class="badge bg-${product.status === 'active' ? 'info' : 'secondary'} bg-opacity-10 text-${product.status === 'active' ? 'info' : 'secondary'} border border-${product.status === 'active' ? 'info' : 'secondary'} border-opacity-20">${product.status === 'active' ? 'Active' : 'Inactive'}</span></td>
                    <td>${product.salesCount} units</td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary me-1" href="vendor-my-products.html"><i class="fa-solid fa-pen-to-square"></i></a>
                        <button class="btn btn-sm btn-outline-secondary" type="button"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');

            document.getElementById('vendorRecentOrders').innerHTML = metrics.recentOrders.map((order) => {
                const product = window.MarketplaceApp.getProductById(order.items[0]?.productId);
                const tone = window.MarketplaceApp.getStatusTone(order.status);
                return `
                    <div class="mb-3 p-3 border rounded-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="fw-semibold">#${order.id}</div>
                            <span class="badge bg-${tone} bg-opacity-10 text-${tone} border border-${tone} border-opacity-20">${order.status}</span>
                        </div>
                        <div class="text-muted small mt-1">${product?.name || 'Marketplace Product'}</div>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="fa-regular fa-calendar-days"></i> ${new Date(order.createdAt).toISOString().slice(0, 10)}</small>
                            <div class="fw-bold text-dark">${window.MarketplaceApp.formatCurrency(order.total)}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        renderVendorDashboard();
        window.addEventListener('marketplace:state-changed', renderVendorDashboard);
    </script>
</body>
</html>
