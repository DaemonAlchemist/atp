;(function ( $, window, document, undefined ) {
    var pluginName = "bannerRotate";
    var defaults = {
		bannerDisplayTime: 10000,
		bannerFadeTime: 800
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
			
			//Hide banners on first load
			elem.children().hide();
			elem.children(':first-child').addClass('banner-displayed');
			elem.children(':first-child').show();
			
			//Rotate banners
			setInterval(function() {
				var curBanner =  elem.children('.banner-displayed');
				var nextBanner = curBanner.next();
				if(nextBanner.length == 0) nextBanner = elem.children(':first-child');
				
				curBanner.removeClass('banner-displayed');
				curBanner.fadeOut({
					duration: options.bannerFadeTime,
					complete: function(){
						nextBanner.addClass('banner-displayed');
						nextBanner.fadeIn({
							duration: options.bannerFadeTime
						});
					}
				});
			}, options.bannerDisplayTime);
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
