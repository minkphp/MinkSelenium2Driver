var node = {{ELEMENT}},
tagName = node.tagName;

if (tagName == 'INPUT' || 'TEXTAREA' == tagName) {
    var type = node.getAttribute('type');
    if (type == 'checkbox') {
        value = 'boolean:' + node.checked;
    } else if (type == 'radio') {
        var name = node.getAttribute('name');
        if (name) {
            var fields = window.document.getElementsByName(name);
            var i, l = fields.length;
            for (i = 0; i < l; i++) {
                var field = fields.item(i);
                if (field.checked) {
                    value = 'string:' + field.value;
                }
            }
        }
    } else {
        value = 'string:' + node.value;
    }
} else if (tagName == 'SELECT') {
    if (node.getAttribute('multiple')) {
        options = [];
        for (var i = 0; i < node.options.length; i++) {
            if (node.options[ i ].selected) {
                options.push(node.options[ i ].value);
            }
        }
        value = 'array:' + options.join(',');
    } else {
        var idx = node.selectedIndex;
        if (idx >= 0) {
            value = 'string:' + node.options.item(idx).value;
        } else {
            value = null;
        }
    }
} else {
    attributeValue = node.getAttribute('value');
    if (attributeValue != null) {
        value = 'string:' + attributeValue;
    } else if (node.value) {
        value = 'string:' + node.value;
    } else {
        return null;
    }
}

return value;