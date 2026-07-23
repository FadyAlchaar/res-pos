<?php require_once 'includes/auth.php'; ?>
<?php require_once '../config/language.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="page-header">
    <h1 class="page-title">Hub Links Management</h1>
    <p class="page-description">Manage the links shown on the customer hub page (QR code destination).</p>
</div>

<div class="page-content">
    <div class="card">
        <div class="card-header">
            <h3>Edit Links</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="api/save_hub_links.php">
                <div id="links-container">
                    <?php
                    $linksFile = __DIR__ . '/../config/hub_links.json';
                    $links = [];
                    if (file_exists($linksFile)) {
                        $data = json_decode(file_get_contents($linksFile), true);
                        $links = $data['links'] ?? [];
                    }
                    foreach ($links as $index => $link):
                    ?>
                    <div class="link-group" style="border:1px solid #e9ecef; padding:15px; margin-bottom:15px; border-radius:15px;">
                        <div class="form-group">
                            <label>Title (English)</label>
                            <input type="text" name="links[<?php echo $index; ?>][title_en]" value="<?php echo htmlspecialchars($link['title_en']); ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Title (Arabic)</label>
                            <input type="text" name="links[<?php echo $index; ?>][title_ar]" value="<?php echo htmlspecialchars($link['title_ar']); ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>URL</label>
                            <input type="url" name="links[<?php echo $index; ?>][url]" value="<?php echo htmlspecialchars($link['url']); ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Icon (Font Awesome class, e.g., fa-instagram)</label>
                            <input type="text" name="links[<?php echo $index; ?>][icon]" value="<?php echo htmlspecialchars($link['icon']); ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Color (hex or CSS color)</label>
                            <input type="text" name="links[<?php echo $index; ?>][color]" value="<?php echo htmlspecialchars($link['color']); ?>" class="form-control" required>
                        </div>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeLink(this)">Remove</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addLink()">+ Add Link</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<script>
function addLink() {
    const container = document.getElementById('links-container');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'link-group';
    div.style.border = '1px solid #e9ecef';
    div.style.padding = '15px';
    div.style.marginBottom = '15px';
    div.style.borderRadius = '15px';
    div.innerHTML = `
        <div class="form-group"><label>Title (English)</label><input type="text" name="links[${index}][title_en]" class="form-control" required></div>
        <div class="form-group"><label>Title (Arabic)</label><input type="text" name="links[${index}][title_ar]" class="form-control" required></div>
        <div class="form-group"><label>URL</label><input type="url" name="links[${index}][url]" class="form-control" required></div>
        <div class="form-group"><label>Icon (Font Awesome class)</label><input type="text" name="links[${index}][icon]" class="form-control" required></div>
        <div class="form-group"><label>Color</label><input type="text" name="links[${index}][color]" class="form-control" required></div>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeLink(this)">Remove</button>
    `;
    container.appendChild(div);
}
function removeLink(btn) {
    btn.closest('.link-group').remove();
}
</script>

<?php include 'layout/sidebar_footer.php'; ?>