        </div><!-- /admin-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const activeLink = document.querySelector('.admin-sidebar .nav-link.active');
    const pageTitle  = document.getElementById('page-title');
    if (activeLink && pageTitle) {
        const txt = activeLink.textContent.trim();
        if (txt) pageTitle.textContent = txt;
    }

    // Mobile Sidebar Toggle
    const mobileToggleBtn = document.getElementById('mobileToggleBtn');
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (mobileToggleBtn && sidebar && overlay) {
        mobileToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
});

function toggleAdminProfile(e) {
    e.stopPropagation();
    const dd = document.getElementById('adminProfileDropdown');
    const open = dd.style.opacity === '1';
    dd.style.opacity      = open ? '0'       : '1';
    dd.style.visibility   = open ? 'hidden'  : 'visible';
    dd.style.transform    = open ? 'translateY(-10px) scale(.97)' : 'translateY(0) scale(1)';
}
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('adminProfileWrap');
    const dd   = document.getElementById('adminProfileDropdown');
    if (wrap && !wrap.contains(e.target)) {
        dd.style.opacity    = '0';
        dd.style.visibility = 'hidden';
        dd.style.transform  = 'translateY(-10px) scale(.97)';
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const dd = document.getElementById('adminProfileDropdown');
        if (dd) { dd.style.opacity = '0'; dd.style.visibility = 'hidden'; dd.style.transform = 'translateY(-10px) scale(.97)'; }
    }
});
</script>
</body>
</html>
