;

/*******************
 *
 * React Bootstrap Component Define
 *
 ********************/
var Input = ReactBootstrap.Input;
var Button = ReactBootstrap.Button;
var Modal = ReactBootstrap.Modal;
var OverlayMixin= ReactBootstrap.OverlayMixin;
var Alert = ReactBootstrap.Alert;


/*******************
 *
 * Components
 *
 ********************/

var BasicConfigureForm = React.createClass({
    handleChange: function (e) {
        var state = this.state;
        var t = e.target;
        state[t.name] = t.value;
        state[t.name + 'Error'] = false;
        state.alertType = null;
        this.setState(state);
    },

    handleSubmit: function () {
    },
    componentDidMount: function () {
    },

    getInitialState: function () {
        return {deploy_root : '', deploy_rootError: false};
    },
    render: function () {
        return (
            <form>
                <Input name="deploy_root" value={this.state.deploy_root} onChange={this.handleChange} bsStyle={this.state.deploy_rootError ? 'danger' : null} type="text" label="项目根目录" placeholder="Deploy Root"/>
                <Button></Button>
                <Button bsStyle="primary" onClick={this.handleSubmit} autoComplete="off">保存</Button>
            </form>
        );
    }
});


var WaitProgressComponent = React.createClass({
    loadStatusFromServer: function () {
        $.getJSON('/is-waiting', function (data) {
            if (data.res == 0) {
                this.setState({waiting: data.data});
                if (data.data == false) {
                    setTimeout('location.href="/"', 2000);
                }
            }
        }.bind(this));
    },
    componentDidMount: function () {
        setInterval(this.loadStatusFromServer, 4000);
    },
    getInitialState: function () {
        return {waiting: true};
    },
    render: function () {
        var style = {width: '100%'};
        var notify = this.state.waiting ? '正在获取你的权限，请稍后...' : '权限获取成功，正在跳转...';
        return (
            <div>
                <div className="text-center">{notify}</div>
                <br />
                <div className="progress">
                    <div className="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style={style}>
                    </div>
                </div>
            </div>
        );
    }
});

var RegisterFormComponent = React.createClass({
    handleChange: function (e) {
        var t = e.target;
        var data = this.state;
        data[t.name] = t.value;
        this.setState(data);
    },
    handleSubmit: function (e) {
        e.preventDefault();
        if (this.state.name.isEmpty()) {
            alert('Name不能为空');
            return ;
        }
        if (!this.state.email.isEmail()) {
            alert('notify email格式错误');
            return ;
        }
        $.post('/user/register', {
            email: this.state.email.trim(),
            name: this.state.name.trim()
        }, function (data) {
            if (data.res == 0) {
                location.href=data.info;
            }
        }, 'json');
    },
    getInitialState: function () {
        return this.props.data;
    },
    render: function () {
        return (
                <form role="form" onSubmit={this.handleSubmit}>
                    <fieldset>
                        <div className="form-group">
                            <input className="form-control" placeholder="Login" name="login" type="text" value={this.state.login} disabled/>
                        </div>
                        <div className={this.state.name.isEmpty() ? 'form-group has-error' : 'form-group'}>
                            <input className="form-control" placeholder="真实姓名" onChange={this.handleChange} name="name" type="text" value={this.state.name} autofocus/>
                        </div>
                        <div className={!this.state.email.isEmail() ? 'form-group has-error' : 'form-group'}>
                            <input className="form-control" placeholder="Notify Email" onChange={this.handleChange} name="email" type="email" value={this.state.email}/>
                        </div>
                        <button className="btn btn-lg btn-success btn-block" type="submit" id="registerBtn">Register</button>
                    </fieldset>
                </form>
        );
    }
});


var NavUlLiComponent = React.createClass({
    render: function () {
        var data = this.props.data;
        if (data.children == undefined) {
            var aClass = data.active === true ? 'active' : '';
            if (data.fa == undefined) {
                return (
                    <li><a className={aClass} href={data.url}>{data.name}</a></li>
                );
            } else {
                var faClass = "fa " + data.fa + " fa-fw";
                return (
                    <li><a className={aClass} href={data.url}><i className={faClass}></i> {data.name}</a></li>
                );
            }
        } else {
            var liClass = '';
            for(var i in data.children) {
                if (data.children[i].active === true) {
                    liClass = 'active';
                    break;
                }
            }
            if (data.fa == undefined) {
                return (
                    <li className={liClass}>
                        <a href={data.url}>{data.name}<span className="fa arrow"></span></a>
                        <NavUlComponent extraClassName="nav-second-level" lists={data.children}/>
                    </li>
                );
            } else {
                var faClass = "fa " + data.fa + " fa-fw";
                return (
                    <li className={liClass}>
                        <a href={data.url}><i className={faClass}></i> {data.name}<span className="fa arrow"></span></a>
                        <NavUlComponent extraClassName="nav-second-level" lists={data.children}/>
                    </li>
                );
            }
        }
    }
});

var NavUlComponent = React.createClass({
    render: function () {
        var navNodes = this.props.lists.map(function (list) {
            if ((list.protected != undefined && loginUser.control(list.protected))
                || (list.protected == undefined && list.admin_control == false)
                || loginUser.isAdmin()) {

                return (
                    <NavUlLiComponent key={list.name} data={list}/>
                );
            }
            return '';
        });

        var className = this.props.extraClassName == undefined ? 'nav' : 'nav ' + this.props.extraClassName;
        var id = this.props.id == undefined ? '' : this.props.id;
        return (
            <ul className={className} id={id}>
                {navNodes}
            </ul>
        );
    }
});


var SideBarNavComponent = React.createClass({
    render: function () {
        return (
            <div className="sidebar-nav navbar-collapse">
                <NavUlComponent id="side-menu" lists={this.props.data}/>
            </div>
        );
    }
});


var InlineFormAlertComponent = React.createClass({
    render: function () {
        var alertType = this.props.alertType;
        var className = alertType == null ? 'hidden' : alertType == 'error' ? 'text-danger' : 'text-success';
        var msg = this.props.alertMsg;
        return (
             <span className={className}>{msg}</span>
        );
    }
});

var RoleAddFormComponent = React.createClass({
    changeHandle: function(e) {
        var t = e.target;
        var state = this.state;
        state[t.name] = t.value;
        if (t.name == 'roleName') {
            state.roleNameError = false;
            state.alertType = null;
        }
        this.setState(state);
    },
    emptySubmitHandle: function (e) {
        e.preventDefault();
    },
    submitHandle: function (e) {
        e.preventDefault();
        var btn = e.currentTarget;
        var state = this.state;
        if (this.state.roleName.isEmpty()) {
            state.alertMsg = "角色名不能为空";
            state.alertType = 'error';
            state.roleNameError = true;
            this.setState(state);
            return ;
        }

        $(btn).button("loading");
        $.post('/api/role', {
            _token: csrfToken,
            roleName: this.state.roleName,
            roleType: this.state.roleType,
        }, function (data) {
            if (data.code == 0) {
                state.roleName = '';
                state.roleType = 0;
                state.roleNameError = false;
                state.alertMsg = data.msg;
                state.alertType = 'success';
                this.props.reloadCallback == null ? '' : this.props.reloadCallback();
            } else {
                state.alertMsg = data.msg;
                state.alertType = 'error';
            }
            this.setState(state);
            $(btn).button("reset");
        }.bind(this), 'json');
    },
    getInitialState: function () {
        return {roleNameError: false, roleName: '', roleType: 0, alertMsg: null, alertType: null};
    },
    render: function () {
        return (
            <form className="form-inline" role="form" onSubmit={this.emptySubmitHandle}>
                <Input name="roleName" value={this.state.roleName} onChange={this.changeHandle} type="text" bsStyle={this.state.roleNameError ? 'error' : null} label="角色名" labelClassName="sr-only" placeholder="角色名"/>
                &nbsp;
                <Input type="select" name="roleType" label="是否管理角色" onChange={this.changeHandle} labelClassName="sr-only" value={this.state.roleType}>
                     <option value="0">普通角色</option>
                     <option value="1">管理角色</option>
                </Input>
                &nbsp;
                <Button bsStyle="primary" data-loading-text="加载中..." onClick={this.submitHandle} autoComplete="off"><i className="fa fa-plus fa-fw"></i>{" 添加角色"}</Button>
                &nbsp; &nbsp;
                <InlineFormAlertComponent alertType={this.state.alertType} alertMsg={this.state.alertMsg}/>
            </form>
        );
    }
});

