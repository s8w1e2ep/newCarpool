$(document).ready(function() {
    initialize();
});

var url = window.location.toString();
var id = "";
var rid = "";
var role = "";
var target = "";
var index = 0;
var rate = 0;

var server = "http://120.114.186.4/carpool/api/";
var local = "file:///android_asset/www/";

/*
 *   data={"id":"860467000642654","role":"driver" ,"rid":["779892335364114","1046779538684826","id3"]}
 */

function initialize() {
    var str = url.substring(url.indexOf("{"), url.length);
    var json = JSON.parse(decodeURIComponent(str));

    id = json.id;
    role = json.role;
    rid = json.rid;

    setTarget();
    check();

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
            var phone = xmlhttp.responseText;
            $('#tel').html(phone);
        }
    }
    xmlhttp.send();
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
    $('#user_image').attr('src', 'http://graph.facebook.com/' + id + '/picture?type=large');
}

function check() {
    var url = server + 'check_friend.php?data={"id":"' + id + '","fid":"' + rid[index] + '"}';

    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var res = xmlhttp.responseText;
            if (res.match("success"))
                $('#add').css("display", "block");
        }
    }
    xmlhttp.send();
}

function setTarget() {
    console.log(index);
    $('#pimage').attr('src', 'http://graph.facebook.com/' + rid[index] + '/picture?type=large');
    setName(rid[index], "rname");
}

function setName(data, mode) {
    var url = server + 'get_name.php?data={"id":"' + data + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            $('#' + mode).html(xmlhttp.responseText);
            console.log($('#' + mode).html());
        }
    }
    xmlhttp.send();
}

function addRating() {
    var comment = $('#comment').val();
    rate = $('#input-21e').val();
    if (rate == 0) {
        alert("最低評價為1分!");
    } else {
        var data = '{"id":"' + id + '","uid":"' + rid[index] + '","role":"' + role + '","rating":"' + rate + '","comment":"' + comment + '"}';
        alert(data);
        var url = server + 'add_rating.php?data=' + data;
        console.log(url);
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.open("GET", url, true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                console.log(xmlhttp.responseText);
                updateFinished();
            }
        }
        xmlhttp.send();
    }
}

function updateFinished() {
    var data = '';
    if (role === "passenger") {
        data = '{"pid":"' + id + '"}';
    } else if (role === 'driver') {
        data = '{"did":"' + id + '"}';
    }

    var url = server + 'update_finished.php?data=' + data;
    console.log(url);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

            console.log(xmlhttp.responseText);
            index++;
            if (index < rid.length) {
                $('#comment').val('');
                setTarget();
            } else if (index == rid.length) {
                window.location = local + 'index.html?data=' + '{"id":"' + id + '"}'
            }
        }
    }
    xmlhttp.send();
}

function nextStep() {
    addRating();
}

function addFriend() {
    var data = '{"id":"' + id + '","fid":"' + rid[index] + '"}';
    var url = server + 'add_friend.php?data=' + data;

    console.log(url);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var res = xmlhttp.responseText;
            $('#add').css("display", "none");
            console.log(res);
            var status = document.getElementById("status");
            if (res.match("success"))
                status.innerHTML = '成功加入好友';
            else if (res.match("failed"))
                status.innerHTML = '加入失敗';
        }
    }
    xmlhttp.send();
}
