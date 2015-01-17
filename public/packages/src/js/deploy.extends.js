
String.prototype.isEmail = function () {
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(this.trim());
};

String.prototype.isEmpty = function () {
    return this.trim() == '';
};

var createUser = function (user) {

    user.isAdmin = function () {
        var tag  = null;
        return function () {
            if (tag === null) {
                tag = false;
                for (var i in this.roles) {
                    if (this.roles[i].is_admin_role == 1) {
                        tag = true;
                        break;
                    }
                }
            }
            return tag;
        };
    }();

    user.haveRole = function () {
        var role_ids = null;
        var role_names = null;
        return function (key) {
            if (role_ids === null) {
                role_ids = []; role_names = [];
                for (var i in this.roles) {
                    role_ids[this.roles[i].id] = 1;
                    role_names[this.roles[i].name] = 1;
                }
            }
            return role_ids[key] === 1 || role_names[key] == 1;
        }
    }();

    user.control = function () {
        var map = null;
        return function (action) {
            if (map == null) {
                map = [];
                for (var i in this.permissions) {
                    map[this.permissions[i]] = 1;
                }
            }

            return map[action] === 1;
        }
    }();

    return user;
};


var createSiteMenuList = function (sites) {
    var list = [];
    for (var i in sites) {
        list.push({
            url: '/site/' + sites[i].id,
            name: sites[i].name,
            protected : sites[i].access_protected
        });
    }

    return list;
};


var createTimeoutEvent= function () {
    var timeout = {
        timeout: null,
        clear: function () {
            if (this.timeout === null) {
                console.log("timeout is null");
            } else {
                window.clearTimeout(this.timeout);
            }
        }
    };
    return timeout;
};
