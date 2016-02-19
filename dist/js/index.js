$(document).ready(function() {
    document.addEventListener("backbutton", onBackKeyDown, false);
    url = window.location.toString();

    if (url.indexOf("data") > 0)
        initialize();

});
//fb登入
$('#login1').click(function() {
    $('#loginbyphone').attr('style', 'display:none');
    loginFacebook();
});
//會員登入
$('#login2').click(function() {
    $('#loginbyphone').attr('style', 'display:table');
    $('.wrapperInside').attr('style', 'background-color: #666666;');
});

$('#check').click(function() {
    var phone = $('#phone_number').val();
    var password = $('#password').val();
    var url = server + 'check_member.php?data={"id":"' + phone + '","password":"' + password + '","regid":"' + regid + '"}';
    console.log(url);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var res = xmlhttp.responseText;
            if (res.match("success")) {
                $('#loginbyphone').attr('style', 'display:none');
                $('.wrapperInside').attr('style', 'background-color: #FFFFFF;');
                id = phone;
                checkCarpool();
            } else {
                alert(res);
            }
        }
    }
    xmlhttp.send();
});
//cancel 會員dialog
$('#cancel').click(function() {
    $('#loginbyphone').attr('style', 'display:none');
    $('.wrapperInside').attr('style', 'background-color: #FFFFFF;');
});
//fb註冊
$('#submit').click(function() {
    registerCarpool();
});

$('#register_button').click(function() {
    show();
});
//司機
$('#driver').click(function() {
    nextDriver();
});
//乘客
$('#passenger').click(function() {
    nextPassenger();
});
//會員註冊
$('#registermember').click(function() {
    var name = $('#rname').val();
    var gender;

    if ($('#male').attr('checked') == true)
        gender = "male";
    else if ($('#female').attr('checked') == true)
        gender = "female";

    var phone = $('#rphonenumber').val();
    var password = $('#rpassword').val();

    //接原本 registerCarpool();
});

$('#test').click(function() {
    window.location = local + 'register.html?data={"regid":"' + regid + '"}';
});

//global variable
var url = "";

var id = "";
var name = "";
var phone = "";
var gender = "";
var email = "";
var rvalue = "";
var check = 0;

var server = "http://120.114.186.4:8080/carpool/api/";
var local = "file:///android_asset/www/";

var pushNotification = "";
var regid = "";

function initialize() {
    var str = url.substring(url.indexOf("{"), url.length);
    var json = JSON.parse(decodeURIComponent(str));

    id = json.id;

    $('#login1').attr('style', 'display:none');
    $('#login2').attr('style', 'display:none');
    $('#test').attr('style', 'display:none');

    getName();
    getGender();
    getRating();

    $('#driver').css("display", "block");
    $('#passenger').css("display", "block");

    setURL();
}

function setURL() {
    var temp = '?data={"id":"' + id + '"}';

    $('#board').attr('href', local + 'board.html' + temp);
    $('#wall').attr('href', local + 'wall.html' + temp);
    $('#friendlist').attr('href', local + 'friendlist.html' + temp);
    $('#about').attr('href', local + 'about.html' + temp);
    $('#setting').attr('href', local + 'setting.html' + temp);
    $('#edit').attr('href', local + 'edit.html' + temp);
    $('#logo').attr('href', local + 'index.html' + temp);
    $('#dsgr').attr('href', local + 'index.html' + temp);
}

function getRating() {
    var url = server + 'get_rating.php?data={"id":"' + id + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            rvalue = xmlhttp.responseText;
            document.getElementById('rating').innerHTML = rvalue + '     ';
            $("#jRate").jRate({
                startColor: 'yellow',
                endColor: 'yellow',
                backgroundColor: 'lightgray',
                shapeGap: '5px',
                rating: rvalue,
                readOnly: true
            });
            $('#rbox').css("display", "block");
        }
    }
    xmlhttp.send();
}

function getGender() {
    var url = server + 'get_gender.php?data={"id":"' + id + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            $('#gender').html(xmlhttp.responseText);
            $('#gbox').css("display", "block");
        }
    }
    xmlhttp.send();
}

function getName() {
    var url = server + 'get_name.php?data={"id":"' + id + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            name = xmlhttp.responseText;
            $('#pname').html(name);
            $('#pname2').html(name);
            $('#name').html(name);
            getPhone();
        }
    }
    xmlhttp.send();
}

function getPhone() {
    var url = server + 'get_phone.php?data={"id":"' + id + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            phone = xmlhttp.responseText;
            setPic();
            $('#state').html('已登入');
            $('#tel').html(phone);
        }
    }
    xmlhttp.send();
}

function setInfo(response) {
    id = response.id;
    name = response.name;
    gender = response.gender;
    email = response.email;
    // alert("verified: " + response.verified);
    //alert("link: " + response.link);

    if (name.length > 0)
        checkCarpool();
}

