/*  global fsi_ajax, wp 
*	Handles UI interactions (source buttons, filters, search, infinite scroll)
*	AJAX calls to fsi_search & fsi_import
*	Renders results in grid
*	Click image → auto import(by importer.php)
*/
jQuery(function ($) {


    const perPage = fsi_ajax.per_page || 20;
    const ajaxUrl = fsi_ajax.ajax_url;
    const nonce = fsi_ajax.nonce;
    const sources = fsi_ajax.sources || { pixabay: 'Pixabay', pexels: 'Pexels', unsplash: 'Unsplash' };

    // We will render the UI in two contexts:
    // 1) Inside the Media Modal (as a new tab)
    // 2) Inside the standalone Media -> Free Stock Images page (#fsi-standalone-app)
    // The UI renderer will be the same for both; we just mount it into two different roots.

    function createRootUI() {
        const container = $('<div class="fsi-root">');

        const toolbar = $(`
            <div class="fsi-toolbar">
                <h2>Select image source: </h2>
                <div class="fsi-sources">

                </div>
            </div>

            <div class="fsi-toolbar-bottom">
                <div class="fsi-filters">
                    <h3>Filters: </h3>
                    <label>Orientation
                        <select class="fsi-filter-orientation">
                            <option value="">Any</option>
                            <option value="landscape">Landscape</option>
                            <option value="portrait">Portrait</option>
                            <option value="square">Square</option>
                        </select>
                    </label>
                    <label>Color
                        <input type="text" class="fsi-filter-color" placeholder="e.g. red">
                    </label>
                </div>

                <div class="fsi-search">
                    <input type="text" class="fsi-search-input" placeholder="Search images...">
                    <button class="button fsi-search-btn">Search</button>
                </div>
            </div>
        `);

        const content = $(`
            <div class="fsi-content">
                <main class="fsi-main">
                    <div class="fsi-results fsi-grid"></div>
                    <div class="fsi-loader" style="display:none;">Loading...</div>
                </main>
            </div>
        `);

        container.append(toolbar).append(content);

        // Build source buttons
        const sourcesWrap = toolbar.find('.fsi-sources');
        for (const key in sources) {
            const btn = $(`<button class="button fsi-source-btn" data-source="${key}">${sources[key]}</button>`);
            sourcesWrap.append(btn);
        }
        // Activate first source by default
        sourcesWrap.find('.fsi-source-btn').first().addClass('active');

        return container;
    }

    function mountInto(rootSelector) {
        const $root = $(rootSelector);
        if (!$root.length) return null;
        // If UI already mounted, return existing
        if ($root.find('.fsi-root').length) return $root.find('.fsi-root');

        const ui = createRootUI();
        $root.find('.fsi-ui-root, .wrap').first().append(ui);
        attachEventHandlers(ui);
        return ui;
    }

    // Attach handlers to UI
    function attachEventHandlers($ui) {
        const $sourceBtns = $ui.find('.fsi-source-btn');
        const $searchInput = $ui.find('.fsi-search-input');
        const $searchBtn = $ui.find('.fsi-search-btn');
        const $results = $ui.find('.fsi-results');
        const $loader = $ui.find('.fsi-loader');

        let currentSource = $sourceBtns.filter('.active').data('source') || 'pixabay';
        let currentQuery = '';
        let page = 1;
        let loading = false;
        let reachedEnd = false;

        function resetResults() {
            $results.empty();
            page = 1;
            reachedEnd = false;
        }

        function renderImages(images) {
            if (!images || !images.length) {
                if (page === 1) {
                    $results.append('<div class="fsi-no-results">No images found.</div>');
                }
                return;
            }

            images.forEach(img => {
                const $item = $(`
                    <div class="fsi-item" data-full="${img.full}" data-title="${escapeHtml(img.title || '')}" data-attribution="${escapeHtml(img.attribution || '')}">
                        <div class="fsi-thumb-wrap">
                            <img src="${img.thumbnail}" alt="${escapeHtml(img.title || '')}">
                        </div>
                        <div class="fsi-meta">
                            <div class="fsi-author">${escapeHtml(img.author || '')}</div>
                            <div class="fsi-source">${escapeHtml(img.source)}</div>
                        </div>
                        <div class="fsi-overlay"><span class="fsi-import-text">Click to import</span></div>
                    </div>
                `);
                $results.append($item);
            });
        }

        function search(triggeredByScroll = false) {
            if (loading || reachedEnd) return;
            loading = true;
            $loader.show();

            $.post(ajaxUrl, {
                action: 'fsi_search',
                _ajax_nonce: nonce,
                query: currentQuery,
                source: currentSource,
                page: page,
                per_page: perPage
            }).done(function (res) {
                if (res && res.success) {
                    const images = res.data.images || [];
                    if (images.length === 0) {
                        // If first page and no images -> show no results
                        if (page === 1) {
                            $results.empty();
                            $results.append('<div class="fsi-no-results">No images found.</div>');
                        }
                        reachedEnd = true;
                    } else {
                        renderImages(images);
                        // Keep page increment for next infinite load
                        page++;
                    }
                } else {
                    console.warn('fsi_search error', res);
                    if (!triggeredByScroll && page === 1) {
                        $results.empty();
                        $results.append('<div class="fsi-error">Error loading images.</div>');
                    }
                }
            }).fail(function () {
                if (!triggeredByScroll && page === 1) {
                    $results.empty();
                    $results.append('<div class="fsi-error">Error loading images.</div>');
                }
            }).always(function () {
                loading = false;
                $loader.hide();
            });
        }

        // Source change
        $ui.on('click', '.fsi-source-btn', function (e) {
            e.preventDefault();
            $sourceBtns.removeClass('active');
            $(this).addClass('active');
            currentSource = $(this).data('source');
            // Reset results and run a search if a query exists
            resetResults();
            if (currentQuery.length > 0) {
                search();
            }
        });

        // Search button
        $searchBtn.on('click', function () {
            currentQuery = $searchInput.val().trim();
            resetResults();
            if (currentQuery.length > 0) {
                search();
            }
        });

        // Enter key in search input
        $searchInput.on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $searchBtn.trigger('click');
            }
        });

        // Infinite scroll inside main area
        $ui.find('.fsi-main').on('scroll', function () {
            const $this = $(this);
            const nearBottom = $this.scrollTop() + $this.innerHeight() >= ($results.outerHeight() - 200);
            if (nearBottom && !loading && !reachedEnd) {
                search(true);
            }
        });

        // Click to import
        $ui.on('click', '.fsi-item', function () {
            const $item = $(this);
            const full = $item.data('full');
            const title = $item.data('title') || '';
            const attribution = $item.data('attribution') || '';

            if (!full) return;

            // Show spinner/overlay
            $item.find('.fsi-overlay').addClass('loading').html('<span>Importing...</span>');

            $.post(ajaxUrl, {
                action: 'fsi_import',
                _ajax_nonce: nonce,
                image_url: full,
                title: title,
                attribution: attribution
            }).done(function (res) {
                if (res && res.success) {
                    const attachId = res.data.attachment_id;
                    $item.find('.fsi-overlay').addClass('done').html('<span>Imported ✓</span>');
                    // optionally: open selection or do something with attachId
                } else {
                    $item.find('.fsi-overlay').removeClass('loading').addClass('error').html('<span>Error</span>');
                }
            }).fail(function () {
                $item.find('.fsi-overlay').removeClass('loading').addClass('error').html('<span>Error</span>');
            });
        });

        // Load default images ("nature") on page load
        currentQuery = 'nature';
        resetResults();
        search();

    }

    // Expose a mount function for other contexts (media modal, standalone page)
    window.fsi_mount = function ($container) {
        if (!$container || !$container.length) return null;

        // if UI already mounted into this container, return existing root
        if ($container.find('.fsi-root').length) {
            return $container.find('.fsi-root');
        }

        // Create UI and attach
        const ui = createRootUI();
        // If the container is the special .fsi-ui-root inside a wrapper, use append
        $container.append(ui);

        attachEventHandlers(ui);

        return ui;
    };


    // Simple HTML escaper for attribute interpolation
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // 1) Mount into standalone media page if present
    mountInto('#fsi-standalone-app .fsi-ui-root');
    mountInto('#fsi-standalone-app');

    // 2) Hook into the Media Modal — DOM-based tab injection
    $(document).on('click', '.insert-media, .add_media', function () {
        // Wait for media modal to open
        setTimeout(function () {
            const $router = $('.media-frame-router');
            if (!$router.length) return;

            // Add router tab if not exists
            if (!$router.find('.fsi-router-tab').length) {
                const tab = $(`<a href="#" class="media-menu-item fsi-router-tab" data-tab="fsi-tab">${sources['unsplash'] ? 'Free Stock Images' : 'Free Stock Images'}</a>`);
                $router.append(tab);

                // Add content container inside media frame content area
                const $contentArea = $('.media-frame-content');
                if ($contentArea.length && !$contentArea.find('.fsi-tab').length) {
                    const $fsiTab = $(`<div class="fsi-tab" style="display:none;"><div class="fsi-ui-root"></div></div>`);
                    $contentArea.append($fsiTab);
                }
            }

            // Click handler for the router tab
            $('.media-frame-router').off('click', '.fsi-router-tab').on('click', '.fsi-router-tab', function (e) {
                e.preventDefault();
                // hide other content
                $('.media-frame-content > :not(.fsi-tab)').hide();
                $('.media-frame-content .fsi-tab').show();

                // mark router links active
                $('.media-frame-router .media-router a, .media-frame-router .media-menu-item').removeClass('active');
                $(this).addClass('active');

                // mount UI into our tab container
                mountInto('.media-frame-content .fsi-tab .fsi-ui-root');
            });
        }, 300);
    });

    // Also try to inject when modal is opened via JS events
    $(document).on('click', '.media-button, .editor-inserter, a.insert-media', function () {
        setTimeout(function () {
            $('.fsi-router-tab').trigger('click');
        }, 800);
    });

    // If modal already open when script loads, attempt to inject
    setTimeout(function () {
        const $router = $('.media-frame-router');
        if ($router.length && !$router.find('.fsi-router-tab').length) {
            // emulate click to inject
            $('.insert-media').trigger('click');
        }
    }, 600);



    // === Safely extend wp.media to add our tab, result: "edit page> add imge> media popup> OUR TAB" ===
    if (typeof wp !== 'undefined' && wp.media && wp.media.view) {
        const OrigMediaFramePost = wp.media.view.MediaFrame.Post;

        wp.media.view.MediaFrame.Post = OrigMediaFramePost.extend({
            browseRouter: function (routerView) {
                OrigMediaFramePost.prototype.browseRouter.apply(this, arguments);
                routerView.set({
                    fsi: {
                        text: 'Free Stock Images',
                        priority: 60
                    }
                });
            },
            bindHandlers: function () {
                OrigMediaFramePost.prototype.bindHandlers.apply(this, arguments);
                this.on('content:create:fsi', this.createFsiContent, this);
            },
            createFsiContent: function (contentRegion) {
                const $el = $('<div class="fsi-tab"><div class="fsi-ui-root"></div></div>');
                contentRegion.view = new wp.Backbone.View({ el: $el[0] });
                contentRegion.$el.append($el);

                const $root = $el.find('.fsi-ui-root');
                if (window.fsi_mount) {
                    window.fsi_mount($root);
                }
            }
        });
    }


});
