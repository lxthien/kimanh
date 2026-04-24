var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .autoProvidejQuery()
    .autoProvideVariables({
        "window.Bloodhound": require.resolve('bloodhound-js'),
        "jQuery.tagsinput": "bootstrap-tagsinput"
    })
    .enableSassLoader()
    .enableVersioning(Encore.isProduction())
    .cleanupOutputBeforeBuild(Encore.isProduction())
    .createSharedEntry('js/common', ['jquery'])
    .addEntry('js/app', './public/assets/js/front/app.js')
    .addEntry('js/admin', './public/assets/js/admin/admin.js')
    .addEntry('js/search', './public/assets/js/admin/search.js')
    .addEntry('js/login', './public/assets/js/admin/login.js')
    .addEntry('js/menu-editor', './public/assets/js/admin/menu-editor.js')
    .addStyleEntry('css/app', ['./public/assets/scss/front/app.scss'])
    .addStyleEntry('css/first', ['./public/assets/scss/front/first.scss'])
    .addStyleEntry('css/homepage', ['./public/assets/scss/front/homepage.scss'])
    .addStyleEntry('css/news', ['./public/assets/scss/front/news.scss'])
    .addStyleEntry('css/list', ['./public/assets/scss/front/list.scss'])
    .addStyleEntry('css/contact', ['./public/assets/scss/front/contact.scss'])
    .addStyleEntry('css/admin', ['./public/assets/scss/admin/admin.scss'])
;

module.exports = Encore.getWebpackConfig();
