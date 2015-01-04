
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
