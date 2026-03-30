import template from './template.twig';

const { Component } = Shopware;

Component.register('admin-guard-index', {
    template,

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        currentRouteName() {
            return this.$route.name;
        },
    },
});
