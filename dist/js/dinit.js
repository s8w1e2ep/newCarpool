$(document).ready(function() {
    url = window.location.toString();
    initialize();
});

$('#next').click(function() {
    nextStep();
});

var url = "";
var id = "";

var server = "http://120.114.186.4/carpool/api/";
var local = "file:///android_asset/www/";

function initialize() {
    var str = url.substring(url.indexOf("{"), url.length);
    var json = JSON.parse(decodeURIComponent(str));
    id = json.id;

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

function getInput() {
    var rating = $('#rating').val();
    var time = $('#time').val();
    var gender = $('#gender').val();
    var percentage = $('#percentage').val();

    var json = '"condition":[' + '{';

    json += '"rating":"' + rating + '",';
    json += '"gender":"' + gender + '",';
    json += '"waiting":"' + time + '",';

    json = '"role":"driver",' + json;

    var cid = $('#cid').val();
    var seat = $('#seat').val();


    json += '"cid":"' + cid + '",';
    json += '"seat":"' + seat;

    return json + '"}]';
}

function nextStep() {
    var json = '{"id":"' + id + '",' + getInput() + '}';
    updateCar(json);
}

function updateCar(json) {
    var cid = $('#cid').val();

    var url = server + 'update_cid.php?data={"id":"' + id + '","cid":"' + cid + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            window.location = local + 'path.html?data=' + json;
            console.log("success");
        }
    }
    xmlhttp.send();
}
