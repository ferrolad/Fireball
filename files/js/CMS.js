if (!CMS) var CMS = {};

$.widget('ui.fireSlide', {

	/**
	 * button list object
	 * @var	jQuery
	 */
	_buttonList: null,

	/**
	 * number of items
	 * @var	integer
	 */
	_count: 0,

	/**
	 * item index
	 * @var	integer
	 */
	_index: 0,

	/**
	 * items
	 * @var jQuery
	 */
	_items: null,

	/**
	 * item container
	 * @var jQuery
	 */
	_itemContainer: null,

	/**
	 * timer object
	 * @var	interval
	 */
	timer: null,

	/**
	 * list of options
	 * @var	object
	 */
	options: {
		/* cycle interval in milliseconds */
		speed: 2000
	},

	/**
	 * elements width
	 * @var	integer
	 */
	width: 0,

	/**
	 * Creates a new instance of ui.FireSlide
	 */
	_create: function () {
		var items = this.element.children('div');
		this._itemContainer = $('<div />').appendTo(this.element).css('left', 0);
		items.appendTo(this._itemContainer);
		this._items = this._itemContainer.children('div');
		this._count = this._items.length;
		this._index = 0;
		var max_height = 0;

		this._width = this.element.innerWidth();
		this._items.each($.proxy(function (index, item) {
			$(item).css({
				left: (index * (40 + this._width)),
				width: this._width
			}).show();
		}, this));

		//create button list & calculate max height
		this._buttonList = $('<ul class="slideshowButtonList" />').appendTo(this.element);

		for (var $i = 0; $i < this._count; $i++) {
			if ($(this._items.get($i)).outerHeight() >= max_height) max_height = $(this._items.get($i)).outerHeight();
			var $icon = $('<li><a><span class="icon icon16 icon-circle"></span></a></li>').click($.proxy(this._click, this)).appendTo(this._buttonList);
			if ($i == 0) $icon.addClass('active');
		}
		this.element.css('height', max_height);

		//handle resize
		$(window).resize($.proxy(this._resize, this));

		//start slider
		this._timer = setInterval($.proxy(this.slideTo, this), this.options.speed);
	},

	/**
	 * manual slide via click
	 */
	_click: function (event) {
		//stop slider
		clearInterval(this._timer);

		//handle click
		event.preventDefault();
		console.log('click');
		console.log($(event.currentTarget).index());
		this.slideTo($(event.currentTarget).index())

		//restart slider
		this._timer = setInterval($.proxy(this.slideTo, this), this.options.speed);
	},

	/**
	 * handles window resize
	 */
	_resize: function () {
		var max_height = 0;

		// calc max-height again
		for (var $i = 0; $i < this._count; $i++) {
			if ($(this._items.get($i)).outerHeight() >= max_height) max_height = $(this._items.get($i)).outerHeight();
		}
		this.element.css('height', max_height);

		//set new width
		this._width = this.element.innerWidth();

		//resize items
		this._items.each($.proxy(function (index, item) {
			$(item).css({
				left: (index * (40 + this._width)),
				width: this._width
			});
		}, this));

		//reset slider
		this._index = 0;
		this.slideTo();
	},

	/**
	 * slides to a specific index
	 */
	slideTo: function (index) {

		if (typeof index !== 'undefined') this._index = index;
		else this._index = this._index + 1;
		if (this._index == this._count) this._index = 0;

		this._buttonList.find('.active').removeClass('active');
		$(this._buttonList.children().get(this._index)).addClass('active');
		this._itemContainer.css({
			'left': (this._index * (40 + this._width) * -1)
		});
	}
});

CMS.Page = { };

/**
 * Shows a dialog to add a new page.
 */
