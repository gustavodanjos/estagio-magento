define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'mage/translate'
], function (_, registry, Abstract, $t) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'Webjump_Gustavo/form/element/product-picker',
            targetField: 'product_id',
            modalTarget: 'webjump_gustavo_review_form.webjump_gustavo_review_form.product_select_modal',
            placeholder: $t('Nenhum produto selecionado')
        },

        openModal: function () {
            var modal = this.getModal();

            if (!modal) {
                console.warn('Webjump_Gustavo: modal de produtos não encontrado no registro.');
                return;
            }

            modal.openModal();
        },

        getModal: function () {
            var targets = [
                this.modalTarget,
                'ns = webjump_gustavo_review_form, index = product_select_modal'
            ];

            return _.reduce(targets, function (modal, target) {
                return modal || (target ? registry.get(target) : null);
            }, null);
        },

        applySelection: function (id, label) {
            this.value(label);
            this.targetComponent().value(id);
            this.error(null);
        },

        validate: function () {
            var result = this._super(),
                target = this.targetComponent();

            if (!target || !target.value()) {
                this.error($t('Selecione um produto pelo botão "Selecionar produto".'));
                this.source.set('params.invalid', true);
                return {valid: false, target: this};
            }

            return result;
        },

        targetComponent: function () {
            return registry.get(this.parentName ? this.parentName + '.' + this.targetField : this.targetField);
        }
    });
});
