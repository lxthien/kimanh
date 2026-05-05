/**
 * Project Management Dashboard — JS Module
 * Handles:
 *   1. Task toggle (checkbox → AJAX → update row styling)
 *   2. Shared Edit modals for Task / Cost / Journal
 *      Uses Bootstrap 4 show.bs.modal pattern so jQuery is guaranteed available.
 */

$(function () {

    // ---------------------------------------------------------------
    // 1. Edit Task Modal — populate on show
    // ---------------------------------------------------------------
    $('#sharedEditTaskModal').on('show.bs.modal', function (event) {
        var btn = $(event.relatedTarget);
        $('#editTaskForm').attr('action', btn.data('edit-url'));
        $('#editTaskName').val(btn.data('name'));
        $('#editTaskStage').val(btn.data('stage'));
        $('#editTaskStatus').val(btn.data('status'));
        $('#editTaskDueDate').val(btn.data('due-date'));
    });

    // ---------------------------------------------------------------
    // 2. Edit Cost Modal — populate on show
    // ---------------------------------------------------------------
    $('#sharedEditCostModal').on('show.bs.modal', function (event) {
        var btn = $(event.relatedTarget);
        $('#editCostForm').attr('action', btn.data('edit-url'));
        $('#editCostCategory').val(btn.data('category'));
        $('#editCostDescription').val(btn.data('description'));
        $('#editCostAmount').val(btn.data('amount'));
        $('#editCostDate').val(btn.data('cost-date'));
        $('#editCostType').val(btn.data('type'));
    });

    // ---------------------------------------------------------------
    // 3. Edit Journal Modal — populate on show
    // ---------------------------------------------------------------
    $('#sharedEditJournalModal').on('show.bs.modal', function (event) {
        var btn = $(event.relatedTarget);
        $('#editJournalForm').attr('action', btn.data('edit-url'));
        $('#editJournalLogDate').val(btn.data('log-date'));
        $('#editJournalWeather').val(btn.data('weather'));
        $('#editJournalWorkerCount').val(btn.data('worker-count'));
        $('#editJournalContent').val(btn.data('content'));
    });

    // ---------------------------------------------------------------
    // 4. Tab navigation (hash-based)
    // ---------------------------------------------------------------
    var $tabs  = $('.tab-link');
    var $panes = $('.tab-pane');

    function showTab(tabId) {
        $tabs.removeClass('active');
        $panes.removeClass('active');

        var $activeTab  = $('[data-tab="' + tabId + '"]');
        var $activePane = $('#' + tabId);

        if ($activeTab.length && $activePane.length) {
            $activeTab.addClass('active');
            $activePane.addClass('active');
        }
    }

    $tabs.on('click', function (e) {
        e.preventDefault();
        var tabId = $(this).data('tab');
        showTab(tabId);
        window.location.hash = tabId;
    });

    // Restore tab from URL hash on page load
    var hash = window.location.hash.replace('#', '');
    if (hash) {
        showTab(hash);
    }
});

// ---------------------------------------------------------------
// 5. Task toggle — AJAX (no jQuery required, plain fetch)
//    Exposed as global so inline onchange="toggleTask(...)" works.
// ---------------------------------------------------------------
window.toggleTask = function (checkbox, taskId, url) {
    checkbox.disabled = true;

    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
        if (data.status === 'success') {
            var row   = document.getElementById('task-row-' + taskId);
            var label = row.querySelector('.task-name-label');
            var done  = data.newStatus === 'completed';

            row.classList.toggle('table-success', done);
            row.classList.toggle('task-done', done);
            label.style.textDecoration = done ? 'line-through' : '';
            label.style.color          = done ? '#aaa' : '';
            checkbox.checked = done;
        } else {
            checkbox.checked = !checkbox.checked;
        }
    })
    .catch(function () {
        checkbox.checked = !checkbox.checked;
    })
    .finally(function () {
        checkbox.disabled = false;
    });
};