var RoleEditModalComponent = React.createClass({
    changeHandle: function(e) {
        var t = e.target;
        var state = this.state;
        state[t.name] = t.value;
        if (t.name == 'roleName') {
            state.roleNameError = false;
            state.alertType = null;
        }
        this.setState(state);
    },
    emptySubmitHandle: function (e) {
        e.preventDefault();
    },
    handleToggle: function () {
        $("#roleModal").modal("show");
    },
    getInitialState: function () {
        return {
            roleNameError: false,
            roleName: this.props.data.name,
            roleType: this.props.data.is_admin_role,
            alertMsg: null,
            alertType: null
        };
    },
    submitHandle: function (e) {
        e.preventDefault();
        var btn = e.currentTarget;
        var state = this.state;
        if (this.state.roleName.isEmpty()) {
            state.alertMsg = "角色名不能为空";
            state.alertType = 'error';
            state.roleNameError = true;
            this.setState(state);
            return ;
        }

        $(btn).button("loading");
        $.post('/api/role/' + this.props.data.id, {
            _token: csrfToken,
            _method: "PUT",
            roleName: this.state.roleName,
            roleType: this.state.roleType,
        }, function (data) {
            if (data.code == 0) {
                $("#roleModal").modal('hide');
                this.props.updateCallback == null ? '' : this.props.updateCallback();
            } else {
                state.roleNameError = true;
                state.alertMsg = data.msg;
                state.alertType = 'error';
            }
            this.setState(state);
            $(btn).button("reset");
        }.bind(this), 'json');
    },
    render: function () {
        return (
    <div className="modal fade" id="roleModal" tabIndex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div className="modal-dialog">
            <div className="modal-content">
                <div className="modal-header">
                    <button type="button" className="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 className="modal-title" id="myModalLabel">修改角色</h4>
                </div>
                <div className="modal-body">
                    <div className="row">
                        <div className="col-lg-12">
                            <form role="form" onSubmit={this.emptySubmitHandle}>
                                <Input name="roleName" value={this.state.roleName} onChange={this.changeHandle} type="text" bsStyle={this.state.roleNameError ? 'error' : null} label="角色名" placeholder="角色名"/>
                                <Input type="select" name="roleType" label="角色类型" onChange={this.changeHandle} value={this.state.roleType}>
                                     <option value="0">普通角色</option>
                                     <option value="1">管理角色</option>
                                </Input>
                            </form>
                        </div>
                    </div>
                </div>
                <div className="modal-footer">
                    <InlineFormAlertComponent alertType={this.state.alertType} alertMsg={this.state.alertMsg}/>
                    &nbsp; &nbsp;
                    <button type="button" className="btn btn-default" data-dismiss="modal">关闭</button>&nbsp;
                    <button type="button" className="btn btn-primary" onClick={this.submitHandle}><i className="fa fa-save fa-fw"></i> 保存</button>
                </div>
            </div>
        </div>
    </div>
        );
    }
});

var DeployModal = React.createClass({
    handleMainBtn: function (e) {
        e.preventDefault();
        if (typeof this.props.clickCallback == 'function') {
            this.props.clickCallback(e.currentTarget);
        }
    },
    render: function (e) {
        var btnStyle ={marginLeft: "16px"};
        var mainBtn = this.props.btn == undefined ? '' : (<button type="button" style={btnStyle} className="btn btn-primary" onClick={this.handleMainBtn}>{this.props.btn}</button>);
        var inlineAlert = '';
        if (this.props.alertType != null) {
            inlineAlert = (
                <InlineFormAlertComponent alertType={this.props.alertType} alertMsg={this.props.alertMsg}/>
            );
        }
        var lg = this.props.lg == 'lg' ?  'modal-lg' : '';
        return (
        <div className="modal fade" id={this.props.id} tabIndex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div className={'modal-dialog ' + lg}>
            <div className="modal-content">
                <div className="modal-header">
                    <button type="button" className="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 className="modal-title" id="myModalLabel">{this.props.title}</h4>
                </div>
                <div id="ctModalBody" className="modal-body">
                    {this.props.children}
                </div>
                <div className="modal-footer">
                    {inlineAlert}
                    &nbsp; &nbsp;
                    <button type="button" className="btn btn-default" data-dismiss="modal">关闭</button>
                    {mainBtn}
                </div>
            </div>
        </div>
        </div>
       )
    }
});

var BlockAlert = React.createClass({
    getInitialState: function() {
        return {
            alertVisible: true
        };
    },

    render: function() {
        if (this.props.msgType !=null && this.state.alertVisible) {
            return (
                <Alert bsStyle={this.props.msgType} onDismiss={this.handleAlertDismiss} >
                    <div dangerouslySetInnerHTML={{__html: this.props.children.toString()}}></div>
                </Alert>
            );
        }
        return (<span />);
    },

    handleAlertDismiss: function() {
        this.setState({alertVisible: false});
    },
});

var HostTypeCatalogEditComponent = React.createClass({
    changeHandle: function (e) {
        var t = e.target;
        var state = this.state;
        state[t.name] = t.value;
        if (t.name == 'name') {
            state.nameError = false;
            state.alertType = null;
        }
        this.setState(state);
    },
    emptySubmitHandle: function (e) {
        e.preventDefault();
    },
    handleSubmit: function (btn) {
        btn = $(btn);
        var state = this.state;
        if (this.state.name.isEmpty()) {
            state.nameError = true;
            state.alertType = 'danger';
            state.alertMsg = '发布环境名称不能为空';
            this.setState(state);
            return ;
        }
        btn.button('loading');
        if (this.props.type == 'new') {
            $.post('/api/hosttypecatalog', {
                _token: csrfToken,
                name: this.state.name,
                is_send_notify: this.state.is_send_notify
            }, function (data) {
                btn.button('reset');
                state.alertMsg = data.msg;
                if (data.code == 0) {
                    state.alertType = 'success';
                    setTimeout(function () {$("#ctModal").modal("hide")}, 1000);
                    this.props.updateCallback == null ? '' : this.props.updateCallback();
                } else {
                    state.nameError = true;
                    state.alertType = 'danger';
                }
                this.setState(state);
            }.bind(this), 'json');
        } else {
            $.post('/api/hosttypecatalog/' + this.props.data.id, {
                _method: 'PUT',
                _token: csrfToken,
                name: this.state.name,
                is_send_notify: this.state.is_send_notify
            }, function (data) {
                btn.button('reset');
                state.alertMsg = data.msg;
                if (data.code == 0) {
                    state.alertType = "success";
                    setTimeout(function () {$("#ctModal").modal("hide")}, 1000);
                    this.props.updateCallback == null ? '' : this.props.updateCallback();
                } else {
                    state.nameError = true;
                    state.alertType = "danger";
                }
                this.setState(state);
            }.bind(this), 'json');
        }
    },
    handleToggle: function () {
        $("#ctModal").modal("show");
    },
    getInitialState: function (e) {
        if (this.props.type == 'edit') {
            return {name: this.props.data.name, is_send_notify: this.props.data.is_send_notify, nameError: false, modalTitle: '修改发布环境', btn: "保存", alertType: null, alertMsg: ''};
        }
        return {name: '', is_send_notify: 0, nameError: false, modalTitle: '新建发布环境', btn: "新建", alertType: null, alertMsg: ''};
    },
    render: function (e) {
        return (
            <DeployModal id="ctModal" title={this.state.modalTitle} btn={this.state.btn} clickCallback={this.handleSubmit}>
                <div className="row">
                    <div className="col-lg-12">
                        <BlockAlert msgType={this.state.alertType}>{this.state.alertMsg}</BlockAlert>
                    </div>
                </div>
                <div className="row">
                    <div className="col-lg-12">
                        <form role="form" onSubmit={this.emptySubmitHandle}>
                            <Input name="name" value={this.state.name} onChange={this.changeHandle} type="text" bsStyle={this.state.nameError ? 'error' : null} label="环境名称" placeholder="环境名称"/>
                            <Input type="select" name="is_send_notify" label="是否发送Notify" onChange={this.changeHandle} value={this.state.is_send_notify}>
                                 <option value="0">不发送Notify</option>
                                 <option value="1">发送Notify</option>
                            </Input>
                        </form>
                    </div>
                </div>
            </DeployModal>
        );
    },
});