CMS.Page.Add = Class.extend({
	_pageID: 0,
	_cache: {},
	_dialog: null,

	init: function (pageID) {

		this._pageID = pageID;

		// bind click event
		$('#pageAddButton').click($.proxy(this._click, this));
	},

	_click: function (event) {
		event.preventDefault();
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});
		this._proxy.setOption('data', {
			actionName: 'getAddDialog',
			className: 'cms\\data\\page\\PageAction',
			objectIDs: [this._pageID]
		});
		this._proxy.sendRequest();
	},

	_show: function (pageID) {
		this._dialog = $('<div id="pageAddDialog">' + this._cache[pageID] + '</div>').appendTo(document.body);

		this._dialog.wcfDialog({
			title: WCF.Language.get('cms.page.add')
		});
		//bind submit event
		this._dialog.find('input[type=submit]').click($.proxy(this._submit, this));
	},

	_submit: function (event) {
		event.preventDefault();
		parameters = {};

		//get all inputs
		$('#pageAddForm input, #pageAddForm textarea, #pageAddForm select').each(function (index) {
			var input = $(this);
			if (input.attr('type') != 'checkbox') {
				parameters[input.attr('name')] = $.trim(input.val())
			}
			else parameters[input.attr('name')] = input.prop('checked') ? 1 : 0;
		});

		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._executed, this)
		});
		this._proxy.setOption('data', {
			actionName: 'frontendCreate',
			className: 'cms\\data\\page\\PageAction',
			objectIDs: [this._pageID],
			parameters: {
				data: parameters
			}
		});
		this._proxy.sendRequest();
	},

	_executed: function (data, textStatus, jqXHR) {
		if (typeof this._cache[data.returnValues.errors] === 'undefined') {
			var $notification = new WCF.System.Notification(WCF.Language.get('wcf.global.success'));
			$notification.show(function () {
				window.location = data.returnValues.link;
			});
		}
	},

	_success: function (data, textStatus, jqXHR) {
		this._cache[data.returnValues.pageID] = data.returnValues.template;
		this._show(data.returnValues.pageID);
	}
});

CMS.Page.ContentTypes = Class.extend({
	_pageID: 0,
	_proxy: null,
	_initialized: 0,
	_cache: {},

	init: function (pageID) {
		this._pageID = pageID;
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});
		this._proxy.setOption('data', {
			actionName: 'getContentTypes',
			className: 'cms\\data\\page\\PageAction',
			objectIDs: [this._pageID],
			parameters: {
				position: 'both'
			}
		});
		this._proxy.sendRequest();
		$('body').append($('<a id="contentAddButton"><span class="icon icon16 icon-chevron-right"></span></a>'));
		$('body').append($('<div id="contentRibbon" />'));
		$('#contentAddButton').click($.proxy(this._openSidebar, this));
	},

	_success: function (data, textStatus, jqXHR) {
		this._cache[data.returnValues.pageID] = data.returnValues.template;
		$('#contentRibbon').append(data.returnValues.template);
	},

	_openSidebar: function (event) {
		event.preventDefault();
		if (!$('#contentRibbon').hasClass('open')) {
			if (!this._initialized) {
				new CMS.Content.Dragging(this._pageID);
				this._initialized = 1;
			}
			$('#contentRibbon').addClass('open');
			$('#contentAddButton').addClass('open').children('span').addClass('icon-chevron-left').removeClass('icon-chevron-right');
			$('body').addClass('ribbonOpen');
		}
		else {
			$('#contentRibbon').removeClass('open');
			$('#contentAddButton').removeClass('open').children('span').removeClass('icon-chevron-left').addClass('icon-chevron-right');
			$('body').removeClass('ribbonOpen');
		}
	},
});

CMS.Content = {};

