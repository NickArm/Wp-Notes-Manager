<?php
/**
 * Debug script to check notes in database
 */

require_once('../../../wp-config.php');
global $wpdb;

echo "=== NOTES DEBUG ===\n";
echo "Total notes: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpnm_notes WHERE status != 'deleted'") . "\n";
echo "Dashboard notes: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpnm_notes WHERE note_type = 'dashboard' AND status != 'deleted'") . "\n";
echo "Post notes: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpnm_notes WHERE note_type = 'post' AND status != 'deleted'") . "\n";
echo "Page notes: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpnm_notes WHERE note_type = 'page' AND status != 'deleted'") . "\n";

echo "\n=== ALL NOTE TYPES ===\n";
$types = $wpdb->get_results("SELECT note_type, COUNT(*) as count FROM {$wpdb->prefix}wpnm_notes WHERE status != 'deleted' GROUP BY note_type");
foreach($types as $type) {
    echo $type->note_type . ": " . $type->count . "\n";
}

echo "\n=== RECENT NOTES (LAST 5) ===\n";
$recent = $wpdb->get_results("SELECT id, title, note_type, status, created_at FROM {$wpdb->prefix}wpnm_notes WHERE status != 'deleted' ORDER BY created_at DESC LIMIT 5");
foreach($recent as $note) {
    echo "ID: {$note->id}, Title: {$note->title}, Type: {$note->note_type}, Status: {$note->status}, Created: {$note->created_at}\n";
}

echo "\n=== DASHBOARD WIDGET QUERY (OLD) ===\n";
$dashboard_notes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpnm_notes WHERE note_type = 'dashboard' AND status != 'deleted' ORDER BY created_at DESC LIMIT 5");
echo "Dashboard widget found " . count($dashboard_notes) . " notes\n";

echo "\n=== DASHBOARD WIDGET QUERY (NEW - getAllNotes) ===\n";
$all_notes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpnm_notes WHERE status != 'deleted' ORDER BY created_at DESC LIMIT 5");
echo "All notes widget found " . count($all_notes) . " notes\n";
foreach($all_notes as $note) {
    echo "- {$note->title} (Type: {$note->note_type}, ID: {$note->id})\n";
}
?>
