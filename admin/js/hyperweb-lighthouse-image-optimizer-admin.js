(function (window, document, wp) {
	'use strict';

	var bootstrapName = 'hwlioAdminConfig';
	var globalClientName = 'hwlioAdmin';
	var defaults = {
		bulk: {
			jobsScanRoute: '/jobs/scan',
			jobsQueueRoute: '/jobs/queue',
			jobsRetryRoute: '/jobs/retry',
			jobsPauseRoute: '/jobs/pause',
			jobsResumeRoute: '/jobs/resume',
			jobsPendingRoute: '/jobs/pending',
			attachmentsRoute: '/attachments',
			scanIntervalMs: 350,
			queueIntervalMs: 500,
			previewPageSize: 20,
			storageKey: 'hwlioBulkScanToken',
			queueModeKey: 'hwlioBulkQueueMode'
		},
		diagnostics: {
			route: '/diagnostics'
		},
		logs: {
			route: '/logs',
			retentionRoute: '/logs/retention',
			defaultPerPage: 20,
			maxPerPage: 100,
			deleteIntervalMs: 250
		},
		selectors: {
			app: '#hwlio-admin-app',
			notices: '#hwlio-admin-notices',
			polite: '#hwlio-admin-live-polite',
			assertive: '#hwlio-admin-live-assertive'
		},
		strings: {
			bootstrapError: 'The admin client could not initialize on this screen.',
			missingMount: 'The admin client mount point is missing from this screen.',
			missingApiFetch: 'The WordPress REST client is unavailable on this screen.',
			requestError: 'A plugin request failed before it could complete.',
			noticeError: 'An unexpected admin error occurred.',
			dashboardLoadError: 'The dashboard data could not be loaded.',
			dashboardUpdated: 'Dashboard statistics have been refreshed.',
			recalculateQueued: 'Statistics recalculation was queued successfully.',
			recalculatePending: 'A statistics recalculation request is already pending.',
			recalculateBusy: 'Statistics recalculation is currently running in the background.',
			recalculateAction: 'Recalculate Statistics',
			recalculateWorking: 'Recalculating...',
			cachePending: 'Statistics recalculation is pending.',
			cacheReady: 'Statistics cache last updated:',
			cacheUnknown: 'Statistics cache has not been generated yet.',
			queueAvailable: 'Available',
			queueUnavailable: 'Unavailable',
			noneDetected: 'None detected.',
			noFailures: 'No recent warning or error entries were found.',
			noConflicts: 'No conservative conflict warnings are active right now.',
			unsupported: 'Unsupported',
			notReady: 'Not ready',
			bulkStart: 'Run Dry-Run Scan',
			bulkScanning: 'Dry-run scan in progress.',
			bulkCompleted: 'Dry-run scan completed.',
			bulkResumed: 'Resumed the latest bulk dry-run session for this browser tab.',
			bulkEmpty: 'No eligible candidates were found for the current dry-run filters.',
			bulkPreviewEmpty: 'No preview items are available for this page.',
			bulkPreviewError: 'The bulk candidate preview could not be loaded.',
			bulkScanError: 'The dry-run scan could not be completed.',
			bulkExcludedSkipped: 'Excluded attachments are skipped during bulk dry-run scans in this subphase.',
			bulkDeferredQueue: 'Bulk queue controls operate only on completed dry-run scan sessions.',
			bulkPageLabel: 'Preview page',
			bulkPrevious: 'Previous',
			bulkNext: 'Next',
			bulkQueueAction: 'Queue Current Scan Results',
			bulkRetryAction: 'Retry Failed Current Scan Results',
			bulkPauseAction: 'Pause Queue',
			bulkResumeAction: 'Resume Queue',
			bulkCancelAction: 'Cancel Pending Jobs',
			bulkQueueRunning: 'Bulk queueing is in progress.',
			bulkRetryRunning: 'Bulk retry queueing is in progress.',
			bulkQueueComplete: 'Bulk queueing is complete.',
			bulkRetryComplete: 'Bulk retry queueing is complete.',
			bulkQueuePaused: 'Attachment processing is paused. Queue continuation will wait until resumed.',
			bulkPauseBeforeCancel: 'Pause processing before canceling pending jobs so currently running work can finish safely.',
			bulkPauseSuccess: 'Attachment processing is now paused.',
			bulkResumeSuccess: 'Attachment processing has resumed.',
			bulkCancelSuccess: 'Pending plugin-owned attachment jobs were canceled.',
			bulkCancelError: 'Pending attachment jobs could not be canceled cleanly.',
			bulkQueueError: 'The bulk queue request could not be completed.',
			bulkRetryError: 'The bulk retry request could not be completed.',
			bulkControlPending: 'Pending jobs',
			bulkControlRunning: 'Running jobs',
			diagnosticsLoadError: 'Diagnostics could not be loaded right now.',
			diagnosticsRefreshAction: 'Refresh Diagnostics',
			diagnosticsRefreshing: 'Refreshing diagnostics...',
			diagnosticsCopied: 'Diagnostic code copied.',
			diagnosticsNoResults: 'No structured diagnostic results are available right now.',
			diagnosticsGroupPass: 'Passing Checks',
			diagnosticsGroupWarning: 'Warnings',
			diagnosticsGroupFail: 'Failures',
			diagnosticsGroupInfo: 'Informational Checks',
			detailsLabel: 'Details',
			copyCodeAction: 'Copy Code',
			copyCodeFallback: 'The code could not be copied automatically. You can still copy it manually.',
			logsLoadError: 'Logs could not be loaded right now.',
			logsEmpty: 'No log rows match the current filters.',
			logsPageLabel: 'Log page',
			logsRefreshAction: 'Refresh Logs',
			logsSaveRetentionAction: 'Save Retention',
			logsClearAction: 'Clear All Logs',
			logsRetentionSaved: 'Log retention days were saved.',
			logsRetentionSaving: 'Saving retention...',
			logsClearConfirm: 'Clear all plugin-owned log rows now? This runs in bounded batches and cannot be undone.',
			logsDeleting: 'Deleting logs in bounded batches...',
			logsDeleted: 'Plugin logs were cleared.',
			logsDeleteProgress: 'Deleted log rows so far:',
			logsDeleteError: 'Plugin logs could not be deleted right now.'
		}
	};

	function element(tag, className, text) {
		var node = document.createElement(tag);

		if (className) {
			node.className = className;
		}

		if ('string' === typeof text) {
			node.textContent = text;
		}

		return node;
	}

	function findElement(selector) {
		return selector ? document.querySelector(selector) : null;
	}

	function ensureNoticeContainer(selectors) {
		var container = findElement(selectors.notices);

		if (container) {
			return container;
		}

		var wrap = document.querySelector('.wrap');

		if (!wrap) {
			return null;
		}

		container = document.createElement('div');
		container.id = selectors.notices.replace(/^#/, '');
		container.className = 'hwlio-admin-notices';
		wrap.insertBefore(container, wrap.firstChild ? wrap.firstChild.nextSibling : null);

		return container;
	}

	function announce(selectors, message, priority) {
		var selector = 'assertive' === priority ? selectors.assertive : selectors.polite;
		var region = findElement(selector);

		if (!region) {
			return;
		}

		region.textContent = '';
		region.textContent = message;
	}

	function showNotice(selectors, level, message) {
		var container = ensureNoticeContainer(selectors);
		var notice;

		if (!container) {
			return;
		}

		notice = document.createElement('div');
		notice.className = 'hwlio-admin-notice hwlio-admin-notice--' + level;
		notice.setAttribute('role', 'alert');
		notice.textContent = message;
		container.appendChild(notice);
	}

	function normalizeError(config, error, fallback) {
		if (error && 'string' === typeof error.message && '' !== error.message) {
			return error.message;
		}

		if ('string' === typeof fallback && '' !== fallback) {
			return fallback;
		}

		return config.strings.noticeError;
	}

	function reportError(config, error, fallback) {
		var message = normalizeError(config, error, fallback);
		showNotice(config.selectors, 'error', message);
		announce(config.selectors, message, 'assertive');

		if (window.console && 'function' === typeof window.console.error) {
			window.console.error(error || message);
		}
	}

	function sleep(delay) {
		return new window.Promise(function (resolve) {
			window.setTimeout(resolve, delay);
		});
	}

	function setButtonBusy(button, isBusy, idleText, busyText) {
		if (!button) {
			return;
		}

		button.disabled = !!isBusy;
		button.textContent = isBusy ? busyText : idleText;
	}

	function copyText(client, config, text, successMessage) {
		function fallbackCopy() {
			var input = document.createElement('textarea');

			input.value = text;
			input.setAttribute('readonly', 'readonly');
			input.style.position = 'absolute';
			input.style.left = '-9999px';
			document.body.appendChild(input);
			input.select();

			try {
				document.execCommand('copy');
				document.body.removeChild(input);
				client.showNotice('success', successMessage || config.strings.diagnosticsCopied);
				client.announce(successMessage || config.strings.diagnosticsCopied, 'polite');
				return window.Promise.resolve();
			} catch (error) {
				document.body.removeChild(input);
				client.showNotice('warning', config.strings.copyCodeFallback);
				client.announce(config.strings.copyCodeFallback, 'assertive');
				return window.Promise.resolve(error);
			}
		}

		if (window.navigator && window.navigator.clipboard && 'function' === typeof window.navigator.clipboard.writeText) {
			return window.navigator.clipboard.writeText(text).then(function () {
				client.showNotice('success', successMessage || config.strings.diagnosticsCopied);
				client.announce(successMessage || config.strings.diagnosticsCopied, 'polite');
			}).catch(function () {
				return fallbackCopy();
			});
		}

		return fallbackCopy();
	}

	function requestWrapper(apiFetch, config) {
		return function (options) {
			return apiFetch(options).catch(function (error) {
				if (!options || true !== options.suppressNotices) {
					reportError(config, error, config.strings.requestError);
				}

				throw error;
			});
		};
	}

	function stateBadge(value, strings) {
		var text = String(value || '');
		var className = 'hwlio-dashboard__badge';
		var normalized = text.toLowerCase();

		if (
			'available' === normalized ||
			'ready' === normalized ||
			'optimized' === normalized ||
			'supported' === normalized ||
			'pass' === normalized
		) {
			className += ' hwlio-dashboard__badge--success';
		} else if (
			'unavailable' === normalized ||
			'error' === normalized ||
			'failed' === normalized ||
			'missing' === normalized ||
			'unsupported' === normalized ||
			'fail' === normalized
		) {
			className += ' hwlio-dashboard__badge--error';
		} else {
			className += ' hwlio-dashboard__badge--warning';
		}

		return element('span', className, text || strings.notReady);
	}

	function clearNode(node) {
		while (node && node.firstChild) {
			node.removeChild(node.firstChild);
		}
	}

	function appendMetaRow(container, label, valueNode) {
		var row = element('div', 'hwlio-dashboard__meta-row');
		var labelNode = element('span', 'hwlio-dashboard__meta-label', label);
		var valueWrap = element('span', 'hwlio-dashboard__meta-value');

		if (valueNode instanceof window.Node) {
			valueWrap.appendChild(valueNode);
		} else {
			valueWrap.textContent = String(valueNode || '');
		}

		row.appendChild(labelNode);
		row.appendChild(valueWrap);
		container.appendChild(row);
	}

	function formatBytes(bytes) {
		var value = Number(bytes || 0);
		var units = ['B', 'KB', 'MB', 'GB', 'TB'];
		var unit = 0;

		while (value >= 1024 && unit < units.length - 1) {
			value = value / 1024;
			unit += 1;
		}

		return (0 === unit ? Math.round(value) : value.toFixed(1)) + ' ' + units[unit];
	}

	function formatPercent(value) {
		var numeric = Number(value || 0);

		return numeric.toFixed(2).replace(/\.00$/, '') + '%';
	}

	function formatTimestamp(value) {
		if (!value) {
			return '';
		}

		return String(value).replace(' ', ' UTC ');
	}

	function dashboardElements(app) {
		return {
			root: app.querySelector('[data-hwlio-dashboard="root"]'),
			button: app.querySelector('[data-hwlio-dashboard-action="recalculate"]'),
			refresh: app.querySelector('[data-hwlio-dashboard-refresh-state]'),
			environment: app.querySelector('[data-hwlio-dashboard-body="environment"]'),
			queue: app.querySelector('[data-hwlio-dashboard-body="queue"]'),
			savings: app.querySelector('[data-hwlio-dashboard-body="savings"]'),
			failures: app.querySelector('[data-hwlio-dashboard-body="failures"]'),
			conflicts: app.querySelector('[data-hwlio-dashboard-body="conflicts"]')
		};
	}

	function diagnosticsElements(app) {
		return {
			root: app.querySelector('[data-hwlio-diagnostics="root"]'),
			button: app.querySelector('[data-hwlio-diagnostics-action="refresh"]'),
			groups: app.querySelector('[data-hwlio-diagnostics-groups]'),
			summaryRoot: app.querySelector('[data-hwlio-diagnostics-summary]')
		};
	}

	function diagnosticsSummaryValue(root, key) {
		return root ? root.querySelector('[data-hwlio-diagnostics-summary-value="' + key + '"]') : null;
	}

	function renderDiagnosticsSummary(elements, summary) {
		['total', 'pass', 'warning', 'fail', 'info'].forEach(function (key) {
			var node = diagnosticsSummaryValue(elements.summaryRoot, key);

			if (node) {
				node.textContent = String(summary && summary[key] ? summary[key] : 0);
			}
		});
	}

	function diagnosticsGroupLabel(status, strings) {
		if ('pass' === status) {
			return strings.diagnosticsGroupPass;
		}

		if ('warning' === status) {
			return strings.diagnosticsGroupWarning;
		}

		if ('fail' === status) {
			return strings.diagnosticsGroupFail;
		}

		return strings.diagnosticsGroupInfo;
	}

	function renderDiagnosticDetails(details) {
		var pre = element('pre', 'hwlio-diagnostics__details');
		var encoded = '{}';

		try {
			encoded = JSON.stringify(details || {}, null, 2) || '{}';
		} catch (error) {
			encoded = '{}';
		}

		pre.textContent = encoded;
		return pre;
	}

	function renderDiagnosticsGroups(elements, payload, client, config) {
		var groups = {
			fail: [],
			warning: [],
			pass: [],
			info: []
		};
		var order = ['fail', 'warning', 'pass', 'info'];

		clearNode(elements.groups);

		(payload.results || []).forEach(function (result) {
			var status = result && result.status ? String(result.status) : 'info';

			if (!groups[status]) {
				groups[status] = [];
			}

			groups[status].push(result);
		});

		if (!payload.results || !payload.results.length) {
			elements.groups.appendChild(element('p', 'hwlio-diagnostics__empty', config.strings.diagnosticsNoResults));
			return;
		}

		order.forEach(function (status) {
			var section;

			if (!groups[status] || !groups[status].length) {
				return;
			}

			section = element('section', 'hwlio-diagnostics__group');
			section.appendChild(element('h4', 'hwlio-diagnostics__group-title', diagnosticsGroupLabel(status, config.strings)));

			groups[status].forEach(function (result) {
				var item = element('article', 'hwlio-diagnostics__item');
				var header = element('div', 'hwlio-diagnostics__item-header');
				var copyButton = element('button', 'button button-secondary button-small hwlio-diagnostics__copy', config.strings.copyCodeAction);
				var meta = element('div', 'hwlio-diagnostics__item-meta');
				var code = element('code', 'hwlio-diagnostics__code', result.code || 'unknown');

				copyButton.type = 'button';
				copyButton.addEventListener('click', function () {
					copyText(client, config, result.code || 'unknown', config.strings.diagnosticsCopied);
				});

				header.appendChild(element('h5', 'hwlio-diagnostics__item-title', result.label || 'Diagnostic check'));
				header.appendChild(stateBadge(result.status || 'info', config.strings));
				meta.appendChild(code);
				meta.appendChild(copyButton);
				item.appendChild(header);
				item.appendChild(element('p', 'hwlio-diagnostics__item-message', result.message || ''));
				item.appendChild(meta);

				if (result.details && Object.keys(result.details).length) {
					item.appendChild(element('h6', 'hwlio-diagnostics__details-label', config.strings.detailsLabel));
					item.appendChild(renderDiagnosticDetails(result.details));
				}

				section.appendChild(item);
			});

			elements.groups.appendChild(section);
		});
	}

	function createDiagnosticsController(client, config) {
		var elements = diagnosticsElements(client.app);

		if (!elements.root || !elements.groups) {
			return null;
		}

		function load() {
			setButtonBusy(elements.button, true, config.strings.diagnosticsRefreshAction, config.strings.diagnosticsRefreshing);

			return client.request({
				path: (config.diagnostics && config.diagnostics.route) || defaults.diagnostics.route,
				method: 'GET',
				suppressNotices: true
			}).then(function (payload) {
				renderDiagnosticsSummary(elements, payload.summary || {});
				renderDiagnosticsGroups(elements, payload || {}, client, config);
				setButtonBusy(elements.button, false, config.strings.diagnosticsRefreshAction, config.strings.diagnosticsRefreshing);
			}).catch(function (error) {
				setButtonBusy(elements.button, false, config.strings.diagnosticsRefreshAction, config.strings.diagnosticsRefreshing);
				reportError(config, error, config.strings.diagnosticsLoadError);
			});
		}

		if (elements.button) {
			elements.button.addEventListener('click', function () {
				load();
			});
		}

		return {
			load: load
		};
	}

	function renderEnvironment(target, data, strings) {
		var meta = element('div', 'hwlio-dashboard__meta');
		var formats = element('div', 'hwlio-dashboard__formats');
		var formatEntries = data.formats || {};
		var availableEditors = (data.image_editors && data.image_editors.available) || [];

		clearNode(target);

		Object.keys(formatEntries).forEach(function (format) {
			var item = element('div', 'hwlio-dashboard__format');
			var label = element('span', 'hwlio-dashboard__meta-label', String(format).toUpperCase());
			var summary = formatEntries[format];
			var badgeText = summary && summary.supported ? 'Supported' : strings.unsupported;

			item.appendChild(label);
			item.appendChild(stateBadge(badgeText, strings));
			formats.appendChild(item);
		});

		appendMetaRow(meta, 'Formats', formats.childNodes.length ? formats : strings.noneDetected);
		appendMetaRow(meta, 'Image editor', availableEditors.length ? availableEditors.join(', ') : strings.notReady);
		appendMetaRow(meta, 'Uploads', stateBadge((data.uploads && data.uploads.status) || strings.notReady, strings));
		appendMetaRow(meta, 'Action Scheduler', stateBadge((data.action_scheduler && data.action_scheduler.status) || strings.notReady, strings));
		appendMetaRow(meta, 'Automatic optimization', stateBadge(data.automatic_optimization ? 'Enabled' : 'Disabled', strings));
		appendMetaRow(meta, 'Frontend delivery', stateBadge(data.delivery_enabled ? 'Enabled' : 'Disabled', strings));

		target.appendChild(meta);
	}

	function renderStats(target, statistics, queue, settings, strings) {
		var states = (statistics && statistics.attachment_states) || {};
		var stats = element('div', 'hwlio-dashboard__stats');
		var meta = element('div', 'hwlio-dashboard__meta');
		var enabledFormats = (settings && settings.enabled_formats) || [];

		clearNode(target);

		[
			['Optimized', states.optimized || 0],
			['Partial', states.partial || 0],
			['Failed', states.failed || 0],
			['Stale', states.stale || 0],
			['Queued', states.queued || 0],
			['Processing', states.processing || 0]
		].forEach(function (entry) {
			var card = element('div', 'hwlio-dashboard__stat');
			card.appendChild(element('span', 'hwlio-dashboard__stat-label', entry[0]));
			card.appendChild(element('span', 'hwlio-dashboard__stat-value', String(entry[1])));
			stats.appendChild(card);
		});

		appendMetaRow(meta, 'Queue', stateBadge(queue && queue.available ? strings.queueAvailable : strings.queueUnavailable, strings));
		appendMetaRow(meta, 'Enabled formats', enabledFormats.length ? enabledFormats.join(', ').toUpperCase() : strings.noneDetected);
		appendMetaRow(meta, 'Automation', stateBadge(settings && settings.automatic_optimization ? 'Enabled' : 'Disabled', strings));

		target.appendChild(stats);
		target.appendChild(meta);
	}

	function renderSavings(target, statistics) {
		var totals = (statistics && statistics.totals) || {};
		var stats = element('div', 'hwlio-dashboard__stats');

		clearNode(target);

		[
			['Sources represented', totals.sources_represented || 0],
			['Source bytes', formatBytes(totals.source_bytes || 0)],
			['Generated bytes', formatBytes(totals.generated_bytes || 0)],
			['Estimated savings', formatBytes(totals.savings_bytes || 0)],
			['Savings percent', formatPercent(totals.savings_percent || 0)],
			['Ready attachments', totals.attachments_with_ready_derivatives || 0]
		].forEach(function (entry) {
			var card = element('div', 'hwlio-dashboard__stat');
			card.appendChild(element('span', 'hwlio-dashboard__stat-label', entry[0]));
			card.appendChild(element('span', 'hwlio-dashboard__stat-value', String(entry[1])));
			stats.appendChild(card);
		});

		target.appendChild(stats);
	}

	function renderFailures(target, failures, strings) {
		var list = element('ul', 'hwlio-dashboard__list');

		clearNode(target);

		if (!failures || !failures.length) {
			target.appendChild(element('p', 'hwlio-dashboard__empty', strings.noFailures));
			return;
		}

		failures.forEach(function (failure) {
			var item = element('li', 'hwlio-dashboard__list-item');
			var title = element('span', 'hwlio-dashboard__list-title', failure.message || failure.code || 'Warning');
			var meta = element(
				'span',
				'hwlio-dashboard__list-meta',
				[(failure.level || 'warning').toUpperCase(), failure.code || '', formatTimestamp(failure.created_at_gmt || '')].filter(Boolean).join(' | ')
			);

			item.appendChild(title);
			item.appendChild(meta);
			list.appendChild(item);
		});

		target.appendChild(list);
	}

	function renderConflicts(target, conflicts, strings) {
		var list = element('ul', 'hwlio-dashboard__list');

		clearNode(target);

		if (!conflicts || !conflicts.length) {
			target.appendChild(element('p', 'hwlio-dashboard__empty', strings.noConflicts));
			return;
		}

		conflicts.forEach(function (conflict) {
			var item = element('li', 'hwlio-dashboard__list-item');
			var title = element('span', 'hwlio-dashboard__list-title', conflict.label || conflict.code || 'Warning');
			var meta = element('span', 'hwlio-dashboard__list-meta', [String(conflict.severity || 'warning').toUpperCase(), conflict.message || ''].filter(Boolean).join(' | '));

			item.appendChild(title);
			item.appendChild(meta);
			list.appendChild(item);
		});

		target.appendChild(list);
	}

	function renderRefresh(elements, refresh, strings) {
		if (!elements.refresh) {
			return;
		}

		if (refresh && refresh.pending) {
			elements.refresh.textContent = strings.cachePending;
			return;
		}

		if (refresh && refresh.generated_at_gmt) {
			elements.refresh.textContent = strings.cacheReady + ' ' + formatTimestamp(refresh.generated_at_gmt);
			return;
		}

		elements.refresh.textContent = strings.cacheUnknown;
	}

	function buildQueryString(values) {
		var parts = [];

		Object.keys(values || {}).forEach(function (key) {
			var value = values[key];

			if (null === value || 'undefined' === typeof value || '' === value) {
				return;
			}

			parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(String(value)));
		});

		return parts.length ? '?' + parts.join('&') : '';
	}

	function storageGet(key) {
		try {
			return window.sessionStorage ? window.sessionStorage.getItem(key) || '' : '';
		} catch (error) {
			return '';
		}
	}

	function storageSet(key, value) {
		try {
			if (window.sessionStorage) {
				window.sessionStorage.setItem(key, value);
			}
		} catch (error) {
			return;
		}
	}

	function storageDelete(key) {
		try {
			if (window.sessionStorage) {
				window.sessionStorage.removeItem(key);
			}
		} catch (error) {
			return;
		}
	}

	function createDashboardController(client, config) {
		var elements = dashboardElements(client.app);
		var pollingTimer = 0;
		var refreshBaseline = '';
		var currentGeneratedAt = '';

		if (!elements.root) {
			return null;
		}

		function stopPolling() {
			if (pollingTimer) {
				window.clearTimeout(pollingTimer);
				pollingTimer = 0;
			}
		}

		function schedulePolling(delay) {
			stopPolling();
			pollingTimer = window.setTimeout(function () {
				loadStatus(true);
			}, delay);
		}

		function updateButton(isBusy) {
			if (!elements.button) {
				return;
			}

			elements.button.disabled = !!isBusy;
			elements.button.textContent = isBusy ? config.strings.recalculateWorking : config.strings.recalculateAction;
		}

		function renderStatus(payload) {
			var refresh = payload.refresh || {};

			renderEnvironment(elements.environment, payload.environment || {}, config.strings);
			renderStats(elements.queue, payload.statistics || {}, payload.queue || {}, payload.settings || {}, config.strings);
			renderSavings(elements.savings, payload.statistics || {});
			renderFailures(elements.failures, payload.recentFailures || [], config.strings);
			renderConflicts(elements.conflicts, payload.conflicts || [], config.strings);
			renderRefresh(elements, refresh, config.strings);

			if (refresh.generated_at_gmt) {
				currentGeneratedAt = refresh.generated_at_gmt;
			}

			if (refresh.pending) {
				updateButton(true);
				schedulePolling((config.polling && config.polling.progressMs) || 5000);
				return;
			}

			if (refreshBaseline && refresh.generated_at_gmt && refresh.generated_at_gmt !== refreshBaseline) {
				client.showNotice('success', config.strings.dashboardUpdated);
				client.announce(config.strings.dashboardUpdated, 'polite');
				refreshBaseline = '';
			}

			updateButton(false);
			stopPolling();
		}

		function loadStatus(isPolling) {
			return client.request({
				path: '/status',
				method: 'GET',
				suppressNotices: true
			}).then(function (payload) {
				renderStatus(payload || {});
			}).catch(function (error) {
				stopPolling();
				updateButton(false);
				reportError(config, error, config.strings.dashboardLoadError);
			});
		}

		function requestRecalculate() {
			updateButton(true);

			return client.request({
				path: '/status',
				method: 'POST'
			}).then(function (payload) {
				var code = payload && payload.result_code ? payload.result_code : '';

				if ('queued' === code) {
					client.showNotice('success', config.strings.recalculateQueued);
					client.announce(config.strings.recalculateQueued, 'polite');
				} else if ('already_pending' === code) {
					client.showNotice('info', config.strings.recalculatePending);
					client.announce(config.strings.recalculateBusy, 'polite');
				}

				refreshBaseline = currentGeneratedAt;
				return loadStatus(true);
			}).catch(function (error) {
				updateButton(false);
				throw error;
			});
		}

		if (elements.button) {
			elements.button.addEventListener('click', function () {
				requestRecalculate();
			});
		}

		return {
			load: loadStatus,
			stop: stopPolling
		};
	}

	function bulkElements(app) {
		return {
			root: app.querySelector('[data-hwlio-bulk="root"]'),
			form: app.querySelector('[data-hwlio-bulk-form]'),
			status: app.querySelector('[data-hwlio-bulk-status]'),
			button: app.querySelector('[data-hwlio-bulk-action="scan"]'),
			queueButton: app.querySelector('[data-hwlio-bulk-action="queue"]'),
			retryButton: app.querySelector('[data-hwlio-bulk-action="retry"]'),
			pauseButton: app.querySelector('[data-hwlio-bulk-action="pause"]'),
			resumeButton: app.querySelector('[data-hwlio-bulk-action="resume"]'),
			cancelButton: app.querySelector('[data-hwlio-bulk-action="cancel"]'),
			queueStatus: app.querySelector('[data-hwlio-bulk-queue-status]'),
			controlPending: app.querySelector('[data-hwlio-bulk-control-pending]'),
			controlRunning: app.querySelector('[data-hwlio-bulk-control-running]'),
			previewBody: app.querySelector('[data-hwlio-bulk-preview-body]'),
			pageStatus: app.querySelector('[data-hwlio-bulk-page-status]'),
			previousButton: app.querySelector('[data-hwlio-bulk-page="previous"]'),
			nextButton: app.querySelector('[data-hwlio-bulk-page="next"]')
		};
	}

	function bulkSummaryValue(root, group, key) {
		return root ? root.querySelector('[data-hwlio-bulk-summary-value="' + group + ':' + key + '"]') : null;
	}

	function setSummaryValues(elements, group, keys, summary) {
		keys.forEach(function (key) {
			var node = bulkSummaryValue(elements.root, group, key);

			if (node) {
				node.textContent = String(summary && summary[key] ? summary[key] : 0);
			}
		});
	}

	function renderBulkStatus(elements, payload, strings) {
		var progress = payload.progress || {};
		var summary = payload.summary || {};
		var parts = [];

		setSummaryValues(elements, 'scan', ['scanned', 'eligible', 'excluded', 'active', 'already_optimized', 'skipped'], summary);

		if (!elements.status) {
			return;
		}

		if (progress.complete) {
			if ((summary.eligible || 0) > 0) {
				parts.push(strings.bulkCompleted);
				parts.push(String(summary.eligible || 0) + ' eligible candidate(s) are ready for preview.');
				parts.push(strings.bulkDeferredQueue);
			} else {
				parts.push(strings.bulkEmpty);
			}
		} else {
			parts.push(strings.bulkScanning);
			parts.push('Scanned ' + String(summary.scanned || 0) + ' attachment(s) so far.');
		}

		if ((summary.excluded || 0) > 0) {
			parts.push(strings.bulkExcludedSkipped);
		}

		elements.status.textContent = parts.join(' ');
	}

	function renderQueueStatus(elements, payload, strings) {
		var progress = (payload && payload.queueProgress) || {};
		var summary = (payload && payload.queueSummary) || {};
		var queueControl = (payload && payload.queueControl) || {};
		var action = payload && payload.action ? payload.action : 'queue';
		var parts = [];

		setSummaryValues(elements, 'queue', ['queued', 'already_queued', 'already_optimized', 'skipped', 'failed_to_queue'], summary);

		if (!elements.queueStatus) {
			return;
		}

		if (queueControl.paused || 'paused' === progress.status) {
			parts.push(strings.bulkQueuePaused);
		} else if (progress.complete) {
			parts.push('retry' === action ? strings.bulkRetryComplete : strings.bulkQueueComplete);
		} else if ('running' === progress.status) {
			parts.push('retry' === action ? strings.bulkRetryRunning : strings.bulkQueueRunning);
		}

		parts.push(String(summary.queued || 0) + ' queued.');
		parts.push(String(summary.already_queued || 0) + ' already queued.');

		if (summary.failed_to_queue || summary.skipped || summary.already_optimized) {
			parts.push(String(summary.already_optimized || 0) + ' already optimized.');
			parts.push(String(summary.skipped || 0) + ' skipped.');
			parts.push(String(summary.failed_to_queue || 0) + ' failed to queue.');
		}

		elements.queueStatus.textContent = parts.join(' ');
	}

	function renderQueueControl(elements, queueControl, strings) {
		var paused = !!(queueControl && queueControl.paused);
		var pending = Number((queueControl && queueControl.pending) || 0);
		var running = Number((queueControl && queueControl.inProgress) || 0);

		if (elements.controlPending) {
			elements.controlPending.textContent = strings.bulkControlPending + ': ' + String(pending);
		}

		if (elements.controlRunning) {
			elements.controlRunning.textContent = strings.bulkControlRunning + ': ' + String(running);
		}

		if (elements.pauseButton) {
			elements.pauseButton.disabled = paused;
		}

		if (elements.resumeButton) {
			elements.resumeButton.disabled = !paused;
		}
	}

	function renderBulkPreview(elements, payload, strings) {
		var items = (payload && payload.items) || [];
		var page = Number((payload && payload.page) || 1);
		var totalPages = Number((payload && payload.totalPages) || 0);

		clearNode(elements.previewBody);

		if (!items.length) {
			var emptyRow = document.createElement('tr');
			var emptyCell = document.createElement('td');

			emptyCell.colSpan = 4;
			emptyCell.className = 'hwlio-bulk__empty';
			emptyCell.textContent = strings.bulkPreviewEmpty;
			emptyRow.appendChild(emptyCell);
			elements.previewBody.appendChild(emptyRow);
		} else {
			items.forEach(function (item) {
				var row = document.createElement('tr');
				var attachmentCell = document.createElement('td');
				var uploadedCell = document.createElement('td');
				var statusCell = document.createElement('td');
				var formatsCell = document.createElement('td');
				var title = element('strong', '', item.title || item.filename || ('#' + String(item.attachmentId || '')));
				var meta = element('div', 'description', item.filename ? item.filename : ('Attachment #' + String(item.attachmentId || '')));
				var formatsWrap = element('div', 'hwlio-bulk__ready-formats');

				attachmentCell.appendChild(title);
				attachmentCell.appendChild(meta);
				uploadedCell.textContent = item.uploadedAtGmt ? formatTimestamp(item.uploadedAtGmt) : '';
				statusCell.appendChild(stateBadge(item.statusLabel || item.state || '', strings));

				if ((item.readyFormats || []).length) {
					item.readyFormats.forEach(function (format) {
						formatsWrap.appendChild(element('span', 'hwlio-bulk__chip', String(format).toUpperCase()));
					});
					formatsCell.appendChild(formatsWrap);
				} else {
					formatsCell.textContent = strings.noneDetected;
				}

				row.appendChild(attachmentCell);
				row.appendChild(uploadedCell);
				row.appendChild(statusCell);
				row.appendChild(formatsCell);
				elements.previewBody.appendChild(row);
			});
		}

		if (elements.pageStatus) {
			elements.pageStatus.textContent = totalPages > 0
				? strings.bulkPageLabel + ' ' + String(page) + ' / ' + String(totalPages)
				: strings.bulkPreviewEmpty;
		}

		if (elements.previousButton) {
			elements.previousButton.disabled = page <= 1 || totalPages <= 1;
			elements.previousButton.textContent = strings.bulkPrevious;
		}

		if (elements.nextButton) {
			elements.nextButton.disabled = totalPages <= 1 || page >= totalPages;
			elements.nextButton.textContent = strings.bulkNext;
		}
	}

	function createBulkController(client, config) {
		var elements = bulkElements(client.app);
		var timer = 0;
		var activeToken = '';
		var activePage = 1;
		var totalPages = 0;
		var storageKey = (config.bulk && config.bulk.storageKey) || defaults.bulk.storageKey;
		var queueModeKey = (config.bulk && config.bulk.queueModeKey) || defaults.bulk.queueModeKey;
		var activeQueueMode = '';

		if (!elements.root || !elements.form || !elements.previewBody) {
			return null;
		}

		function stopPolling() {
			if (timer) {
				window.clearTimeout(timer);
				timer = 0;
			}
		}

		function setBusy(isBusy) {
			if (elements.button) {
				elements.button.disabled = !!isBusy;
				elements.button.textContent = isBusy ? config.strings.bulkScanning : config.strings.bulkStart;
			}
		}

		function setQueueBusy(mode, isBusy) {
			if (elements.queueButton) {
				elements.queueButton.disabled = !!isBusy || !activeToken;
				elements.queueButton.textContent = config.strings.bulkQueueAction;
			}

			if (elements.retryButton) {
				elements.retryButton.disabled = !!isBusy || !activeToken;
				elements.retryButton.textContent = config.strings.bulkRetryAction;
			}

			if (isBusy && 'queue' === mode && elements.queueButton) {
				elements.queueButton.disabled = true;
			}

			if (isBusy && 'retry' === mode && elements.retryButton) {
				elements.retryButton.disabled = true;
			}
		}

		function formPayload() {
			var payload = {};
			var fields = new window.FormData(elements.form);

			fields.forEach(function (value, key) {
				if ('string' === typeof value && '' === value.trim()) {
					return;
				}

				payload[key] = value;
			});

			return payload;
		}

		function loadPreview(page) {
			if (!activeToken) {
				return window.Promise.resolve();
			}

			return client.request({
				path: config.bulk.attachmentsRoute + buildQueryString({
					scan_token: activeToken,
					page: page,
					per_page: (config.bulk && config.bulk.previewPageSize) || defaults.bulk.previewPageSize
				}),
				method: 'GET',
				suppressNotices: true
			}).then(function (payload) {
				activePage = Number((payload && payload.page) || page || 1);
				totalPages = Number((payload && payload.totalPages) || 0);
				renderBulkPreview(elements, payload || {}, config.strings);
			}).catch(function (error) {
				reportError(config, error, config.strings.bulkPreviewError);
			});
		}

		function scheduleContinue() {
			stopPolling();
			timer = window.setTimeout(function () {
				runScan({
					scan_token: activeToken
				}, true);
			}, (config.bulk && config.bulk.scanIntervalMs) || defaults.bulk.scanIntervalMs);
		}

		function scheduleQueueContinue() {
			stopPolling();
			timer = window.setTimeout(function () {
				runQueue(activeQueueMode, true);
			}, (config.bulk && config.bulk.queueIntervalMs) || defaults.bulk.queueIntervalMs);
		}

		function loadQueueControl() {
			return client.request({
				path: '/status',
				method: 'GET',
				suppressNotices: true
			}).then(function (payload) {
				renderQueueControl(elements, payload && payload.queueControl ? payload.queueControl : {}, config.strings);
				return payload || {};
			}).catch(function () {
				return {};
			});
		}

		function handleScanPayload(payload, resumed) {
			var progress = (payload && payload.progress) || {};
			var summary = (payload && payload.summary) || {};

			activeToken = payload && payload.scanToken ? payload.scanToken : '';

			if (activeToken) {
				storageSet(storageKey, activeToken);
			}

			renderBulkStatus(elements, payload || {}, config.strings);

			if (progress.complete) {
				stopPolling();
				setBusy(false);
				activePage = 1;

				if (resumed) {
					client.showNotice('info', config.strings.bulkResumed);
					client.announce(config.strings.bulkResumed, 'polite');
				} else {
					client.showNotice('success', config.strings.bulkCompleted);
					client.announce(config.strings.bulkCompleted, 'polite');
				}

				if ((summary.eligible || 0) > 0) {
					return loadPreview(1).then(function () {
						return loadQueueControl();
					});
				}

				renderBulkPreview(elements, {
					page: 1,
					totalPages: 0,
					items: []
				}, config.strings);

				return loadQueueControl();
			}

			setBusy(true);
			scheduleContinue();

			return window.Promise.resolve();
		}

		function runScan(payload, resumed) {
			return client.request({
				path: config.bulk.jobsScanRoute,
				method: 'POST',
				data: payload,
				suppressNotices: true
			}).then(function (response) {
				return handleScanPayload(response || {}, resumed);
			}).catch(function (error) {
				stopPolling();
				setBusy(false);
				reportError(config, error, config.strings.bulkScanError);
				storageDelete(storageKey);
			});
		}

		function startScan() {
			stopPolling();
			activeToken = '';
			activeQueueMode = '';
			activePage = 1;
			totalPages = 0;
			storageDelete(storageKey);
			storageDelete(queueModeKey);
			renderBulkPreview(elements, {
				page: 1,
				totalPages: 0,
				items: []
			}, config.strings);
			renderQueueStatus(elements, {
				queueProgress: {},
				queueSummary: {}
			}, config.strings);
			setBusy(true);
			setQueueBusy('', false);

			return runScan(formPayload(), false);
		}

		function maybeResume() {
			var storedToken = storageGet(storageKey);
			var storedQueueMode = storageGet(queueModeKey);

			if (!storedToken) {
				loadQueueControl();
				return;
			}

			setBusy(true);
			runScan({
				scan_token: storedToken
			}, true).then(function () {
				if (storedQueueMode) {
					activeQueueMode = storedQueueMode;
					runQueue(storedQueueMode, true);
				} else {
					loadQueueControl();
				}
			});
		}

		function handleQueuePayload(payload, resumed) {
			var progress = (payload && payload.queueProgress) || {};
			var queueControl = (payload && payload.queueControl) || {};
			var action = payload && payload.action ? payload.action : activeQueueMode;

			if (payload && payload.scanToken) {
				activeToken = payload.scanToken;
				storageSet(storageKey, activeToken);
			}

			activeQueueMode = action;
			storageSet(queueModeKey, activeQueueMode);
			renderQueueStatus(elements, payload || {}, config.strings);
			renderQueueControl(elements, queueControl, config.strings);

			if (queueControl.paused || 'paused' === progress.status) {
				stopPolling();
				setQueueBusy('', false);
				client.showNotice('info', config.strings.bulkQueuePaused);
				client.announce(config.strings.bulkQueuePaused, 'polite');
				return window.Promise.resolve();
			}

			if (progress.complete) {
				stopPolling();
				setQueueBusy('', false);
				storageDelete(queueModeKey);
				client.showNotice('success', 'retry' === action ? config.strings.bulkRetryComplete : config.strings.bulkQueueComplete);
				client.announce('retry' === action ? config.strings.bulkRetryComplete : config.strings.bulkQueueComplete, 'polite');
				return loadQueueControl();
			}

			setQueueBusy(action, true);
			if (resumed) {
				client.showNotice('info', 'retry' === action ? config.strings.bulkRetryRunning : config.strings.bulkQueueRunning);
			}
			scheduleQueueContinue();
			return window.Promise.resolve();
		}

		function runQueue(mode, resumed) {
			if (!activeToken) {
				return window.Promise.resolve();
			}

			return client.request({
				path: 'retry' === mode ? config.bulk.jobsRetryRoute : config.bulk.jobsQueueRoute,
				method: 'POST',
				data: {
					scan_token: activeToken
				},
				suppressNotices: true
			}).then(function (payload) {
				return handleQueuePayload(payload || {}, resumed);
			}).catch(function (error) {
				stopPolling();
				setQueueBusy('', false);
				storageDelete(queueModeKey);
				reportError(config, error, 'retry' === mode ? config.strings.bulkRetryError : config.strings.bulkQueueError);
			});
		}

		function startQueue(mode) {
			if (!activeToken) {
				client.showNotice('info', config.strings.bulkDeferredQueue);
				client.announce(config.strings.bulkDeferredQueue, 'polite');
				return;
			}

			activeQueueMode = mode;
			setQueueBusy(mode, true);
			runQueue(mode, false);
		}

		function pauseQueue() {
			return client.request({
				path: config.bulk.jobsPauseRoute,
				method: 'POST',
				suppressNotices: true
			}).then(function (payload) {
				renderQueueControl(elements, payload && payload.queueControl ? payload.queueControl : {}, config.strings);
				stopPolling();
				setQueueBusy('', false);
				client.showNotice('success', config.strings.bulkPauseSuccess);
				client.announce(config.strings.bulkPauseSuccess, 'polite');
			}).catch(function (error) {
				reportError(config, error, config.strings.bulkQueueError);
			});
		}

		function resumeQueue() {
			return client.request({
				path: config.bulk.jobsResumeRoute,
				method: 'POST',
				suppressNotices: true
			}).then(function (payload) {
				renderQueueControl(elements, payload && payload.queueControl ? payload.queueControl : {}, config.strings);
				client.showNotice('success', config.strings.bulkResumeSuccess);
				client.announce(config.strings.bulkResumeSuccess, 'polite');

				if (activeQueueMode) {
					runQueue(activeQueueMode, true);
				}
			}).catch(function (error) {
				reportError(config, error, config.strings.bulkQueueError);
			});
		}

		function cancelPending() {
			if (elements.pauseButton && !elements.pauseButton.disabled) {
				client.showNotice('info', config.strings.bulkPauseBeforeCancel);
				client.announce(config.strings.bulkPauseBeforeCancel, 'polite');
				return;
			}

			return client.request({
				path: config.bulk.jobsPendingRoute,
				method: 'DELETE',
				suppressNotices: true
			}).then(function (payload) {
				var result = payload && payload.result ? payload.result : {};

				renderQueueControl(elements, payload && payload.queueControl ? payload.queueControl : {}, config.strings);

				if (result.successful) {
					client.showNotice('success', config.strings.bulkCancelSuccess);
					client.announce(config.strings.bulkCancelSuccess, 'polite');
				} else {
					client.showNotice('error', config.strings.bulkCancelError);
					client.announce(config.strings.bulkCancelError, 'assertive');
				}
			}).catch(function (error) {
				reportError(config, error, config.strings.bulkCancelError);
			});
		}

		elements.form.addEventListener('submit', function (event) {
			event.preventDefault();
			startScan();
		});

		if (elements.previousButton) {
			elements.previousButton.addEventListener('click', function () {
				if (activePage > 1) {
					loadPreview(activePage - 1);
				}
			});
		}

		if (elements.nextButton) {
			elements.nextButton.addEventListener('click', function () {
				if (totalPages > 0 && activePage < totalPages) {
					loadPreview(activePage + 1);
				}
			});
		}

		if (elements.queueButton) {
			elements.queueButton.addEventListener('click', function () {
				startQueue('queue');
			});
		}

		if (elements.retryButton) {
			elements.retryButton.addEventListener('click', function () {
				startQueue('retry');
			});
		}

		if (elements.pauseButton) {
			elements.pauseButton.addEventListener('click', function () {
				pauseQueue();
			});
		}

		if (elements.resumeButton) {
			elements.resumeButton.addEventListener('click', function () {
				resumeQueue();
			});
		}

		if (elements.cancelButton) {
			elements.cancelButton.addEventListener('click', function () {
				cancelPending();
			});
		}

		return {
			start: startScan,
			stop: stopPolling,
			resume: maybeResume
		};
	}

	function logsElements(app) {
		return {
			root: app.querySelector('[data-hwlio-logs="root"]'),
			filters: app.querySelector('[data-hwlio-logs-filters]'),
			body: app.querySelector('[data-hwlio-logs-body]'),
			refreshButton: app.querySelector('[data-hwlio-logs-action="refresh"]'),
			resetButton: app.querySelector('[data-hwlio-logs-action="reset-filters"]'),
			retentionButton: app.querySelector('[data-hwlio-logs-action="save-retention"]'),
			clearButton: app.querySelector('[data-hwlio-logs-action="clear-all"]'),
			retentionInput: app.querySelector('[data-hwlio-logs-retention-input]'),
			deleteStatus: app.querySelector('[data-hwlio-logs-delete-status]'),
			pageStatus: app.querySelector('[data-hwlio-logs-page-status]'),
			previousButton: app.querySelector('[data-hwlio-logs-page="previous"]'),
			nextButton: app.querySelector('[data-hwlio-logs-page="next"]')
		};
	}

	function renderLogsRows(elements, payload, client, config) {
		var items = (payload && payload.items) || [];

		clearNode(elements.body);

		if (!items.length) {
			var emptyRow = document.createElement('tr');
			var emptyCell = document.createElement('td');

			emptyCell.colSpan = 6;
			emptyCell.className = 'hwlio-logs__empty';
			emptyCell.textContent = config.strings.logsEmpty;
			emptyRow.appendChild(emptyCell);
			elements.body.appendChild(emptyRow);
			return;
		}

		items.forEach(function (item) {
			var row = document.createElement('tr');
			var created = document.createElement('td');
			var level = document.createElement('td');
			var code = document.createElement('td');
			var message = document.createElement('td');
			var attachment = document.createElement('td');
			var job = document.createElement('td');
			var codeWrap = element('div', 'hwlio-logs__code-wrap');
			var codeText = element('code', 'hwlio-logs__code', item.code || 'unknown');
			var copyButton = element('button', 'button button-secondary button-small hwlio-logs__copy', config.strings.copyCodeAction);

			copyButton.type = 'button';
			copyButton.addEventListener('click', function () {
				copyText(client, config, item.code || 'unknown', config.strings.diagnosticsCopied);
			});

			created.textContent = formatTimestamp(item.created_at_gmt || '');
			level.appendChild(stateBadge(item.level || 'info', config.strings));
			codeWrap.appendChild(codeText);
			codeWrap.appendChild(copyButton);
			code.appendChild(codeWrap);
			message.textContent = item.message || '';
			attachment.textContent = item.attachment_id ? String(item.attachment_id) : '-';
			job.textContent = item.job_id ? String(item.job_id) : '-';
			row.appendChild(created);
			row.appendChild(level);
			row.appendChild(code);
			row.appendChild(message);
			row.appendChild(attachment);
			row.appendChild(job);
			elements.body.appendChild(row);
		});
	}

	function renderLogsPagination(elements, payload, strings) {
		var page = Number((payload && payload.page) || 1);
		var totalPages = Number((payload && payload.totalPages) || 0);
		var totalItems = Number((payload && payload.totalItems) || 0);

		if (elements.pageStatus) {
			elements.pageStatus.textContent = totalPages > 0
				? strings.logsPageLabel + ' ' + String(page) + ' / ' + String(totalPages) + ' (' + String(totalItems) + ')'
				: strings.logsEmpty;
		}

		if (elements.previousButton) {
			elements.previousButton.disabled = page <= 1 || totalPages <= 1;
		}

		if (elements.nextButton) {
			elements.nextButton.disabled = totalPages <= 1 || page >= totalPages;
		}
	}

	function createLogsController(client, config) {
		var elements = logsElements(client.app);
		var currentPage = 1;
		var totalPages = 0;
		var deleting = false;

		if (!elements.root || !elements.filters || !elements.body) {
			return null;
		}

		function filterParams(page) {
			var values = {};
			var fields = new window.FormData(elements.filters);

			fields.forEach(function (value, key) {
				if ('string' === typeof value && '' === value.trim()) {
					return;
				}

				values[key] = value;
			});

			values.page = page || 1;
			values.per_page = (config.logs && config.logs.defaultPerPage) || defaults.logs.defaultPerPage;

			return values;
		}

		function setDeleting(isBusy) {
			deleting = !!isBusy;
			setButtonBusy(elements.clearButton, isBusy, config.strings.logsClearAction, config.strings.logsDeleting);
		}

		function load(page) {
			currentPage = page || 1;

			return client.request({
				path: ((config.logs && config.logs.route) || defaults.logs.route) + buildQueryString(filterParams(currentPage)),
				method: 'GET',
				suppressNotices: true
			}).then(function (payload) {
				totalPages = Number((payload && payload.totalPages) || 0);
				renderLogsRows(elements, payload || {}, client, config);
				renderLogsPagination(elements, payload || {}, config.strings);
			}).catch(function (error) {
				reportError(config, error, config.strings.logsLoadError);
			});
		}

		function saveRetention() {
			setButtonBusy(elements.retentionButton, true, config.strings.logsSaveRetentionAction, config.strings.logsRetentionSaving);

			return client.request({
				path: (config.logs && config.logs.retentionRoute) || defaults.logs.retentionRoute,
				method: 'POST',
				data: {
					retention_days: elements.retentionInput ? elements.retentionInput.value : ''
				},
				suppressNotices: true
			}).then(function (payload) {
				var result = payload && payload.result ? payload.result : {};

				if (elements.retentionInput && result.retentionDays) {
					elements.retentionInput.value = String(result.retentionDays);
				}

				setButtonBusy(elements.retentionButton, false, config.strings.logsSaveRetentionAction, config.strings.logsRetentionSaving);
				client.showNotice('success', config.strings.logsRetentionSaved);
				client.announce(config.strings.logsRetentionSaved, 'polite');
			}).catch(function (error) {
				setButtonBusy(elements.retentionButton, false, config.strings.logsSaveRetentionAction, config.strings.logsRetentionSaving);
				reportError(config, error, config.strings.requestError);
			});
		}

		function clearAllLogs(totalDeleted) {
			totalDeleted = Number(totalDeleted || 0);

			if (elements.deleteStatus) {
				elements.deleteStatus.textContent = config.strings.logsDeleteProgress + ' ' + String(totalDeleted);
			}

			return client.request({
				path: (config.logs && config.logs.route) || defaults.logs.route,
				method: 'DELETE',
				suppressNotices: true
			}).then(function (payload) {
				var result = payload && payload.result ? payload.result : {};
				var deletedThisRequest = Number(result.deletedCount || 0);
				var runningTotal = totalDeleted + deletedThisRequest;

				if (elements.deleteStatus) {
					elements.deleteStatus.textContent = config.strings.logsDeleteProgress + ' ' + String(runningTotal);
				}

				if (!result.complete) {
					return sleep((config.logs && config.logs.deleteIntervalMs) || defaults.logs.deleteIntervalMs).then(function () {
						return clearAllLogs(runningTotal);
					});
				}

				setDeleting(false);
				client.showNotice('success', config.strings.logsDeleted);
				client.announce(config.strings.logsDeleted, 'polite');
				return load(1);
			}).catch(function (error) {
				setDeleting(false);
				reportError(config, error, config.strings.logsDeleteError);
			});
		}

		elements.filters.addEventListener('submit', function (event) {
			event.preventDefault();
			load(1);
		});

		if (elements.resetButton) {
			elements.resetButton.addEventListener('click', function () {
				elements.filters.reset();
				load(1);
			});
		}

		if (elements.refreshButton) {
			elements.refreshButton.addEventListener('click', function () {
				load(currentPage);
			});
		}

		if (elements.retentionButton) {
			elements.retentionButton.addEventListener('click', function () {
				saveRetention();
			});
		}

		if (elements.clearButton) {
			elements.clearButton.addEventListener('click', function () {
				if (deleting || !window.confirm(config.strings.logsClearConfirm)) {
					return;
				}

				setDeleting(true);
				if (elements.deleteStatus) {
					elements.deleteStatus.textContent = config.strings.logsDeleting;
				}
				clearAllLogs(0);
			});
		}

		if (elements.previousButton) {
			elements.previousButton.addEventListener('click', function () {
				if (currentPage > 1) {
					load(currentPage - 1);
				}
			});
		}

		if (elements.nextButton) {
			elements.nextButton.addEventListener('click', function () {
				if (totalPages > 0 && currentPage < totalPages) {
					load(currentPage + 1);
				}
			});
		}

		return {
			load: function () {
				return load(1);
			}
		};
	}

	function initializeClient(config) {
		var elements = {
			app: findElement(config.selectors.app)
		};
		var apiFetch;
		var request;
		var dashboard;
		var bulk;
		var diagnostics;
		var logs;

		if (!elements.app) {
			throw new Error(config.strings.missingMount);
		}

		elements.app.setAttribute('data-state', 'loading');

		if (!wp || !wp.apiFetch || 'function' !== typeof wp.apiFetch.use) {
			throw new Error(config.strings.missingApiFetch);
		}

		if (!config.rest || !config.rest.root || !config.rest.nonce) {
			throw new Error(config.strings.bootstrapError);
		}

		apiFetch = wp.apiFetch;

		if ('function' === typeof apiFetch.createRootURLMiddleware) {
			apiFetch.use(apiFetch.createRootURLMiddleware(config.rest.root));
		}

		if ('function' === typeof apiFetch.createNonceMiddleware) {
			apiFetch.use(apiFetch.createNonceMiddleware(config.rest.nonce));
		}

		request = requestWrapper(apiFetch, config);

		elements.app.setAttribute('data-state', 'ready');
		elements.app.setAttribute('data-tab', config.currentTab);

		dashboard = 'dashboard' === config.currentTab
			? createDashboardController(
				{
					request: request,
					showNotice: function (level, message) {
						showNotice(config.selectors, level, message);
					},
					announce: function (message, priority) {
						announce(config.selectors, message, priority);
					},
					app: elements.app
				},
				config
			)
			: null;
		bulk = 'bulk-optimize' === config.currentTab
			? createBulkController(
				{
					request: request,
					showNotice: function (level, message) {
						showNotice(config.selectors, level, message);
					},
					announce: function (message, priority) {
						announce(config.selectors, message, priority);
					},
					app: elements.app
				},
				config
			)
			: null;
		diagnostics = 'diagnostics' === config.currentTab
			? createDiagnosticsController(
				{
					request: request,
					showNotice: function (level, message) {
						showNotice(config.selectors, level, message);
					},
					announce: function (message, priority) {
						announce(config.selectors, message, priority);
					},
					app: elements.app
				},
				config
			)
			: null;
		logs = 'logs' === config.currentTab
			? createLogsController(
				{
					request: request,
					showNotice: function (level, message) {
						showNotice(config.selectors, level, message);
					},
					announce: function (message, priority) {
						announce(config.selectors, message, priority);
					},
					app: elements.app
				},
				config
			)
			: null;

		window[globalClientName] = {
			app: elements.app,
			request: request,
			showNotice: function (level, message) {
				showNotice(config.selectors, level, message);
			},
			announce: function (message, priority) {
				announce(config.selectors, message, priority);
			},
			dashboard: dashboard,
			bulk: bulk,
			diagnostics: diagnostics,
			logs: logs
		};

		if (dashboard) {
			dashboard.load(false);
		}

		if (bulk) {
			bulk.resume();
		}

		if (diagnostics) {
			diagnostics.load();
		}

		if (logs) {
			logs.load();
		}
	}

	function ready(callback) {
		if ('loading' === document.readyState) {
			document.addEventListener('DOMContentLoaded', callback);
			return;
		}

		callback();
	}

	function mergedConfig() {
		var config = window[bootstrapName] || {};

		return {
			bulk: config.bulk || defaults.bulk,
			diagnostics: config.diagnostics || defaults.diagnostics,
			logs: config.logs || defaults.logs,
			rest: config.rest || {},
			currentTab: config.currentTab || 'dashboard',
			polling: config.polling || {},
			selectors: config.selectors || defaults.selectors,
			strings: config.strings || defaults.strings
		};
	}

	ready(function () {
		var config;

		if (!window[bootstrapName]) {
			reportError(defaults, new Error(defaults.strings.bootstrapError), defaults.strings.bootstrapError);
			return;
		}

		config = mergedConfig();

		try {
			initializeClient(config);
		} catch (error) {
			reportError(config, error, config.strings.bootstrapError);
		}
	});

	window.addEventListener('error', function (event) {
		reportError(mergedConfig(), event.error, event.message);
	});

	window.addEventListener('unhandledrejection', function (event) {
		reportError(mergedConfig(), event.reason, defaults.strings.requestError);
	});
}(window, document, window.wp || {}));
