/* global fsi_ajax, wp */
jQuery(function ($) {
	const config = window.fsi_ajax || {};
	const ajaxUrl = config.ajaxUrl || '';
	const nonce = config.nonce || '';
	const perPage = config.perPage || 20;
	const searchAction = config.searchAction || 'fsi_search';
	const importAction = config.importAction || 'fsi_import';
	const i18n = config.i18n || {};
	const sources = config.sources || {};
	const debugEnabled = !!config.debug;

	function debugLog() {
		if (!debugEnabled || typeof console === 'undefined' || !console.log) {
			return;
		}
		const args = Array.prototype.slice.call(arguments);
		args.unshift('[FSI]');
		console.log.apply(console, args);
	}

	function t(key, fallback) {
		return i18n[key] || fallback;
	}

	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}

	function resolveMediaRuntime() {
		const candidates = [window];
		try {
			if (window.top && window.top !== window) {
				candidates.unshift(window.top);
			}
		} catch (e) {
			debugLog('Cannot access window.top', e);
		}

		for (let i = 0; i < candidates.length; i++) {
			const candidate = candidates[i];
			if (candidate.wp && candidate.wp.media && candidate.jQuery) {
				const runtimeType = candidate === window ? 'window' : 'top';
				debugLog('Using media runtime from', runtimeType);
				return {
					win: candidate,
					$: candidate.jQuery,
					media: candidate.wp.media,
					backbone: candidate.wp.Backbone || null
				};
			}
		}

		debugLog('No media runtime detected in window/top');
		return {
			win: window,
			$: $,
			media: (window.wp && window.wp.media) ? window.wp.media : null,
			backbone: (window.wp && window.wp.Backbone) ? window.wp.Backbone : null
		};
	}

	function ApiClient() {}

	ApiClient.prototype.search = function (params) {
		return $.post(ajaxUrl, {
			action: searchAction,
			_ajax_nonce: nonce,
			query: params.query || '',
			source: params.source || 'pixabay',
			page: params.page || 1,
			per_page: perPage
		});
	};

	ApiClient.prototype.importImage = function (image) {
		return $.post(ajaxUrl, {
			action: importAction,
			_ajax_nonce: nonce,
			image_url: image.full,
			title: image.title || '',
			attribution: image.attribution || '',
			source: image.source || '',
			remote_id: image.id || ''
		});
	};

	function FsiStore() {
		const defaultSource = Object.keys(sources).find(function (key) {
			return sources[key] && sources[key].enabled;
		}) || Object.keys(sources)[0] || 'pixabay';

		this.source = defaultSource;
		this.query = '';
		this.page = 1;
		this.loading = false;
		this.reachedEnd = false;
	}

	FsiStore.prototype.resetPagination = function () {
		this.page = 1;
		this.reachedEnd = false;
	};

	FsiStore.prototype.nextPage = function () {
		this.page += 1;
	};

	function createRootUI() {
		const $root = $('<div class="fsi-root"></div>');
		const $toolbar = $('<div class="fsi-toolbar"></div>');
		const $sources = $('<div class="fsi-sources"></div>');
		const $searchWrap = $('<div class="fsi-search"></div>');

		Object.keys(sources).forEach(function (key) {
			const source = sources[key] || {};
			const disabledAttr = source.enabled ? '' : 'disabled="disabled"';
			const disabledClass = source.enabled ? '' : ' fsi-source-btn-disabled';
			const $button = $(
				'<button type="button" class="button fsi-source-btn' + disabledClass + '" data-source="' + escapeHtml(key) + '" ' + disabledAttr + '>' +
				escapeHtml(source.label || key) +
				'</button>'
			);
			$sources.append($button);
		});

		$searchWrap.append('<input type="text" class="fsi-search-input" placeholder="' + escapeHtml(t('searchPlaceholder', 'Search images...')) + '">');
		$searchWrap.append('<button type="button" class="button button-primary fsi-search-btn">' + escapeHtml(t('search', 'Search')) + '</button>');

		$toolbar.append('<h2 class="fsi-title">' + escapeHtml(t('title', 'Free Stock Images')) + '</h2>');
		$toolbar.append($sources);
		$toolbar.append($searchWrap);

		const $content = $(
			'<div class="fsi-content">' +
			'  <div class="fsi-notice" style="display:none;"></div>' +
			'  <div class="fsi-results fsi-grid"></div>' +
			'  <div class="fsi-loader" style="display:none;">' + escapeHtml(t('loading', 'Loading...')) + '</div>' +
			'</div>'
		);

		$root.append($toolbar).append($content);
		return $root;
	}

	function renderImages($results, images) {
		images.forEach(function (img) {
			const $item = $(
				'<button type="button" class="fsi-item">' +
				'  <div class="fsi-thumb-wrap">' +
				'    <img src="' + escapeHtml(img.thumbnail) + '" alt="' + escapeHtml(img.title || '') + '">' +
				'  </div>' +
				'  <div class="fsi-meta">' +
				'    <span class="fsi-author">' + escapeHtml(img.author || '') + '</span>' +
				'    <span class="fsi-source">' + escapeHtml(img.source || '') + '</span>' +
				'  </div>' +
				'  <span class="fsi-overlay"><span>' + escapeHtml(t('importing', 'Importing...')) + '</span></span>' +
				'</button>'
			);
			$item.data('fsiImage', img);
			$results.append($item);
		});
	}

	function showNotice($ui, message, type) {
		const $notice = $ui.find('.fsi-notice');
		$notice.removeClass('fsi-notice-error fsi-notice-warning fsi-notice-success');
		$notice.addClass('fsi-notice-' + type);
		$notice.text(message);
		$notice.show();
	}

	function clearNotice($ui) {
		$ui.find('.fsi-notice').hide().text('');
	}

	function autoInsertIntoFrame(frame, attachmentId, mediaApi) {
		if (!frame || !mediaApi || !mediaApi.attachment) {
			return;
		}

		const attachment = mediaApi.attachment(attachmentId);
		attachment.fetch().done(function () {
			const state = frame.state ? frame.state() : null;
			if (!state || !state.get) {
				return;
			}

			const selection = state.get('selection');
			if (!selection) {
				return;
			}

			selection.reset([attachment]);
			try {
				state.trigger('insert', selection);
			} catch (e) {
				if (frame.close) {
					frame.close();
				}
			}
		});
	}

	function attachUIHandlers($ui, options) {
		const api = new ApiClient();
		const store = new FsiStore();
		const autoInsert = !!(options && options.autoInsert);
		const frameResolver = options && typeof options.frameResolver === 'function' ? options.frameResolver : function () { return null; };
		const mediaApi = options && options.mediaApi ? options.mediaApi : null;

		const $results = $ui.find('.fsi-results');
		const $loader = $ui.find('.fsi-loader');
		const $searchInput = $ui.find('.fsi-search-input');

		function setActiveSource(sourceKey) {
			$ui.find('.fsi-source-btn').removeClass('active');
			$ui.find('.fsi-source-btn[data-source="' + sourceKey + '"]').addClass('active');
			store.source = sourceKey;
		}

		function runSearch() {
			if (store.loading || store.reachedEnd) {
				return;
			}

			const source = sources[store.source] || {};
			if (!source.enabled) {
				showNotice($ui, t('needsKey', 'API key is required for this source.'), 'warning');
				return;
			}

			store.loading = true;
			$loader.show();
			clearNotice($ui);

			api.search({
				query: store.query,
				source: store.source,
				page: store.page
			}).done(function (res) {
				if (!res || !res.success) {
					const message = res && res.data && res.data.message ? res.data.message : t('error', 'Something went wrong.');
					showNotice($ui, message, 'error');
					return;
				}

				const images = (res.data && res.data.images) || [];
				if (!images.length) {
					if (store.page === 1) {
						$results.html('<div class="fsi-no-results">' + escapeHtml(t('noResults', 'No images found.')) + '</div>');
					}
					store.reachedEnd = true;
					return;
				}

				renderImages($results, images);
				store.nextPage();
			}).fail(function () {
				showNotice($ui, t('error', 'Something went wrong.'), 'error');
			}).always(function () {
				store.loading = false;
				$loader.hide();
			});
		}

		$ui.on('click', '.fsi-source-btn', function () {
			const sourceKey = $(this).data('source');
			const source = sources[sourceKey] || {};
			if (!source.enabled) {
				showNotice($ui, t('needsKey', 'API key is required for this source.'), 'warning');
				return;
			}

			setActiveSource(sourceKey);
			$results.empty();
			store.resetPagination();
			if (store.query) {
				runSearch();
			}
		});

		$ui.on('click', '.fsi-search-btn', function () {
			store.query = String($searchInput.val() || '').trim();
			$results.empty();
			store.resetPagination();
			if (store.query) {
				runSearch();
			}
		});

		$searchInput.on('keypress', function (event) {
			if (event.which === 13) {
				event.preventDefault();
				$ui.find('.fsi-search-btn').trigger('click');
			}
		});

		$ui.find('.fsi-content').on('scroll', function () {
			const bottom = this.scrollTop + this.clientHeight >= this.scrollHeight - 250;
			if (bottom && !store.loading && !store.reachedEnd && store.query) {
				runSearch();
			}
		});

		$ui.on('click', '.fsi-item', function () {
			const $item = $(this);
			const image = $item.data('fsiImage') || {};
			if (!image.full) {
				return;
			}

			$item.addClass('is-loading');
			api.importImage(image).done(function (res) {
				if (!res || !res.success) {
					const message = res && res.data && res.data.message ? res.data.message : t('error', 'Something went wrong.');
					showNotice($ui, message, 'error');
					$item.removeClass('is-loading').addClass('is-error');
					return;
				}

				$item.removeClass('is-loading').addClass('is-done');
				if (autoInsert && res.data && res.data.attachment_id) {
					autoInsertIntoFrame(frameResolver(), res.data.attachment_id, mediaApi);
					showNotice($ui, t('inserted', 'Inserted'), 'success');
				} else {
					showNotice($ui, t('imported', 'Imported'), 'success');
				}
			}).fail(function () {
				$item.removeClass('is-loading').addClass('is-error');
				showNotice($ui, t('error', 'Something went wrong.'), 'error');
			});
		});

		setActiveSource(store.source);
	}

	function mountStandalone() {
		const $target = $('#fsi-standalone-app .fsi-ui-root');
		if (!$target.length || $target.data('fsiMounted')) {
			return;
		}

		const $ui = createRootUI();
		$target.append($ui);
		$target.data('fsiMounted', true);
		attachUIHandlers($ui, { autoInsert: false });
	}

	function patchMediaFrame(FrameClass, runtime) {
		if (!FrameClass || FrameClass.prototype.fsiEnhanced || !runtime.backbone) {
			return;
		}

		return FrameClass.extend({
			fsiEnhanced: true,
			browseRouter: function (routerView) {
				if (FrameClass.prototype.browseRouter) {
					FrameClass.prototype.browseRouter.apply(this, arguments);
				}
				routerView.set({
					fsi: {
						text: t('title', 'Free Stock Images'),
						priority: 80
					}
				});
			},
			bindHandlers: function () {
				if (FrameClass.prototype.bindHandlers) {
					FrameClass.prototype.bindHandlers.apply(this, arguments);
				}
				this.on('content:create:fsi', this.createFsiContent, this);
			},
			createFsiContent: function (region) {
				const view = new runtime.backbone.View();
				view.setElement($('<div class="fsi-tab"><div class="fsi-ui-root"></div></div>'));
				region.view = view;

				const $root = view.$el.find('.fsi-ui-root');
				if (!$root.data('fsiMounted')) {
					$root.data('fsiMounted', true);
					const $ui = createRootUI();
					$root.append($ui);
					attachUIHandlers($ui, {
						autoInsert: true,
						mediaApi: runtime.media,
						frameResolver: function () { return runtime.media.frame || null; }
					});
				}
			}
		});
	}

	function integrateMediaFrame(runtime) {
		if (!runtime.media || !runtime.media.view || !runtime.media.view.MediaFrame) {
			debugLog('wp.media frame is not available on this screen');
			return false;
		}

		const MediaFrame = runtime.media.view.MediaFrame;
		let patched = false;
		if (MediaFrame.Post) {
			debugLog('Patching MediaFrame.Post');
			MediaFrame.Post = patchMediaFrame(MediaFrame.Post, runtime) || MediaFrame.Post;
			patched = true;
		}
		if (MediaFrame.Select) {
			debugLog('Patching MediaFrame.Select');
			MediaFrame.Select = patchMediaFrame(MediaFrame.Select, runtime) || MediaFrame.Select;
			patched = true;
		}
		return patched;
	}

	function setupDomFallback(runtime) {
		const $runtime = runtime.$;
		const runtimeDoc = runtime.win.document;
		const fallbackClass = 'fsi-router-tab';
		const fallbackPanelClass = 'fsi-tab-panel';

		function mountFallbackPanel($panel) {
			const $root = $panel.find('.fsi-ui-root');
			if ($root.data('fsiMounted')) {
				return;
			}
			$root.data('fsiMounted', true);
			const $ui = createRootUI();
			$root.append($ui);
			attachUIHandlers($ui, {
				autoInsert: true,
				mediaApi: runtime.media,
				frameResolver: function () {
					return runtime.media && runtime.media.frame ? runtime.media.frame : null;
				}
			});
		}

		function ensureFallbackTab() {
			const $router = $runtime('.media-frame-router');
			const $content = $runtime('.media-frame-content');
			if (!$router.length || !$content.length) {
				return;
			}

			if (!$router.find('.' + fallbackClass).length) {
				$router.append(
					'<a href="#" class="media-menu-item ' + fallbackClass + '" data-content="fsi">' +
					escapeHtml(t('title', 'Free Stock Images')) +
					'</a>'
				);
				debugLog('DOM fallback tab injected');
			}

			let $panel = $content.find('.' + fallbackPanelClass);
			if (!$panel.length) {
				$panel = $runtime('<div class="' + fallbackPanelClass + '" style="display:none;"><div class="fsi-ui-root"></div></div>');
				$content.append($panel);
				debugLog('DOM fallback panel injected');
			}
			mountFallbackPanel($panel);
		}

		$runtime(runtimeDoc).off('click.fsiRouterTab').on('click.fsiRouterTab', '.' + fallbackClass, function (event) {
			event.preventDefault();
			const $content = $runtime('.media-frame-content');
			const $panel = $content.find('.' + fallbackPanelClass);

			$runtime('.media-frame-router .media-menu-item, .media-frame-router .media-router a').removeClass('active');
			$runtime(this).addClass('active');

			$content.children().hide();
			$panel.show();
			mountFallbackPanel($panel);
		});

		ensureFallbackTab();

		if (runtime.win.MutationObserver) {
			const observer = new runtime.win.MutationObserver(function () {
				ensureFallbackTab();
			});
			observer.observe(runtimeDoc.body, { childList: true, subtree: true });
		}

		runtime.win.setTimeout(ensureFallbackTab, 300);
		runtime.win.setTimeout(ensureFallbackTab, 1000);
	}

	if (!ajaxUrl || !nonce) {
		if (typeof console !== 'undefined' && console.warn) {
			console.warn('[FSI] Missing critical localized config: ajaxUrl or nonce');
		}
		return;
	}

	const runtime = resolveMediaRuntime();
	mountStandalone();
	integrateMediaFrame(runtime);
	setupDomFallback(runtime);
});
