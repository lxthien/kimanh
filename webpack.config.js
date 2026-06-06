const crypto = require("crypto");
const crypto_orig_createHash = crypto.createHash;
crypto.createHash = algorithm => crypto_orig_createHash(algorithm == "md4" ? "sha256" : algorithm);

var Encore = require('@symfony/webpack-encore');
Encore
    .setOutputPath('web/build/')
    .setPublicPath('/build')
    .autoProvidejQuery()
    .autoProvideVariables({
        "window.Bloodhound": require.resolve('bloodhound-js'),
        "jQuery.tagsinput": "bootstrap-tagsinput"
    })
    .enableSassLoader((options) => {
        options.sassOptions = {
            quietDeps: true,
            silenceDeprecations: ['color-functions', 'global-builtin', 'import', 'slash-div', 'if-function', 'legacy-js-api']
        };
    })
    .enableVersioning(Encore.isProduction())
    .cleanupOutputBeforeBuild()
    .createSharedEntry('js/common', './web/assets/js/common.js')
    .addEntry('js/app', './web/assets/js/front/app.js')
    .addEntry('js/admin', './web/assets/js/admin/admin.js')
    .addEntry('js/search', './web/assets/js/admin/search.js')
    .addEntry('js/login', './web/assets/js/admin/login.js')
    .addStyleEntry('css/app', ['./web/assets/scss/front/app.scss'])
    .addStyleEntry('css/first', ['./web/assets/scss/front/first.scss'])
    .addStyleEntry('css/homepage', ['./web/assets/scss/front/homepage.scss'])
    .addStyleEntry('css/news', ['./web/assets/scss/front/news.scss'])
    .addStyleEntry('css/list', ['./web/assets/scss/front/list.scss'])
    .addStyleEntry('css/contact', ['./web/assets/scss/front/contact.scss'])
    .addStyleEntry('css/admin', ['./web/assets/scss/admin/admin.scss'])
    .addStyleEntry('css/ckeditor-content', ['./web/assets/scss/admin/ckeditor-content.scss'])
;

module.exports = Encore.getWebpackConfig();