CMS.Content.Dragging = Class.extend({

	_pageID: 0,

	init: function (pageID) {
		this._pageID = pageID;
		var dropButton = '<div class="ui-droppable"></div>';
	
		//no parent drag area
		$('.userNotice').after($(dropButton));

		//add ui-droppable class to all content, because we support infinte nesting
		$("div[id^='cmsContent']").each(function () {
			//add margins and paddings to all cms contents to show where exactly the drop-zone is
			$(this).addClass('ui-droppable').css({
				'padding': '10px',
				'marginTop': '10px'
			}).prepend('<div class="cmsOptions"><span class="badge red infoBadge">' + $(this).data('contentType') + '</span></div>');
			//add nested sortable to cms contents
			$(this).nestedSortable({ connectWith: ".sideMenu", listType: "div[id^='cmsContent']" });
		});

		$('.draggable').draggable({
			cursor: "move",
			helper: "clone",
			revert: "invalid",
			containment: "document",
			drag: $.proxy(this._drag, this)
		});
		$('.ui-droppable').droppable({
			activeClass: "droppable-state-active",
			greedy: true,
			drop: $.proxy(this._drop, this)
		});
	},

	_drop: function (event, ui) {
		$(event.target).append('<div class="draggedContent ' + ui.draggable.attr('id') + '" />')
		var type = ui.draggable.attr('id');
		var position = 'body';
		if (typeof $(event.target).attr('id') !== 'undefined' && $(event.target).attr('id').match('^cmsContent')) {
			var data = $(event.target).attr('id').replace('cmsContent', '');
		}
		var parentID = 0;
		if (typeof data !== 'undefined') {
			parentID = data;
		}
		//call add form
		new CMS.Content.AddForm(this._pageID, position, type, parentID);
	}
}),

CMS.Content.AddForm = Class.extend({

	_pageID: 0,
	_cache: {},
	_dialog: null,

	init: function (pageID, position, type, parentID) {
		this._pageID = pageID;

		//show content settings dialog
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});
		this._proxy.setOption('data', {
			actionName: 'getAddDialog',
			className: 'cms\\data\\content\\ContentAction',
			objectIDs: [],
			parameters: {
				position: position,
				type: type,
				parentID: parentID,
				pageID: this._pageID
			}
		});
		this._proxy.sendRequest();
	},

	_show: function (pageID) {
		this._dialog = $('<div id="contentAddDialog">' + this._cache[pageID] + '</div>').appendTo(document.body);

		this._dialog.wcfDialog({
			title: WCF.Language.get('cms.content.add')
		});

		//bind submit event
		this._dialog.find('input[type=submit]').click($.proxy(this._submit, this));
	},

	_executed: function (data, textStatus, jqXHR) {
		if (typeof data.returnValues.errors === 'undefined') {
			this._dialog.wcfDialog('close');
			var $notification = new WCF.System.Notification(WCF.Language.get('wcf.global.success'));
			$notification.show(function () {
				window.location = location;
			});
		}

	},

	_submit: function (event) {
		event.preventDefault();
		parameters = {};

		//get all inputs
		parameters['contentData'] = {};
		$('#contentAddForm input, #contentAddForm textarea, #pageAddForm select').each(function (index) {
			var input = $(this);
			if (input.attr('type') != 'checkbox') {
				parameters[input.attr('name')] = $.trim(input.val())
			}
			else parameters[input.attr('name')] = input.prop('checked') ? 1 : 0;
			//fill contentData arra
			if (typeof input.attr('name') !== 'undefined' && input.attr('name').match('^contentData')) {
				delete parameters[input.attr('name')];
				//watch for wysiwyg
				if ($('#contentAddForm .redactor-box').length != 0) {
					//on toggling code, bbcode is saved to textarea
					$('#' + input.attr('id')).redactor('code.toggle');
				}
				parameters['contentData'][input.attr('id')] = $.trim(input.val());
			}
		});
		console.log(parameters);

		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._executed, this)
		});
		this._proxy.setOption('data', {
			actionName: 'frontendCreate',
			className: 'cms\\data\\content\\ContentAction',
			objectIDs: [],
			parameters: {
				data: parameters
			}
		});
		this._proxy.sendRequest();
	},

	_success: function (data, textStatus, jqXHR) {
		this._cache[this._pageID] = data.returnValues.template;
		this._show(this._pageID);
	}
})
