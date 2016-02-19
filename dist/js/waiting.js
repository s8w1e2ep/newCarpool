$(document).ready(function() {
    url = window.location.toString();
    initialize();
});

// $('#accept').click(function() {
// var value = $('#accept').val();
// confirmCarpool();
// });

// $('#reject').click(function() {
// cancelCarpool();
// });

var url = "";
var id = "";
var tid = "";
var json = "";
var trace_str = "";
var count = 0;
var num = 0;

var server = "http://120.114.186.4:8080/carpool/api/";
var local = "file:///android_asset/www/";

var countdownnumber = 300;
var countdownid, x;

function countdownfunc() {
    x.innerHTML = countdownnumber;
    if (countdownnumber == 0) {
        alert("倒數結束!");
        clearInterval(countdownid);
        window.location = local + 'index.html?data={"id":"' + id + '"}';
    }
    countdownnumber--;
    var show = document.getElementById("show_time");
    show.innerHTML = parseInt(countdownnumber / 60, 10) + "分" + (countdownnumber % 60) + "秒";
}

function initialize() {
    var str = url.substring(url.indexOf("{"), url.length);
    trace_str = str;
    console.log(trace_str);
    json = JSON.parse(decodeURIComponent(str));
    id = json.id;
    num = json.num;
    json = decodeURIComponent(str);

    x = document.getElementById("countdown");
    x.innerHTML = countdownnumber;
    countdownnumber--;
    countdownid = window.setInterval(countdownfunc, 1000);


    getName();
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
            setPic();
            phone = xmlhttp.responseText;
            $('#tel').html(phone);
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
                $('#user_image').attr('src', result);
            }
        }
        xmlhttp.send();
    } else {
        $('#user_image').attr('src', 'http://graph.facebook.com/' + id + '/picture?type=large');
    }
}

// passenger: {"role":"passenger","id":"838717559541922","result":[{"did":"1046779538684826"},{"did":"1046779538684826"}]}
function confirmCarpool() {
    $('#mbody').attr('style', 'background-color: #FFFFFF;');
    count++;
    console.log(trace_str);
    if (count == num)
        window.location = local + 'tracePassengerPage.html?data=' + trace_str;
}

function setName(data, mode) {
    var url = server + 'get_name.php?data={"id":"' + data + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            $('#image').attr('src', 'http://graph.facebook.com/' + data + '/picture?type=large');
            document.getElementById(mode).innerHTML = xmlhttp.responseText;
        }
    }
    xmlhttp.send();
}

//確認device ready
function onDeviceReady() {

    try {
        var push = PushNotification.init({
            "android": {
                "senderID": "47580372845"
                    //"image": "http://120.114.186.4/carpool/assets/logo.png"
            },
            "ios": {},
            "windows": {}
        });

        //通知設定
        push.on('notification', function(data) {
            var additional = JSON.stringify(data.additionalData);
            additional = JSON.parse(additional);
            //alert("tid: " + additional.tid);
            document.getElementById("message").innerHTML = data.message;
            if (additional.foreground) {
                setName(additional.tid, 'name');
                $('#dialog').css("display", "table");
                $('.wrapperInside').attr('style', 'background-color: #666666;');
            } else {
                setName(additional.tid, 'name');
                $('#dialog').css("display", "table");
                $('.wrapperInside').attr('style', 'background-color: #666666;');
            }

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

document.addEventListener('deviceready', onDeviceReady, true);
