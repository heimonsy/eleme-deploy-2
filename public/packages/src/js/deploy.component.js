
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
        console.log(this.state.name.isEmpty());
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
