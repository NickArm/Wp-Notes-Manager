<?php
/**
 * Script to fix security issues in WP Notes Manager
 */

$files = [
    'src/Admin/AdminManager.php',
    'src/Notes/NotesManager.php', 
    'src/Notifications/NotificationManager.php'
];

foreach ($files as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (!file_exists($filepath)) {
        echo "File not found: $filepath\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Fix common security issues
    $replacements = [
        // Fix echo without escaping
        '/echo \$([a-zA-Z_][a-zA-Z0-9_]*);/' => 'echo esc_html($$1);',
        '/echo \$([a-zA-Z_][a-zA-Z0-9_]*)\./ ' => 'echo esc_html($$1) . ',
        
        // Fix admin_url without escaping
        '/admin_url\(([^)]+)\)/' => 'esc_url(admin_url($1))',
        
        // Fix printf without escaping
        '/printf\(\'<option value="%d">%s<\/option>\', \$([a-zA-Z_][a-zA-Z0-9_]*)->ID, \$([a-zA-Z_][a-zA-Z0-9_]*)\);/' => 'printf(\'<option value="%d">%s</option>\', $$1->ID, esc_html($$2));',
        
        // Fix paginate_links without escaping
        '/echo paginate_links\(([^)]+)\);/' => 'echo wp_kses_post(paginate_links($1));',
        
        // Fix date_i18n without escaping
        '/date_i18n\(([^)]+)\)/' => 'esc_html(date_i18n($1))',
    ];
    
    foreach ($replacements as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    file_put_contents($filepath, $content);
    echo "Fixed security issues in: $file\n";
}

echo "Security fixes completed!\n";
