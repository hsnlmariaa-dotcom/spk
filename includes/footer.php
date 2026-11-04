            </div>
        </main>

        <!-- Dashboard Footer -->
        <footer class="dashboard-footer no-print">
            <div>
                <strong style="color: #0f172a;">CDSS-DBD</strong> — Sistem Pendukung Keputusan Klinis Diagnosis Awal Demam Berdarah Dengue
                <span style="display: block; font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    Metode Certainty Factor (Shortliffe & Buchanan) • Standar Diagnosis WHO & Kemenkes RI
                </span>
            </div>
            <div style="text-align: right;">
                <span class="badge badge-success" style="font-size: 0.72rem;">Model Validation: 100% Sensitivity</span>
                <span style="display: block; font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    Versi 2.0 • PHP 8.1 & MySQL 8.0
                </span>
            </div>
        </footer>
    </div>
</div>

<!-- Chart.js (Offline Local Asset) -->
<script src="<?= BASE_URL ?>/assets/js/chart.min.js"></script>
<!-- Main Dashboard App Script -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<script>
// Mobile Sidebar Toggle
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('dashboardSidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('sidebar-open');
        });
        // Close sidebar on click outside
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 1024 && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('sidebar-open');
            }
        });
    }
});
</script>
</body>
</html>