var HostTypeAddComponent = React.createClass({
    changeHandle: function(e) {
        var t = e.target;
        var state = this.state;
        state[t.name] = t.value;
        if (t.name == 'name') {
            state.nameError = false;
            state.alertType = null;
        }
        this.setState(state);
    },

    emptySubmitHandle: function (e) {
        e.preventDefault();
    },

    submitHandle: function (e) {
        e.preventDefault();
        if (this.state.name.isEmpty()) {
        }
    },


    getInitialState: function () {
        if (this.props.type == 'new') {
            return {name: '', catalog: '', alertType: null, alertMsg: ''};
        }
        return {name: this.props.data.name, catalog: this.props.data.catalog, alertType: null, alertMsg: ''};
    },

    render: function () {
        var titleName = this.props.type == 'new' ? '新建机器分组' : '修改机器分组';
        var btnName = this.props.type == 'new' ? '新增' : '修改';
        return (
    <div className="modal fade" id="roleModal" tabIndex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div className="modal-dialog">
            <div className="modal-content">
                <div className="modal-header">
                    <button type="button" className="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 className="modal-title" id="myModalLabel">{titleName}</h4>
                </div>
                <div className="modal-body">
                    <div className="row">
                        <div className="col-lg-12"><div className="alert">hi</div></div>
                    </div>
                    <div className="row">
                        <div className="col-lg-12">
                            <form role="form" onSubmit={this.emptySubmitHandle}>
                                <Input name="name" value={this.state.name} onChange={this.changeHandle} type="text" bsStyle={this.state.name ? 'error' : null} label="Host Type" placeholder="Host Type"/>
                                <Input type="select" name="catalog" label="角色类型" onChange={this.changeHandle} value={this.state.catalog}>
                                     <option value="0">请选择分类..</option>
                                     <option value="1">管理角色</option>
                                </Input>
                            </form>
                        </div>
                    </div>
                </div>
                <div className="modal-footer">
                    <InlineFormAlertComponent alertType={this.state.alertType} alertMsg={this.state.alertMsg}/>
                    &nbsp; &nbsp;
                    <button type="button" className="btn btn-default" data-dismiss="modal">关闭</button>&nbsp;
                    <button type="button" className="btn btn-primary" onClick={this.submitHandle}><i className="fa fa-save fa-fw"></i> {btnName}</button>
                </div>
            </div>
        </div>
    </div>

        );
    }
});


var SiteEditComponent = React.createClass({
    handleToggle: function () {
        $("#ctModal").modal("show");
    },
    handleChange: function (e) {
        var state = this.state;
        var t = e.target;
        state[t.name] = t.value;
        state[t.name + 'Error'] = false;
        state.alertType = null;
        this.setState(state);
    },

    getInitialState: function () {
        if (this.props.type == 'edit') {
            return {name: this.props.data.name, repo_git: this.props.data.repo_git, repo_gitError: false, nameError: false, modalTitle: '修改项目', btn: "保存", alertType: null, alertMsg: ''};
        }
        return {name: '', repo_git: '', nameError: false, repo_gitError: false, modalTitle: '新建项目', btn: "新建", alertType: null, alertMsg: ''};
    },

    emptySubmitHandle: function (e) {
        e.preventDefault();
    },

    handleSubmit: function (btn) {
        btn = $(btn);
        var state = this.state;
        if (this.state.name.isEmpty()) {
            state.nameError = 'error';
            state.alertType = 'danger';
            state.alertMsg = '项目名不能为空';
            this.setState(state);
            return ;
        }

        if (this.props.type == 'new') {
            if (! /^git@(.+)/i.test(this.state.repo_git.trim())) {
                state.repo_gitError = 'error';
                state.alertType = 'danger';
                state.alertMsg = 'Fetch Url格式不正确';
                this.setState(state);
                return ;
            }
            btn.button('loading');
            $.post('/api/site', {
                _token: csrfToken,
                name: this.state.name,
                repo_git: this.state.repo_git,
            }, function (data) {
                state.alertMsg = data.msg;
                if (data.code == 0) {
                    state.alertType = 'success';
                    setTimeout(function () {$("#ctModal").modal("hide")}, 1000);
                    this.props.updateCallback == null ? '' : this.props.updateCallback();
                } else {
                    for (i in data.fields) {
                        state[data.fields[i] + 'Error'] = true;
                    }
                    state.alertType = 'danger';
                }
                this.setState(state);
                btn.button('reset');
            }.bind(this), 'json');
        } else {
            btn.button('loading');
            $.post('/api/site/' + this.props.id, {
                _token: csrfToken,
                _method: 'PUT',
                name: this.state.name,
            }, function (data) {
                state.alertMsg = data.msg;
                if (data.code == 0) {
                    state.alertType= 'success';
                    setTimeout(function () {$("#ctModal").modal("hide")}, 1000);
                    this.props.updateCallback == null ? '' : this.props.updateCallback();
                } else {
                    for (i in data.fields) {
                        state[data.fields[i] + 'Error'] = true;
                    }
                    state.alertType = 'danger';
                }
                this.setState(state);
                btn.button('reset');
            }.bind(this), 'json');
        }
    },

    render: function () {
        var repoInput = this.props.type == 'edit' ? (<Input name="repo_git" value={this.state.repo_git} type="text" label="Fetch Url" disabled/> ) : (<Input name="repo_git" value={this.state.repo_git} onChange={this.handleChange} type="text" bsStyle={this.state.repo_gitError ? 'error' : null} label ="Fetch Url" placeholder="Fetch Url"/>);
        return (
             <DeployModal id="ctModal" title={this.state.modalTitle} btn={this.state.btn} clickCallback={this.handleSubmit}>
                <div className="row">
                    <div className="col-lg-12">
                        <BlockAlert msgType={this.state.alertType}>{this.state.alertMsg}</BlockAlert>
                    </div>
                </div>
                <div className="row">
                    <div className="col-lg-12">
                        <form role="form" onSubmit={this.emptySubmitHandle}>
                            <Input name="name" value={this.state.name} onChange={this.handleChange} type="text" bsStyle={this.state.nameError ? 'error' : null} label="项目名 " placeholder="项目名"/>
                            {repoInput}
                        </form>
                    </div>
                </div>
            </DeployModal>
        );
   }
});


var RolePermissionModal = React.createClass({
    handleToggle: function () {
        $("#ctModal").modal("show");
    },

    handleSubmit: function (btn) {
        var btn = $(btn);
        $.post("/api/role/" + this.props.data.id + "/permission?_token=" + csrfToken, $("#pForm").serialize(), function (data) {
            var state = this.state;
            state.alertMsg = data.msg;
            if (data.code == 0) {
                state.alertType = 'success';
                setTimeout(function () {$("#ctModal").modal("hide")}, 1000);
            } else {
                state.alertType = 'danger';
            }
            this.setState(state);
        }.bind(this));
    },
    getInitialState: function () {
        return {modalTitle: "编辑权限: " + this.props.data.name, btn: '保存', alertType: null, alertMsg: ''};
    },
    emptySubmitHandle: function () {
    },
    render: function () {
        var trNodes = [];
        var permissions = this.props.data.permissions;
        for (var i in permissions) {
            trNodes.push(<tr key={'tr' + i}><th className="info" colSpan={3}>{permissions[i].description}</th></tr>);
            for (var j in permissions[i].list) {
                var checkbox;
                if (permissions[i].list[j].is_controlled == 1){
                    checkbox = (
                        <label>
                            <input type="checkbox" name="permissions[]" value={permissions[i].list[j].action} defaultChecked/>
                        </label>
                    );
                } else {
                    checkbox = (
                        <label>
                            <input type="checkbox" name="permissions[]" value={permissions[i].list[j].action} />
                        </label>
                    );
                }
                trNodes.push(<tr key={'tr' + i + j}><td>{this.props.data.name}</td><td>{permissions[i].list[j].description}</td><td>{checkbox}</td></tr>);
            }
        }
        return (
         <DeployModal id="ctModal" title={this.state.modalTitle} btn={this.state.btn} clickCallback={this.handleSubmit} alertType={this.state.alertType} alertMsg={this.state.alertMsg}>
             <div className="row">
                 <div className="col-lg-12">
                     <form id="pForm" role="form" onSubmit={this.emptySubmitHandle}>
                         <table className="table table-bordered table-hover">
                             <thead>
                                 <tr>
                                     <td>角色名</td>
                                     <td>权限名</td>
                                     <td>是否拥有权限</td>
                                 </tr>
                             </thead>
                             <tbody>
                                 {trNodes}
                             </tbody>
                         </table>
                     </form>
                 </div>
             </div>
         </DeployModal>
         );
    }
});

