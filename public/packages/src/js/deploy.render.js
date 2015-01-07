;
var renderWaitProcess = function (element) {
    React.render(
        <WaitProgressComponent />,
        element
    );
};

var renderRegisterForm = function (element, data) {
    React.render(
        <RegisterFormComponent data={data}/>,
        element
    );
};

var renderSideNavBar = function (element, menuData, path) {
    //console.log(location.pathname);
    var hasActive = function (data) {
        for (var i in data) {
            if (data[i].url == path) {
                data[i].active = true;
                return true;
            }
            if (data[i].children != undefined) {
                if (hasActive(data[i].children)) {
                    return true;
                }
            }
        }
        return false;
    }
    hasActive(menuData);
    React.render(
        <SideBarNavComponent data={menuData}/>,
        element
    );
};

var renderRoleForm = function (element, callback) {
    React.render(<RoleAddFormComponent reloadCallback={callback}/>, element);
};


var renderRoleModal = function (element, data, updateCallback) {
    $(element).html("");
    var c = React.render(<RoleEditModalComponent data={data} updateCallback={updateCallback}/>, element);
    c.handleToggle();
};

;
