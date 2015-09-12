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

var server = "http://120.114.186.4/carpool/api/";
var local = "file:///android_asset/www/";

function initialize() {
    var str = url.substring(url.indexOf("{"), url.length);
    trace_str = str;
    console.log(trace_str);
    json = JSON.parse(decodeURIComponent(str));
    id = json.id;
    num = json.num;
    json = decodeURIComponent(str);

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

// passenger: {"role":"passenger","id":"838717559541922","result":[{"did":"1046779538684826"},{"did":"1046779538684826"}]}
function confirmCarpool() {
    $('#mbody').attr('style', 'background-color: #FFFFFF;');
    count++;
    console.log(trace_str);
    if (count == num)
        window.location = local + 'tracePassengerPage.html?data=' + trace_str;
}
