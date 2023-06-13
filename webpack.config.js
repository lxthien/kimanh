var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('web/build/')
    .setPublicPath('/build')
    .autoProvidejQuery()
    .autoProvideVariables({
        "window.Bloodhound": require.resolve('bloodhound-js'),
        "jQuery.tagsinput": "bootstrap-tagsinput"
    })
    .enableSassLoader()
    .enableVersioning(false)
    .createSharedEntry('js/common', ['jquery'])
    .addEntry('js/app', './web/assets/js/front/app.js')
    .addEntry('js/admin', './web/assets/js/admin/admin.js')
    .addEntry('js/search', './web/assets/js/admin/search.js')
    .addEntry('js/login', './web/assets/js/admin/login.js')
    .addStyleEntry('css/app', ['./web/assets/scss/front/app.scss'])
    .addStyleEntry('css/homepage', ['./web/assets/scss/front/homepage.scss'])
    .addStyleEntry('css/news', ['./web/assets/scss/front/news.scss'])
    .addStyleEntry('css/list', ['./web/assets/scss/front/list.scss'])
    .addStyleEntry('css/contact', ['./web/assets/scss/front/contact.scss'])
    .addStyleEntry('css/amp', ['./web/assets/scss/front/amp.scss'])
    .addStyleEntry('css/new', ['./web/assets/scss/front/new.scss'])
    .addStyleEntry('css/admin', ['./web/assets/scss/admin/admin.scss'])
;

module.exports = Encore.getWebpackConfig();
