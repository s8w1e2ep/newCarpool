$(document).ready(function(e) {
    document.addEventListener("backbutton", onBackKeyDown, false);
    url = window.location.toString();
    initialize();
});

$('#logout').click(function() {
    logout();
})

$('#status').click(function() {
    status();
})

var url = "";
var id = "";
var admin = ['1046779538684826', '678671252207481', '779892335364114', '860467000642654'];

var server = "http://120.114.186.4:8080/carpool/api/";
var local = "file:///android_asset/www/";


function initialize() {
    var str = url.substring(url.indexOf("{"), url.length);
    var json = JSON.parse(decodeURIComponent(str));
    id = json.id;

    for (var i = 0; i < admin.length; i++) {
        if (id.match(admin[i]) != null) {
            $('#status').css("display", "block");
            break;
        }
    }

    getName();
    setURL();
}

function getName() {
    var url = server + 'get_name.php?data={"id":"' + id + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var name = xmlhttp.responseText;
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
            var phone = xmlhttp.responseText;
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

function logout() {
    window.location = local + 'index.html';
}

function status() {
    window.location = local + 'uncertified.html?data={"id":"' + id + '"}';
}

function onBackKeyDown() {
    var temp = '?data={"id":"' + id + '"}';
    window.location = local + 'index.html' + temp;
}
