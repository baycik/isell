$.ajaxPrefilter(function (settings) {
    if(settings.url === '../Stock/matchesListFetch') {
        settings.url = '../AttributeManager/matchesListFetch';
        settings.dataFilter = function (responseText) {
            let response = App.json(responseText);
            if (response && response.groupped_filter && App.stock.filter) {
                App.stock.filter.setData(response.groupped_filter);
            }
            if (response && response.matches) {
                return JSON.stringify(response.matches);
            }
            return '';
        };
    }
});