function checkCarpool() {
    var url = server + 'check.php?data={"id":"' + id + '","regid":"' + regid + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            //registed
            if (xmlhttp.responseText.trim() === "success") {
                window.location = local + 'index.html?data={"id":"' + id + '"}';
            } else if (xmlhttp.responseText.trim() === "uncertified") {
                $('#login1').attr('style', 'display:none');
                $('#login2').attr('style', 'display:none');
                $('#test').attr('style', 'display:none');
                $('#state').html('尚未審核');
                $('#name').html('Hi, ' + name);
                $('#dialog_name').html(name);
                setPic();
            } else {
                $('#login1').attr('style', 'display:none');
                $('#login2').attr('style', 'display:none');
                $('#test').attr('style', 'display:none');
                $('#register_button').attr('style', 'display:');
                $('#state').html('尚未註冊');
                $('#name').html('Hi, ' + name);
                $('#dialog_name').html(name);
                setPic();
            }
        }
    }
    xmlhttp.send();
}

//設定大頭貼
function setPic() {
    if (id.length == 10 && id.substr(0, 2) === "09") {
        var url = server + 'get_image.php?data={"id":"' + id + '"}';
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.open("GET", url, true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var result = "http://120.114.186.4:8080/carpool/" + xmlhttp.responseText.trim();
                $('#image').attr('src', result);
                $('#dialog_image').attr('src', result);
                $('#user_image').attr('src', result);
            }
        }
        xmlhttp.send();
    } else {
        $('#image').attr('src', 'http://graph.facebook.com/' + id + '/picture?type=large');
        $('#dialog_image').attr('src', 'http://graph.facebook.com/' + id + '/picture?type=large');
        $('#user_image').attr('src', 'http://graph.facebook.com/' + id + '/picture?type=large');
    }
}

function show() {
    $('#dialog').attr('style', 'display:table');
    $('#register_button').attr('style', 'display:none');
    $('.wrapperInside').attr('style', 'background-color: #666666;');
}

function registerCarpool() {
    //get input
    var phone = $('#phone').val();

    if (phone.length == 10) {
        var data = [];
        data.push({
            'id': id,
            'name': name,
            'gender': gender,
            'email': email,
            'phone': phone,
            'regid': regid
        });
        data = JSON.stringify(data);
        data = data.substring(1, data.length - 1);
        var url = server + 'register.php?data=' + data;
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.open("GET", url, true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.onreadystatechange = function() {
            //registed
            if (xmlhttp.responseText.trim() === "success") {
                $('.wrapperInside').attr('style', 'background-color: #FFFFFF;');
                $('#dialog').attr('style', 'display:none');
                var temp = [];
                temp.push({
                    'id': id
                });
                var data = JSON.stringify(temp);
                data = data.substring(1, data.length - 1);
                checkCarpool();
                // window.location = (local + 'index.html?data=' + data);
            } else {
                $('#register_state').html(xmlhttp.responseText);
            }

        }
        xmlhttp.send();
    } else {
        $('#register_state').html('手機長度錯誤');
    }
}


function nextDriver() {
    var temp = [];
    temp.push({
        'id': id
    });
    var data = JSON.stringify(temp);
    data = data.substring(1, data.length - 1);
    window.location = local + 'driver.html?data=' + data;
}

function nextPassenger() {
    var temp = [];
    temp.push({
        'id': id
    });
    var data = JSON.stringify(temp);
    data = data.substring(1, data.length - 1);
    window.location = local + 'passenger.html?data=' + data;
}

//facebook api
var loginFacebook = function() {
    facebookConnectPlugin.login(["email"],
        function(response) {
            apiTest()
        },
        function(response) {
            alert(JSON.stringify(response))
        });
}
var apiTest = function() {
    facebookConnectPlugin.api("me/?fields=id,name,gender,email,link", ["user_birthday"],
        function(response) {
            setInfo(response);
        },
        function(response) {
            alert(JSON.stringify(response))
        });
}
var logout = function() {
    facebookConnectPlugin.logout(
        function(response) {
            alert(JSON.stringify(response))
        },
        function(response) {
            alert(JSON.stringify(response))
        });
}

//確認device ready
function onDeviceReady() {
    try {
        var push = PushNotification.init({
            "android": {
                "senderID": "47580372845",
                "image": "http://120.114.186.4/carpool/assets/logo.png"
            },
            "ios": {},
            "windows": {}
        });
        //取得註冊ID
        push.on('registration', function(data) {
            // alert(data.registrationId);
            regid = data.registrationId;
        });

        push.on('error', function(e) {
            console.log("push error");
        });
    } catch (err) {
        txt = "There was an error on this page.\n\n";
        txt += "Error description: " + err.message + "\n\n";
        alert(txt);
    }
}

function onBackKeyDown() {
    if (confirm('確定退出嗎?')) {
        navigator.app.exitApp(); //確定後退出
    }
}

document.addEventListener('deviceready', onDeviceReady, true);