var UserRoleAddModal = React.createClass({
    handleChange: function (e) {
        var target = e.target;
        var state = this.state;
        state.role_id = target.value;
        state.alertType = null;
        state.hasError = null;
        this.setState(state);
    },

    handleToggle: function () {
        $("#ctModal").modal("show");
    },

    handleSubmit: function (btn) {
        var btn = $(btn);
        var state = this.state;
        if (this.state.role_id == 0) {
            state.alertType = 'danger';
            state.alertMsg = '请选择角色';
            state.hasError = 'error';
            this.setState(state);
            return ;
        }
        $.post("/api/user/" + this.props.data.userId + "/role", {
            _token : csrfToken,
            role_id: this.state.role_id
        }, function (data) {
            state.alertMsg = data.msg;
            if (data.code == 0) {
                state.role_id = 0;
                state.alertType = 'success';
                setTimeout(function () {$("#ctModal").modal("hide")}, 1000);
                this.props.updateCallback == null ? '' : this.props.updateCallback();
            } else {
                state.hasError = 'error';
                state.alertType = 'danger';
            }
            this.setState(state);
        }.bind(this), 'json');
    },
    getInitialState: function () {
        return {role_id: 0, modalTitle: "为用户 " + this.props.data.userName + " 添加角色", btn: '添加', alertType: null, alertMsg: '', hasError: null};
    },
    emptySubmitHandle: function () {
    },
    render: function () {
        var options = this.props.data.roles.map(function (data) {
            return (<option key={data.id} value={data.id} >{data.name}</option>);
        });
        return (
         <DeployModal id="ctModal" title={this.state.modalTitle} btn={this.state.btn} clickCallback={this.handleSubmit}>
            <div className="row">
                <div className="col-lg-12">
                    <BlockAlert msgType={this.state.alertType}>{this.state.alertMsg}</BlockAlert>
                </div>
            </div>
             <div className="row">
                 <div className="col-lg-12">
                     <form id="pForm" role="form" onSubmit={this.emptySubmitHandle}>
                        <Input type="select" name="role_id" label="选择角色" onChange={this.handleChange} value={this.state.role_id} bsStyle={this.state.hasError}>
                             <option value="0">请选择角色...</option>
                             {options}
                        </Input>
                     </form>
                 </div>
             </div>
         </DeployModal>
         );
    }
});


var DeployRow = React.createClass({
    render: function () {
        return (
            <div className="row">
                <div className="col-lg-12">
                    {this.props.children}
                </div>
            </div>
        );
    }
});

var SiteConfigComponent = React.createClass({
    handleEmpty: function (e) {
        e.preventDefault();
    },
    handleSubmit: function (e) {
        var btn = $(e.target);
        var state = this.state;
        var passphrase = $('#siteConfigForm input[name="pull_key_passphrase"]').get(0);
        if (passphrase.value.length > 32) {
            alert('passphrase 长度不能超过32位');
            passphrase.focus();
            return ;
        }
        btn.button('loading');
        $.post('/api/site/' + siteId + '/configure?_method=PUT&_token=' + csrfToken, $("#siteConfigForm").serialize(), function (data) {
            btn.button('reset');
            state.alertMsg = data.msg;
            if (data.code == 0) {
                state.alertType = 'success';
            } else {
                state.alertType = 'error';
            }
            this.setState(state);
        }.bind(this), 'json');
    },
    getInitialState: function () {
        return {alertType: null, alertMsg: ''};
    },
    render: function () {
        return (
            <DeployRow>
                <form id="siteConfigForm" onSubmit={this.handleEmpty}>
                    <Input type="text" name="name" defaultValue={this.props.data.name} label="项目名" disabled/>
                    <Input type="text" name="repo_git" help="{{repo_git}}" defaultValue={this.props.data.repo_git} label="Fetch Url" disabled/>
                    <Input type="text" name="static_dir" help="{{static_dir}}" defaultValue={this.props.data.static_dir} label="静态文件目录" />
                    <Input type="text" name="rsync_exclude_file" help="{{rsync_exclude}}" defaultValue={this.props.data.rsync_exclude_file} label="Rsync Exclude File" />
                    <Input type="text" name="default_branch" help="{{default_branch}}" defaultValue={this.props.data.default_branch} label="默认Branch" />
                    <Input type="text" name="build_command" help="{{build_command}}" defaultValue={this.props.data.build_command} label="Build Command" />
                    <Input type="text" name="test_command" help="{{test_command}}" defaultValue={this.props.data.test_command} label="Test Command" />
                    <Input type="textarea" name="pull_key" defaultValue={this.props.data.pull_key} label="Pull Key" />
                    <Input type="text" ref="passphrase" name="pull_key_passphrase" defaultValue={this.props.data.pull_key_passphrase} label="Pull Key Passphrase" />
                    <Input type="text" name="hipchat_room" help="{{hipchat_room}}" defaultValue={this.props.data.hipchat_room} label="Hipchat Room" />
                    <Input type="text" name="hipchat_token" help="{{hipchat_token}}" defaultValue={this.props.data.hipchat_token} label="Hipchat Token" />
                    <Input type="text" name="github_token" help="{{github_token}}, 安全起见，应该只给repo:state权限" defaultValue={this.props.data.github_token} label="Github Token" />
                    <Button onClick={this.handleSubmit} bsStyle="primary" >保存</Button>
                    &nbsp;&nbsp;&nbsp;
                    <InlineFormAlertComponent alertType={this.state.alertType} alertMsg={this.state.alertMsg}/>
                </form>
            </DeployRow>
        );
    }
});

