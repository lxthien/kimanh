import 'typeahead.js';
import 'bootstrap-tagsinput';

import 'bootstrap-sass/assets/javascripts/bootstrap/modal.js';

$(function() {
    // Build the slug for object entiry from the name
    initBuildSluggable();

    // Init CkEditor and CKfinder
    initCkeditor();

    // Update object when change the enable button toggle
    initEnableToggleButton();

    initMakePrimaryCategory();
    initAdminNotifications();
    initPageBuilder();

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
                filebrowserBrowseUrl: '/assets/cksourceckfinder/ckfinder/ckfinder.html',
                filebrowserUploadUrl: '/assets/cksourceckfinder/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
                filebrowserWindowWidth: '1000',
                filebrowserWindowHeight: '700'
            });
        });
    }

    /**
     * Update object when change the enable button toggle
     **/
    function initEnableToggleButton() {
        $(document).on('change', '.switch-input[data-action]', function() {
            let $input = $(this);
            let isChecked = $input.prop('checked');
            isChecked = isChecked ? 1 : 0;
            let id = $input.data('id');
            let url = $input.data('action');
            
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
    var $input = $('input[data-toggle="tagsinput"]');
    if ($input.length && typeof Bloodhound !== 'undefined') {
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

    function initAdminNotifications() {
        var $button = $('#notificationDropdown');
        var $countNode = $('[data-notification-count]');
        var $menuNode = $('[data-notification-menu]');

        if (!$button.length || !$countNode.length || !$menuNode.length) {
            return;
        }

        var feedUrl = $button.data('feed-url');
        if (!feedUrl) {
            return;
        }

        function renderNotificationCount(total) {
            $countNode.text(total);

            if (total > 0) {
                $countNode.removeClass('d-none');
            } else {
                $countNode.addClass('d-none');
            }
        }

        function refreshNotifications() {
            $.ajax({
                url: feedUrl,
                type: 'GET',
                dataType: 'json'
            }).done(function(payload) {
                renderNotificationCount(payload.total || 0);

                if (typeof payload.html === 'string') {
                    $menuNode.html(payload.html);
                }
            });
        }

        $button.on('click', refreshNotifications);
        window.setInterval(refreshNotifications, 30000);
    }

    function initPageBuilder() {
        var $builder = $('[data-page-builder]');

        if (!$builder.length) {
            return;
        }

        var $enabled = $builder.find('input[id$="_pageBuilderEnabled"]');
        var $dataInput = $builder.find('input[id$="_pageBuilderData"]');
        var $form = $builder.closest('form');
        var $contents = $form.find('textarea[id$="_contents"]');
        var $workspace = $builder.find('[data-page-builder-workspace]');
        var $legacy = $form.find('[data-page-builder-legacy]');
        var $list = $builder.find('[data-page-builder-list]');
        var $empty = $builder.find('[data-page-builder-empty]');
        var $addButton = $builder.find('[data-page-builder-add]');
        var editorPrefix = 'page_builder_block_';

        if (!$enabled.length || !$dataInput.length || !$contents.length) {
            return;
        }
        var blockOptions = [
            { value: 'hero', label: 'Hero' },
            { value: 'rich_text', label: 'Rich text' },
            { value: 'image', label: 'Image' },
            { value: 'gallery', label: 'Gallery' },
            { value: 'faq', label: 'FAQ' },
            { value: 'video', label: 'Video' },
            { value: 'features', label: 'Features' },
            { value: 'contact_form', label: 'Contact form' },
            { value: 'cta', label: 'CTA' },
            { value: 'spacer', label: 'Spacer' }
        ];
        var blocks = parseBlocks($dataInput.val());
        var dragIndex = null;

        render();
        syncVisibility();

        $enabled.on('change', function() {
            syncVisibility();
            syncBuilderState();
        });

        $addButton.on('click', function() {
            syncFromDom();
            blocks.push(createDefaultBlock('rich_text'));
            render();
            syncBuilderState();
        });

        $list.on('click', '[data-block-clone]', function() {
            syncFromDom();
            var index = $(this).closest('[data-block-index]').data('block-index');
            var source = blocks[index];

            if (!source) {
                return;
            }

            blocks.splice(index + 1, 0, $.extend(true, {}, source, { id: createId() }));
            render();
            syncBuilderState();
        });

        $list.on('click', '[data-block-remove]', function() {
            syncFromDom();
            var index = $(this).closest('[data-block-index]').data('block-index');
            blocks.splice(index, 1);
            render();
            syncBuilderState();
        });

        $list.on('click', '[data-block-move]', function() {
            syncFromDom();
            var $block = $(this).closest('[data-block-index]');
            var index = $block.data('block-index');
            var direction = $(this).data('block-move');
            var targetIndex = direction === 'up' ? index - 1 : index + 1;

            if (targetIndex < 0 || targetIndex >= blocks.length) {
                return;
            }

            var current = blocks[index];
            blocks[index] = blocks[targetIndex];
            blocks[targetIndex] = current;
            render();
            syncBuilderState();
        });

        $list.on('change', '[data-block-type]', function() {
            syncFromDom();
            var $block = $(this).closest('[data-block-index]');
            var index = $block.data('block-index');
            blocks[index] = createDefaultBlock($(this).val(), blocks[index].id);
            render();
            syncBuilderState();
        });

        $list.on('dragstart', '[data-block-index]', function(event) {
            dragIndex = $(this).data('block-index');
            $(this).addClass('is-dragging');
            event.originalEvent.dataTransfer.effectAllowed = 'move';
            event.originalEvent.dataTransfer.setData('text/plain', String(dragIndex));
        });

        $list.on('dragend', '[data-block-index]', function() {
            dragIndex = null;
            $list.find('[data-block-index]').removeClass('is-dragging is-drop-target');
        });

        $list.on('dragover', '[data-block-index]', function(event) {
            event.preventDefault();
            $(this).addClass('is-drop-target');
            event.originalEvent.dataTransfer.dropEffect = 'move';
        });

        $list.on('dragleave', '[data-block-index]', function() {
            $(this).removeClass('is-drop-target');
        });

        $list.on('drop', '[data-block-index]', function(event) {
            event.preventDefault();
            $(this).removeClass('is-drop-target');
            syncFromDom();

            var targetIndex = $(this).data('block-index');

            if (dragIndex === null || dragIndex === targetIndex) {
                return;
            }

            var moved = blocks.splice(dragIndex, 1)[0];
            blocks.splice(targetIndex, 0, moved);
            render();
            syncBuilderState();
        });

        $list.on('input change', 'input, textarea, select', function() {
            syncFromDom();
            syncBuilderState();
            updateBlockPreview($(this).closest('[data-block-index]'));
        });

        $form.on('submit', function(event) {
            syncFromDom();

            if ($enabled.is(':checked') && !blocks.length) {
                event.preventDefault();
                window.alert('Vui long them it nhat mot block cho Page Builder.');
                return false;
            }

            syncBuilderState();
        });

        function syncVisibility() {
            var enabled = $enabled.is(':checked');
            $workspace.toggle(enabled);
            $legacy.toggle(!enabled);
        }

        function syncFromDom() {
            syncRichTextEditorsToTextareas();
            var nextBlocks = [];

            $list.find('[data-block-index]').each(function() {
                var $block = $(this);
                var index = $block.data('block-index');
                var type = $block.find('[data-block-type]').val();
                var data = {};

                $block.find('[data-field]').each(function() {
                    data[$(this).data('field')] = $(this).val();
                });

                nextBlocks.push({
                    id: blocks[index] && blocks[index].id ? blocks[index].id : createId(),
                    type: type,
                    data: data
                });
            });

            blocks = nextBlocks;
        }

        function syncBuilderState() {
            var json = blocks.length ? JSON.stringify(blocks) : '';
            $dataInput.val(json);

            if ($enabled.is(':checked')) {
                $contents.val(buildLegacyHtml(blocks));
            }
        }

        function render() {
            destroyPageBuilderEditors();
            $list.empty();
            $empty.toggle(!blocks.length);

            $.each(blocks, function(index, block) {
                $list.append(renderBlock(index, block));
            });

            initPageBuilderEditors();
        }

        function renderBlock(index, block) {
            var optionMarkup = $.map(blockOptions, function(option) {
                return '<option value="' + option.value + '"' + (option.value === block.type ? ' selected' : '') + '>' + option.label + '</option>';
            }).join('');
            var fieldsMarkup = renderBlockFields(block);

            return [
                '<div class="page-builder-admin__block card" data-block-index="' + index + '" draggable="true">',
                    '<div class="card-header page-builder-admin__block-header">',
                        '<div class="page-builder-admin__block-title">',
                            '<strong><i class="fa fa-bars" aria-hidden="true"></i> Block ' + (index + 1) + '</strong>',
                            '<select class="form-control form-control-sm" data-block-type>' + optionMarkup + '</select>',
                        '</div>',
                        '<div class="page-builder-admin__block-actions">',
                            '<button type="button" class="btn btn-light btn-sm" data-block-clone><i class="fa fa-copy" aria-hidden="true"></i></button>',
                            '<button type="button" class="btn btn-light btn-sm" data-block-move="up"><i class="fa fa-arrow-up" aria-hidden="true"></i></button>',
                            '<button type="button" class="btn btn-light btn-sm" data-block-move="down"><i class="fa fa-arrow-down" aria-hidden="true"></i></button>',
                            '<button type="button" class="btn btn-danger btn-sm" data-block-remove><i class="fa fa-trash" aria-hidden="true"></i></button>',
                        '</div>',
                    '</div>',
                    '<div class="card-body page-builder-admin__block-body">',
                        fieldsMarkup,
                        '<div class="page-builder-admin__preview">',
                            '<div class="page-builder-admin__preview-label">Preview</div>',
                            '<div class="page-builder-admin__preview-body">' + renderPreviewHtml(block) + '</div>',
                        '</div>',
                    '</div>',
                '</div>'
            ].join('');
        }

        function renderBlockFields(block) {
            var data = block.data || {};

            if (block.type === 'hero') {
                return [
                    renderInput('Eyebrow', 'eyebrow', data.eyebrow),
                    renderInput('Title', 'title', data.title),
                    renderTextarea('Body', 'body', data.body, 4),
                    renderInput('Button text', 'button_text', data.button_text),
                    renderInput('Button URL', 'button_url', data.button_url),
                    renderInput('Background image URL', 'background_image', data.background_image)
                ].join('');
            }

            if (block.type === 'image') {
                return [
                    renderInput('Image URL', 'url', data.url),
                    renderInput('Alt text', 'alt', data.alt),
                    renderInput('Caption', 'caption', data.caption),
                    renderInput('Width', 'width', data.width)
                ].join('');
            }

            if (block.type === 'cta') {
                return [
                    renderInput('Title', 'title', data.title),
                    renderTextarea('Body', 'body', data.body, 3),
                    renderInput('Button text', 'button_text', data.button_text),
                    renderInput('Button URL', 'button_url', data.button_url),
                    renderSelect('Style', 'style', data.style, [
                        { value: 'primary', label: 'Primary' },
                        { value: 'outline', label: 'Outline' }
                    ])
                ].join('');
            }

            if (block.type === 'gallery') {
                return [
                    '<div class="alert alert-light page-builder-admin__hint">Moi dong: image_url | alt text | caption</div>',
                    renderTextarea('Gallery items', 'items', data.items, 7)
                ].join('');
            }

            if (block.type === 'faq') {
                return [
                    '<div class="alert alert-light page-builder-admin__hint">Moi dong: Cau hoi | Cau tra loi</div>',
                    renderTextarea('FAQ items', 'items', data.items, 8)
                ].join('');
            }

            if (block.type === 'video') {
                return [
                    renderInput('Video URL', 'url', data.url),
                    renderInput('Title', 'title', data.title),
                    renderTextarea('Caption', 'caption', data.caption, 3)
                ].join('');
            }

            if (block.type === 'features') {
                return [
                    renderInput('Section title', 'title', data.title),
                    '<div class="alert alert-light page-builder-admin__hint">Moi dong: icon_class | title | mo ta</div>',
                    renderTextarea('Feature items', 'items', data.items, 8)
                ].join('');
            }

            if (block.type === 'contact_form') {
                return [
                    renderInput('Title', 'title', data.title),
                    renderTextarea('Body', 'body', data.body, 3),
                    renderInput('Hotline', 'hotline', data.hotline)
                ].join('');
            }

            if (block.type === 'spacer') {
                return renderInput('Height (px)', 'height', data.height || '48');
            }

            return renderRichTextField(block, data.html);
        }

        function renderInput(label, field, value) {
            return [
                '<div class="form-group page-builder-admin__field">',
                    '<label>' + escapeHtml(label) + '</label>',
                    '<input type="text" class="form-control" data-field="' + field + '" value="' + escapeHtml(value || '') + '">',
                '</div>'
            ].join('');
        }

        function renderTextarea(label, field, value, rows) {
            return [
                '<div class="form-group page-builder-admin__field">',
                    '<label>' + escapeHtml(label) + '</label>',
                    '<textarea class="form-control" rows="' + rows + '" data-field="' + field + '">' + escapeHtml(value || '') + '</textarea>',
                '</div>'
            ].join('');
        }

        function renderRichTextField(block, value) {
            var editorId = editorPrefix + (block.id || createId());

            return [
                '<div class="form-group page-builder-admin__field">',
                    '<label>HTML content</label>',
                    '<textarea id="' + editorId + '" class="form-control page-builder-richtext" rows="8" data-field="html" data-editor-id="' + editorId + '">' + escapeHtml(value || '') + '</textarea>',
                '</div>'
            ].join('');
        }

        function renderSelect(label, field, selectedValue, options) {
            var markup = $.map(options, function(option) {
                return '<option value="' + option.value + '"' + (option.value === selectedValue ? ' selected' : '') + '>' + option.label + '</option>';
            }).join('');

            return [
                '<div class="form-group page-builder-admin__field">',
                    '<label>' + escapeHtml(label) + '</label>',
                    '<select class="form-control" data-field="' + field + '">' + markup + '</select>',
                '</div>'
            ].join('');
        }

        function parseBlocks(rawValue) {
            if (!rawValue) {
                return [];
            }

            try {
                var parsed = JSON.parse(rawValue);

                if (!$.isArray(parsed)) {
                    return [];
                }

                return $.map(parsed, function(block) {
                    if (!block || !block.type) {
                        return null;
                    }

                    return {
                        id: block.id || createId(),
                        type: block.type,
                        data: block.data || {}
                    };
                });
            } catch (error) {
                return [];
            }
        }

        function createDefaultBlock(type, id) {
            var block = {
                id: id || createId(),
                type: type,
                data: {}
            };

            if (type === 'hero') {
                block.data = {
                    eyebrow: '',
                    title: '',
                    body: '',
                    button_text: '',
                    button_url: '',
                    background_image: ''
                };
            } else if (type === 'image') {
                block.data = {
                    url: '',
                    alt: '',
                    caption: '',
                    width: ''
                };
            } else if (type === 'cta') {
                block.data = {
                    title: '',
                    body: '',
                    button_text: '',
                    button_url: '',
                    style: 'primary'
                };
            } else if (type === 'gallery') {
                block.data = {
                    items: ''
                };
            } else if (type === 'faq') {
                block.data = {
                    items: ''
                };
            } else if (type === 'video') {
                block.data = {
                    url: '',
                    title: '',
                    caption: ''
                };
            } else if (type === 'features') {
                block.data = {
                    title: '',
                    items: ''
                };
            } else if (type === 'contact_form') {
                block.data = {
                    title: '',
                    body: '',
                    hotline: ''
                };
            } else if (type === 'spacer') {
                block.data = {
                    height: '48'
                };
            } else {
                block.data = {
                    html: ''
                };
            }

            return block;
        }

        function buildLegacyHtml(items) {
            return $.map(items, function(block) {
                var data = block.data || {};

                if (block.type === 'hero') {
                    var heroBody = data.body ? '<p>' + escapeHtml(data.body).replace(/\n/g, '<br>') + '</p>' : '';
                    var heroButton = data.button_text && data.button_url ? '<p><a class="ka-builder-button" href="' + escapeAttribute(data.button_url) + '">' + escapeHtml(data.button_text) + '</a></p>' : '';
                    var heroStyle = data.background_image ? ' style="background-image:url(\'' + escapeAttribute(data.background_image) + '\')"' : '';

                    return '<section class="ka-builder-hero"' + heroStyle + '><div class="ka-builder-hero__inner">' +
                        (data.eyebrow ? '<span class="ka-builder-eyebrow">' + escapeHtml(data.eyebrow) + '</span>' : '') +
                        (data.title ? '<h2>' + escapeHtml(data.title) + '</h2>' : '') +
                        heroBody +
                        heroButton +
                    '</div></section>';
                }

                if (block.type === 'image') {
                    if (!data.url) {
                        return '';
                    }

                    return '<figure class="ka-builder-image"' + (data.width ? ' style="max-width:' + escapeAttribute(data.width) + 'px"' : '') + '>' +
                        '<img loading="lazy" src="' + escapeAttribute(data.url) + '" alt="' + escapeAttribute(data.alt || '') + '">' +
                        (data.caption ? '<figcaption>' + escapeHtml(data.caption) + '</figcaption>' : '') +
                    '</figure>';
                }

                if (block.type === 'gallery') {
                    var galleryItems = parseLineItems(data.items, 3);

                    if (!galleryItems.length) {
                        return '';
                    }

                    return '<section class="ka-builder-gallery">' + $.map(galleryItems, function(item) {
                        return '<figure class="ka-builder-gallery__item">' +
                            '<img loading="lazy" src="' + escapeAttribute(item[0]) + '" alt="' + escapeAttribute(item[1] || '') + '">' +
                            (item[2] ? '<figcaption>' + escapeHtml(item[2]) + '</figcaption>' : '') +
                        '</figure>';
                    }).join('') + '</section>';
                }

                if (block.type === 'faq') {
                    var faqItems = parseLineItems(data.items, 2);

                    if (!faqItems.length) {
                        return '';
                    }

                    return '<section class="ka-builder-faq">' + $.map(faqItems, function(item) {
                        return '<details class="ka-builder-faq__item"><summary>' + escapeHtml(item[0]) + '</summary><div class="ka-builder-faq__answer"><p>' + escapeHtml(item[1] || '').replace(/\n/g, '<br>') + '</p></div></details>';
                    }).join('') + '</section>';
                }

                if (block.type === 'video') {
                    var embedUrl = buildVideoEmbedUrl(data.url || '');

                    if (!embedUrl) {
                        return '';
                    }

                    return '<section class="ka-builder-video">' +
                        (data.title ? '<h3>' + escapeHtml(data.title) + '</h3>' : '') +
                        '<div class="ka-builder-video__frame"><iframe src="' + escapeAttribute(embedUrl) + '" allowfullscreen loading="lazy"></iframe></div>' +
                        (data.caption ? '<p class="ka-builder-video__caption">' + escapeHtml(data.caption).replace(/\n/g, '<br>') + '</p>' : '') +
                    '</section>';
                }

                if (block.type === 'features') {
                    var featureItems = parseLineItems(data.items, 3);

                    if (!featureItems.length) {
                        return '';
                    }

                    return '<section class="ka-builder-features">' +
                        (data.title ? '<h3>' + escapeHtml(data.title) + '</h3>' : '') +
                        '<div class="ka-builder-features__grid">' + $.map(featureItems, function(item) {
                            return '<article class="ka-builder-features__item">' +
                                (item[0] ? '<div class="ka-builder-features__icon"><i class="' + escapeAttribute(item[0]) + '" aria-hidden="true"></i></div>' : '') +
                                (item[1] ? '<h4>' + escapeHtml(item[1]) + '</h4>' : '') +
                                (item[2] ? '<p>' + escapeHtml(item[2]).replace(/\n/g, '<br>') + '</p>' : '') +
                            '</article>';
                        }).join('') + '</div></section>';
                }

                if (block.type === 'contact_form') {
                    return '<section class="ka-builder-contact-form">' +
                        (data.title ? '<h3>' + escapeHtml(data.title) + '</h3>' : '') +
                        (data.body ? '<p>' + escapeHtml(data.body).replace(/\n/g, '<br>') + '</p>' : '') +
                        (data.hotline ? '<p><strong>' + escapeHtml(data.hotline) + '</strong></p>' : '') +
                        '<div class="ka-builder-contact-form__placeholder">Embedded contact form</div>' +
                    '</section>';
                }

                if (block.type === 'cta') {
                    return '<section class="ka-builder-cta ka-builder-cta--' + escapeAttribute(data.style || 'primary') + '">' +
                        (data.title ? '<h3>' + escapeHtml(data.title) + '</h3>' : '') +
                        (data.body ? '<p>' + escapeHtml(data.body).replace(/\n/g, '<br>') + '</p>' : '') +
                        (data.button_text && data.button_url ? '<a class="ka-builder-button" href="' + escapeAttribute(data.button_url) + '">' + escapeHtml(data.button_text) + '</a>' : '') +
                    '</section>';
                }

                if (block.type === 'spacer') {
                    return '<div class="ka-builder-spacer" style="height:' + escapeAttribute(data.height || '48') + 'px"></div>';
                }

                return '<section class="ka-builder-rich-text">' + (data.html || '') + '</section>';
            }).join('\n');
        }

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        function escapeAttribute(value) {
            return String(value || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function createId() {
            return 'block_' + Math.random().toString(36).slice(2, 10);
        }

        function renderPreviewHtml(block) {
            var html = buildLegacyHtml([block]);

            if (!html) {
                return '<div class="page-builder-admin__preview-empty">Chua co du lieu de preview.</div>';
            }

            return html;
        }

        function parseLineItems(value, expectedParts) {
            if (!value) {
                return [];
            }

            return $.map(String(value).split(/\r?\n/), function(line) {
                var trimmed = $.trim(line);

                if (!trimmed) {
                    return null;
                }

                var parts = $.map(trimmed.split('|'), function(part) {
                    return $.trim(part);
                });

                while (parts.length < expectedParts) {
                    parts.push('');
                }

                return [parts.slice(0, expectedParts)];
            });
        }

        function buildVideoEmbedUrl(url) {
            var value = $.trim(url || '');

            if (!value) {
                return '';
            }

            var youtubeMatch = value.match(/youtube\.com\/watch\?v=([^&]+)/);
            if (youtubeMatch) {
                return 'https://www.youtube.com/embed/' + youtubeMatch[1];
            }

            var shortYoutubeMatch = value.match(/youtu\.be\/([^?&/]+)/);
            if (shortYoutubeMatch) {
                return 'https://www.youtube.com/embed/' + shortYoutubeMatch[1];
            }

            var vimeoMatch = value.match(/vimeo\.com\/(\d+)/);
            if (vimeoMatch) {
                return 'https://player.vimeo.com/video/' + vimeoMatch[1];
            }

            return value;
        }

        function initPageBuilderEditors() {
            if (!window.CKEDITOR) {
                return;
            }

            $list.find('textarea.page-builder-richtext').each(function() {
                var textareaId = $(this).attr('id');

                if (!textareaId || CKEDITOR.instances[textareaId]) {
                    return;
                }

                CKEDITOR.replace(textareaId, {
                    protectedSource: [
                        /<script[\s\S]*?<\/script>/gi,
                        /<style[\s\S]*?<\/style>/gi
                    ],
                    height: '280px',
                    filebrowserBrowseUrl: '/assets/cksourceckfinder/ckfinder/ckfinder.html',
                    filebrowserUploadUrl: '/assets/cksourceckfinder/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
                    filebrowserWindowWidth: '1000',
                    filebrowserWindowHeight: '700'
                });

                CKEDITOR.instances[textareaId].on('change', function() {
                    syncRichTextEditorsToTextareas();
                    syncFromDom();
                    syncBuilderState();
                    updateBlockPreviewByTextareaId(textareaId);
                });
            });
        }

        function destroyPageBuilderEditors() {
            if (!window.CKEDITOR || !CKEDITOR.instances) {
                return;
            }

            syncRichTextEditorsToTextareas();

            $.each(CKEDITOR.instances, function(instanceId, instance) {
                if (instanceId.indexOf(editorPrefix) === 0) {
                    instance.destroy(true);
                }
            });
        }

        function syncRichTextEditorsToTextareas() {
            if (!window.CKEDITOR || !CKEDITOR.instances) {
                return;
            }

            $list.find('textarea.page-builder-richtext').each(function() {
                var textareaId = $(this).attr('id');
                var instance = textareaId ? CKEDITOR.instances[textareaId] : null;

                if (instance) {
                    $(this).val(instance.getData());
                }
            });
        }

        function updateBlockPreviewByTextareaId(textareaId) {
            var $textarea = $('#' + textareaId);
            updateBlockPreview($textarea.closest('[data-block-index]'));
        }

        function updateBlockPreview($block) {
            if (!$block || !$block.length) {
                return;
            }

            var index = $block.data('block-index');
            var block = blocks[index];

            if (!block) {
                return;
            }

            $block.find('.page-builder-admin__preview-body').html(renderPreviewHtml(block));
        }
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
