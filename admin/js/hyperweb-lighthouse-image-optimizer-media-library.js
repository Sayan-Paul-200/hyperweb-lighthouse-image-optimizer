(function (window, document, wp) {
	'use strict';

	var bootstrapName = 'hwlioMediaLibraryConfig';
	var defaults = {
		selectors: {
			noticeContainerId: 'hwlio-media-notices',
			politeRegionId: 'hwlio-media-live-polite',
			assertiveRegionId: 'hwlio-media-live-assertive'
		},
		labels: {
			states: {
				unprocessed: 'Unprocessed',
				queued: 'Queued',
				processing: 'Processing',
				partial: 'Partially optimized',
				optimized: 'Optimized',
				failed: 'Failed',
				stale: 'Stale',
				skipped: 'Skipped',
				excluded: 'Excluded'
			},
			actions: {
				optimize: 'Optimize Now',
				retry: 'Retry',
				reoptimize: 'Re-optimize',
				reconcile: 'Reconcile Files',
				exclude: 'Exclude from Optimization',
				include: 'Include in Optimization',
				'view-details': 'View Details'
			}
		},
		strings: {
			bootstrapError: 'The Media Library controls could not initialize on this screen.',
			requestError: 'A Media Library request failed before it could complete.',
			detailsLoading: 'Loading attachment details...',
			detailsEmpty: 'No attachment optimization details are available yet.',
			reoptimizeConfirm: 'Re-optimize this attachment using the current quality settings?',
			exclusionNotice: 'Exclusion prevents future queueing but does not cancel work that is already queued.',
			queuedNotice: 'Attachment work has been queued.',
			includeNotice: 'Attachment included in optimization.',
			excludeNotice: 'Attachment excluded from future optimization queueing.'
		},
		settings: {
			allowAttachmentExclusion: true
		},
		polling: {
			activeMs: 5000
		}
	};
	var pollers = {};
	var apiConfigured = false;

	function mergedConfig() {
		var config = window[bootstrapName] || {};

		return {
			rest: config.rest || {},
			version: config.version || '',
			settings: config.settings || defaults.settings,
			polling: config.polling || defaults.polling,
			labels: config.labels || defaults.labels,
			strings: config.strings || defaults.strings,
			selectors: config.selectors || defaults.selectors
		};
	}

	function find(selector, root) {
		return (root || document).querySelector(selector);
	}

	function findAll(selector, root) {
		return Array.prototype.slice.call((root || document).querySelectorAll(selector));
	}

	function ensureRegions(config) {
		var selectors = config.selectors;
		var root = find('.wrap') || document.body;
		var notices = document.getElementById(selectors.noticeContainerId);
		var polite = document.getElementById(selectors.politeRegionId);
		var assertive = document.getElementById(selectors.assertiveRegionId);

		if (!notices) {
			notices = document.createElement('div');
			notices.id = selectors.noticeContainerId;
			notices.className = 'hwlio-media-notices';
			root.insertBefore(notices, root.firstChild);
		}

		if (!polite) {
			polite = document.createElement('div');
			polite.id = selectors.politeRegionId;
			polite.className = 'hwlio-media-live-region';
			polite.setAttribute('aria-live', 'polite');
			polite.setAttribute('aria-atomic', 'true');
			document.body.appendChild(polite);
		}

		if (!assertive) {
			assertive = document.createElement('div');
			assertive.id = selectors.assertiveRegionId;
			assertive.className = 'hwlio-media-live-region';
			assertive.setAttribute('aria-live', 'assertive');
			assertive.setAttribute('aria-atomic', 'true');
			document.body.appendChild(assertive);
		}

		return {
			notices: notices,
			polite: polite,
			assertive: assertive
		};
	}

	function announce(config, message, priority) {
		var regions = ensureRegions(config);
		var target = 'assertive' === priority ? regions.assertive : regions.polite;

		target.textContent = '';
		target.textContent = message;
	}

	function showNotice(config, level, message) {
		var regions = ensureRegions(config);
		var notice = document.createElement('div');

		notice.className = 'hwlio-media-notice hwlio-media-notice--' + level;
		notice.setAttribute('role', 'alert');
		notice.textContent = message;
		regions.notices.appendChild(notice);
	}

	function reportError(config, error, fallback) {
		var message = fallback || config.strings.requestError;

		if (error && 'string' === typeof error.message && '' !== error.message) {
			message = error.message;
		}

		showNotice(config, 'error', message);
		announce(config, message, 'assertive');
	}

	function restUrl(config, path) {
		var root = config && config.rest && config.rest.root ? String(config.rest.root) : '';
		var route = 'string' === typeof path ? path : '';

		if (!root || !route) {
			return route;
		}

		return root.replace(/\/+$/, '') + '/' + route.replace(/^\/+/, '');
	}

	function normalizeRequestOptions(config, options) {
		var normalized = {};
		var key;

		options = options || {};

		for (key in options) {
			if (Object.prototype.hasOwnProperty.call(options, key)) {
				normalized[key] = options[key];
			}
		}

		if (normalized.path && !normalized.url) {
			normalized.url = restUrl(config, normalized.path);
			delete normalized.path;
		}

		return normalized;
	}

	function configureApiFetch(config) {
		var apiFetch;

		if (apiConfigured) {
			return wp.apiFetch;
		}

		if (!wp || !wp.apiFetch || 'function' !== typeof wp.apiFetch.use) {
			throw new Error(config.strings.bootstrapError);
		}

		if (!config.rest.root || !config.rest.nonce) {
			throw new Error(config.strings.bootstrapError);
		}

		apiFetch = wp.apiFetch;

		if ('function' === typeof apiFetch.createNonceMiddleware) {
			apiFetch.use(apiFetch.createNonceMiddleware(config.rest.nonce));
		}

		apiConfigured = true;

		return apiFetch;
	}

	function request(config, options) {
		return configureApiFetch(config)(normalizeRequestOptions(config, options)).catch(function (error) {
			reportError(config, error, config.strings.requestError);
			throw error;
		});
	}

	function attachmentPath(id, action) {
		var path = '/attachments/' + String(id);

		if (action) {
			path += '/' + action;
		}

		return path;
	}

	function uniqueActions(actions) {
		var seen = {};

		return (actions || []).filter(function (action) {
			if (!action || seen[action]) {
				return false;
			}

			seen[action] = true;

			return true;
		});
	}

	function actionsForState(summary, config) {
		var state = summary.state;
		var allowExclusion = !!config.settings.allowAttachmentExclusion;
		var actions = [];

		if ('excluded' === state) {
			if (allowExclusion) {
				actions.push('include');
			}

			actions.push('view-details');
			return uniqueActions(actions);
		}

		if ('unprocessed' === state) {
			actions.push('optimize');
		}

		if (-1 !== ['failed', 'partial', 'stale'].indexOf(state)) {
			actions.push('retry', 'reoptimize', 'reconcile');
		}

		if (-1 !== ['optimized', 'skipped'].indexOf(state)) {
			actions.push('reoptimize', 'reconcile');
		}

		if (allowExclusion) {
			actions.push('exclude');
		}

		actions.push('view-details');

		return uniqueActions(actions);
	}

	function decorateSummary(raw, config) {
		var summary = raw || {};
		var id = parseInt(summary.attachmentId || summary.attachment_id || 0, 10);
		var state = summary.state || 'unprocessed';
		var readyFormats = Array.isArray(summary.readyFormats) ? summary.readyFormats : [];
		var statusLabel = summary.statusLabel || (config.labels.states[state] || config.labels.states.unprocessed);
		var active = 'boolean' === typeof summary.active ? summary.active : -1 !== ['queued', 'processing', 'stale'].indexOf(state);
		var allowedActions = Array.isArray(summary.allowedActions) ? summary.allowedActions : actionsForState({ state: state }, config);

		return {
			attachmentId: id,
			state: state,
			statusLabel: statusLabel,
			readyFormats: readyFormats,
			excluded: !!summary.excluded,
			active: active,
			allowedActions: allowedActions,
			routes: summary.routes || {
				details: attachmentPath(id),
				optimize: attachmentPath(id, 'optimize'),
				retry: attachmentPath(id, 'retry'),
				reconcile: attachmentPath(id, 'reconcile'),
				exclude: attachmentPath(id, 'exclude'),
				include: attachmentPath(id, 'include')
			}
		};
	}

	function summaryFromSnapshot(snapshot, config) {
		var status = snapshot && snapshot.status ? snapshot.status : {};

		return decorateSummary({
			attachmentId: snapshot.attachment_id || snapshot.attachmentId || 0,
			state: status.state || 'unprocessed',
			readyFormats: Array.isArray(status.formats) ? status.formats : [],
			excluded: !!status.excluded
		}, config);
	}

	function actionMarkup(summary, action, config) {
		var label = config.labels.actions[action] || config.labels.actions['view-details'];
		var force = 'reoptimize' === action ? ' data-force="1"' : '';

		return '<a href="#" class="hwlio-media-action" data-hwlio-action="' + action + '" data-attachment-id="' + summary.attachmentId + '"' + force + '>' + label + '</a>';
	}

	function statusMarkup(summary) {
		var chips = (summary.readyFormats || []).map(function (format) {
			return '<span class="hwlio-media-chip">' + String(format || '').toUpperCase() + '</span>';
		}).join('');

		return '<span class="hwlio-media-badge hwlio-media-badge--' + summary.state + '">' + summary.statusLabel + '</span>' + chips;
	}

	function actionsMarkup(summary, config) {
		return (summary.allowedActions || []).map(function (action) {
			return actionMarkup(summary, action, config);
		}).join('');
	}

	function updateSummaryContainers(id, summary, config) {
		findAll('[data-hwlio-summary][data-attachment-id="' + id + '"]').forEach(function (container) {
			var status = find('.hwlio-media-summary__status', container);
			var actions = find('.hwlio-media-summary__actions', container);

			container.setAttribute('data-state', summary.state);
			container.setAttribute('data-active', summary.active ? '1' : '0');

			if (status) {
				status.innerHTML = statusMarkup(summary);
			}

			if (actions) {
				actions.innerHTML = actionsMarkup(summary, config);
			}
		});
	}

	function updateTile(id, summary) {
		findAll('.attachment[data-id="' + id + '"]').forEach(function (tile) {
			var badge = find('.hwlio-media-tile-badge', tile);

			if (!badge) {
				badge = document.createElement('div');
				badge.className = 'hwlio-media-tile-badge';
				tile.appendChild(badge);
			}

			badge.innerHTML = '<span class="hwlio-media-badge hwlio-media-badge--' + summary.state + '">' + summary.statusLabel + '</span>';
		});
	}

	function setModelSummary(summary) {
		if (!wp || !wp.media || !wp.media.model || !wp.media.model.Attachment || !summary.attachmentId) {
			return;
		}

		try {
			wp.media.model.Attachment.get(summary.attachmentId).set('hwlio', summary);
		} catch (error) {
			window.console && window.console.warn && window.console.warn(error);
		}
	}

	function openDetailsContainer(id) {
		var container = find('[data-hwlio-summary][data-attachment-id="' + id + '"] .hwlio-media-summary__details');

		if (!container) {
			return null;
		}

		container.hidden = false;

		return container;
	}

	function detailsMarkup(snapshot, config) {
		var manifest = snapshot && snapshot.manifest ? snapshot.manifest : {};
		var sizes = manifest.sizes || {};
		var keys = Object.keys(sizes);
		var sections = [];

		sections.push('<p><strong>' + (config.labels.states[snapshot.status.state] || snapshot.status.state) + '</strong></p>');

		if (0 === keys.length) {
			sections.push('<p>' + config.strings.detailsEmpty + '</p>');
			return sections.join('');
		}

		keys.forEach(function (sizeName) {
			var size = sizes[sizeName] || {};
			var formats = size.formats || {};
			var items = Object.keys(formats).map(function (format) {
				var item = formats[format] || {};
				var text = String(format || '').toUpperCase() + ': ' + String(item.status || 'unknown');

				if ('number' === typeof item.savings_percent) {
					text += ' - ' + item.savings_percent + '% smaller';
				}

				return '<li>' + text + '</li>';
			}).join('');

			sections.push('<div class="hwlio-media-detail-size"><strong>' + sizeName + '</strong><ul class="hwlio-media-detail-list">' + items + '</ul></div>');
		});

		return sections.join('');
	}

	function refreshDetails(id, snapshot, config) {
		findAll('[data-hwlio-summary][data-attachment-id="' + id + '"] .hwlio-media-summary__details').forEach(function (container) {
			if (!container.hidden) {
				container.innerHTML = detailsMarkup(snapshot, config);
			}
		});
	}

	function updateAttachment(id, snapshot, config) {
		var summary = summaryFromSnapshot(snapshot, config);

		updateSummaryContainers(id, summary, config);
		updateTile(id, summary);
		refreshDetails(id, snapshot, config);
		setModelSummary(summary);
		schedulePoll(id, summary, config);

		return summary;
	}

	function clearPoll(id) {
		if (pollers[id]) {
			window.clearTimeout(pollers[id]);
			delete pollers[id];
		}
	}

	function schedulePoll(id, summary, config) {
		clearPoll(id);

		if (!summary.active) {
			return;
		}

		pollers[id] = window.setTimeout(function () {
			loadSnapshot(id, config).then(function (snapshot) {
				updateAttachment(id, snapshot, config);
			}).catch(function () {
				clearPoll(id);
			});
		}, config.polling.activeMs || defaults.polling.activeMs);
	}

	function loadSnapshot(id, config) {
		return request(config, {
			path: attachmentPath(id),
			method: 'GET'
		});
	}

	function busyState(id, busy) {
		findAll('.hwlio-media-action[data-attachment-id="' + id + '"]').forEach(function (action) {
			if (busy) {
				action.classList.add('is-busy');
				action.setAttribute('aria-disabled', 'true');
				return;
			}

			action.classList.remove('is-busy');
			action.removeAttribute('aria-disabled');
		});
	}

	function actionRequest(id, action, config, force) {
		var path = attachmentPath(id, action);
		var data = null;

		if ('reoptimize' === action || 'optimize' === action) {
			path = attachmentPath(id, 'optimize');
			data = {
				force: !!force
			};
		}

		return request(config, {
			path: path,
			method: 'POST',
			data: data
		});
	}

	function actionMessage(action, config) {
		if ('include' === action) {
			return config.strings.includeNotice;
		}

		if ('exclude' === action) {
			return config.strings.excludeNotice + ' ' + config.strings.exclusionNotice;
		}

		return config.strings.queuedNotice;
	}

	function onActionClick(event, config) {
		var target = event.target.closest('.hwlio-media-action');
		var action;
		var id;
		var details;

		if (!target) {
			return;
		}

		event.preventDefault();

		action = target.getAttribute('data-hwlio-action') || '';
		id = parseInt(target.getAttribute('data-attachment-id') || '0', 10);

		if (!id || !action) {
			return;
		}

		if ('view-details' === action) {
			details = openDetailsContainer(id);

			if (!details) {
				return;
			}

			if (details.getAttribute('data-loaded') === '1') {
				details.hidden = !details.hidden;
				return;
			}

			details.hidden = false;
			details.innerHTML = '<p>' + config.strings.detailsLoading + '</p>';

			loadSnapshot(id, config).then(function (snapshot) {
				details.innerHTML = detailsMarkup(snapshot, config);
				details.setAttribute('data-loaded', '1');
				updateAttachment(id, snapshot, config);
			});

			return;
		}

		if ('reoptimize' === action && !window.confirm(config.strings.reoptimizeConfirm)) {
			return;
		}

		busyState(id, true);

		actionRequest(id, action, config, '1' === target.getAttribute('data-force')).then(function (response) {
			var snapshot = response && response.snapshot ? response.snapshot : null;

			if (snapshot) {
				updateAttachment(id, snapshot, config);
			}

			showNotice(config, 'success', actionMessage(action, config));
			announce(config, actionMessage(action, config), 'polite');
		}, function () {
			return null;
		}).then(function () {
			busyState(id, false);
		});
	}

	function primeSummaries(config) {
		findAll('[data-hwlio-summary][data-attachment-id]').forEach(function (container) {
			var summary = decorateSummary({
				attachmentId: parseInt(container.getAttribute('data-attachment-id') || '0', 10),
				state: container.getAttribute('data-state') || 'unprocessed',
				active: '1' === container.getAttribute('data-active'),
				allowedActions: []
			}, config);

			summary.allowedActions = actionsForState(summary, config);
			schedulePoll(summary.attachmentId, summary, config);
		});
	}

	function primeTiles(config) {
		findAll('.attachment[data-id]').forEach(function (tile) {
			var id = parseInt(tile.getAttribute('data-id') || '0', 10);
			var model;
			var summary;

			if (!id || !wp || !wp.media || !wp.media.model || !wp.media.model.Attachment) {
				return;
			}

			try {
				model = wp.media.model.Attachment.get(id);
				summary = model ? model.get('hwlio') : null;
			} catch (error) {
				summary = null;
			}

			if (!summary) {
				return;
			}

			summary = decorateSummary(summary, config);
			updateTile(id, summary);
			schedulePoll(id, summary, config);
		});
	}

	function observeTiles(config) {
		if (!window.MutationObserver) {
			return;
		}

		(new MutationObserver(function () {
			primeTiles(config);
		})).observe(document.body, {
			childList: true,
			subtree: true
		});
	}

	function ready(callback) {
		if ('loading' === document.readyState) {
			document.addEventListener('DOMContentLoaded', callback);
			return;
		}

		callback();
	}

	ready(function () {
		var config;

		if (!window[bootstrapName]) {
			return;
		}

		config = mergedConfig();

		try {
			configureApiFetch(config);
		} catch (error) {
			reportError(config, error, config.strings.bootstrapError);
			return;
		}

		document.addEventListener('click', function (event) {
			onActionClick(event, config);
		});

		primeSummaries(config);
		primeTiles(config);
		observeTiles(config);
	});
}(window, document, window.wp || {}));
