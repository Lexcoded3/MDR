<?php
// nurse/nurse_footer.php
 $notify_text    = $notify_text ?? '';
 $notify_variant = $notify_variant ?? 'success';
?>
      </main>
    </div>
    <div id="x-teleport-target"></div>
    <script>
      window.addEventListener("DOMContentLoaded", () => Alpine.start());
    </script>
   <?php if ($notify_text): ?>
<div x-data
     x-init="
        const params = new URLSearchParams(window.location.search);
        // Trigger if PHP variable is set (for immediate errors) 
        // OR if URL has the status (for redirects)
        if('<?= $notify_text ?>' !== '' || params.get('status')) {
            $notification({
                text:'<?= addslashes($notify_text) ?>', 
                variant:'<?= $notify_variant ?>', 
                position:'right-top'
            });
            
            // Clean up the URL if needed
            if(params.get('status')) {
                const url = new URL(window.location);
                url.searchParams.delete('status');
                window.history.replaceState({}, document.title, url.pathname);
            }
        }
     "></div>
<?php endif; ?>
</body>
</html>