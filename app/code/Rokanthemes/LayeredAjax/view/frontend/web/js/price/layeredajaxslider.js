define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'jquery/ui',
    'Rokanthemes_LayeredAjax/js/layeredajax'
], function($, ultil) {
    "use strict";

    $.widget('rokanthemes.layeredAjaxSlider', $.rokanthemes.layeredAjax, {
        options: {
            sliderElement: '#layered_ajax_price_slider',
            textElement: '#layered_ajax_price_text'
        },
        _create: function () {
            var self = this;
            $(this.options.sliderElement).slider({
                min: self.options.minValue,
                max: self.options.maxValue,
                values: [self.options.selectedFrom, self.options.selectedTo],
                slide: function( event, ui ) {
                    self.displayText(ui.values[0], ui.values[1]);
                },
                change: function(event, ui) {
                    self.ajaxSubmit(self.getUrl(ui.values[0], ui.values[1]));
                }
            });
            this.displayText(this.options.selectedFrom, this.options.selectedTo);
        },

        getUrl: function(from, to){
            return this.options.ajaxUrl.replace(encodeURI('{price_start}'), from).replace(encodeURI('{price_end}'), to);
        },

        displayText: function(from, to){
            $(this.options.textElement).html('<span class="from_fixed">'+this.formatPrice(from) + '</span><span class="space_fixed"> - </span><span class="to_fixed">' + this.formatPrice(to)+'</span>');
        },

        formatPrice: function(value) {
            return ultil.formatPrice(value, this.options.priceFormat);
        }
    });

    return $.rokanthemes.layeredAjaxSlider;
});
