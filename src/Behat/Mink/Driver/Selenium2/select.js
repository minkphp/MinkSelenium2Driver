// Function to triger an event. Cross-browser compliant. See http://stackoverflow.com/a/2490876/135494
var triggerEvent = function (element, eventName) {
    var event;
    if (document.createEvent) {
        event = document.createEvent('HTMLEvents');
        event.initEvent(eventName, true, true);
    } else {
        event = document.createEventObject();
        event.eventType = eventName;
    }

    event.eventName = eventName;

    if (document.createEvent) {
        element.dispatchEvent(event);
    } else {
        element.fireEvent('on' + event.eventType, event);
    }
}

var node = {{ELEMENT}}
if (node.tagName == 'SELECT') {
    var i, l = node.length;
    for (i = 0; i < l; i++) {
        if (node[i].value == '{{valueEscaped}}') {
            node[i].selected = true;
        } else if (!{{multipleJS}}) {
            node[i].selected = false;
        }
    }
    triggerEvent(node, 'change');

} else {
    var nodes = window.document.getElementsByName(node.getAttribute('name'));
    var i, l = nodes.length;
    for (i = 0; i < l; i++) {
        if (nodes[i].getAttribute('value') == '{{valueEscaped}}') {
            node.checked = true;
        }
    }
    if (node.tagName == 'INPUT') {
        var type = node.getAttribute('type');
        if (type == 'radio') {
            triggerEvent(node, 'change');
        }
    }
}