
var WaitProgressComponent = React.createClass({displayName: "WaitProgressComponent",
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
            React.createElement("div", null, 
                React.createElement("div", {className: "text-center"}, notify), 
                React.createElement("br", null), 
                React.createElement("div", {className: "progress"}, 
                    React.createElement("div", {className: "progress-bar progress-bar-striped active", role: "progressbar", "aria-valuenow": "100", "aria-valuemin": "0", "aria-valuemax": "100", style: style}
                    )
                )
            )
        );
    }
});
