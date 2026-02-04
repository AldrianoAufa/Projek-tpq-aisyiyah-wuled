<?php
/**
 * Start output buffering with error control
 * This prevents 'headers already sent' errors
 */
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';

// Clean any previous output
if (ob_get_level() > 0) {
    ob_clean();
}

// Redirect if not logged in or unauthorized
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'guru', 'kepsek'])) {
    header("Location: login.php");
    exit;
}

$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TPQ Aisyiyah Wuled</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3,
        .font-serif {
            font-family: 'Merriweather', serif;
        }
    </style>
</head>

<body class="bg-gray-50 h-screen overflow-hidden flex">
    <!-- Mobile Header -->
    <div class="md:hidden fixed w-full bg-teal-900 shadow-md z-20 flex justify-between items-center px-4 h-16">
        <div class="flex items-center">
            <i class="fas fa-graduation-cap text-teal-100 text-2xl mr-2"></i>
            <span class="font-bold text-white font-serif text-lg">TPQ Admin</span>
        </div>
        <button id="mobile-menu-btn" class="text-teal-100 hover:text-white focus:outline-none">
            <i class="fas fa-bars fa-lg"></i>
        </button>
    </div>

    <!-- Sidebar -->
    <div id="sidebar" class="bg-teal-900 shadow-xl flex flex-col w-64 fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-200 ease-in-out z-30 pt-16 md:pt-0">
        <div class="p-6 flex items-center justify-center border-b border-teal-800 hidden md:flex">
            <i class="fas fa-graduation-cap text-teal-400 text-3xl mr-3"></i>
            <span class="font-bold text-white text-xl font-serif">TPQ Admin</span>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 space-y-1">
            <a href="?section=dashboard" class="nav-link block px-6 py-3 text-teal-100 hover:bg-teal-800 hover:text-white transition-colors <?php echo $section == 'dashboard' ? 'bg-teal-800 text-white border-r-4 border-teal-400' : ''; ?>">
                <i class="fas fa-home w-6"></i> Dashboard
            </a>

            <?php if ($role == 'admin' || $role == 'guru'): ?>
                <a href="?section=siswa_management" class="nav-link block px-6 py-3 text-teal-100 hover:bg-teal-800 hover:text-white transition-colors <?php echo $section == 'siswa_management' ? 'bg-teal-800 text-white border-r-4 border-teal-400' : ''; ?>">
                    <i class="fas fa-user-graduate w-6"></i> Data Siswa
                </a>
                
                <div class="px-6 py-2 text-xs font-semibold text-teal-400 uppercase tracking-wider mt-2">Pembayaran</div>
                <a href="?section=spp1_management" class="nav-link block px-6 py-3 text-teal-100 hover:bg-teal-800 hover:text-white transition-colors <?php echo $section == 'spp1_management' ? 'bg-teal-800 text-white border-r-4 border-teal-400' : ''; ?>">
                    <i class="fas fa-hand-holding-usd w-6"></i> SPP Harian
                </a>
                <a href="?section=spp2_management" class="nav-link block px-6 py-3 text-teal-100 hover:bg-teal-800 hover:text-white transition-colors <?php echo $section == 'spp2_management' ? 'bg-teal-800 text-white border-r-4 border-teal-400' : ''; ?>">
                    <i class="fas fa-calendar-check w-6"></i> SPP Mingguan
                </a>
                <a href="?section=biaya_lain_payment_management" class="nav-link block px-6 py-3 text-teal-100 hover:bg-teal-800 hover:text-white transition-colors <?php echo $section == 'biaya_lain_payment_management' ? 'bg-teal-800 text-white border-r-4 border-teal-400' : ''; ?>">
                    <i class="fas fa-receipt w-6"></i> Biaya Lain
                </a>
            <?php endif; ?>

            <?php if ($role == 'admin'): ?>
                <div class="px-6 py-2 text-xs font-semibold text-teal-400 uppercase tracking-wider mt-2">Master Data</div>
                <a href="?section=kelas_management" class="nav-link block px-6 py-3 text-teal-100 hover:bg-teal-800 hover:text-white transition-colors <?php echo $section == 'kelas_management' ? 'bg-teal-800 text-white border-r-4 border-teal-400' : ''; ?>">
                    <i class="fas fa-chalkboard w-6"></i> Data Kelas & Guru
                </a>
                <a href="?section=biaya_lain_management" class="nav-link block px-6 py-3 text-teal-100 hover:bg-teal-800 hover:text-white transition-colors <?php echo $section == 'biaya_lain_management' ? 'bg-teal-800 text-white border-r-4 border-teal-400' : ''; ?>">
                    <i class="fas fa-money-bill w-6"></i> Master Biaya Lain
                </a>
                <a href="?section=libur_management" class="nav-link block px-6 py-3 text-teal-100 hover:bg-teal-800 hover:text-white transition-colors <?php echo $section == 'libur_management' ? 'bg-teal-800 text-white border-r-4 border-teal-400' : ''; ?>">
                    <i class="fas fa-calendar-alt w-6"></i> Hari Libur
                </a>
                <a href="?section=settings" class="nav-link block px-6 py-3 text-teal-100 hover:bg-teal-800 hover:text-white transition-colors <?php echo $section == 'settings' ? 'bg-teal-800 text-white border-r-4 border-teal-400' : ''; ?>">
                    <i class="fas fa-cog w-6"></i> Pengaturan
                </a>
            <?php endif; ?>
            
            <div class="mt-auto p-6 border-t border-teal-800">
                <a href="logout.php" class="flex items-center text-teal-100 hover:text-white transition-colors">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden relative">
        <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center hidden md:flex border-b border-gray-200">
            <h2 id="page-title" class="text-2xl font-bold text-teal-900 capitalize font-serif"><?php echo str_replace('_', ' ', $section); ?></h2>
            <div class="flex items-center">
                <span class="text-gray-600 mr-3">Halo, <b class="text-teal-700"><?php echo $_SESSION['name']; ?></b> (<?php echo ucfirst($role); ?>)</span>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name']); ?>&background=0f766e&color=fff" class="h-10 w-10 rounded-full shadow-sm">
            </div>
        </header>
        <!-- Mobile Page Title -->
        <div class="bg-white shadow-sm px-4 py-3 md:hidden flex justify-between items-center border-b border-gray-200">
            <h2 id="mobile-page-title" class="text-lg font-bold text-teal-900 capitalize font-serif"><?php echo str_replace('_', ' ', $section); ?></h2>
        </div>

        <main id="main-content" class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 md:p-6 relative">
            <?php
            $file = "section/{$section}.php";
            if (file_exists($file)) {
                include $file;
            } else {
                echo "<div class='bg-white p-8 rounded-xl shadow-sm text-center text-gray-500'>Halaman tidak ditemukan atau sedang dalam pengembangan.</div>";
            }
            ?>
        </main>
    </div>

    <script>
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const pageTitle = document.getElementById('page-title');
        const mobilePageTitle = document.getElementById('mobile-page-title');

        // Mobile Menu Toggle
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768 && !sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        });

        // AJAX Navigation
        document.addEventListener('DOMContentLoaded', () => {
            document.body.addEventListener('click', (e) => {
                const link = e.target.closest('a.nav-link');
                if (link) {
                    e.preventDefault();
                    const url = link.getAttribute('href');
                    loadContent(url);

                    // Update Active State in Sidebar
                    document.querySelectorAll('.nav-link').forEach(el => {
                        el.classList.remove('bg-teal-800', 'text-white', 'border-r-4', 'border-teal-400');
                        el.classList.add('text-teal-100');
                    });
                    link.classList.add('bg-teal-800', 'text-white', 'border-r-4', 'border-teal-400');
                    link.classList.remove('text-teal-100');

                    // Close sidebar on mobile
                    if (!sidebar.classList.contains('-translate-x-full') && window.innerWidth < 768) {
                        sidebar.classList.add('-translate-x-full');
                    }
                }
            });

            // Handle Form Submissions
            document.body.addEventListener('submit', (e) => {
                const form = e.target;
                if (e.target.tagName === 'FORM' && !form.classList.contains('no-ajax')) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const url = window.location.href; // Post to current URL usually

                    // Show loading state
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    }

                    fetch(url, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(html => {
                            updateContent(html);
                        })
                        .catch(err => console.error('Error:', err));
                }
            });
        });

        function loadContent(url) {
            // Show loading indicator
            mainContent.style.opacity = '0.5';

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    updateContent(html);
                    history.pushState(null, '', url);
                    mainContent.style.opacity = '1';
                })
                .catch(err => {
                    console.error('Error loading content:', err);
                    mainContent.style.opacity = '1';
                });
        }

        function updateContent(html) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Update Main Content
            const newContent = doc.getElementById('main-content').innerHTML;
            mainContent.innerHTML = newContent;

            // Update Titles
            const newTitle = doc.getElementById('page-title') ? doc.getElementById('page-title').innerText : '';
            if (pageTitle && newTitle) pageTitle.innerText = newTitle;
            if (mobilePageTitle && newTitle) mobilePageTitle.innerText = newTitle;

            // Re-execute scripts in the new content (Crucial for Chart.js)
            const scripts = mainContent.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                if (script.src) {
                    newScript.src = script.src;
                } else {
                    newScript.textContent = script.textContent;
                }
                document.body.appendChild(newScript);
            });
        }

        // Handle Browser Back/Forward
        window.addEventListener('popstate', () => {
            loadContent(window.location.href);
        });
    </script>
</body>

</html>