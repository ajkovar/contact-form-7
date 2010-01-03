(function($){

	$.widget("ui.popup", {

		_init: function() {
			/*
			 * Remove data so multiple instances may exist per node
			 */
			//this.element.removeData("popup");
			
			(this.options.autoOpen && this.open());
		},
		
		open: function() {
			var divFormError = document.createElement('div');
			var formErrorContent = document.createElement('div');
			var arrow = document.createElement('div');
			var elem = this.element;
			var displayArrow = this.options.arrow;
			var promptText = this.options.text;
			
			$(divFormError).addClass("formError")
			
			this._attachPopup(divFormError);
			
			$(formErrorContent).addClass("formErrorContent")
			$(arrow).addClass("formErrorArrow")
	
	
			if (elem[0] == document || elem[0] == window)
				$("body").append(divFormError);
			else this.element.append(divFormError);
			
			$(divFormError).append(arrow);
			$(divFormError).append(formErrorContent);
	
			if(displayArrow)
				$(arrow).html('<div class="line10"></div><div class="line9"></div><div class="line8"></div><div class="line7"></div><div class="line6"></div><div class="line5"></div><div class="line4"></div><div class="line3"></div><div class="line2"></div><div class="line1"></div>')
				
			$(formErrorContent).html(promptText);
			
			if(this.options)
				$(divFormError).css(this.options);
			
			var my = this.options.my; 
			
			if(!my)
				my = displayArrow ? "left bottom" : "center";
			
			$(divFormError).position({
				of: elem[0],
				my: my,
				at: this.options.at,
				//	offset: [x, y],
				collision: this.options.collision
			});
				
			$(divFormError).fadeTo("fast",0.9);
			
		},
		
		_attachPopup: function(popupDiv) {
			this.popupDiv = $(popupDiv);
			var attachedPopups = this.element.data("popupDiv");
			attachedPopups = attachedPopups || [];
			attachedPopups.push($(popupDiv));
			this.element.data("popupDiv", attachedPopups);
		},
		
		close: function() {
			var self = this;
			if(self.popupDiv)
				self._close(self.popupDiv);
			else if(self.element.data("popupDiv"))
				$.each(self.element.data("popupDiv"), function(){self._close(this)});
			
			
		},
		
		_close : function(closingPrompt) {
			closingPrompt.fadeOut("fast",function(){
				closingPrompt.remove()
			});
			this.element.removeData("popup");
		}
	});
	
	$.extend($.ui.popup, {
		version: "1.0",
		defaults: {
			text: "",
			arrow: true,
			at: "center",
			collision:"none",
			autoOpen: true
			
		},
	});
	
}(jQuery));
