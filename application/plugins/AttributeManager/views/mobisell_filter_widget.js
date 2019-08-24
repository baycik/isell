$.ajaxPrefilter(function (settings) {
    if(settings.url === '../Stock/matchesListFetch') {
        settings.url = '../AttributeManager/matchesListFetch';
        settings.dataFilter = function (responseText) {
            let response = App.json(responseText);
            if (response && response.groupped_filter) {
                console.log(response.groupped_filter);
            }
            if (response && response.matches) {
                return JSON.stringify(response.matches);
            }
            return '';
        };
    }
});



App.stock.filter = {
    tree: [],
    selected: {},
    last_expanded: [],
    init: function () {
        $('#attributes_container').accordion({
            duration: 0,
            exclusive: false,
            onOpen: function () {
                App.stock.filter.last_expanded.push($(this).data('attribute_id'));
            },
            onClose: function () {
                var closed = $(this).data('attribute_id');
                var is_in_list = App.stock.filter.isInList(App.stock.filter.last_expanded, closed);
                App.stock.filter.last_expanded.splice(is_in_list, 1);
            }
        });




        $("#filteeer_widget").load("../AttributeManager/view/?path=mobisell_filter_widget.html");
    },
    render: function (groupped_filter) {
        App.stock.filter.checked = [];
        App.stock.filter.list.forEach(function iter(attribute) {
            if (App.stock.filter.selected.includes(attribute.attribute_value_hash)) {
                attribute.is_selected = 'checked';
                App.stock.filter.checked.push(attribute);
            }
            Array.isArray(attribute.attribute_values) && attribute.attribute_values.forEach(iter)
                    ;
        });
        if (!App.stock.filter.checked.length) {
            App.stock.filter.selected = {};
        }
        App.renderTpl("attributes_container", {attributes: App.stock.filter.list});
        App.renderTpl("attributes_selected", {attributes_selected: App.stock.filter.checked});
        $('.stock-attributes-button .attributes-selected-length').html(App.stock.filter.selected.length);
        App.stock.filter.last_expanded.forEach(function (attribute_id) {
            $('#attribute_' + attribute_id).addClass('active');
            $('#content_attribute_' + attribute_id).addClass('active');
        });
    },
    select: function (node) {
        var checked = $(node).prop('checked');
        var attribute_value_hash = $(node).data('attribute_value_hash');
        if (checked) {
            App.stock.filter.add(attribute_value_hash);
        } else {
            App.stock.filter.remove(attribute_value_hash);
        }
        App.stock.entries.show_cart = false;
        App.stock.entries.load();
    },
    isInList: function (list, attribute_value_hash) {
        var i = 0;
        list.forEach(function (attribute, index) {
            if (attribute === attribute_value_hash) {
                i = index;
            }
        });
        return i;
    },
    add: function (attribute_value_hash) {
        App.stock.filter.selected[attribute_value_hash] = 1;
    },
    remove: function (e) {
        let attribute_value_hash = $(e.currentTarget).data('attribute_value_hash');
        if (App.stock.filter.selected[attribute_value_hash]) {
            delete App.stock.filter.selected[attribute_value_hash];
        }
    },
    clearSelected: function () {
        if (!App.stock.filter.selected.length) {
            return;
        }
        App.stock.filter.selected = {};
        //App.stock.entries.load();
    }
};