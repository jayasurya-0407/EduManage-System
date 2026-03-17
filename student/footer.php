</div> <!-- /container -->
</div> <!-- /student-main -->

<footer style="background:#0f172a;border-top:1px solid rgba(99,102,241,.1);padding:1.25rem 0;margin-top:2rem;">
    <div class="container text-center" style="color:#475569;font-size:.78rem;">
        &copy; <?= date('Y') ?> Life Skills Coaching. All rights reserved.
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* Profile dropdown toggle */
function toggleProfile(e) {
    e.stopPropagation();
    const dd = document.getElementById('profileDropdown');
    dd.classList.toggle('open');
}
/* Close when clicking outside */
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('profileWrap');
    const dd   = document.getElementById('profileDropdown');
    if (wrap && !wrap.contains(e.target)) dd.classList.remove('open');
});
/* Close on Escape key */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.getElementById('profileDropdown')?.classList.remove('open');
});
</script>
</body>
</html>
