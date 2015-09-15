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
 *   data={"id":"860467000642654", "rid":["779892335364114","1046779538684826","id3"]}
 */

function initialize() {
    var str = url.substring(url.indexOf("{"), url.length);
    var json = JSON.parse(decodeURIComponent(str));

    id = json.id;
    role = json.role;
    rid = json.rid;

    setTarget();

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

// function getTarget() {
//     var url = server + 'get_unrated.php?data={"id":"' + id + '","role":"' + role + '"}';
//     var xmlhttp = new XMLHttpRequest();
//     xmlhttp.open("GET", url, true);
//     xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
//     xmlhttp.onreadystatechange = function() {
//         if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
//             target = JSON.parse(xmlhttp.responseText);
//             setTarget();
//         }
//     }
//     xmlhttp.send();
// }

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
    var data = '{"id":"' + id + '","uid":"' + rid[index] + '","role":"' + role + '","rating":"' + rate + '","comment":"' + comment + '"}';
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
            alert("進入update finished");
            console.log(xmlhttp.responseText);
            index++;
            if (index < rid.length) {
                setTarget();
            }

            $('#comment').val('');

            if (index == rid.length - 1) {
                // setTimeout(function() {
                window.location = local + 'index.html?data=' + '{"id":"' + id + '"}'
                    // }, 5000);
            }
        }
    }
    xmlhttp.send();
}

function nextStep() {
    addRating();

    // if (index < rid.length) {
    //     addRating();

    //     index++;

    //     if (index < target.length - 1) {
    //         setTarget();
    //     }
    //     $('#comment').val('');
    // } else
    //     window.location = local + 'index.html?data=' + '{"id":"' + id + '"}';
}