var SiteDeployConfigComponent = React.createClass({
    handleEmpty: function (e) {
        e.preventDefault();
    },
    handleSubmit: function (e) {
        var btn = $(e.target);
        var state = this.state;
        var passphrase = $('#deployConfigForm input[name="deploy_key_passphrase"]').get(0);
        if (passphrase.value.length > 32) {
            alert('passphrase 长度不能超过32位');
            passphrase.focus();
            return ;
        }
        var emails = $('#deployConfigForm input[name="notify_emails"]').get(0).value.trim();
        var errorEmails = '';
        if (emails.length > 0) {
            emailArray = emails.split(';');
            for (var i in emailArray) {
                if (!emailArray[i].isEmail()) {
                    errorEmails += emailArray[i] + ' ';
                }
            }
        }
        if (errorEmails.length > 0) {
            alert('下列邮箱格式错误:' + errorEmails);
            return ;
        }
        btn.button('loading');
        $.post('/api/site/' + siteId + '/deploy_configure?_method=PUT&_token=' + csrfToken, $("#deployConfigForm").serialize(), function (data) {
            btn.button('reset');
            state.alertMsg = data.msg;
            if (data.code == 0) {
                state.alertType = 'success';
            } else {
                state.alertType = 'error';
            }
            this.setState(state);
        }.bind(this), 'json');
    },
    getInitialState: function () {
        return {alertType: null, alertMsg: ''};
    },
    render: function () {
        var scriptHelp = (<span>格式 , <code>@(after|before):(remote|local|handle)</code>,示例：<br /><code>
                @after:remote<br />
                //同步之前远端执行的命令<br />
                @before:local<br />
                //同步之后远端执行的命令<br />
        </code>remote和local都是每次同步每个主机都要执行的命令，而handle命令是只执行一次的本地命令。<br />支持发布配置和项目配置的部分变量, 如 <code>ls &#123;&#123;remote_static_dir&#125;&#125;</code>。<br/>对于需要更改工作目录的命令，请使用<code>cd 目录 && 命令</code>的方式执行</span>);
        return (
            <DeployRow>
                <form id="deployConfigForm" onSubmit={this.handleEmpty}>
                    <Input type="text" name="remote_user" help="{{remote_user}}" defaultValue={this.props.data.remote_user} label="Remote User" />
                    <Input type="text" name="remote_owner" help="{{remote_owner}}" defaultValue={this.props.data.remote_owner} label="Rmote Owner" />
                    <Input type="text" name="remote_app_dir" help="{{remote_app_dir}}" defaultValue={this.props.data.remote_app_dir} label="Remote App Dir" />
                    <Input type="text" name="remote_static_dir" help="{{remote_static_dir}}" defaultValue={this.props.data.remote_static_dir} label="Remote Static Dir" />
                    <Input type="textarea" name="app_script" rows="5" help="{{app_script}}" help={scriptHelp} defaultValue={this.props.data.app_script} label="APP发布前后执行的脚本" />
                    <Input type="textarea" rows="5" name="static_script" help="同上" defaultValue={this.props.data.static_script} label="静态文件发布前后执行的脚本" />
                    <Input type="textarea" name="deploy_key" defaultValue={this.props.data.deploy_key} label="Deploy Login Key" />
                    <Input type="text" ref="passphrase" name="deploy_key_passphrase" defaultValue={this.props.data.deploy_key_passphrase} label="Deploy Login Key Passphrase" />
                    <Input type="text" help="使用;分割" name="notify_emails" defaultValue={this.props.data.notify_emails} label="Notify Emails" />
                    <Button onClick={this.handleSubmit} bsStyle="primary" >保存</Button>
                    &nbsp;&nbsp;&nbsp;
                    <InlineFormAlertComponent alertType={this.state.alertType} alertMsg={this.state.alertMsg}/>
                </form>
            </DeployRow>
        );
    }
});

var SiteHostTypeEditModal = React.createClass({
    handleToggle: function () {
        $("#ctModal").modal("show");
    },
    handleChange: function (e) {
        var state = this.state;
        var t = e.target;
        state[t.name] = t.value;
        state[t.name + 'Error'] = false;
        state.alertType = null;
        this.setState(state);
    },

    getInitialState: function () {
        if (this.props.type == 'edit') {
            return {name: this.props.data.hosttype.name, catalog_id: this.props.data.hosttype.catalog.id, catalog_idError: false, nameError: false, modalTitle: '修改HostType', btn: "保存", alertType: null, alertMsg: ''};
        }

        return {name: '', catalog_id: 0, catalog_idError: false, nameError: false, modalTitle: '新增HostType', btn: "保存", alertType: null, alertMsg: ''};
    },

    emptySubmitHandle: function (e) {
        e.preventDefault();
    },

    handleSubmit: function (btn) {
        btn = $(btn);
        var state = this.state;
        if (this.state.name.isEmpty()) {
            state.nameError = true;
            state.alertType = 'danger';
            state.alertMsg = '机器分组名不能为空';
            this.setState(state);
            return ;
        }

        if (this.state.catalog_id == 0) {
            state.catalog_idError = true;
            state.alertType = 'danger';
            state.alertMsg = '请选择机器分组环境类型';
            this.setState(state);
            return ;
        }

        if (this.props.type == 'new') {
            btn.button('loading');
            $.post('/api/site/' + siteId + '/hosttype', {
                _token: csrfToken,
                name: this.state.name,
                catalog_id: this.state.catalog_id
            }, function (data) {
                state.alertMsg = data.msg;
                if (data.code == 0) {
                    state.alertType = 'success';
                    setTimeout(function () {$("#ctModal").modal("hide")}, 1000);
                    this.props.updateCallback == null ? '' : this.props.updateCallback();
                } else {
                    for (i in data.fields) {
                        state[data.fields[i] + 'Error'] = true;
                    }
                    state.alertType = 'danger';
                }
                this.setState(state);
                btn.button('reset');
            }.bind(this), 'json');
        } else {
            btn.button('loading');
            $.post('/api/site/' + siteId + '/hosttype/' + this.props.data.hosttype.id, {
                _token: csrfToken,
                _method: 'PUT',
                name: this.state.name,
                catalog_id: this.state.catalog_id
            }, function (data) {
                state.alertMsg = data.msg;
                if (data.code == 0) {
                    state.alertType= 'success';
                    setTimeout(function () {$("#ctModal").modal("hide")}, 1000);
                    this.props.updateCallback == null ? '' : this.props.updateCallback();
                } else {
                    for (i in data.fields) {
                        state[data.fields[i] + 'Error'] = 'error';
                    }
                    state.alertType = 'danger';
                }
                this.setState(state);
                btn.button('reset');
            }.bind(this), 'json');
        }
    },

    render: function () {
        var catalogs = this.props.data.catalogs.map(function (catalog) {
            return (<option key={catalog.id} value={catalog.id}>{catalog.name}</option>);
        });
        return (
             <DeployModal id="ctModal" title={this.state.modalTitle} btn={this.state.btn} clickCallback={this.handleSubmit}>
                <div className="row">
                    <div className="col-lg-12">
                        <BlockAlert msgType={this.state.alertType}>{this.state.alertMsg}</BlockAlert>
                    </div>
                </div>
                <div className="row">
                    <div className="col-lg-12">
                        <form role="form" onSubmit={this.emptySubmitHandle}>
                            <Input name="name" value={this.state.name} onChange={this.handleChange} type="text" bsStyle={this.state.nameError ? 'error' : null} label="Host Type " placeholder="Host Type"/>
                            <Input type="select" value={this.state.catalog_id} onChange={this.handleChange} name="catalog_id" bsStyle={this.state.catalog_idError ? 'error' : null} label="Host Type 环境">
                                <option value="0">请选择环境...</option>
                                {catalogs}
                            </Input>
                        </form>
                    </div>
                </div>
            </DeployModal>
        );
   }
});


var SiteHostEditModal = React.createClass({
    handleToggle: function () {
        $("#ctModal").modal("show");
    },
    handleChange: function (e) {
        var state = this.state;
        var t = e.target;
        state[t.name] = t.value;
        state[t.name + 'Error'] = false;
        state.alertType = null;
        this.setState(state);
    },

    getInitialState: function () {
        if (this.props.type == 'edit') {
            return {name: this.props.data.host.name, host_type_id: this.props.data.host.host_type.id, port: this.props.data.host.port, ip: this.props.data.host.ip, type: this.props.data.host.type, nameError: false, host_type_idError: false, portError: false, ipError: false, modalTitle: '修改机器', btn: "保存", alertType: null, alertMsg: ''};
        }

        return {name: '', host_type_id: '', port: '', ip: '', type: 'APP', nameError: false, host_type_idError: false, portError: false, ipError: false, modalTitle: '新增机器', btn: "保存", alertType: null, alertMsg: ''};
    },

    emptySubmitHandle: function (e) {
        e.preventDefault();
    },

    handleSubmit: function (btn) {
        btn = $(btn);
        var state = this.state;
        if (this.state.name.isEmpty()) {
            state.nameError = true;
            state.alertType = 'danger';
            state.alertMsg = '机器名不能为空';
            this.setState(state);
            return ;
        }

        if (this.state.host_type_id == 0) {
            state.host_type_idError = true;
            state.alertType = 'danger';
            state.alertMsg = '机器分组不能为空';
            this.setState(state);
            return ;
        }

        if (! /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(this.state.ip)) {
            state.ipError = true;
            state.alertType = 'danger';
            state.alertMsg = '机器ip格式错误';
            this.setState(state);
            return ;
        }

        if (!(/^\d{1,5}$/i.test(this.state.port) && this.state.port < 65536)) {
            state.portError = true;
            state.alertType = 'danger';
            state.alertMsg = 'SSH 端口格式错误';
            this.setState(state);
            return ;
        }

        if (this.props.type == 'new') {
            btn.button('loading');
            $.post('/api/site/' + siteId + '/host', {
                _token: csrfToken,
                name: this.state.name,
                port: this.state.port,
                ip: this.state.ip,
                host_type_id: this.state.host_type_id,
                type: this.state.type,
            }, function (data) {
                state.alertMsg = data.msg;
                if (data.code == 0) {
                    state.alertType = 'success';
                    setTimeout(function () {$("#ctModal").modal("hide")}, 1000);
                    this.props.updateCallback == null ? '' : this.props.updateCallback();
                } else {
                    for (i in data.fields) {
                        state[data.fields[i] + 'Error'] = true;
                    }
                    state.alertType = 'danger';
                }
                this.setState(state);
                btn.button('reset');
                $('#ctModal').animate({ scrollTop: 0 }, 'fast');
            }.bind(this), 'json');
        } else {
            btn.button('loading');
            $.post('/api/site/' + siteId + '/host/' + this.props.data.host.id, {
                _token: csrfToken,
                _method: 'PUT',
                name: this.state.name,
                port: this.state.port,
                ip: this.state.ip,
                host_type_id: this.state.host_type_id,
                type: this.state.type,
            }, function (data) {
                state.alertMsg = data.msg;
                if (data.code == 0) {
                    state.alertType= 'success';
                    setTimeout(function () {$("#ctModal").modal("hide")}, 1000);
                    this.props.updateCallback == null ? '' : this.props.updateCallback();
                } else {
                    for (i in data.fields) {
                        state[data.fields[i] + 'Error'] = 'error';
                    }
                    state.alertType = 'danger';
                }
                this.setState(state);
                btn.button('reset');
            }.bind(this), 'json');
        }
    },

    render: function () {
        var options = this.props.data.host_types.map(function (host_type) {
            return (<option key={host_type.id} value={host_type.id}>{host_type.name}</option>);
        });
        return (
             <DeployModal id="ctModal" title={this.state.modalTitle} btn={this.state.btn} clickCallback={this.handleSubmit}>
                <div className="row">
                    <div className="col-lg-12">
                        <BlockAlert msgType={this.state.alertType}>{this.state.alertMsg}</BlockAlert>
                    </div>
                </div>
                <div className="row">
                    <div className="col-lg-12">
                        <form role="form" onSubmit={this.emptySubmitHandle}>
                            <Input name="name" value={this.state.name} onChange={this.handleChange} type="text" bsStyle={this.state.nameError ? 'error' : null} label="机器名" placeholder="Host Name"/>
                            <Input name="ip" value={this.state.ip} onChange={this.handleChange} type="text" bsStyle={this.state.ipError ? 'error' : null} label="机器IP" placeholder="Host IP"/>
                            <Input name="port" value={this.state.port} onChange={this.handleChange} type="text" bsStyle={this.state.portError ? 'error' : null} label="SSH 端口" placeholder="Host Port"/>
                            <Input type="select" value={this.state.host_type_id} onChange={this.handleChange} name="host_type_id" bsStyle={this.state.host_type_idError ? 'error' : null} label="机器分组">
                                <option value="0">请选择...</option>
                                {options}
                            </Input>
                            <Input type="select" value={this.state.type} onChange={this.handleChange} name="type" bsStyle={this.state.typeError ? 'error' : null} label="机器发布类型">
                                <option value="APP">应用发布机器</option>
                                <option value="STATIC">静态文件发布机器</option>
                            </Input>
                        </form>
                    </div>
                </div>
            </DeployModal>
        );
   }
});


var NewBuildForm = React.createClass({
    handleChange: function (e) {
        var state = this.state;
        var t = e.target;
        state[t.name] = t.value;
        state[t.name + 'Error'] = false;
        state.alertType = null;
        this.setState(state);
    },
    emptySubmitHandle: function () {
    },
    handleSubmit: function (e) {
        var btn = $(e.currentTarget);
        var state = this.state;
        if (this.state.checkout.isEmpty()) {
            state.checkoutError = true;
            state.alertType = 'error';
            state.alertMsg = 'Checkout 不能为空';
            this.setState(state);
            return ;
        }
        btn.button('loading');
        $.post('/api/site/' + siteId + '/build', {
            _token: csrfToken,
            checkout: this.state.checkout
        }, function (data) {
            btn.button('reset');
            state.alertMsg = data.msg;
            if (data.code == 0) {
                state.checkout = '';
                state.alertType = 'success';
                location.hash = '#JobInfo-' + data.data.jobId + '-nj';
                //this.props.updateCallback == null ? '' : this.props.updateCallback();
            } else {
                state.checkoutError = true;
                state.alertType = 'error';
            }
            this.setState(state);
        }.bind(this), 'json')
    },
    getInitialState: function () {
        return {checkout: this.props.data.checkout, checkoutError: false, alertType: null, alertMsg: ''};
    },
    render: function () {
        return (
            <form className="form-inline" role="form" onSubmit={this.emptySubmitHandle}>
                <Input name="checkout" value={this.state.checkout} onChange={this.handleChange} type="text" bsStyle={this.state.checkoutError ? 'error' : null} label="Checkout" labelClassName="sr-only" placeholder="Checkout"/>
                &nbsp;
                <Button bsStyle="primary" onClick={this.handleSubmit} autoComplete="off">Build</Button>
                &nbsp; &nbsp;
                <InlineFormAlertComponent alertType={this.state.alertType} alertMsg={this.state.alertMsg}/>
            </form>
        );
    }
});


var OutputComponent = React.createClass({
    render : function () {
        var lines = [];
        var last = 0;
        for (var i in this.props.output) {
            last = i;
            var start = this.props.output[i].substr(0, 3);

            switch(start){
                case 'err':
                    var o = ansi_up.ansi_to_html(this.props.output[i].substr(4));
                    lines.push(<p key={i}><a>{i * 1 +1}</a><span className="line-error" dangerouslySetInnerHTML={{__html: o}}></span></p>);
                    break;
                case 'out':
                    var o = ansi_up.ansi_to_html(this.props.output[i].substr(4));
                    lines.push(<p key={i}><a>{i * 1 +1}</a><span dangerouslySetInnerHTML={{__html: o}}></span></p>);
                    break;
                case 'cmd':
                    var o = '$ ' + this.props.output[i].substr(4);
                    lines.push(<p key={i}><a>{i * 1 +1}</a><span className="line-cmd" dangerouslySetInnerHTML={{__html: o}}></span></p>);
                    break;
            }
        }
        last++;
        lines.push(<p key={last}><a>{last + 1}</a><span></span></p>);
        last++;
        if (this.props.isFinish) {
            lines.push(<p key={last}><a>{last + 1}</a><span className="line-finish">Finish</span></p>);
        } else {
            lines.push(<p key={last}><a>{last + 1}</a><span className="line-waiting">Waiting...</span></p>);
        }
        last++;
        lines.push(<p key={last}><a>{last + 1}</a><span></span></p>);

        return (
            <div className="output">
                {lines}
            </div>
        );
    }
});

var HostDetailButton = React.createClass({
    handleClick: function (e) {
        this.props.clicked(e);
    },
    render: function () {
        return (<button onClick={this.handleClick} className="btn btn-primary btn-xs">{this.props.children}</button>);
    }
});

var HostDetailModal = React.createClass({
    timeoutEvent: createTimeoutEvent(),

    componentDidUpdate: function (prevProps, prevState) {
        var height = $("#hostModal .modal-dialog").outerHeight(true);
        $("#hostModal .modal-backdrop").css('height', height + 'px');
    },

    loadDataFromServer: function () {
        $.getJSON('/api/site/' + siteId + '/deployhost/' + this.props.hostId, function (data) {
            if (data.code == 0) {
                this.setState(data.data);
                if (data.data.status == 'Deploying' || data.data.status == 'Waiting') {
                    this.timeoutEvent = window.setTimeout(this.loadDataFromServer, 3000);
                }
            } else {
                alert(data.msg);
            }
        }.bind(this));
    },
    componentDidMount: function () {
        this.loadDataFromServer();

        $("#hostModal").modal("show");
        $('#hostModal').data('bs.modal').handleUpdate();
        $('#hostModal').on('hidden.bs.modal', function (e) {
            this.timeoutEvent.clear();
        }.bind(this));
    },

    getInitialState: function () {
        return {};
    },

    render: function () {
        var isFinish = !(this.state.status == 'Deploying' || this.state.status == 'Waiting');
        var labels = {"Created": 'default', 'Waiting': 'default', 'Success': 'success', 'Error': 'danger', 'Doing': 'info', 'Finish': 'success'};
        var title = this.state.host_name == undefined ? '正在加载...' : (<span>{this.state.host_name} ({this.state.host_ip})&nbsp;&nbsp;<span className={'label label-' + labels[this.state.status]}>{this.state.status}</span></span>);
        var output = this.state.output === undefined ? (<div className="text-center"><img src="/static/ajax-loader.gif"/></div>) : (<OutputComponent isFinish={isFinish} output={this.state.output}/>);

        return (
             <DeployModal id="hostModal" lg="lg" title={title}>
                <div className="row">
                    <div className="col-lg-12">
                        {output}
                    </div>
                </div>
            </DeployModal>
        );
    }
});

var JobInfoTabContent = React.createClass({
    timeoutEvent: createTimeoutEvent(),
    loadStateFromServer: function () {
        var url = '/api/site/' + siteId + '/job/' + this.props.jobId + '?type=' + this.props.jobType;
        $.getJSON(url, function (data) {
            if (data.code == 0) {
                this.setState(data.data);
                if (data.status == 'Doing') {
                    this.timeoutEvent.timeout = window.setTimeout(this.loadStateFromServer, 3000);
                }
            } else {
                alert(data.msg);
            }
        }.bind(this));
    },
    componentDidMount: function () {
        this.loadStateFromServer();
    },
    getInitialState : function () {
        return { job: {id: this.props.jobId} };
    },
    handleHostDetail: function (e) {
        e.preventDefault();
        var btn  = $(e.target);
        var element = document.getElementById('normalModalWrapper');
        $(element).html('');
        React.render(<HostDetailModal hostId={btn.attr('data-host-id')} />, element);
    },
    killDeploy: function (e) {
        var btn = $(e.target);
        btn.button('loading');
        $.post('/api/site/' + siteId + '/kill-deploy', {
            _token: csrfToken,
            deploy_id: this.state.deploy.id
        }, function (data) {
            if (data.code != 0) {
                alert(data.msg);
            }
        }, 'json');
    },
    render: function () {
        var labels = {"Created": 'default', 'Waiting': 'default', 'Success': 'success', 'Error': 'danger', 'Deploying': 'info', 'Doing': 'info', 'Finish': 'success', 'Have Error': 'warning', 'Kill' : 'warning'};
        statusCls = 'label label-' + labels[this.state.job.status] + ' label-h4 ';
        var isFinish = this.state.job.status == 'Error' || this.state.job.status == 'Success' ? true : false;
        var output = this.state.job.output === undefined ? (<div className="text-center"><img src="/static/ajax-loader.gif"/></div>) : (<OutputComponent isFinish={isFinish} output={this.state.job.output}/>);

        var selfTable = '';
        if (this.props.jobType == 'deploy' && this.state.deploy != undefined) {
            var deploy = this.state.deploy;
            var status = deploy.status;
            if (deploy.total_hosts == deploy.error_hosts) {
                status = 'Error';
            } else if (deploy.error_hosts > 0 && status != 'Kill') {
                status = 'Have Error';
            }

            var operation = '';
            if (this.state.job.status == 'Doing' || this.state.job.status == 'Waiting') {
                operation = (<button className="btn btn-warning btn-xs" onClick={this.killDeploy}>终止发布</button>);
            }
            var costTime = Math.ceil(((new Date(convertDate(deploy.updated_at))).getTime() - (new Date(convertDate(deploy.created_at))).getTime()) / 1000);
            selfTable = (
                <div className="panel panel-default">
                    <div className="panel-heading">Deploy</div>
                    <div className="panel-body">
                        <table className="table table-hover small-table table-nomgb">
                            <thead>
                                <tr>
                                    <th>Commit</th>
                                    <th>发布到</th>
                                    <th>操作者</th>
                                    <th>状态</th>
                                    <th>主机数</th>
                                    <th>创建时间</th>
                                    <th>耗时(s)</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{deploy.commit.substr(0, 7)}</td>
                                    <td>{deploy.description}</td>
                                    <td>{deploy.user.name}</td>
                                    <td><span className={'label label-' + labels[status]}>{status}</span></td>
                                    <td><span className="label label-default label-num">{deploy.total_hosts}</span><span className="label label-success label-num">{deploy.success_hosts}</span><span className="label label-danger label-num">{deploy.error_hosts}</span></td>
                                    <td>{deploy.created_at}</td>
                                    <td>{costTime}</td>
                                    <td>{operation}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            );
        }

        var table = '';
        if (this.state.hosts != undefined) {
            var trs = this.state.hosts.map(function (host) {
                var btn = host.status == 'Waiting' ? '' : (<button onClick={this.handleHostDetail} className="btn btn-primary btn-xs" data-host-id={host.id}>详细输出</button>);
                var costTime = Math.ceil(((new Date(convertDate(host.updated_at))).getTime() - (new Date(convertDate(host.created_at))).getTime()) / 1000);
                return (
                    <tr key={"host-" + host.id}>
                        <td>{host.host_name}</td>
                        <td>{host.host_ip}</td>
                        <td>{host.host_port}</td>
                        <td><span className={'label label-' + labels[host.status]}>{host.status}</span></td>
                        <td>{host.created_at}</td>
                        <td>{costTime}</td>
                        <td>{btn}</td>
                    </tr>
                );
            }.bind(this));
            table = (
                <div className="panel panel-default">
                    <div className="panel-heading">机器列表</div>
                    <div className="panel-body">
                        <table className="table table-hover small-table table-nomgb">
                            <thead>
                                <tr>
                                    <th>主机名</th>
                                    <th>主机IP</th>
                                    <th>主机端口</th>
                                    <th>发布状态</th>
                                    <th>创建时间</th>
                                    <th>耗时(s)</th>
                                    <th>详细输出</th>
                                </tr>
                            </thead>
                            <tbody>
                                {trs}
                            </tbody>
                        </table>
                    </div>
                </div>
            );
        }
        return (
            <div className="container-fluid">
                <div className="row">
                    <div className="col-lg-12 ">
                        <div className="well">
                            <p className="job-title">Job #{this.state.job.id} &nbsp; <span className={statusCls}>{this.state.job.status}</span></p>
                            <p dangerouslySetInnerHTML={{__html: this.state.job.description}}></p>
                            <p>
                                创建时间：{this.state.job.created_at} &nbsp;&nbsp; 更新时间：{this.state.job.updated_at}
                            </p>
                        </div>
                    </div>
                </div>
                <div className="row">
                    <div className="col-lg-12">
                        {selfTable}
                    </div>
                </div>
                <div className="row">
                    <div className="col-lg-12">
                        {table}
                    </div>
                </div>
                <div className="row">
                    <div className="col-lg-12">
                        {output}
                    </div>
                </div>
            </div>
        );
    }
});

var DeployJobForm = React.createClass({
    emptySubmitHandle: function (e) {
        e.preventDefault();
    },
    getInitialState: function () {
        var commit = this.props.toDeploy ? this.props.toDeploy : '';
        return {alertType: null, alertMsg: null, commit: commit, commitError: null, deploy_kind: 'type', deploy_kindError: null, deploy_toError: null, deploy_to: null, envs: [], types: [], commits: []};
    },
    handleChange: function (e) {
        var state = this.state;
        var t = e.target;
        state[t.name] = t.value;
        state[t.name + 'Error'] = false;
        state.alertType = null;
        this.setState(state);
    },
    onDeployKindChange: function (e) {
        e.preventDefault();
        var t = e.target;
        var state = this.state;
        state.deploy_kind = t.value;
        state.deploy_to = '';
        this.setState(state);
    },
    handleSubmit: function (e) {
        e.preventDefault();
        var btn = $(e.target);
        var state = this.state;
        if (this.state.deploy_to == null || this.state.deploy_to.isEmpty()) {
            state.deploy_toError = true;
            state.alertType = 'error';
            state.alertMsg = '请选择发布到哪里';
            this.setState(state);
            return ;
        }
        if (this.state.commit == null || this.state.commit.isEmpty()) {
            state.commitError = true;
            state.alertType = 'error';
            state.alertMsg = '请选择Commit';
            this.setState(state);
            return ;
        }

        btn.button('loading');
        $.post('/api/site/' + this.props.siteId + '/deploy?type=' + this.props.deployType, {
            _token: csrfToken,
            deploy_kind: this.state.deploy_kind,
            deploy_to: this.state.deploy_to,
            commit: this.state.commit
        }, function (data) {
            btn.button('reset');
            state.alertMsg = data.msg
            if (data.code == 0) {
                state.alertType = 'success';
                this.setState(state);
                location.hash = '#JobHost-' + data.data.jobId + '-nj';
            } else {
                state.alertType = 'error';
                this.setState(state);
            }
        }.bind(this), 'json');
    },
    componentDidMount: function () {
        $.getJSON('/api/site/' + this.props.siteId + '/typenv?type=' + this.props.deployType, function (data) {
            if (data.code == 0) {
                state = this.state;
                state.envs = data.data.envs;
                state.commits = data.data.commits;
                state.types = data.data.types;
                state.hosts = data.data.hosts;
                this.setState(state);
            } else {
                alert(data.msg);
            }
        }.bind(this));
    },
    render: function () {
        var kind = '';
        if (this.state.deploy_kind == 'type') {
            var types = this.state.types.map(function (type) {
                if (loginUser.control(type.access_protected)) {
                    return (<option key={'type-' + type.id} value={type.id}>[{type.catalog.name}]&nbsp;&nbsp;{type.name}</option>);
                }
            });
            kind = (
                <Input bsStyle={this.state.deploy_toError ? 'error' : null} type="select" onChange={this.handleChange} name="deploy_to" value={this.state.deploy_to}>
                    <option key="type-0" value="">请选择...</option>
                    {types}
                </Input>
            );
        } else if (this.state.deploy_kind == 'env') {
            var envs = this.state.envs.map(function (env) {
                if (loginUser.control(env.access_protected)) {
                    return (<option key={'env-' + env.id} value={env.id}>{env.name}</option>);
                }
            });
            kind = (
                <Input bsStyle={this.state.deploy_toError ? 'error' : null} onChange={this.handleChange} type="select" name="deploy_to" value={this.state.deploy_to}>
                    <option key="env-0" value="">请选择...</option>
                    {envs}
                </Input>
            );
        } else {
            var hosts = this.state.hosts.map(function (host) {
                return (<option key={'host-' + host.id} value={host.id}>{host.name} {host.ip} {host.type}</option>);
            });
            kind = (
                <Input bsStyle={this.state.deploy_toError ? 'error' : null} onChange={this.handleChange} type="select" name="deploy_to" value={this.state.deploy_to}>
                    <option key="env-0" value="">请选择...</option>
                    {hosts}
                </Input>
            );
        }

        var commits = this.state.commits.map(function (commit) {
            if (this.props.deployType == 'deploy') {
                return (<option key={'commit-' + commit.id} onChange={this.handleChange} value={commit.commit}>[{commit.checkout.substr(0, 16)}]&nbsp;&nbsp;{commit.commit.substr(0, 7)}</option>);
            } else {
                return (<option key={'commit-' + commit.id} onChange={this.handleChange} value={commit.commit}>[{commit.number}]&nbsp;&nbsp;{commit.commit.substr(0, 7)}</option>);
            }
        }.bind(this));
        var btnName = this.props.deployType == 'deploy' ? 'Deploy' : 'PR Deploy';

        return (
            <form className="form-inline" role="form" onSubmit={this.emptySubmitHandle}>
                <Input type="select" name="deploy_kind" value={this.state.deploy_kind} onChange={this.onDeployKindChange}>
                    <option value="type">按机器分组</option>
                    <option value="env">按环境</option>
                    <option value="host">按机器</option>
                </Input>
                &nbsp;
                {kind}
                &nbsp;
                <Input type="select" name="commit" value={this.state.commit} onChange={this.handleChange}>
                    <option value="">请选择Commit...</option>
                    {commits}
                </Input>
                &nbsp;
                <Button bsStyle="primary" data-loading-text="加载中..." onClick={this.handleSubmit} autoComplete="off">{btnName}</Button>
                &nbsp; &nbsp;
                <InlineFormAlertComponent alertType={this.state.alertType} alertMsg={this.state.alertMsg}/>
            </form>
        );
    }
});

var WatchComponent = React.createClass({
    getInitialState: function () {
        return { isWatching: this.props.isWatching };
    },
    handleClick: function (e) {
        var btn = $(e.target);
        var action = btn.attr('class') == 'do-watch' ? 'watch' : 'unwatch';
        $.post('/api/site/' + this.props.siteId + '/' + action, {
            _token : csrfToken,
        }, function (data) {
            if (data.code == 0) {
                this.setState({isWatching: action == 'watch' ? true : false});
            } else {
                alert(data.msg);
            }
        }.bind(this), 'json');
    },
    render: function () {
        var watch = this.state.isWatching ? (
            <button type="button" className="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
                Watching <span className="caret"></span>
            </button>
        ) : (
            <button type="button" className="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                Not Watch <span className="caret"></span>
            </button>
        );
        return (
            <div className="btn-group">
                {watch}
                <ul className="dropdown-menu">
                    <li><a href="#" className="do-watch" onClick={this.handleClick} data-id="web2">Watch</a></li>
                    <li><a href="#" className="not-watch" onClick={this.handleClick} data-id="web2">Not Watch</a></li>
                </ul>
            </div>
        );
    }
});

var AddSiteHostsMany = React.createClass({
    emptySubmitHandle: function (e) {
        e.preventDefault();
    },

    handleSubmit: function (btn) {
        btn = $(btn);
        var state = this.state;
        if (state.hostList.isEmpty()) {
            state.alertType = 'danger';
            state.alertMsg = '不能为空';
            this.setState(state);
            return ;
        }
        btn.button('loading');
        $.post('/api/site/' + siteId + '/multihost', {
            _token: csrfToken,
            host_list: state.hostList
        }, function (data) {
            state.alertMsg = data.msg;
            if (data.code == 0) {
                state.alertType = 'success';
                setTimeout(function () {$("#ctModal").modal("hide")}, 1000);
                this.props.updateCallback == null ? '' : this.props.updateCallback();
            } else {
                state.alertType = 'danger';
            }
            btn.button('reset');
            this.setState(state);
        }.bind(this), 'json');
    },

    handleChange: function (e) {
        var state = this.state;
        state.hostList = e.target.value;
        state.alertType = null;
        this.setState(state);
    },

    getInitialState: function () {
        return {hostList: '', alertType: null, alertMsg: ''};
    },

    componentDidMount: function () {
        $("#ctModal").modal({
            backdrop: 'static',
            keyboard: false
        });
        $("#ctModal").modal("show");
    },

    render: function () {
        var groups = '';
        for (var i=0; i < this.props.data.host_types.length; i++) {
            groups = groups + this.props.data.host_types[i].name;
            if (i != this.props.data.host_types.length - 1) {
                groups += ', ';
            }
        }
        var areaStyle = {resize: 'none'};
        return (
            <DeployModal id="ctModal" title="批量添加主机" btn="添加" clickCallback={this.handleSubmit}>
                <div className="row">
                    <div className="col-lg-12">
                        <BlockAlert msgType={this.state.alertType}>{this.state.alertMsg}</BlockAlert>
                    </div>
                </div>
                <div className="row">
                    <div className="col-lg-12">
                        <form role="form" onSubmit={this.emptySubmitHandle}>
                            <p>格式 <code>分组名 发布类型 主机名 ip 端口</code> , 各个字段使用空格分隔</p>
                            <p>主机分组有：{groups}</p>
                            <p>发布类型有：STATIC, APP</p>
                            <Input value={this.state.hostList} id="hostList" onChange={this.handleChange} name="host_list" rows="6" sytle={areaStyle} type="textarea">
                            </Input>
                        </form>
                    </div>
                </div>
            </DeployModal>
        );
    }
});


;
