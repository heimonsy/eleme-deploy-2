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

var renderHostTypeCatalog = function (element, type, data, updateCallback) {
    $(element).html("");
    var c = React.render(<RoleEditModalComponent data={data} updateCallback={updateCallback}/>, element);
    c.handleToggle();
};

var renderHostTypeCatalogModal = function (element, type, data, updateCallback) {
    $(element).html("");
    var c = React.render(<HostTypeCatalogEditComponent id={data.id} type={type} data={data} updateCallback={updateCallback}/>, element);
    c.handleToggle();
};

var renderSiteModal = function (element, type, data, updateCallback) {
    $(element).html("");
    var c = React.render(<SiteEditComponent id={data.id} type={type} data={data} updateCallback={updateCallback}/>, element);
    c.handleToggle();
};

var renderRolePermissionModal = function (element, data, updateCallback) {
    $(element).html("");
    var c = React.render(<RolePermissionModal data={data} updateCallback={updateCallback}/>, element);
    c.handleToggle();
};

var renderUserRoleAddModal = function (element, data, updateCallback) {
    $(element).html("");
    var c = React.render(<UserRoleAddModal data={data} updateCallback={updateCallback}/>, element);
    c.handleToggle();
};

var renderSiteConfig = function (element, data) {
    React.render(<SiteConfigComponent data={data} />, element);
};

var renderSiteDeployConfig = function (element, data) {
    React.render(<SiteDeployConfigComponent data={data} />, element);
};

var renderSiteHostType = function (element, type, data, updateCallback) {
    $(element).html("");
    var c = React.render(<SiteHostTypeEditModal data={data} type={type} updateCallback={updateCallback} />, element);
    c.handleToggle();
};

var renderSiteHosts = function (element, type, data, updateCallback) {
    $(element).html("");
    var c = React.render(<SiteHostEditModal data={data} type={type} updateCallback={updateCallback} />, element);
    c.handleToggle();
};


var renderNewBuildForm = function (element, data, updateCallback) {
    $(element).html("");
    var c = React.render(<NewBuildForm data={data} updateCallback={updateCallback} />, element);
};


var renderSystemConfigureForm = function (element) {
    React.render(<BasicConfigureForm />, element);
};
;
