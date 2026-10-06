<?php
// includes/footer.php
// Common footer for all dashboards with mobile menu script
?>
            </div>
        </div>
    </div>

    <!-- Mobile Menu JavaScript -->
    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const body = document.body;

            function openSidebar() {
                sidebar.classList.remove('sidebar-closed');
                sidebar.classList.add('sidebar-open');
                overlay.classList.remove('hidden');
                body.classList.add('no-scroll');
            }

            function closeSidebarFunc() {
                sidebar.classList.remove('sidebar-open');
                sidebar.classList.add('sidebar-closed');
                overlay.classList.add('hidden');
                body.classList.remove('no-scroll');
            }

            if (menuToggle) {
                menuToggle.addEventListener('click', openSidebar);
            }
            if (closeSidebar) {
                closeSidebar.addEventListener('click', closeSidebarFunc);
            }
            if (overlay) {
                overlay.addEventListener('click', closeSidebarFunc);
            }

            // Close sidebar on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidebarFunc();
                }
            });

            // Close sidebar on window resize (if screen becomes large)
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    closeSidebarFunc();
                }
            });
        });


    </script>

    
</body>
</html>