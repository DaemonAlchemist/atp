;(function ( $, window, document, undefined ) {
    var pluginName = "fileInputStyled";
    var defaults = {
		includeInput: true,
		includeButton: true
	};

    function Plugin( element, options ) {
        this.element = element;

        this.options = $.extend( {}, defaults, options );

        this._defaults = defaults;
        this._name = pluginName;

        this.init();
    }

    Plugin.prototype = {

        init: function() {
			var elem = $(this.element);
			var options = this.options;
			
			//Create the replacement inputs
			var inputId = elem.attr("id") + "-input";
			elem.before("<input type=\"text\" id=\"" + inputId + "\"/>");
			var buttonId = elem.attr("id") + "-button";
			elem.before("<button id=\"" + buttonId + "\">Browse...</button>");
			
			//Update new element classes
			$("#" + inputId).addClass(elem.attr("class"));
			$("#" + buttonId).addClass(elem.attr("class"));
			
			//Hide the original file input
			elem.wrap("<div style=\"display: none;\">");
			
			//Update the new text input when the file value changes
			elem.change(function(){
				$("#" + inputId).val(elem.val());
			});
			
			//Trigger the file input when the button or text input are clicked
			$("#" + buttonId + ", #" + inputId).click(function(){
				elem.click();
				return false;
			});
        },
    };

    $.fn[pluginName] = function ( options ) {
        return this.each(function () {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" + pluginName, new Plugin( this, options ));
            }
        });
    };

})( jQuery, window, document );
