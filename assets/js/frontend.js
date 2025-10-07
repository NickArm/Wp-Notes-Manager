/* WP Notes Manager - Frontend JavaScript */

jQuery(document).ready(function($) {
    "use strict";
    
    // Initialize frontend notes
    initFrontendNotes();
    
    function initFrontendNotes() {
        // Create notes toggle button
        createNotesToggle();
        
        // Create notes panel
        createNotesPanel();
        
        // Load notes for current page
        loadPageNotes();
    }
    
    function createNotesToggle() {
        var toggle = $("<button>")
            .addClass("wpnm-frontend-notes-toggle")
            .html("&#128221;")
            .attr("title", "View Notes")
            .on("click", toggleNotesPanel);
        
        $("body").append(toggle);
    }
    
    function createNotesPanel() {
        var panel = $("<div>")
            .addClass("wpnm-frontend-notes")
            .hide();
        
        var header = $("<div>")
            .addClass("wpnm-frontend-notes-header")
            .html("<span>Page Notes</span><span>×</span>")
            .on("click", toggleNotesPanel);
        
        var content = $("<div>")
            .addClass("wpnm-frontend-notes-content");
        
        panel.append(header).append(content);
        $("body").append(panel);
    }
    
    function toggleNotesPanel() {
        $(".wpnm-frontend-notes").slideToggle(300);
    }
    
    function loadPageNotes() {
        var postId = getCurrentPostId();
        if (!postId) return;
        
        $.ajax({
            url: wpnm_frontend.ajax_url,
            type: "POST",
            data: {
                action: "wpnm_get_notes",
                note_type: getCurrentPostType(),
                post_id: postId,
                nonce: wpnm_frontend.nonce
            },
            success: function(response) {
                if (response.success && response.data.notes) {
                    displayNotes(response.data.notes);
                }
            },
            error: function() {
                console.log("Failed to load notes");
            }
        });
    }
    
    function displayNotes(notes) {
        var content = $(".wpnm-frontend-notes-content");
        content.empty();
        
        if (notes.length === 0) {
            content.html("<p>No notes for this page.</p>");
            return;
        }
        
        notes.forEach(function(note) {
            var noteElement = createNoteElement(note);
            content.append(noteElement);
        });
    }
    
    function createNoteElement(note) {
        var noteDiv = $("<div>").addClass("wpnm-frontend-note");
        
        var title = $("<div>")
            .addClass("wpnm-frontend-note-title")
            .text(note.title);
        
        var content = $("<div>")
            .addClass("wpnm-frontend-note-content")
            .html(note.content);
        
        var meta = $("<div>")
            .addClass("wpnm-frontend-note-meta")
            .text("Priority: " + note.priority);
        
        noteDiv.append(title).append(content).append(meta);
        
        return noteDiv;
    }
    
    function getCurrentPostId() {
        // Try to get post ID from body data attribute (set by WordPress)
        var postId = $("body").data("post-id");
        if (postId) return postId;
        
        // Try to get from global WordPress object
        if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            var postId = wp.data.select('core/editor').getCurrentPostId();
            if (postId) return postId;
        }
        
        // Try to extract from URL for single posts/pages
        var url = window.location.href;
        var path = window.location.pathname;
        
        // For single posts/pages, try to match against WordPress URL structure
        // This is a fallback - ideally the theme should provide post ID via body data
        if (path && path !== '/' && !path.includes('/wp-admin/')) {
            // Try to get post ID via AJAX call to WordPress
            var postId = null;
            $.ajax({
                url: wpnm_frontend.ajax_url,
                type: "POST",
                async: false, // Synchronous for this fallback
                data: {
                    action: "wpnm_get_current_post_id",
                    url: url,
                    nonce: wpnm_frontend.nonce
                },
                success: function(response) {
                    if (response.success && response.data.post_id) {
                        postId = response.data.post_id;
                    }
                }
            });
            if (postId) return postId;
        }
        
        return null;
    }
    
    function getCurrentPostType() {
        // Try to get post type from various sources
        var postType = $("body").data("post-type");
        if (postType) return postType;
        
        postType = $("input[name='post_type']").val();
        if (postType) return postType;
        
        // Default to page
        return "page";
    }
    
    // Close notes panel when clicking outside
    $(document).on("click", function(e) {
        if (!$(e.target).closest(".wpnm-frontend-notes, .wpnm-frontend-notes-toggle").length) {
            $(".wpnm-frontend-notes").slideUp(300);
        }
    });
    
    // Keyboard shortcuts
    $(document).on("keydown", function(e) {
        // Alt + N to toggle notes panel
        if (e.altKey && e.keyCode === 78) {
            e.preventDefault();
            toggleNotesPanel();
        }
    });
});