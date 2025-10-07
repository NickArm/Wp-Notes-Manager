/* WP Notes Manager - Admin JavaScript */

jQuery(document).ready(function($) {
    "use strict";
    
    // Initialize notes functionality
    initNotesManager();
    
    function initNotesManager() {
        // Quick add note form
        $("#wpnm-quick-add-form").on("submit", handleQuickAddNote);
        
        // Add note button (for meta box)
        $(document).on("click", "#wpnm-add-note", handleAddNoteFromMetaBox);
        
        // Note actions
        $(document).on("click", ".wpnm-delete-note, .wpnm-btn-delete", handleDeleteNote);
        $(document).on("click", ".wpnm-archive-note, .wpnm-btn-archive", handleArchiveNote);
        $(document).on("click", ".wpnm-restore-note", handleRestoreNote);
        $(document).on("click", ".wpnm-btn-edit", handleEditNote);
        
        // Auto-save functionality
        initAutoSave();
        
        // Real-time updates
        initRealTimeUpdates();
        
        // Disable action buttons for notes not owned by current user
        disableNonOwnedActionButtons();
        
        // Initialize All Notes page functionality
        initAllNotesPage();
        
        // Initialize Stage Filter
        initStageFilter();
        
        // Initialize Stages Management
        initStagesManagement();
        
        // Initialize Audit Logs
        initAuditLogs();
        
        // Initialize Stage Change Dropdowns
        initStageChangeDropdowns();
        
        // Initialize Layout Controls
        initLayoutControls();
    }
    
    function handleQuickAddNote(e) {
        e.preventDefault();
        
        // Debug logging
        console.log('WP Notes Manager: handleQuickAddNote called');
        console.log('wpnm_admin object:', wpnm_admin);
        
        var form = $(this);
        var submitBtn = form.find("button[type=submit]");
        var originalText = submitBtn.text();
        
        // Show loading state
        submitBtn.text(wpnm_admin.strings.saving).prop("disabled", true);
        form.addClass("wpnm-loading");
        
        // Prepare data
        var data = {
            action: "wpnm_add_note",
            note_type: "dashboard",
            title: form.find("#wpnm-quick-title").val(),
            content: form.find("#wpnm-quick-content").val(),
            priority: form.find("#wpnm-quick-priority").val(),
            // color removed
            assigned_to: form.find("#wpnm-quick-assigned").val(),
            stage_id: form.find("#wpnm-quick-stage").val(),
            deadline: form.find("#wpnm-quick-deadline").val(),
            nonce: wpnm_admin.nonce
        };
        
        // Debug logging
        console.log('WP Notes Manager: Sending AJAX data:', data);
        
        // Send AJAX request
        $.ajax({
            url: wpnm_admin.ajax_url,
            type: "POST",
            data: data,
            success: function(response) {
                console.log('WP Notes Manager: AJAX response:', response);
                if (response.success) {
                    showMessage(wpnm_admin.strings.note_added, "success");
                    form[0].reset();
                    refreshNotesList();
                } else {
                    console.log('WP Notes Manager: AJAX error:', response.data);
                    showMessage(response.data.message || wpnm_admin.strings.error_occurred, "error");
                }
            },
            error: function() {
                showMessage(wpnm_admin.strings.error_occurred, "error");
            },
            complete: function() {
                submitBtn.text(originalText).prop("disabled", false);
                form.removeClass("wpnm-loading");
            }
        });
    }
    
    function handleAddNoteFromMetaBox(e) {
        e.preventDefault();
        
        // Debug logging
        console.log('WP Notes Manager: handleAddNoteFromMetaBox called');
        console.log('wpnm_admin object:', wpnm_admin);
        
        var button = $(this);
        var container = button.closest('#wpnm-notes-container');
        var originalText = button.text();
        
        // Show loading state
        button.text('Adding...').prop("disabled", true);
        
        // Prepare data
        var postType = $('input[name="post_type"]').val() || 'post';
        var data = {
            action: "wpnm_add_note",
            note_type: postType,
            post_id: $('input[name="post_ID"]').val(),
            title: container.find("#wpnm-note-title").val(),
            content: container.find("#wpnm-note-content").val(),
            priority: container.find("#wpnm-note-priority").val(),
            nonce: wpnm_admin.nonce
        };
        
        // Debug logging
        console.log('WP Notes Manager: Sending AJAX data:', data);
        
        // Send AJAX request
        $.ajax({
            url: wpnm_admin.ajax_url,
            type: "POST",
            data: data,
            success: function(response) {
                console.log('WP Notes Manager: AJAX response:', response);
                if (response.success) {
                    showMessage('Note added successfully!', "success");
                    // Clear form
                    container.find("#wpnm-note-title").val('');
                    container.find("#wpnm-note-content").val('');
                    container.find("#wpnm-note-priority").val('medium');
                    // Refresh notes list
                    location.reload();
                } else {
                    console.log('WP Notes Manager: AJAX error:', response.data);
                    showMessage(response.data.message || 'An error occurred. Please try again.', "error");
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', "error");
            },
            complete: function() {
                button.text(originalText).prop("disabled", false);
            }
        });
    }
    
    function handleDeleteNote(e) {
        e.preventDefault();
        
        if (!confirm(wpnm_admin.strings.confirm_delete)) {
            return;
        }
        
        var button = $(this);
        var noteId = button.data("note-id");
        var noteCard = button.closest(".wpnm-beautiful-note, .wpnm-note-card, .wpnm-note-item");
        
        console.log("Delete note clicked:", {noteId: noteId, noteCard: noteCard});
        
        // Show loading state
        button.text(wpnm_admin.strings.loading).prop("disabled", true);
        noteCard.addClass("wpnm-loading");
        
        // Send AJAX request
        $.ajax({
            url: wpnm_admin.ajax_url,
            type: "POST",
            data: {
                action: "wpnm_delete_note",
                note_id: noteId,
                nonce: wpnm_admin.nonce
            },
            success: function(response) {
                console.log("Delete AJAX response:", response);
                if (response.success) {
                    showMessage(wpnm_admin.strings.note_deleted, "success");
                    console.log("Removing note card:", noteCard);
                    noteCard.fadeOut(300, function() {
                        $(this).remove();
                        console.log("Note card removed from DOM");
                    });
                } else {
                    console.log("Delete failed:", response.data.message);
                    showMessage(response.data.message || wpnm_admin.strings.error_occurred, "error");
                }
            },
            error: function() {
                showMessage(wpnm_admin.strings.error_occurred, "error");
            },
            complete: function() {
                button.prop("disabled", false);
                noteCard.removeClass("wpnm-loading");
            }
        });
    }
    
    function handleArchiveNote(e) {
        e.preventDefault();
        
        if (!confirm(wpnm_admin.strings.confirm_archive)) {
            return;
        }
        
        var button = $(this);
        var noteId = button.data("note-id");
        var noteCard = button.closest(".wpnm-beautiful-note, .wpnm-note-card, .wpnm-note-item");
        
        // Show loading state
        button.text(wpnm_admin.strings.loading).prop("disabled", true);
        noteCard.addClass("wpnm-loading");
        
        // Send AJAX request
        $.ajax({
            url: wpnm_admin.ajax_url,
            type: "POST",
            data: {
                action: "wpnm_archive_note",
                note_id: noteId,
                nonce: wpnm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(wpnm_admin.strings.note_archived, "success");
                    noteCard.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    showMessage(response.data.message || wpnm_admin.strings.error_occurred, "error");
                }
            },
            error: function() {
                showMessage(wpnm_admin.strings.error_occurred, "error");
            },
            complete: function() {
                button.prop("disabled", false);
                noteCard.removeClass("wpnm-loading");
            }
        });
    }
    
    function handleRestoreNote(e) {
        e.preventDefault();
        
        var button = $(this);
        var noteId = button.data("note-id");
        var noteCard = button.closest(".wpnm-beautiful-note, .wpnm-note-card, .wpnm-note-item");
        
        // Show loading state
        button.text(wpnm_admin.strings.loading).prop("disabled", true);
        noteCard.addClass("wpnm-loading");
        
        // Send AJAX request
        $.ajax({
            url: wpnm_admin.ajax_url,
            type: "POST",
            data: {
                action: "wpnm_restore_note",
                note_id: noteId,
                nonce: wpnm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage("Note restored successfully!", "success");
                    noteCard.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    showMessage(response.data.message || wpnm_admin.strings.error_occurred, "error");
                }
            },
            error: function() {
                showMessage(wpnm_admin.strings.error_occurred, "error");
            },
            complete: function() {
                button.prop("disabled", false);
                noteCard.removeClass("wpnm-loading");
            }
        });
    }
    
    function initAutoSave() {
        // Auto-save note content every 30 seconds
        var autoSaveInterval = setInterval(function() {
            var activeNote = $(".wpnm-note-card:visible").first();
            if (activeNote.length && activeNote.find("textarea").length) {
                // Auto-save logic here if needed
            }
        }, 30000);
    }
    
    function initRealTimeUpdates() {
        // Refresh notes list every 60 seconds
        var refreshInterval = setInterval(function() {
            if ($(".wpnm-notes-list").length) {
                refreshNotesList();
            }
        }, 60000);
    }
    
    function refreshNotesList() {
        // Refresh the notes list without full page reload
        var notesContainer = $(".wpnm-notes-list");
        if (notesContainer.length) {
            // AJAX call to refresh notes
            $.ajax({
                url: wpnm_admin.ajax_url,
                type: "POST",
                data: {
                    action: "wpnm_get_notes",
                    note_type: "dashboard",
                    nonce: wpnm_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update notes list
                        updateNotesList(response.data.notes);
                    }
                }
            });
        }
    }
    
    function updateNotesList(notes) {
        var container = $(".wpnm-notes-list");
        if (container.length && notes) {
            // Update the notes list with new data
            // This would require server-side rendering of note cards
        }
    }
    
    function showMessage(message, type) {
        // Remove existing messages
        $(".wpnm-message").remove();
        
        // Create new message
        var messageDiv = $("<div>")
            .addClass("wpnm-message " + type)
            .text(message);
        
        // Insert message at top of content
        $(".wrap h1").after(messageDiv);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            messageDiv.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Utility functions
    function debounce(func, wait) {
        var timeout;
        return function executedFunction() {
            var later = function() {
                clearTimeout(timeout);
                func();
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    function throttle(func, limit) {
        var inThrottle;
        return function() {
            var args = arguments;
            var context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(function() {
                    inThrottle = false;
                }, limit);
            }
        };
    }
    
    // Keyboard shortcuts
    $(document).on("keydown", function(e) {
        // Ctrl/Cmd + N to add new note
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 78) {
            e.preventDefault();
            $("#wpnm-quick-title").focus();
        }
        
        // Escape to close any open modals
        if (e.keyCode === 27) {
            $(".wpnm-modal").fadeOut();
        }
    });
    
    // Initialize tooltips
    $("[title]").tooltip();
    
    // Initialize sortable notes (if needed)
    if ($.fn.sortable) {
        $(".wpnm-notes-list").sortable({
            handle: ".wpnm-note-header",
            placeholder: "wpnm-note-placeholder",
            update: function(event, ui) {
                // Handle note reordering
                var noteIds = [];
                $(".wpnm-note-card").each(function() {
                    noteIds.push($(this).data("note-id"));
                });
                
                // Send AJAX request to update order
                $.ajax({
                    url: wpnm_admin.ajax_url,
                    type: "POST",
                    data: {
                        action: "wpnm_update_note_order",
                        note_ids: noteIds,
                        nonce: wpnm_admin.nonce
                    }
                });
            }
        });
    }
    
    function handleEditNote() {
        var noteId = $(this).data("note-id");
        var noteCard = $(this).closest(".wpnm-beautiful-note, .wpnm-note-card");
        var authorId = noteCard.data("author-id");
        var currentUserId = wpnm_admin.current_user_id || 0;
        
        // Check if current user is the owner
        if (authorId != currentUserId) {
            showMessage("You can only edit your own notes.", "error");
            return;
        }
        
        // Create edit form
        var editForm = createEditForm(noteCard, noteId);
        
        // Replace note content with edit form
        noteCard.find(".wpnm-note-content").html(editForm);
        
        // Focus on title field
        noteCard.find(".wpnm-edit-title").focus();
    }
    
    function createEditForm(noteCard, noteId) {
        var title = noteCard.find(".wpnm-note-title").text();
        var content = noteCard.find(".wpnm-content-text").text();
        var priorityElement = noteCard.find(".wpnm-priority-tag");
        var priority = priorityElement.length ? priorityElement.attr("class").split(" ")[1].replace("priority-", "") : "medium";
        
        var form = $("<form>").addClass("wpnm-edit-form");
        
        var titleField = $("<input>")
            .attr("type", "text")
            .addClass("wpnm-edit-title")
            .val(title)
            .css({
                "width": "100%",
                "padding": "8px",
                "margin-bottom": "10px",
                "border": "1px solid #ddd",
                "border-radius": "4px"
            });
        
        var contentField = $("<textarea>")
            .addClass("wpnm-edit-content")
            .val(content)
            .css({
                "width": "100%",
                "height": "100px",
                "padding": "8px",
                "margin-bottom": "10px",
                "border": "1px solid #ddd",
                "border-radius": "4px",
                "resize": "vertical"
            });
        
        var priorityField = $("<select>")
            .addClass("wpnm-edit-priority")
            .css({
                "padding": "8px",
                "margin-bottom": "10px",
                "border": "1px solid #ddd",
                "border-radius": "4px"
            });
        
        priorityField.append("<option value='low'>Low</option>");
        priorityField.append("<option value='medium'>Medium</option>");
        priorityField.append("<option value='high'>High</option>");
        priorityField.append("<option value='urgent'>Urgent</option>");
        priorityField.val(priority);
        
        var assignedField = $("<select>")
            .addClass("wpnm-edit-assigned")
            .css({
                "padding": "8px",
                "margin-bottom": "10px",
                "border": "1px solid #ddd",
                "border-radius": "4px"
            });
        
        assignedField.append("<option value=''>No Assignment</option>");
        // Add users from wpnm_admin.users if available
        if (wpnm_admin.users) {
            $.each(wpnm_admin.users, function(id, name) {
                assignedField.append("<option value='" + id + "'>" + name + "</option>");
            });
        }
        
        var currentAssigned = noteCard.data("assigned-to") || "";
        assignedField.val(currentAssigned);
        
        var stageField = $("<select>")
            .addClass("wpnm-edit-stage")
            .css({
                "padding": "8px",
                "margin-bottom": "10px",
                "border": "1px solid #ddd",
                "border-radius": "4px"
            });
        
        stageField.append("<option value=''>No Stage</option>");
        if (wpnm_admin.stages) {
            $.each(wpnm_admin.stages, function(id, stage) {
                stageField.append("<option value='" + id + "'>" + stage.name + "</option>");
            });
        }
        
        var currentStage = noteCard.data("stage-id") || "";
        stageField.val(currentStage);

        // Add deadline field
        var deadlineField = $("<input>")
            .attr("type", "datetime-local")
            .addClass("wpnm-edit-deadline")
            .css({
                "padding": "8px",
                "margin-bottom": "10px",
                "border": "1px solid #ddd",
                "border-radius": "4px",
                "width": "100%"
            });

        var currentDeadline = noteCard.data("deadline") || "";
        if (currentDeadline && currentDeadline !== '') {
            // Convert MySQL datetime to datetime-local format
            var deadlineDate = new Date(currentDeadline);
            var formatted = deadlineDate.toISOString().slice(0, 16);
            deadlineField.val(formatted);
        }

        var deadlineLabel = $("<label>")
            .text("Deadline:")
            .css({
                "display": "block",
                "margin-bottom": "5px",
                "font-weight": "500",
                "color": "#374151"
            });
        
        var buttonGroup = $("<div>").css("text-align", "right");
        
        var saveBtn = $("<button>")
            .attr("type", "button")
            .addClass("button button-primary")
            .text("Save")
            .css("margin-right", "5px")
            .on("click", function() {
                saveNoteEdit(noteId, noteCard);
            });
        
        var cancelBtn = $("<button>")
            .attr("type", "button")
            .addClass("button")
            .text("Cancel")
            .on("click", function() {
                location.reload(); // Simple reload for now
            });
        
        buttonGroup.append(saveBtn).append(cancelBtn);
        
        form.append(titleField).append(contentField).append(priorityField).append(assignedField).append(stageField).append(deadlineLabel).append(deadlineField).append(buttonGroup);
        
        return form;
    }
    
    function saveNoteEdit(noteId, noteCard) {
        var title = noteCard.find(".wpnm-edit-title").val();
        var content = noteCard.find(".wpnm-edit-content").val();
        var priority = noteCard.find(".wpnm-edit-priority").val();
        var assignedTo = noteCard.find(".wpnm-edit-assigned").val();
        var stageId = noteCard.find(".wpnm-edit-stage").val();
        var deadline = noteCard.find(".wpnm-edit-deadline").val();
        
        // Show loading state
        var saveBtn = noteCard.find(".wpnm-save-btn");
        var originalText = saveBtn.text();
        saveBtn.text('Saving...').prop('disabled', true);
        
        $.ajax({
            url: wpnm_admin.ajax_url,
            type: "POST",
            data: {
                action: "wpnm_update_note",
                note_id: noteId,
                title: title,
                content: content,
                priority: priority,
                assigned_to: assignedTo,
                stage_id: stageId,
                deadline: deadline,
                nonce: wpnm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage("Note updated successfully!", "success");
                    location.reload(); // Reload to show updated content
                } else {
                    showMessage(response.data.message || wpnm_admin.strings.error_occurred, "error");
                }
            },
            error: function() {
                showMessage(wpnm_admin.strings.error_occurred, "error");
            },
            complete: function() {
                // Restore button state
                saveBtn.text(originalText).prop('disabled', false);
            }
        });
    }
    
    function disableNonOwnedActionButtons() {
        var currentUserId = wpnm_admin.current_user_id || 0;
        
        $(".wpnm-beautiful-note").each(function() {
            var noteCard = $(this);
            var authorId = noteCard.data("author-id");
            var editBtn = noteCard.find(".wpnm-btn-edit");
            var deleteBtn = noteCard.find(".wpnm-btn-delete");
            var archiveBtn = noteCard.find(".wpnm-btn-archive");
            
            if (authorId != currentUserId) {
                editBtn.addClass("disabled");
                editBtn.attr("title", "You can only edit your own notes");
                
                deleteBtn.addClass("disabled");
                deleteBtn.attr("title", "You can only delete your own notes");
                
                archiveBtn.addClass("disabled");
                archiveBtn.attr("title", "You can only archive your own notes");
            }
        });
    }
    
    function initAllNotesPage() {
        // Add New Note button functionality
        $(".wpnm-add-note-btn").on("click", function() {
            $("#wpnm-quick-add-form-container").slideDown(300);
            $("#wpnm-quick-title").focus();
        });
        
        // Cancel Add Note button
        $(".wpnm-cancel-add-btn").on("click", function() {
            $("#wpnm-quick-add-form-container").slideUp(300);
            $("#wpnm-quick-add-form")[0].reset();
        });
        
        // Close form when clicking outside (but not on form elements)
        $(document).on("click", function(e) {
            var target = $(e.target);
            var isFormElement = target.closest(".wpnm-quick-add-form-container").length > 0;
            var isAddButton = target.closest(".wpnm-add-note-btn").length > 0;
            
            console.log("Click outside handler:", {
                target: target[0],
                isFormElement: isFormElement,
                isAddButton: isAddButton,
                formVisible: $("#wpnm-quick-add-form-container").is(":visible")
            });
            
            if (!isFormElement && !isAddButton && $("#wpnm-quick-add-form-container").is(":visible")) {
                console.log("Closing form and resetting");
                $("#wpnm-quick-add-form-container").slideUp(300);
                $("#wpnm-quick-add-form")[0].reset();
            }
        });
    }
    
    function initStageFilter() {
        // Stage filter dropdown change
        $("#wpnm-stage-filter-select").on("change", function() {
            var stageId = $(this).val();
            var currentUrl = new URL(window.location);
            
            if (stageId) {
                currentUrl.searchParams.set('stage', stageId);
            } else {
                currentUrl.searchParams.delete('stage');
            }
            
            // Remove page parameter to go to first page
            currentUrl.searchParams.delete('paged');
            
            // Redirect to filtered URL
            window.location.href = currentUrl.toString();
        });
    }
    
    function initStagesManagement() {
        // Add Stage button
        $("#wpnm-add-stage-btn").on("click", function() {
            $("#wpnm-stage-form-container").slideDown(300);
            $("#wpnm-stage-name").focus();
            $("#wpnm-stage-form-title").text("Add New Stage");
            $("#wpnm-stage-form")[0].reset();
            $("#wpnm-stage-id").val("");
        });
        
        // Edit Stage button
        $(document).on("click", ".wpnm-edit-stage-btn", function() {
            var stageId = $(this).data("stage-id");
            var row = $(this).closest("tr");
            var name = row.find("td:first strong").text();
            var description = row.find("td:nth-child(2)").text();
            var color = row.find(".wpnm-stage-color-preview").css("background-color");
            var sortOrder = row.find("td:nth-child(4)").text();
            var isDefault = row.find(".dashicons-yes-alt").length > 0;
            
            $("#wpnm-stage-form-title").text("Edit Stage");
            $("#wpnm-stage-id").val(stageId);
            $("#wpnm-stage-name").val(name);
            $("#wpnm-stage-description").val(description);
            $("#wpnm-stage-color").val(rgbToHex(color));
            $("#wpnm-stage-sort-order").val(sortOrder);
            $("#wpnm-stage-is-default").prop("checked", isDefault);
            
            $("#wpnm-stage-form-container").slideDown(300);
            $("#wpnm-stage-name").focus();
        });
        
        // Delete Stage button
        $(document).on("click", ".wpnm-delete-stage-btn", function() {
            var stageId = $(this).data("stage-id");
            var stageName = $(this).closest("tr").find("td:first strong").text();
            
            if (confirm("Are you sure you want to delete the stage '" + stageName + "'? This will move all notes using this stage to the default stage.")) {
                $.ajax({
                    url: wpnm_admin.ajax_url,
                    type: "POST",
                    data: {
                        action: "wpnm_delete_stage",
                        stage_id: stageId,
                        nonce: wpnm_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, "success");
                            location.reload();
                        } else {
                            showMessage(response.data.message, "error");
                        }
                    },
                    error: function() {
                        showMessage("An error occurred. Please try again.", "error");
                    }
                });
            }
        });
        
        // Cancel Stage Form
        $(".wpnm-cancel-stage-btn").on("click", function() {
            $("#wpnm-stage-form-container").slideUp(300);
            $("#wpnm-stage-form")[0].reset();
        });
        
        // Stage Form Submit
        $("#wpnm-stage-form").on("submit", function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            var isEdit = $("#wpnm-stage-id").val() !== "";
            var action = isEdit ? "wpnm_update_stage" : "wpnm_create_stage";
            
            $.ajax({
                url: wpnm_admin.ajax_url,
                type: "POST",
                data: formData + "&action=" + action + "&nonce=" + wpnm_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        showMessage(response.data.message, "success");
                        location.reload();
                    } else {
                        showMessage(response.data.message, "error");
                    }
                },
                error: function() {
                    showMessage("An error occurred. Please try again.", "error");
                }
            });
        });
    }
    
    function initAuditLogs() {
        // Clear Audit Logs button
        $("#wpnm-clear-audit-logs-btn").on("click", function() {
            if (confirm("Are you sure you want to clear all audit logs? This action cannot be undone.")) {
                $.ajax({
                    url: wpnm_admin.ajax_url,
                    type: "POST",
                    data: {
                        action: "wpnm_clear_audit_logs",
                        nonce: wpnm_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, "success");
                            location.reload();
                        } else {
                            showMessage(response.data.message, "error");
                        }
                    },
                    error: function() {
                        showMessage("An error occurred. Please try again.", "error");
                    }
                });
            }
        });
    }
    
    function rgbToHex(rgb) {
        if (rgb.indexOf("rgb") === -1) {
            return rgb;
        }
        
        var result = rgb.match(/\d+/g);
        if (result && result.length >= 3) {
            var r = parseInt(result[0]);
            var g = parseInt(result[1]);
            var b = parseInt(result[2]);
            return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
        }
        return "#6b7280";
    }
    
    function initStageChangeDropdowns() {
        // Stage change button click
        $(document).on("click", ".wpnm-stage-change-btn", function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var dropdown = $(this).siblings(".wpnm-stage-dropdown");
            var dropdownContainer = $(this).closest(".wpnm-stage-change-dropdown");
            
            // Close other dropdowns
            $(".wpnm-stage-dropdown").removeClass("show");
            $(".wpnm-stage-change-dropdown").removeClass("dropdown-open");
            
            // Toggle current dropdown
            dropdown.toggleClass("show");
            dropdownContainer.toggleClass("dropdown-open");
        });
        
        // Stage dropdown item click
        $(document).on("click", ".wpnm-stage-dropdown-item", function(e) {
            e.preventDefault();
            
            var stageId = $(this).data("stage-id");
            var noteId = $(this).data("note-id");
            var stageName = $(this).text();
            
            console.log("Stage change clicked:", {stageId: stageId, noteId: noteId, stageName: stageName});
            
            // Close dropdown
            $(".wpnm-stage-dropdown").removeClass("show");
            
            // Update stage via AJAX
            var ajaxData = {
                action: "wpnm_change_note_stage",
                note_id: noteId,
                stage_id: stageId,
                nonce: wpnm_admin.nonce
            };
            console.log("Sending AJAX data:", ajaxData);
            
            $.ajax({
                url: wpnm_admin.ajax_url,
                type: "POST",
                data: ajaxData,
                success: function(response) {
                    console.log("AJAX response:", response);
                    if (response.success) {
                        // Update the stage button
                        var button = $(".wpnm-stage-change-btn[data-note-id='" + noteId + "']");
                        button.text(response.data.stage_name);
                        button.css("background-color", response.data.stage_color);
                        
                        // Update the note card data attributes
                        var noteCard = $(".wpnm-beautiful-note[data-note-id='" + noteId + "']");
                        noteCard.attr("data-stage-id", stageId);
                        
                        console.log("Stage updated successfully for note:", noteId);
                        showMessage(response.data.message, "success");
                    } else {
                        console.log("Stage update failed:", response.data.message);
                        showMessage(response.data.message || "Failed to update stage", "error");
                    }
                },
                error: function(xhr, status, error) {
                    console.log("AJAX error:", {xhr: xhr, status: status, error: error});
                    showMessage("An error occurred. Please try again.", "error");
                }
            });
        });
        
        // Close dropdown when clicking outside
        $(document).on("click", function(e) {
            if (!$(e.target).closest(".wpnm-stage-change-dropdown").length) {
                $(".wpnm-stage-dropdown").removeClass("show");
                $(".wpnm-stage-change-dropdown").removeClass("dropdown-open");
            }
        });
    }
    
    function initLayoutControls() {
        // Layout button click handlers
        $(".wpnm-layout-btn").on("click", function() {
            var layout = $(this).data("layout");
            var container = $("#wpnm-notes-container");
            
            // Remove active class from all buttons
            $(".wpnm-layout-btn").removeClass("active");
            
            // Add active class to clicked button
            $(this).addClass("active");
            
            // Remove all layout classes
            container.removeClass("wpnm-layout-list wpnm-layout-2-columns wpnm-layout-3-columns");
            
            // Add new layout class
            container.addClass("wpnm-layout-" + layout);
            
            // Save layout preference
            saveLayoutPreference(layout);
            
            console.log("Layout changed to:", layout);
        });
        
        // Load saved layout preference
        loadLayoutPreference();
    }
    
    function saveLayoutPreference(layout) {
        // Save to localStorage
        localStorage.setItem('wpnm_notes_layout', layout);
        
        // Also save to user meta (optional)
        $.ajax({
            url: wpnm_admin.ajax_url,
            type: "POST",
            data: {
                action: "wpnm_save_layout_preference",
                layout: layout,
                nonce: wpnm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log("Layout preference saved");
                }
            },
            error: function() {
                console.log("Failed to save layout preference");
            }
        });
    }
    
    function loadLayoutPreference() {
        // Load from localStorage first
        var savedLayout = localStorage.getItem('wpnm_notes_layout');
        
        if (savedLayout) {
            var container = $("#wpnm-notes-container");
            var button = $(".wpnm-layout-btn[data-layout='" + savedLayout + "']");
            
            if (button.length > 0) {
                // Remove active class from all buttons
                $(".wpnm-layout-btn").removeClass("active");
                
                // Add active class to saved layout button
                button.addClass("active");
                
                // Remove all layout classes
                container.removeClass("wpnm-layout-list wpnm-layout-2-columns wpnm-layout-3-columns");
                
                // Add saved layout class
                container.addClass("wpnm-layout-" + savedLayout);
                
                console.log("Loaded layout preference:", savedLayout);
            }
        }
    }
});
