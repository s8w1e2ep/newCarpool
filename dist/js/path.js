var url = window.location.toString();
var id = "";
var role = "";
var json = "";
var file = "";
var passenger_json = "";
var his_json = ""; //紀錄history json

var server = "http://120.114.186.4:8080/carpool/api/";
var local = "file:///android_asset/www/";

function initialize() {
    var str = url.substring(url.indexOf("{"), url.length);
    file = str;
    json = JSON.parse(decodeURIComponent(str));

    id = json.id;
    role = json.role;
    file = decodeURIComponent(file);

    GetCurrentPos(id, role);
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

function requestAPI(url, data, gid) //傳資料給php
{
    url += '?data=' + data;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            if (gid === "match") {
                var jstr = xmlhttp.responseText; //php回傳的媒合結果--json string
                console.log("Jstr: ", jstr);

                if (jstr.match("NoDriver") != null) {
                    alert("沒有司機符合評價與性別門檻");
                } else if (jstr.match("NoOverlap") != null) {
                    alert("沒有重疊路徑");
                } else if (jstr.match("NoMatch") != null) {
                    alert("沒有司機符合媒合條件");
                } else {
                    window.location = local + 'result.html?data=' + jstr; //跳轉到result頁面
                }
            }
        }
    }
    xmlhttp.send();
}

function nextStep(pathJSON, PathLength) {
    his_json = "";
    var driver_json = "";
    var temp_json = "";
    var pathTemp = JSON.parse(pathJSON);
    temp_json = JSON.stringify(json);

    //history json
    his_json = file;
    //passenger json
    passenger_json = file.substring(0, file.length - 1) + ',"path":' + pathJSON + ',"total":' + PathLength + ',"start":' + '{"at":"' + (new Number(StartPoint.lat())).toFixed(14) + '","ng":"' + (new Number(StartPoint.lng())).toFixed(14) + '"}' + ',"end":' + '{"at":"' + (new Number(pathTemp[pathTemp.length - 1].at)).toFixed(14) + '","ng":"' + (new Number(pathTemp[pathTemp.length - 1].ng)).toFixed(14) + '"}}';
    //driver json
    driver_json = temp_json.substring(0, temp_json.length - 1) + ',"path":' + pathJSON + '}';

    console.log("passenger_json: ", passenger_json);

    if (role == "driver") {
        var url = "";
        url = server + 'driver.php';
        console.log("driver: " + url);
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.open("POST", url, true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                window.location = local + 'traceDriverPage.html?data={"id":"' + id + '"}';
            }
        }
        xmlhttp.send('data=' + driver_json);
    } else if (role == "passenger") {
        requestAPI(server + "path.php", passenger_json, "match"); //取得評價篩選後的司機路徑 => 88行
    }
}
