$(document).ready(function() {
    url = window.location.toString();
    initialize();
});

// $('#submit').click(function() {
// sendGCM();
// });

// $('#cancel').click(function() {
// $('#dialog').css("display", "none");
// });

var url = "";
var id = "";
var did = "";
var did2 = "";
var did3 = "";

var json = "";

var server = "http://120.114.186.4/carpool/api/";
var local = "file:///android_asset/www/";

function initialize() {
    var str = url.substring(url.indexOf("{"), url.length);
    json = JSON.parse(decodeURIComponent(str));
    id = json.id;

    var result = json.result;
    var temp = json.result[0].did;

    result = JSON.stringify(result);
    json = decodeURIComponent(str);

    //set Table
    console.log("json:" + json);
    requestAPI(server + "result.php", json, "table1");
    requestAPI(server + "result2.php", json, "table2");
    //requestAPI(server + "result3.php", json, "table3");

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
    $('#user_image').attr('src', 'http://graph.facebook.com/' + id + '/picture?type=large');
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
            $('#name').html('Hi, ' + name);
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
            $('#image').attr('src', 'http://graph.facebook.com/' + id + '/picture?type=large');
            $('#state').html('已登入');
            $('#tel').html(phone);
        }
    }
    xmlhttp.send();
}

function requestAPI(url, data, mode) {
    var xmlhttp = new XMLHttpRequest();
    url += "?data=" + data;
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById(mode).innerHTML = xmlhttp.responseText;
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}

function setDialog(value) {
    $('#mbody').attr('style', 'background-color: #666666;');
    $('#dialog').css("display", "table");
    $('#dialog_image').attr('src', 'http://graph.facebook.com/' + value + '/picture');
    requestAPI(server + "get_name.php", '{"id":"' + value + '"}', "dialog_name");

    did = value;
}

function setDialog2(value1, value2) {
    $('#mbody').attr('style', 'background-color: #666666;');
    $('#dialog').css("display", "table");
    $('#dialog_image').attr('src', 'http://graph.facebook.com/' + value1 + '/picture');
    requestAPI(server + "get_name.php", '{"id":"' + value1 + '"}', "dialog_name");
    $('#dialog_image2').attr('src', 'http://graph.facebook.com/' + value2 + '/picture');
    $('#dialog_image2').css("display", "inline");
    requestAPI(server + "get_name.php", '{"id":"' + value2 + '"}', "dialog_name2");

    did = value1;
    did2 = value2;

}

function showResult2(value) {
    var cid = '#child' + value;
    $(cid).css("display", "inline");
    $("#table1").hide();
}

function showResult3(value1, value2) {
    var cid = '#lastchild' + (value1 * 10 + value2);
    $(cid).css("display", "inline");
    $("#table2").hide();
}

function confirm() {
    $('#mbody').attr('style', 'background-color: #FFFFFF;');
    var wait_str = 'waiting.html?data={"id":["' + id + '","';
    var num = 0;
    if (did != "") {
        num++;
        sendGCM(did);
        wait_str += did + '"';
    }
    if (did2 != "") {
        num++;
        sendGCM(did2);
        wait_str += ',"' + did2 + '"';
    }
    if (did3 != "") {
        num++;
        sendGCM(did3);
        wait_str += ',"' + did3 + '"';
    }
    wait_str += '], "num":"' + num + '"}';

    console.log(wait_str);
    window.location = local + wait_str;
}

function sendGCM(driver_id) {
    var xmlhttp = new XMLHttpRequest();
    url = server + 'gcm_server.php?data={"id":"' + id + '","tid":"' + driver_id + '","mode":"1"}';
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            alert(xmlhttp.responseText);
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}

function cancel() {
    $('#mbody').attr('style', 'background-color: #FFFFFF;');
    $('#dialog').css("display", "none");
    did = "";
    did2 = "";
    did3 = "";
}
