/*
 * Search box element addon for Bear CMS
 * https://github.com/bearcms/search-box-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

/* global clientPackages */

var bearCMS = bearCMS || {};
bearCMS.search = bearCMS.search || (function () {

    var open = function () {
        clientPackages.get('modalWindows').then(function (modalWindows) {
            modalWindows.open('-bearcms-search-input');
        });
    };

    return {
        'open': open
    };

}());