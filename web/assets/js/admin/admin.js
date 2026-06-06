import 'typeahead.js';
import Bloodhound from "bloodhound-js";
import 'bootstrap-tagsinput';
import Chart from 'chart.js';

import 'bootstrap-sass/assets/javascripts/bootstrap/modal.js';

$(function() {
    initAdminSidebarState();

    // Build the slug for object entiry from the name
    initBuildSluggable();

    // Init CkEditor and CKfinder
    initCkeditor();

    // Update object when change the enable button toggle
    initEnableToggleButton();

    initMakePrimaryCategory();

    initSeoRealtimeChecklist();

    initDashboardCharts();

    function initAdminSidebarState() {
        var storageKey = 'kimanh_admin_sidebar_open';

        function setSessionCookie(value) {
            document.cookie = storageKey + '=' + value + '; path=/; SameSite=Lax';
        }

        function persistSidebarState() {
            var isCollapsed = $('body').hasClass('open');
            var value = isCollapsed ? '1' : '0';

            try {
                sessionStorage.setItem(storageKey, value);
            } catch (error) {}

            setSessionCookie(value);
        }

        try {
            if (sessionStorage.getItem(storageKey) === '1') {
                $('body').addClass('open');
                setSessionCookie('1');
            }
        } catch (error) {}

        var menuToggle = document.getElementById('menuToggle');

        if (!menuToggle) {
            return;
        }

        menuToggle.addEventListener('click', function() {
            setTimeout(persistSidebarState, 0);
        });
    }

    /**
     * Create sluggable from name
     * 
     **/
    function initBuildSluggable() {
        $("body.new :input.sluggable").keyup(function () {
            $(":input.url").val(remove_vietnamese_accents($(this).val()));
        });

        $(":input.url").click(function () {
            if ($(this).attr('readonly')) {
                $(":input.url").removeAttr('readonly');
            }
        });

        $(":input.url").focusout(function () {
            if (!$(this).attr('readonly')) {
                $(":input.url").attr('readonly', 'readonly');
            }
        });
    }

    /**
     * @var string
     * Remove vietnamese from string
     **/
    function remove_vietnamese_accents(str) {
        var accents_arr = new Array("à", "á", "ạ", "ả", "ã", "â", "ầ", "ấ", "ậ", "ẩ", "ẫ", "ă", "ằ", "ắ", "ặ", "ẳ", "ẵ", "è", "é", "ẹ", "ẻ", "ẽ", "ê", "ề", "ế", "ệ", "ể", "ễ", "ì", "í", "ị", "ỉ", "ĩ", "ò", "ó", "ọ", "ỏ", "õ", "ô", "ồ", "ố", "ộ", "ổ", "ỗ", "ơ", "ờ", "ớ", "ợ", "ở", "ỡ", "ù", "ú", "ụ", "ủ", "ũ", "ư", "ừ", "ứ", "ự", "ử", "ữ", "ỳ", "ý", "ỵ", "ỷ", "ỹ", "đ", "À", "Á", "Ạ", "Ả", "Ã", "Â", "Ầ", "Ấ", "Ậ", "Ẩ", "Ẫ", "Ă", "Ằ", "Ắ", "Ặ", "Ẳ", "Ẵ", "È", "É", "Ẹ", "Ẻ", "Ẽ", "Ê", "Ề", "Ế", "Ệ", "Ể", "Ễ", "Ì", "Í", "Ị", "Ỉ", "Ĩ", "Ò", "Ó", "Ọ", "Ỏ", "Õ", "Ô", "Ồ", "Ố", "Ộ", "Ổ", "Ỗ", "Ơ", "Ờ", "Ớ", "Ợ", "Ở", "Ỡ", "Ù", "Ú", "Ụ", "Ủ", "Ũ", "Ư", "Ừ", "Ứ", "Ự", "Ử", "Ữ", "Ỳ", "Ý", "Ỵ", "Ỷ", "Ỹ", "Đ", " ", "\"", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", ".", ",", ";", "'", "[", "]", "{", "}", ":", "“", "”", "--", '.', '>', '<', '--', '---', '‘', '’', '/', '?', '~', "|");

        var no_accents_arr = new Array("a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "e", "e", "e", "e", "e", "e", "e", "e", "e", "e", "e", "i", "i", "i", "i", "i", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "u", "u", "u", "u", "u", "u", "u", "u", "u", "u", "u", "y", "y", "y", "y", "y", "d", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "e", "e", "e", "e", "e", "e", "e", "e", "e", "e", "e", "i", "i", "i", "i", "i", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "u", "u", "u", "u", "u", "u", "u", "u", "u", "u", "u", "y", "y", "y", "y", "y", "d", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", '-', '-', '-', '-', '---', '-', '-', '-', '', '', '');

        return str_replace(accents_arr, no_accents_arr, str).toLowerCase();
    }

    /**
     * @var string
     * Replace the string
     **/
    function str_replace(search, replace, str) {
        var ra = replace instanceof Array,
            sa = str instanceof Array,
            l = (search = [].concat(search)).length,
            replace = [].concat(replace),
            i = (str = [].concat(str)).length,
            j;

        while (j = 0, i--) {
            while (str[i] = str[i].split(search[j]).join(ra ? replace[j] || "" : replace[0]), ++j < l) {}
        }return sa ? str : str[0];
    }

    /**
     * Init Ckeditor and Ckfinder.
     **/
    function initCkeditor() {
        $('.txt-ckeditor').each(function (e, elements) {
            var height = $(this).data("height") ? $(this).data("height") : "500";
            CKEDITOR.replace(this.id, {
                height: height + 'px',
                contentsCss: [window.KIMANH_ADMIN ? window.KIMANH_ADMIN.ckeditorContentCss : '/build/css/ckeditor-content.css'],
                bodyClass: 'news-container',
                filebrowserBrowseUrl: '/assets/cksourceckfinder/ckfinder/ckfinder.html',
                filebrowserUploadUrl: '/assets/cksourceckfinder/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
                filebrowserWindowWidth: '1000',
                filebrowserWindowHeight: '700',
                on: {
                    instanceReady: function (ev) {
                        initCkeditorToc(ev.editor);
                    },
                    contentDom: function (ev) {
                        initCkeditorToc(ev.editor);
                    }
                }
            });
        });
    }

    /**
     * Add TOC toggle behavior in CKEditor iframe.
     **/
    function initCkeditorToc(editor) {
        var iframeDoc = editor.document.$;

        if (!iframeDoc) return;

        var tocElements = iframeDoc.querySelectorAll('.ka-table-of-contents');

        tocElements.forEach(function (toc) {
            toc.classList.add('collapsed');

            var mainToc = toc.querySelector('#main-toc');
            
            if (mainToc && !mainToc.dataset.initialized) {
                mainToc.dataset.initialized = 'true';
                mainToc.addEventListener('click', function () {
                    toc.classList.toggle('collapsed');
                });
            }
        });
    }

    /**
     * Update object when change the enable button toggle
     **/
    function initEnableToggleButton() {
        $('.switch-input').on('change', function() {
            let isChecked = $(this).prop('checked');
            isChecked = isChecked ? 1 : 0;
            let id = $(this).data('id');
            let url = $(this).data('action');
            
            $.ajax({
                type: "POST",
                url: url,
                data: 'newsId=' + id + '&enable=' + isChecked,
                success: function(data) {
                    var response = JSON.parse(data);
                }
            });
        });
    }

    // Bootstrap-tagsinput initialization
    // http://bootstrap-tagsinput.github.io/bootstrap-tagsinput/examples/
    var $input = $('input[data-toggle="tagsinput"]');
    if ($input.length) {
        var source = new Bloodhound({
            local: $input.data('tags'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            datumTokenizer: Bloodhound.tokenizers.whitespace
        });
        source.initialize();

        $input.tagsinput({
            trimValue: true,
            focusClass: 'focus',
            typeaheadjs: {
                name: 'tags',
                source: source.ttAdapter()
            }
        });
    }

    function initMakePrimaryCategory() {
        var categoryPrimaryId = $('#news_categoryPrimary').val();

        $("#news_category .checkbox").each(function() {
            var categoryId = $(this).find('input[type="checkbox"]').val();

            if ($(this).find('input[type="checkbox"]').is(':checked') && categoryPrimaryId != categoryId) {
                $(this).append('<label class="label-primary"> <input type="radio" name="categoryPrimary"></input> Chọn làm danh mục chính</label>');
            }
        });

        $('#news_category .checkbox input[type="checkbox"]').change(function() {
            if (!this.checked) {
                $(this).closest('.checkbox').find('label.label-primary').remove();
            } else {
                $(this).parent().parent('.checkbox').append('<label class="label-primary"> <input type="radio" name="categoryPrimary"></input> Chọn làm danh mục chính</label>');
            }
        });

        $(document).on('change', '#news_category .checkbox .label-primary input[type="radio"]', function(e) {
            var categoryId = $(this).closest('.checkbox').find('input[type="checkbox"]').val();

            if (categoryId > 0 ) {
                $('#news_categoryPrimary').val(categoryId);
            }
        });
    }

    function initSeoRealtimeChecklist() {
        $('[data-seo-checklist]').each(function() {
            var $checker = $(this);
            var prefix = $checker.data('form-prefix');
            var previewUrlPattern = String($checker.data('preview-url-pattern') || '/__slug__');
            var hasExistingImage = String($checker.data('has-image')) === '1';
            var checkCategory = String($checker.data('check-category')) === '1';
            var fieldIds = {
                title: prefix + '_title',
                url: prefix + '_url',
                description: prefix + '_description',
                contents: prefix + '_contents',
                imageFile: prefix + '_imageFile_file',
                pageTitle: prefix + '_pageTitle',
                pageDescription: prefix + '_pageDescription',
                pageKeyword: prefix + '_pageKeyword',
                category: prefix + '_category'
            };
            var debounceTimer = null;

            function getFieldValue(fieldId) {
                if (window.CKEDITOR && CKEDITOR.instances[fieldId]) {
                    return CKEDITOR.instances[fieldId].getData();
                }

                return $('#' + fieldId).val() || '';
            }

            function cleanText(value) {
                var text = $('<div>').html(value || '').text();

                return $.trim(text.replace(/\s+/g, ' '));
            }

            function countWords(text) {
                text = cleanText(text);

                if (!text) {
                    return 0;
                }

                return text.split(/\s+/).length;
            }

            function getPrimaryKeyword(value) {
                var parts = String(value || '').split(/[,;]+/);

                return $.trim(parts[0] || '');
            }

            function containsText(haystack, needle) {
                haystack = String(haystack || '').toLowerCase();
                needle = String(needle || '').toLowerCase();

                return needle !== '' && haystack.indexOf(needle) !== -1;
            }

            function getStatus(score) {
                if (score >= 90) {
                    return 'Tốt';
                }

                if (score >= 80) {
                    return 'Khá';
                }

                if (score >= 60) {
                    return 'Cần cải thiện';
                }

                return 'Yếu';
            }

            function getScoreClass(score) {
                if (score >= 90) {
                    return 'success';
                }

                if (score >= 80) {
                    return 'primary';
                }

                if (score >= 60) {
                    return 'warning';
                }

                return 'danger';
            }

            function appendChecklistItem($list, type, text) {
                var icon = type === 'danger' ? 'fa-times-circle' : 'fa-exclamation-circle';

                $('<li>')
                    .addClass('seo-score-item seo-score-item-' + type)
                    .append($('<i>').addClass('fa ' + icon).attr('aria-hidden', 'true'))
                    .append(document.createTextNode(text))
                    .appendTo($list);
            }

            function renderList($list, items, emptyText, type) {
                $list.empty();

                if (!items.length) {
                    $('<li>').addClass('text-muted').text(emptyText).appendTo($list);
                    return;
                }

                $.each(items, function(index, item) {
                    appendChecklistItem($list, type, item);
                });
            }

            function hasImage() {
                var input = document.getElementById(fieldIds.imageFile);

                if (input && input.files && input.files.length > 0) {
                    return true;
                }

                return hasExistingImage;
            }

            function hasCategory() {
                if (!checkCategory) {
                    return true;
                }

                var $tree = $('[data-category-tree]');

                if ($tree.length) {
                    return $tree.find('.news-category-checkbox:checked').length > 0;
                }

                return $('#' + fieldIds.category + ' input[type="checkbox"]:checked').length > 0;
            }

            function analyze() {
                var score = 100;
                var errors = [];
                var warnings = [];
                var title = $.trim(getFieldValue(fieldIds.title));
                var seoTitle = $.trim(getFieldValue(fieldIds.pageTitle));
                var effectiveTitle = seoTitle || title;
                var summaryDescription = cleanText(getFieldValue(fieldIds.description));
                var metaDescription = cleanText(getFieldValue(fieldIds.pageDescription));
                var effectiveDescription = metaDescription || summaryDescription;
                var rawContent = getFieldValue(fieldIds.contents);
                var wordCount = countWords(rawContent);
                var primaryKeyword = getPrimaryKeyword(getFieldValue(fieldIds.pageKeyword));
                var titleLength = effectiveTitle.length;
                var descriptionLength = effectiveDescription.length;

                if (!effectiveTitle) {
                    score -= 25;
                    errors.push('Thiếu tiêu đề SEO');
                } else if (titleLength < 30) {
                    score -= 8;
                    errors.push('Tiêu đề SEO ngắn');
                } else if (titleLength > 70) {
                    score -= 8;
                    errors.push('Tiêu đề SEO dài');
                }

                if (!effectiveDescription) {
                    score -= 25;
                    errors.push('Thiếu meta description');
                } else if (descriptionLength < 120) {
                    score -= 8;
                    errors.push('Meta description ngắn');
                } else if (descriptionLength > 170) {
                    score -= 8;
                    errors.push('Meta description dài');
                }

                if (!hasImage()) {
                    score -= 10;
                    errors.push('Thiếu ảnh đại diện');
                }

                if (wordCount < 300) {
                    score -= 15;
                    errors.push('Nội dung mỏng');
                } else if (wordCount < 600) {
                    score -= 6;
                    warnings.push('Có thể mở rộng nội dung');
                }

                if (String(rawContent || '').toLowerCase().indexOf('<h2') === -1) {
                    score -= 5;
                    warnings.push('Thiếu heading H2');
                }

                if (String(rawContent || '').toLowerCase().indexOf('<a ') === -1) {
                    score -= 5;
                    warnings.push('Thiếu liên kết nội bộ/ngoài');
                }

                if (!hasCategory()) {
                    score -= 8;
                    errors.push('Chưa gán danh mục');
                }

                if (!summaryDescription) {
                    score -= 4;
                    warnings.push('Thiếu mô tả tóm tắt cho danh sách');
                }

                if (primaryKeyword) {
                    if (!containsText(effectiveTitle, primaryKeyword)) {
                        score -= 5;
                        warnings.push('Từ khóa chính chưa có trong tiêu đề');
                    }

                    if (!containsText(effectiveDescription, primaryKeyword)) {
                        score -= 5;
                        warnings.push('Từ khóa chính chưa có trong mô tả');
                    }
                }

                score = Math.max(0, Math.min(100, score));

                return {
                    score: score,
                    status: getStatus(score),
                    scoreClass: getScoreClass(score),
                    errors: errors,
                    warnings: warnings,
                    effectiveTitle: effectiveTitle,
                    effectiveDescription: effectiveDescription,
                    primaryKeyword: primaryKeyword,
                    wordCount: wordCount,
                    titleLength: titleLength,
                    descriptionLength: descriptionLength,
                    url: $.trim(getFieldValue(fieldIds.url))
                };
            }

            function render() {
                var audit = analyze();
                var $scoreBox = $checker.find('[data-seo-score-box]');
                var previewUrl = previewUrlPattern.replace('__slug__', audit.url || 'duong-dan-bai-viet');

                $scoreBox
                    .removeClass('seo-score-meter-success seo-score-meter-primary seo-score-meter-warning seo-score-meter-danger')
                    .addClass('seo-score-meter-' + audit.scoreClass);

                $checker.find('[data-seo-score]').text(audit.score);
                $checker.find('[data-seo-status]').text(audit.status);
                $checker.find('[data-seo-preview-title]').text(audit.effectiveTitle || 'Chưa có tiêu đề');
                $checker.find('[data-seo-preview-url]').text(previewUrl);
                $checker.find('[data-seo-preview-description]').text(audit.effectiveDescription || 'Chưa có meta description hoặc mô tả tóm tắt.');
                $checker.find('[data-seo-primary-keyword]').text(audit.primaryKeyword || 'Chưa đặt');
                $checker.find('[data-seo-word-count]').text(audit.wordCount);
                $checker.find('[data-seo-title-length]').text(audit.titleLength);
                $checker.find('[data-seo-description-length]').text(audit.descriptionLength);

                renderList($checker.find('[data-seo-errors]'), audit.errors, 'Không có lỗi SEO nghiêm trọng.', 'danger');
                renderList($checker.find('[data-seo-warnings]'), audit.warnings, 'Không có gợi ý bổ sung.', 'warning');
            }

            function scheduleRender() {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(render, 150);
            }

            $checker.closest('form').on('keyup change input', 'input, textarea, select', scheduleRender);

            if (window.CKEDITOR) {
                CKEDITOR.on('instanceReady', function(event) {
                    if (event.editor.name === fieldIds.contents || event.editor.name === fieldIds.description) {
                        event.editor.on('change', scheduleRender);
                        scheduleRender();
                    }
                });

                $.each(CKEDITOR.instances, function(id, editor) {
                    if (id === fieldIds.contents || id === fieldIds.description) {
                        editor.on('change', scheduleRender);
                    }
                });
            }

            render();
        });
    }

});

// Handling the modal confirmation message.
$(document).on('submit', 'form[data-confirmation]', function (event) {
    var $form = $(this),
        $confirm = $($form.find('button').data('target'));

    if ($confirm.data('result') !== 'yes') {
        //cancel submit event
        event.preventDefault();

        $confirm
            .off('click', '#btnYes')
            .on('click', '#btnYes', function () {
                $confirm.data('result', 'yes');
                $form.find('input[type="submit"]').attr('disabled', 'disabled');
                $form.submit();
            })
            .modal('show');
    }
});

function initDashboardCharts() {
        var dataNode = document.getElementById('dashboard-chart-data');

        if (!dataNode || !window.Chart && !Chart) {
            return;
        }

        var chartData;

        try {
            chartData = JSON.parse(dataNode.textContent || '{}');
        } catch (error) {
            return;
        }

        Chart.defaults.global.defaultFontFamily = "'Open Sans', Arial, sans-serif";
        Chart.defaults.global.defaultFontColor = '#5f6b7a';
        Chart.defaults.global.elements.line.tension = 0.25;

        var gridColor = 'rgba(148, 163, 184, 0.22)';

        function hasCanvas(id) {
            return document.getElementById(id);
        }

        function axisOptions() {
            return {
                xAxes: [{
                    gridLines: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 8
                    }
                }],
                yAxes: [{
                    gridLines: {
                        color: gridColor,
                        zeroLineColor: gridColor
                    },
                    ticks: {
                        beginAtZero: true,
                        precision: 0
                    }
                }]
            };
        }

        function baseOptions(extra) {
            return $.extend(true, {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    labels: {
                        boxWidth: 12,
                        padding: 16
                    }
                },
                tooltips: {
                    mode: 'index',
                    intersect: false
                },
                scales: axisOptions()
            }, extra || {});
        }

        if (hasCanvas('dashboardViewsChart') && chartData.views) {
            new Chart(document.getElementById('dashboardViewsChart'), {
                type: 'line',
                data: {
                    labels: chartData.views.labels || [],
                    datasets: [{
                        label: 'Lượt xem',
                        data: chartData.views.views || [],
                        backgroundColor: 'rgba(0, 123, 255, 0.12)',
                        borderColor: '#007bff',
                        pointBackgroundColor: '#007bff',
                        pointRadius: 2,
                        borderWidth: 2
                    }]
                },
                options: baseOptions({
                    legend: {
                        display: false
                    }
                })
            });
        }

        if (hasCanvas('dashboardTopPostsChart') && chartData.topPosts) {
            new Chart(document.getElementById('dashboardTopPostsChart'), {
                type: 'horizontalBar',
                data: {
                    labels: chartData.topPosts.labels || [],
                    datasets: [{
                        label: 'Lượt xem',
                        data: chartData.topPosts.views || [],
                        backgroundColor: '#17a2b8',
                        borderColor: '#138496',
                        borderWidth: 1
                    }]
                },
                options: baseOptions({
                    legend: {
                        display: false
                    },
                    scales: {
                        xAxes: [{
                            gridLines: {
                                color: gridColor,
                                zeroLineColor: gridColor
                            },
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }],
                        yAxes: [{
                            gridLines: {
                                display: false
                            },
                            ticks: {
                                fontSize: 11
                            }
                        }]
                    }
                })
            });
        }

        if (hasCanvas('dashboardPostGrowthChart') && chartData.posts) {
            new Chart(document.getElementById('dashboardPostGrowthChart'), {
                type: 'bar',
                data: {
                    labels: chartData.posts.labels || [],
                    datasets: [{
                        label: 'Bài mới',
                        data: chartData.posts.daily || [],
                        backgroundColor: 'rgba(40, 167, 69, 0.35)',
                        borderColor: '#28a745',
                        borderWidth: 1
                    }, {
                        label: 'Tổng bài viết',
                        type: 'line',
                        data: chartData.posts.cumulative || [],
                        backgroundColor: 'rgba(255, 193, 7, 0.12)',
                        borderColor: '#ffc107',
                        pointBackgroundColor: '#ffc107',
                        pointRadius: 2,
                        borderWidth: 2,
                        yAxisID: 'total'
                    }]
                },
                options: baseOptions({
                    scales: {
                        xAxes: [{
                            gridLines: {
                                display: false
                            },
                            ticks: {
                                maxTicksLimit: 8
                            }
                        }],
                        yAxes: [{
                            id: 'daily',
                            position: 'left',
                            gridLines: {
                                color: gridColor,
                                zeroLineColor: gridColor
                            },
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }, {
                            id: 'total',
                            position: 'right',
                            gridLines: {
                                display: false
                            },
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }]
                    }
                })
            });
        }

        if (hasCanvas('dashboardCommentTrendChart') && chartData.comments) {
            new Chart(document.getElementById('dashboardCommentTrendChart'), {
                type: 'line',
                data: {
                    labels: chartData.comments.labels || [],
                    datasets: [{
                        label: 'Tổng bình luận',
                        data: chartData.comments.total || [],
                        backgroundColor: 'rgba(220, 53, 69, 0.10)',
                        borderColor: '#dc3545',
                        pointBackgroundColor: '#dc3545',
                        pointRadius: 2,
                        borderWidth: 2
                    }, {
                        label: 'Đã duyệt',
                        data: chartData.comments.approved || [],
                        backgroundColor: 'rgba(40, 167, 69, 0.10)',
                        borderColor: '#28a745',
                        pointBackgroundColor: '#28a745',
                        pointRadius: 2,
                        borderWidth: 2
                    }]
                },
                options: baseOptions()
            });
        }
    }

