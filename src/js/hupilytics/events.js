jQuery(function () {
    jQuery('.hupi-recommendation [data-product-id]').each(function() {

        jQuery(this).find('a[href]').bind('click', function(e) {
            e.preventDefault();

            var productId = jQuery(this).closest('[data-product-id]').data('product-id');
            var productName = jQuery(this).closest('[data-product-id]').data('product-name');
            var endpoint = jQuery(this).closest('[data-endpoint]').data('endpoint');

            _paq.push(['trackEvent', 'Recommandation_HUPI', endpoint, productId, productName]);

            setTimeout(function() { window.location.href = jQuery(this).attr('href'); }, 500);
        });
    });
});