<?php
if (!defined('ABSPATH')) {
    exit;
}

class ACD_Init {
    public function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    public static function activate() {
        self::create_files();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    private function includes() {
        require_once ACD_PLUGIN_DIR . 'includes/class-acd-shortcodes.php';
        require_once ACD_PLUGIN_DIR . 'includes/class-acd-assets.php';
    }

    private function init_hooks() {
        // Initialize classes
        new ACD_Shortcodes();
        new ACD_Assets();
    }

    private static function create_files() {
        $files = array(
            array(
                'base'    => ACD_PLUGIN_DIR . 'assets/css',
                'file'    => 'acd-styles.css',
                'content' => self::get_default_css()
            ),
            array(
                'base'    => ACD_PLUGIN_DIR . 'assets/js',
                'file'    => 'acd-scripts.js',
                'content' => self::get_default_js()
            )
        );

        foreach ($files as $file) {
            if (wp_mkdir_p($file['base']) && !file_exists(trailingslashit($file['base']) . $file['file'])) {
                $file_handle = @fopen(trailingslashit($file['base']) . $file['file'], 'w');
                if ($file_handle) {
                    fwrite($file_handle, $file['content']);
                    fclose($file_handle);
                }
            }
        }
    }

    private static function get_default_css() {
        return '/* Advanced Categories Display Styles */
.acd-container {
    display: flex;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.acd-list-view {
    flex: 1;
    max-width: 250px;
}

.acd-grid-view {
    flex: 3;
}

.acd-section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

/* List View Styles */
.acd-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.acd-list-item {
    margin-bottom: 8px;
}

.acd-list-item a {
    display: block;
    padding: 10px 15px;
    color: #555;
    text-decoration: none;
    transition: all 0.3s ease;
    border-radius: 4px;
}

.acd-list-item.all-categories a {
    font-weight: bold;
    color: #000;
}

.acd-list-item a:hover {
    background-color: #f5f5f5;
    color: #222;
}

/* Grid View Styles */
.acd-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 20px;
}

.acd-grid-item {
    text-align: center;
    transition: transform 0.3s ease;
}

.acd-grid-item:hover {
    transform: translateY(-5px);
}

.acd-grid-link {
    text-decoration: none;
    color: #333;
    display: block;
}

.acd-image-container {
    position: relative;
    padding-top: 100%; /* 1:1 Aspect Ratio */
    overflow: hidden;
    border-radius: 8px;
    margin-bottom: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.acd-category-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.acd-grid-link:hover .acd-category-image {
    transform: scale(1.05);
}

.acd-category-name {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-top: 5px;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .acd-container {
        flex-direction: column;
    }
    
    .acd-list-view {
        max-width: 100%;
        margin-bottom: 20px;
    }
    
    .acd-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
}

@media (max-width: 480px) {
    .acd-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
    }
}';
    }

    private static function get_default_js() {
        return 'jQuery(document).ready(function($) {
    // Add active class to clicked list item
    $(".acd-list-item a").on("click", function(e) {
        e.preventDefault();
        $(".acd-list-item").removeClass("active");
        $(this).parent().addClass("active");
        
        // You can add AJAX loading of category products here if needed
        var categoryUrl = $(this).attr("href");
        // window.location.href = categoryUrl; // Uncomment to enable direct linking
    });
});';
    }
}