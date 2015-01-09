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
                            <input className="form-control" placeholder="Name" onChange={this.handleChange} name="name" type="text" value={this.state.name} autofocus/>
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
            return (
                <li className={liClass}>
                    <a href={data.url}><i className="fa fa-wrench fa-fw"></i> {data.name}<span className="fa arrow"></span></a>
                    <NavUlComponent extraClassName="nav-second-level" lists={data.children}/>
                </li>
            );
        }
    }
});

var NavUlComponent = React.createClass({
    render: function () {
        var navNodes = this.props.lists.map(function (list) {
            return (
                <NavUlLiComponent key={list.name} data={list}/>
            );
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
        console.log(this.props.data);
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
        return (
        <div className="modal fade" id={this.props.id} tabIndex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div className="modal-dialog">
            <div className="modal-content">
                <div className="modal-header">
                    <button type="button" className="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 className="modal-title" id="myModalLabel">{this.props.title}</h4>
                </div>
                <div className="modal-body">
                    {this.props.children}
                </div>
                <div className="modal-footer">
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
        var state = this.state;
        state.name = e.target.value;
        state.nameError = false;
        state.alertType = null;
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
                name: this.state.name
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
                name: this.state.name
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
            return {name: this.props.data.name, nameError: false, modalTitle: '修改发布环境', btn: "保存", alertType: null, alertMsg: ''};
        }
        return {name: '', nameError: false, modalTitle: '新建发布环境', btn: "新建", alertType: null, alertMsg: ''};
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
        var titleName = this.props.type == 'new' ? '新建Host Type' : '修改Host Type';
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
                        <div className="col-lg-12"><div class="alert">hi</div></div>
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

;
