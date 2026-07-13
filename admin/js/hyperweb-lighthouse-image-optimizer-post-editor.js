(function (window, document, wp) {
	'use strict';

	var config = window.hwlioCriticalImageEditor || null;

	function ready(callback) {
		if ('loading' === document.readyState) {
			document.addEventListener('DOMContentLoaded', callback);
			return;
		}

		callback();
	}

	function find(root, selector) {
		return (root || document).querySelector(selector);
	}

	function text(node, value) {
		if (node) {
			node.textContent = value;
		}
	}

	function updateSummary(box, attachment) {
		var selectors = config.selectors;
		var input = find(box, selectors.input);
		var title = find(box, selectors.title);
		var summary = find(box, selectors.summary);
		var preview = find(box, selectors.preview);
		var selectButton = find(box, selectors.select);
		var clearButton = find(box, selectors.clear);
		var img = preview ? preview.querySelector('img') : null;
		var selected = !!(attachment && attachment.id);

		if (!input || !title || !selectButton || !clearButton) {
			return;
		}

		input.value = selected ? String(attachment.id) : '';
		text(title, selected ? (attachment.title || config.strings.noSelection) : config.strings.noSelection);
		selectButton.textContent = selected ? config.strings.buttonReplace : config.strings.buttonSelect;
		clearButton.hidden = !selected;

		if (!summary || !preview) {
			return;
		}

		if (selected && attachment.thumbnail) {
			if (!img) {
				img = document.createElement('img');
				img.alt = '';
				img.style.maxWidth = '100%';
				img.style.height = 'auto';
				preview.appendChild(img);
			}

			img.src = attachment.thumbnail;
			preview.hidden = false;
			return;
		}

		if (img && img.parentNode) {
			img.parentNode.removeChild(img);
		}

		preview.hidden = true;
	}

	function openFrame(box) {
		var frame;

		if (!wp || !wp.media || !config) {
			return;
		}

		frame = wp.media({
			title: config.strings.selectTitle,
			multiple: false,
			library: {
				type: 'image'
			},
			button: {
				text: config.strings.buttonSelect
			}
		});

		frame.on('select', function () {
			var selection = frame.state().get('selection').first();
			var attachment = selection ? selection.toJSON() : null;

			updateSummary(box, {
				id: attachment && attachment.id ? attachment.id : 0,
				title: attachment && attachment.title ? attachment.title : '',
				thumbnail: attachment && attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : (attachment && attachment.icon ? attachment.icon : '')
			});
		});

		frame.open();
	}

	ready(function () {
		var selectors = config && config.selectors ? config.selectors : null;

		if (!selectors) {
			return;
		}

		document.addEventListener('click', function (event) {
			var target = event.target;
			var box;

			if (!target) {
				return;
			}

			if (target.matches(selectors.select)) {
				event.preventDefault();
				box = target.closest(selectors.box);

				if (box) {
					openFrame(box);
				}
			}

			if (target.matches(selectors.clear)) {
				event.preventDefault();
				box = target.closest(selectors.box);

				if (box) {
					updateSummary(box, null);
				}
			}
		});
	});
}(window, document, window.wp || {}));
