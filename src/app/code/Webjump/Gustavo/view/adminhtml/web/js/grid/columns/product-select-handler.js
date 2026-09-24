define([
    'underscore',
    'uiRegistry',
    'uiElement'
], function (_, registry, Element) {
    'use strict';

    return Element.extend({
        defaults: {
            formProviderName: 'webjump_gustavo_review_form.webjump_gustavo_review_form_data_source',
            listingProviderName: 'webjump_gustavo_product_listing.webjump_gustavo_product_listing_data_source',
            labelFieldName: 'webjump_gustavo_review_form.webjump_gustavo_review_form.general.product_label',
            modalTarget: 'webjump_gustavo_review_form.webjump_gustavo_review_form.product_select_modal'
        },

        selectProduct: function (actionIndex, recordId, action) {
            var item = this.findItem(recordId);

            if (!item || !item.entity_id) {
                console.error('webjump: product not found in chooser grid', recordId);
                return;
            }

            var label = item.name + ' (SKU: ' + item.sku + ') [#' + item.entity_id + ']',
                provider = registry.get(this.formProviderName),
                labelField = this.labelField(),
                modal = registry.get(this.modalTarget);

            if (provider) {
                provider.set('data.product_id', item.entity_id);
                provider.set('data.product_label', label);
            }

            if (labelField) {
                labelField.applySelection(item.entity_id, label);
            }

            if (modal) {
                modal.closeModal();
            }
        },

        findItem: function (recordId) {
            var listing = registry.get(this.listingProviderName),
                items = listing && listing.data ? listing.data.items : [];

            return _.find(items, function (item) {
                return String(item.entity_id) === String(recordId);
            });
        },

        labelField: function () {
            var field = registry.get(this.labelFieldName);

            if (!field) {
                field = registry.get('ns = webjump_gustavo_review_form, index = product_label');
            }

            return field;
        }
    });
});
