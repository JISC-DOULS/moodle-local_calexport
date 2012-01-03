M.local_calexport={
    Y : null,
    transaction : [],
    init_link_update : function(Y, link, manualcopy, autocopy) {
        this.Y = Y;
        //This script will update calendar links based on form selection
        //means user doesn't need to submit page to get updated links

        //First check for selection form and ready state
        Y.on("contentready", function () {
            //Add update event to every radio button in form
            Y.on("click", updatelink, "#calexport_options input[type='radio'], #calexport_options input[type='checkbox']");
        }, "#calexport_options");
        //Make google link open in new window/tab
        Y.on("click", function(e){
            window.open(this.get('href'), '_blank');
            e.preventDefault();
        }, '#calexport_links #google_link');

        //Make feed link copy to clipboard
        Y.on("click", function(e){
            if (window.clipboardData && clipboardData.setData) {
                if (!clipboardData.setData('text', Y.one("#ical_link").get('href'))) {
                    message(manualcopy, e, function(){Y.one('#feedfield').focus();Y.one('#feedfield').select();});
                } else {
                    message(autocopy, e);
                };
            } else {
                message(manualcopy, e, function(){Y.one('#feedfield').focus();Y.one('#feedfield').select();});
            }
            e.preventDefault();
        }, '#calexport_links #feed_link');

        var updatelink = function(e) {
            var ical = Y.one("#ical_link");

            var google = Y.one("#google_link");

            var icaltext = Y.one('#feedfield');

            var url = link+'?';

            //get all inputs (inc hidden etc) and work out query string
            Y.all("#calexport_options input").each(
                    function(node){
                        var name = this.get('name');
                        var type = this.get('type');
                        if (name != '') {
                            if (type == 'radio') {
                                if (this.get('checked') != true) {
                                    return;
                                }
                            } else if (type == 'checkbox') {
                                url += "&" + name + "=" + this.get("checked");
                                return;
                            }
                            url += "&" + name + "=" + this.get("value");
                        }
                    }
            );
            ical.set("href", encodeURI(url));
            icaltext.set("value", url);

            if (google) {
                gurl = google.get("href").split("=")[0];
                google.set("href", gurl + "=" + escape(url));
            }
        };
    }
};

function message(message, e, okfunc) {
    try {
    YUI().use('node', 'yui2-resize', 'yui2-dragdrop', 'yui2-container', 'yui2-button',
            'yui2-layout', 'yui2-event', function(Y) {
        // Instantiate a Panel from script
        var errordiv = Y.one('#error');
        if (!errordiv) {
            Y.one('body').append('<div id="error"> </div>');
        }
        var handlecancel = function() {
            this.cancel();
            okfunc();
        };
        var mybuttons = [
            { text: M.str.moodle.ok, handler: handlecancel, isDefault: true }
        ];
        var params = {
                width: "160px",
                draggable: true,
                x: e.clientX,
                y: e.clientY,
                zIndex: 999,
                fixedcenter: true
        };
        var mydialog = new Y.YUI2.widget.Dialog('error', params);
        mydialog.cfg.queueProperty("buttons", mybuttons);
        mydialog.setBody(message);
        mydialog.render();
        mydialog.show();
    });
    } catch(err) {
        alert(message);
        okfunc();
    }
}
