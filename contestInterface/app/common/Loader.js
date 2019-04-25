//
// Loader
//

var Loader = function(base, log_fn) {
    this.log = log_fn;
    this.base = base;
    this.queue = [];
    this.parts = [];
    this.n_loaded = 0;
    this.n_total = 0;
};
Loader.prototype.version = 1.2;
Loader.prototype.add = function(items) {
    this.queue = this.queue.concat(items);
    this.n_total += items.length;
};
Loader.prototype.assemble = function() {
    var self = this;
    self.log("A");
    setTimeout(function() {
        var data = self.parts.join("");
        for (var i = 0; i < window.config.imagesURLReplacements.length; i++) {
            data = data.replace(
                new RegExp(window.config.imagesURLReplacements[i][0], "g"),
                window.config.imagesURLReplacements[i][1]
            );
        }
        if (window.config.upgradeToHTTPS) {
            if (window.config.upgradeToHTTPS.length) {
                for (var i = 0; i < window.config.upgradeToHTTPS.length; i++) {
                    var uthDomain = window.config.upgradeToHTTPS[i];
                    data = data.replace(
                        new RegExp("http://" + uthDomain, "g"),
                        "https://" + uthDomain
                    );
                }
            } else {
                data = data.replace(/http:\/\//g, "https://");
            }
        }
        self.promise.resolve(data);
    }, 100);
};
Loader.prototype.load_next = function() {
    var self = this;
    if (self.queue.length === 0) {
        this.assemble();
    } else {
        var item = self.queue.shift();
        var url = self.base + item;
        self.start_time = new Date().getTime();
        $.ajax(self.base + item, { dataType: "text", global: false })
            .done(function(data, textStatus, xhr) {
                try {
                    var delta = new Date().getTime() - self.start_time;
                    self.n_loaded += 1;
                    // speed of last download in b/ms, or kb/s (data.length is approximately in bytes)
                    var last_speed = (data.length * 8) / delta;
                    // factor so that delay is around 4s at 10kb/s, 0.4s at 100kb/s
                    // multiplying by 1+rand() so that users in the same room don't wait the same time, causing bottlenecks
                    var k = 30000 * (1 + Math.random());
                    var delay = Math.round(k / last_speed);
                    if (delay > 5000) {
                        // no more than 5s waiting
                        delay = 5000;
                    }
                    self.log(
                        Math.round((self.n_loaded * 100) / self.n_total) + "%"
                    );
                    self.parts.push(data);
                    setTimeout(function() {
                        self.load_next();
                    }, delay);
                } catch (e) {
                    self.promise.reject(e);
                }
            })
            .fail(function(xhr, textStatus, err) {
                self.log(textStatus);
                self.promise.reject(textStatus);
            });
    }
};
Loader.prototype.run = function() {
    var self = this;
    self.log("v" + self.version);
    this.promise = jQuery.Deferred(function() {
        $.ajax(self.base + "index.txt", { dataType: "text", global: false })
            .done(function(data, textStatus, xhr) {
                var index = data.replace(/^\s+|\s+$/g, "").split(/\s+/);
                index = self.shuffleArray(index);
                self.add(index);
                self.log("I");
                self.load_next();
            })
            .fail(function(xhr, textStatus, err) {
                self.promise.reject(textStatus);
            });
    });
    return self.promise;
};
Loader.prototype.shuffleArray = function(values) {
    var nbValues = values.length;
    for (var iValue = 0; iValue < nbValues; iValue++) {
        var pos =
            iValue + (Math.round(1000 * Math.random()) % (nbValues - iValue));
        var tmp = values[iValue];
        values[iValue] = values[pos];
        values[pos] = tmp;
    }
    return values;
};


export default Loader;