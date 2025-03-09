<?php
// Footer content

// Don't display footer on login page
if (basename($_SERVER['SCRIPT_NAME']) === 'login.php') {
    return;
}
?>
        </main>
        
        <footer class="bg-white dark:bg-gray-800 shadow mt-auto">
            <div class="container mx-auto py-4 px-4">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-500 dark:text-gray-400 text-sm">
                        &copy; <?= date('Y') ?> Supreme Rice Mills. All rights reserved.
                    </div>
                    <div class="text-gray-500 dark:text-gray-400 text-sm mt-2 md:mt-0">
                        Version 1.0.0
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- General scripts -->
    <script src="/assets/js/scripts.js"></script>
    
    <?php
    // Page-specific JavaScript
    $page_js = array_key_exists(0, $url) ? $url[0] : 'login';
    $js_file = "/assets/js/{$page_js}.js";
    if (file_exists('.' . $js_file)): 
    ?>
    <script src="<?= $js_file ?>"></script>
    <?php endif; ?>
</body>
</html>