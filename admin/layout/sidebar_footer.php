        </main>
    </div>
    
    <script>
        // Update printer badge
        function updatePrinterBadge() {
            fetch('get_printer_status.php')
                .then(response => response.json())
                .then(data => {
                    const offlineCount = data.filter(p => p.status !== 'online').length;
                    const badge = document.getElementById('printer-badge');
                    if (badge && offlineCount > 0) {
                        badge.textContent = offlineCount;
                        badge.style.display = 'inline-block';
                    } else if (badge) {
                        badge.style.display = 'none';
                    }
                });
        }
        
        // Auto-update every 30 seconds
        setInterval(updatePrinterBadge, 30000);
        updatePrinterBadge();

        function openAboutModal() {
            document.getElementById('about-modal').style.display = 'flex';
        }
        function closeAboutModal() {
            document.getElementById('about-modal').style.display = 'none';
        }
        document.addEventListener('DOMContentLoaded', function() {
            var aboutBtn = document.getElementById('about-btn');
            if (aboutBtn) {
                aboutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openAboutModal();
                });
            }
            window.onclick = function(event) {
                var modal = document.getElementById('about-modal');
                if (event.target === modal) {
                    closeAboutModal();
                }
            };
        });
    </script>
</body>
</html>