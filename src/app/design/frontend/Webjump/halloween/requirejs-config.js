var config = {
    paths: {
        'halloween-fx': 'js/halloween-fx',
        'halloween-mode': 'js/halloween-mode',
        'minicart-mixin': 'js/minicart-mixin'
    },
    deps: [
        'halloween-fx/init',
        'halloween-mode'
    ],
    config: {
        mixins: {
            'Magento_Checkout/js/view/minicart': {
                'minicart-mixin': true
            }
        }
    }
};
