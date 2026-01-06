(function(api, $) {
	'use strict';

	function VelocityRepeater(control) {
		var self = this;
		this.control = control;
		this.$container = control.container.find('.velocity-repeater');
		this.$store = this.$container.find('.velocity-repeater-store');
		this.$itemsWrap = this.$container.find('.velocity-repeater-items');
		this.template = (this.$container.find('.velocity-repeater-template').html() || '').trim();
		this.defaultLabel = this.$container.data('default-label') || '';
		this.$container.find('.velocity-repeater-template').remove();
		this.bindEvents();

		if (!this.$itemsWrap.children().length) {
			this.addItem({}, null, true);
		}

		this.updateSummaries();
		this.$itemsWrap.children('.velocity-repeater-item').each(function() {
			self.toggleItem($(this), false);
		});
	}

	VelocityRepeater.prototype.bindEvents = function() {
		var self = this;

		self.$container.on('click', '.velocity-repeater-add', function(event) {
			event.preventDefault();
			self.addItem({}, null, true);
		});

		self.$container.on('click', '.velocity-repeater-remove', function(event) {
			event.preventDefault();
			$(this).closest('.velocity-repeater-item').remove();
			self.sync();
		});

		self.$container.on('click', '.velocity-repeater-clone', function(event) {
			event.preventDefault();
			var $item = $(this).closest('.velocity-repeater-item');
			var values = self.readItem($item);
			self.addItem(values, $item, true);
		});

		self.$container.on('click', '.velocity-repeater-toggle', function(event) {
			event.preventDefault();
			var $item = $(this).closest('.velocity-repeater-item');
			self.toggleItem($item);
		});

		self.$container.on('input change', '[data-field]', function() {
			var $field = $(this);
			if ($field.is('[data-summary-field]')) {
				self.updateSummary($field.closest('.velocity-repeater-item'));
			}
			self.sync();
		});
	};

	VelocityRepeater.prototype.createItem = function(values) {
		var $item = $(this.template);
		values = values || {};

		$item.find('[data-field]').each(function() {
			var $field = $(this);
			var key = $field.data('field');
			var defaultValue = $field.data('default') || '';
			var value = Object.prototype.hasOwnProperty.call(values, key) ? values[key] : defaultValue;
			$field.val(value);
		});

		return $item;
	};

	VelocityRepeater.prototype.addItem = function(values, after, openByDefault) {
		var $item = this.createItem(values);

		if (after && after.length) {
			$item.insertAfter(after);
		} else {
			this.$itemsWrap.append($item);
		}

		this.toggleItem($item, !!openByDefault);
		this.updateSummary($item);
		this.sync();
		return $item;
	};

	VelocityRepeater.prototype.readItem = function($item) {
		var values = {};

		$item.find('[data-field]').each(function() {
			var $field = $(this);
			values[$field.data('field')] = $field.val();
		});

		return values;
	};

	VelocityRepeater.prototype.toggleItem = function($item, forceOpen) {
		var shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : $item.hasClass('is-collapsed');

		if (shouldOpen) {
			$item.removeClass('is-collapsed');
			$item.find('.velocity-repeater-toggle').attr('aria-expanded', 'true');
		} else {
			$item.addClass('is-collapsed');
			$item.find('.velocity-repeater-toggle').attr('aria-expanded', 'false');
		}
	};

	VelocityRepeater.prototype.updateSummary = function($item) {
		var summary = this.defaultLabel || '';
		var $field = $item.find('[data-summary-field]').first();

		if ($field.length) {
			var value;
			if ($field.is('select')) {
				value = ($field.find('option:selected').text() || '').toString().trim();
				if (!value) {
					value = ($field.val() || '').toString().trim();
				}
			} else {
				value = ($field.val() || '').toString().trim();
			}

			if (value) {
				summary = value;
			}
		}

		if (!summary) {
			summary = this.defaultLabel || '--Pilih Bank--';
		}

		$item.find('.velocity-repeater-item-label').text(summary);
	};

	VelocityRepeater.prototype.updateSummaries = function() {
		var self = this;
		this.$itemsWrap.children('.velocity-repeater-item').each(function() {
			self.updateSummary($(this));
		});
	};

	VelocityRepeater.prototype.sync = function() {
		var data = [];

		this.$itemsWrap.children('.velocity-repeater-item').each(function() {
			var row = {};
			var isEmpty = true;

			$(this).find('[data-field]').each(function() {
				var $field = $(this);
				var key = $field.data('field');
				var value = $field.val();

				row[key] = value;

				if (value) {
					isEmpty = false;
				}
			});

			if (!isEmpty) {
				data.push(row);
			}
		});

		this.$store.val(JSON.stringify(data)).trigger('change');
	};

	function initControl(control) {
		if (!control || control.velocityRepeaterInitialized) {
			return;
		}

		control.velocityRepeaterInitialized = true;
		new VelocityRepeater(control);
	}

	api.controlConstructor.velocity_repeater = api.Control.extend({
		ready: function() {
			initControl(this);
		}
	});

	api.bind('ready', function() {
		api.control.each(function(control) {
			if ('velocity_repeater' === control.params.type) {
				initControl(control);
			}
		});
	});
})(wp.customize, jQuery);
