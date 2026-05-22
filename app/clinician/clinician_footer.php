<?php
// clinician/clinician_footer.php
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
            if(params.get('status') === '<?= $notify_text ?>') {
                $notification({text:'<?= addslashes($notify_text) ?>', variant:'<?= $notify_variant ?>', position:'right-top'});
                const url = new URL(window.location);
                url.searchParams.delete('status');
                window.history.replaceState({}, document.title, url.pathname);
            }
         "></div>
    <?php endif; ?>
</body>
</html>