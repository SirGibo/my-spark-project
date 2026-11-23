</div> <!-- End of main-wrapper -->

    <!-- Citation Modal -->
    <div class="modal fade" id="citationModal" tabindex="-1" aria-labelledby="citationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="citationModalLabel">
                        <i class="bi bi-quote me-2"></i> Cite Research Study
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold text-body mb-3" id="citePaperTitle">Paper Title Here</h6>

                    <!-- Citation Tabs -->
                    <ul class="nav nav-tabs mb-3" id="citationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold" id="apa-tab" data-bs-toggle="tab" data-bs-target="#apa-pane" type="button" role="tab">APA 7th</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="mla-tab" data-bs-toggle="tab" data-bs-target="#mla-pane" type="button" role="tab">MLA 9th</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="ieee-tab" data-bs-toggle="tab" data-bs-target="#ieee-pane" type="button" role="tab">IEEE</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="chicago-tab" data-bs-toggle="tab" data-bs-target="#chicago-pane" type="button" role="tab">Chicago</button>
                        </li>
                    </ul>

                    <!-- Citation Tab Panes -->
                    <div class="tab-content border rounded p-3 bg-body-tertiary">
                        <div class="tab-pane fade show active" id="apa-pane" role="tabpanel">
                            <p class="mb-2 font-monospace small" id="citeApaText"></p>
                            <button class="btn btn-sm btn-outline-primary" onclick="copyCitation('citeApaText', this)"><i class="bi bi-clipboard"></i> Copy APA</button>
                        </div>
                        <div class="tab-pane fade" id="mla-pane" role="tabpanel">
                            <p class="mb-2 font-monospace small" id="citeMlaText"></p>
                            <button class="btn btn-sm btn-outline-primary" onclick="copyCitation('citeMlaText', this)"><i class="bi bi-clipboard"></i> Copy MLA</button>
                        </div>
                        <div class="tab-pane fade" id="ieee-pane" role="tabpanel">
                            <p class="mb-2 font-monospace small" id="citeIeeeText"></p>
                            <button class="btn btn-sm btn-outline-primary" onclick="copyCitation('citeIeeeText', this)"><i class="bi bi-clipboard"></i> Copy IEEE</button>
                        </div>
                        <div class="tab-pane fade" id="chicago-pane" role="tabpanel">
                            <p class="mb-2 font-monospace small" id="citeChicagoText"></p>
                            <button class="btn btn-sm btn-outline-primary" onclick="copyCitation('citeChicagoText', this)"><i class="bi bi-clipboard"></i> Copy Chicago</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-body-tertiary border-top py-4 mt-5">
        <div class="container text-center text-md-start">
            <div class="row align-items-center">
                <div class="col-md-7 mb-2 mb-md-0">
                    <p class="mb-0 text-body-secondary small">
                        <strong>Project SPARK</strong> &copy; <?= date('Y') ?> Regional Science High School for Region II. All Rights Reserved.
                    </p>
                    <small class="text-body-tertiary">Student Portal for Accessing Research Knowledge</small>
                </div>
                <div class="col-md-5 text-md-end">
                    <span class="badge bg-body-secondary text-body border">
                        <i class="bi bi-shield-check text-success me-1"></i> Secure Institutional Repository
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Dark/Light Theme & Citation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const themeIcon = document.getElementById('themeIcon');
            
            const updateThemeUI = (theme) => {
                document.documentElement.setAttribute('data-bs-theme', theme);
                localStorage.setItem('spark_theme', theme);
                if (themeToggleBtn && themeIcon) {
                    themeIcon.className = theme === 'dark' ? 'bi bi-sun-fill text-warning' : 'bi bi-moon-stars-fill';
                }
            };

            const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
            updateThemeUI(currentTheme);

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', () => {
                    const activeTheme = document.documentElement.getAttribute('data-bs-theme');
                    updateThemeUI(activeTheme === 'dark' ? 'light' : 'dark');
                });
            }
        });

        // Citation Modal Handler
        function showCitationModal(title, author, year) {
            document.getElementById('citePaperTitle').innerText = title;

            const cleanYear = year.split('-')[0] || new Date().getFullYear();
            const source = "Project SPARK: Student Portal for Accessing Research Knowledge, Regional Science High School for Region II.";

            // Format strings
            document.getElementById('citeApaText').innerText = `${author}. (${cleanYear}). ${title}. ${source}`;
            document.getElementById('citeMlaText').innerText = `${author}. "${title}." ${source} ${cleanYear}.`;
            document.getElementById('citeIeeeText').innerText = `${author}, "${title}," ${source} ${cleanYear}.`;
            document.getElementById('citeChicagoText').innerText = `${author}. "${title}." ${source} (${cleanYear}).`;

            const modal = new bootstrap.Modal(document.getElementById('citationModal'));
            modal.show();
        }

        // Copy to Clipboard Action
        function copyCitation(elementId, btn) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
                btn.classList.replace('btn-outline-primary', 'btn-success');
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.replace('btn-success', 'btn-outline-primary');
                }, 2000);
            });
        }
    </script>
</body>
</html>