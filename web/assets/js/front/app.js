'use strict';

var $ = require('jquery');

require('bootstrap-sass');

var news = require('./pages/news');
var global = require('./global/global');
var videoPopup = require('./video_popup');

var app = {
    init: function () {
        news.init();
        global.init();
        videoPopup.init();
    }
};

// initialize app
$(document).ready(function () {
    app.init();

    $('#nav').css('overflow', 'visible');
});