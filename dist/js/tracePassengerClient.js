$(document).ready(function() {
    // RESIZE SCREEN HEIGHT
    resizeScreen();

    // INITIALIZE GOOGLE MAPS
    InitializeMap();

    InitializePassenger();

    DetectCurPoint();
    setInterval(DetectCurPoint, 6000);

    $('.wrapperInside').attr('style', 'background-color: rgba(0,0,0,0.5);');
    $('#ahead').click(function(){
        removeDriver();
    });

    $('#ok2').click(function() {
        $('#dialog').css("display", "none");
        updateHistory();
        if (did.length == 1) {
            var arr = [];
            arr.push(tid);
            var arr_str = JSON.stringify(arr);
            window.location = local + 'rating.html?data={"id":"' + pid + '","role":"passenger","rid":' + arr_str + '}';
        } else {
            for (var i = 0; i < did.length; i++) {
                if (did[i] != tid) {
                    var count = 1;
                    var data2 = [];
                    data2.push({
                        'id': pid,
                        'tid': did[i],
                        'pnum': pnum,
                        'mode': 7
                    });
                    data2 = JSON.stringify(data2);
                    data2 = data2.substring(1, data2.length - 1);
                    var xmlhttp2 = new XMLHttpRequest();
                    url = server + 'gcm_server.php?data=' + data2;
                    xmlhttp2.onreadystatechange = function() {
                        if (xmlhttp2.readyState == 4 && xmlhttp2.status == 200) {
                            count++;
                            if (count == did.length) {
                                var arr = [];
                                arr.push(tid);
                                var arr_str = JSON.stringify(arr);
                                window.location = local + 'rating.html?data={"id":"' + pid + '","role":"passenger","rid":' + arr_str + '}';
                            }
                        }
                    }
                    xmlhttp2.open("GET", url, true);
                    xmlhttp2.send();
                }
            }
        }
    });

    $('#cancel').click(function() {
        //cancel carpool
        var count = 0;
        for (var i = 0; i < did.length; i++) {
            var data = [];
            data.push({
                'id': pid,
                'tid': did[i],
                'pnum': pnum,
                'mode': 6
            });
            data = JSON.stringify(data);
            data = data.substring(1, data.length - 1);
            var xmlhttp = new XMLHttpRequest();
            url = server + 'gcm_server.php?data=' + data;
            xmlhttp.onreadystatechange = function() {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    count++;
                    if (count == did.length) {
                        window.location = local + 'index.html?data={"id":"' + pid + '"}';
                    }
                }
            }
            xmlhttp.open("GET", url, true);
            xmlhttp.send();
        }
    });
}).on('deviceready', onDeviceReady);

//global variables
var map;
var local = "file:///android_asset/www/";
var server = "http://120.114.186.4:8080/carpool/api/";
var COLOR = ["#176ae6", "#ff0000", "#6a3906", "#800080"];

var global_url = window.location.toString();
// passenger id
var pid = null;
var pnum;
var dnum;

//gcm
var mode;
var tid = null;

// driver is
var did = [];
var rid = [];
var cid = []; //store the cid of the driver
// first driver index map to carpool path two-dimentional array index
var currentDid = 0;

// driver variables
var driverList = [];
var DriverObj = function() {
    return {
        'Name': null,
        'Path': null,
        'Point': {
            'Getin': null,
            'Getoutoff': null,
            'Current': null
        },
        'Marker': {
            'Path': null,
            'Getin': null,
            'Getoutoff': null,
            'Current': null,
            'InfoWindow': null
        }
    }
};

// passenger list and passenger object
var passenger = null;
var PassengerObj = function() {
    return {
        'Name': null,
        'Path': null,
        'Point': {
            'Start': null,
            'End': null,
            'Current': null
        },
        'Marker': {
            'Path': null,
            'Start': null,
            'End': null
        }
    }
};

function InitializeMap() {
    //set map options
    var mapOptions = {
        zoom: 16,
        center: new google.maps.LatLng(22.975375, 120.218936),
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };

    //set map
    map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
}

function InitializePassenger() {
    var thestr = global_url.substring(global_url.indexOf("{"), global_url.length);
    var thejson = JSON.parse(decodeURIComponent(thestr)).id;
    pid = thejson[0];
    pnum = JSON.parse(decodeURIComponent(thestr)).pnum;

    // add drivers
    for (var i = 1; i < thejson.length; i++) {
        AddDriver(thejson[i], i - 1);
    }
    passenger = new PassengerObj();

    var toServerStr = '{"init": 1,"role": 1, "pid":"' + pid + '"}';
    var url = server + 'tracePassengerServer.php?data=' + toServerStr;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var result = JSON.parse(xmlhttp.responseText);
            passenger.Name = result.passenger.Name;
            passenger.Path = ConvertToGoogleLatLng(result.passenger.Path);
            passenger.Point.Current = new google.maps.LatLng(result.passenger.CurPoint.at, result.passenger.CurPoint.ng);
            passenger.Point.Start = passenger.Path[0];
            passenger.Point.End = passenger.Path.last();

            // set map path marker
            passenger.Marker.Path = new google.maps.Polyline({
                path: passenger.Path,
                geodesic: true,
                strokeColor: '#000',
                strokeOpacity: 0.7,
                strokeWeight: 8
            });
            passenger.Marker.Path.setMap(map);

            // set map start point marker
            passenger.Marker.Start = new google.maps.Marker({
                position: passenger.Point.Start,
                map: map,
                icon: "img/start_pin.png",
                title: passenger.Point.Start.lat() + ", " + passenger.Point.Start.lng()
            });

            // set map end point marker
            passenger.Marker.End = new google.maps.Marker({
                position: passenger.Point.End,
                map: map,
                icon: "img/end_pin.png",
                title: passenger.Point.End.lat() + ", " + passenger.Point.End.lng()
            });

            // set map current point marker
            passenger.Marker.Current = new google.maps.Marker({
                position: passenger.Point.Current,
                map: map,
                icon: "img/passenger_1.png",
                title: passenger.Point.Current.lat() + ", " + passenger.Point.Current.lng(),
                zIndex: 802
            });

            // set map center to current point
            map.setCenter(passenger.Point.Current);
        }
    }
    xmlhttp.send();

    getName();
    setURL();
}

function setURL() {
    var temp = '?data={"id":"' + pid + '"}';

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
    var url = server + 'get_name.php?data={"id":"' + pid + '"}';
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
    var url = server + 'get_phone.php?data={"id":"' + pid + '"}';
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

function updateHistory() {
    //finish: 2代表司機取消 3代表乘客取消
    var data = '{"dnum":"' + dnum + '","pnum":"' + pnum + '","finish":"' + 2 + '"}';
    var url = server + 'update_history.php?data=' + data;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {}
    }
    xmlhttp.send();
}

function AddDriver(id, pathid) {
    // be sure that id is string
    if (!isNaN(id)) {
        id = id.toString();
    }
    getCid(id);
    did.push(id);
    rid.push(id);
    var theDriverIndex = did.length - 1;
    driverList.push(new DriverObj());

    // get the driver's info
    var toServerStr = '{"init": 1, "role": 0, "did":"' + id + '", "pid":"' + pid + '", "pathid": "' + pathid + '"}';
    var url = server + 'tracePassengerServer.php?data=' + toServerStr;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var result = JSON.parse(xmlhttp.responseText);

            // set info to obj
            driverList[theDriverIndex].Name = result.driver.Name;
            driverList[theDriverIndex].Path = ConvertToGoogleLatLng(result.driver.Path);
            driverList[theDriverIndex].Point.Current = new google.maps.LatLng(result.driver.CurPoint.at, result.driver.CurPoint.ng);
            driverList[theDriverIndex].Point.Getin = driverList[theDriverIndex].Path[0];
            driverList[theDriverIndex].Point.Getoutoff = driverList[theDriverIndex].Path.last();

            // set map carpoolpath marker
            driverList[theDriverIndex].Marker.Path = new google.maps.Polyline({
                path: driverList[theDriverIndex].Path,
                geodesic: true,
                strokeColor: COLOR[theDriverIndex],
                strokeOpacity: 0.7,
                strokeWeight: 8,
                zIndex: 800
            });
            driverList[theDriverIndex].Marker.Path.setMap(map);

            // set map getin point marker
            driverList[theDriverIndex].Marker.Getin = new google.maps.Marker({
                position: driverList[theDriverIndex].Point.Getin,
                map: map,
                icon: "img/pstart" + (theDriverIndex + 1) + ".png",
                title: driverList[theDriverIndex].Point.Getin.lat() + ", " + driverList[theDriverIndex].Point.Getin.lng()
            });

            // set map Getoutoff point marker
            driverList[theDriverIndex].Marker.Getoutoff = new google.maps.Marker({
                position: driverList[theDriverIndex].Point.Getoutoff,
                map: map,
                icon: "img/pend" + (theDriverIndex + 1) + ".png",
                title: driverList[theDriverIndex].Point.Getoutoff.lat() + ", " + driverList[theDriverIndex].Point.Getoutoff.lng()
            });

            // set map current point marker
            driverList[theDriverIndex].Marker.Current = new google.maps.Marker({
                position: driverList[theDriverIndex].Point.Current,
                map: map,
                icon: "img/driver_1.png",
                title: driverList[theDriverIndex].Point.Current.lat() + ", " + driverList[theDriverIndex].Point.Current.lng(),
                zIndex: 801
            });

            // set info window number
            driverList[theDriverIndex].Marker.InfoWindow = new google.maps.InfoWindow();
            driverList[theDriverIndex].Marker.InfoWindow.setOptions({
                content: (theDriverIndex + 1).toString() + "車牌: " + cid[theDriverIndex],
                position: driverList[theDriverIndex].Point.Current,
                disableAutoPan: true,
                zIndex: 803
            });
            driverList[theDriverIndex].Marker.InfoWindow.open(map, driverList[theDriverIndex].Marker.Current);
        }
    }
    xmlhttp.send();
}

function UpdateView(re, dcurpoints) {
    // determine passenger status with getting in or getting out off car
    switch (re) {
        case 0:
            // remove get in car point marker
            if (driverList[0].Marker.Getin != null) {
                driverList[0].Marker.Getin.setMap(null);
                driverList[0].Marker.Getin = null;
                $('#ahead').css("display", "inline");
                $('#cancel').css("display", "none"); //if the passenger get on,the driver can't cancel
            }
            break;
        case 1:
            removeDriver();
            break;
        case 2:
            console.log("passenger current point update");
            break
        default:
            console.log("Something wrong!! update view!");
    }

    // update passenger current point marker
    if (passenger.Marker.Current != null)
        passenger.Marker.Current.setMap(null);

    passenger.Marker.Current = new google.maps.Marker({
        position: passenger.Point.Current,
        map: map,
        icon: "img/passenger_1.png",
        title: passenger.Point.Current.lat() + ", " + passenger.Point.Current.lng(),
        zIndex: 802
    });

    // update drivers' current point marker
    for (var i = 0; i < dcurpoints.length; i++) {
        var driverIndex = did.indexOf(dcurpoints[i].did);
        if (driverIndex == -1)
            continue;

        driverList[driverIndex].Point.Current = new google.maps.LatLng(dcurpoints[i].curpoint.at, dcurpoints[i].curpoint.ng);

        if (driverList[driverIndex].Marker.Current != null)
            driverList[driverIndex].Marker.Current.setMap(null);

        driverList[driverIndex].Marker.Current = new google.maps.Marker({
            position: driverList[driverIndex].Point.Current,
            map: map,
            icon: "img/driver_1.png",
            title: driverList[driverIndex].Point.Current.lat() + ", " + driverList[driverIndex].Point.Current.lng(),
            zIndex: 801
        });

        // set info window number
        driverList[driverIndex].Marker.InfoWindow = new google.maps.InfoWindow();
        driverList[driverIndex].Marker.InfoWindow.setOptions({
            content: (driverIndex + 1).toString() + "車牌: " + cid[driverIndex],
            position: driverList[driverIndex].Point.Current,
            disableAutoPan: true,
            zIndex: 803
        });
        driverList[driverIndex].Marker.InfoWindow.open(map, driverList[driverIndex].Marker.Current);
    }

    // set map center to current point
    map.setCenter(passenger.Point.Current);
}

function DetectCurPoint() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
                // pass current location to server
                var didsStr = '';
                if (did.length > 0)
                    didsStr = '"' + did.join('","') + '"';

                var toServerStr = '{"init": 0, "resetStatus" : 0, "pid":"' + pid + '", "dids": [' + didsStr + '], "curpoint": {"at":' + position.coords.latitude.toFixed(5) + ', "ng": ' + position.coords.longitude.toFixed(5) + '}, "curdriveridx": ' + currentDid + '}';
                var url = server + 'tracePassengerServer.php?data=' + toServerStr;
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.open("GET", url, true);
                xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xmlhttp.onreadystatechange = function() {
                    if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                        var result = JSON.parse(xmlhttp.responseText);
                        passenger.Point.Current = new google.maps.LatLng(position.coords.latitude.toFixed(5), position.coords.longitude.toFixed(5));

                        // update screen infomation
                        UpdateView(result.calResult, result.driverCurpoints);
                    }
                }
                xmlhttp.send();
            },
            function(error) {
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        console.log("User denied the request for Geolocation.");
                        break;
                    case error.POSITION_UNAVAILABLE:
                        console.log("Location information is .");
                        break;
                    case error.TIMEOUT:
                        console.log("The request to get user location timed out.");
                        break;
                    case error.UNKNOWN_ERROR:
                        console.log("An unknown error occurred.");
                        break;
                }
            }, {
                enableHighAccuracy: true
            });
    } else {
        alertify.success("Not support geolocation");
    }
}

function removeDriver() {
    // carpoolpath marker
    if (driverList[0].Marker.Path)
        driverList[0].Marker.Path.setMap(null);

    // current point marker
    if (driverList[0].Marker.Current)
        driverList[0].Marker.Current.setMap(null);

    // infowindow
    if (driverList[0].Marker.InfoWindow)
        driverList[0].Marker.InfoWindow.setMap(null);

    // get out off point
    if (driverList[0].Marker.Getoutoff)
        driverList[0].Marker.Getoutoff.setMap(null);

    // remove passenger id in pid and passList
    did.splice(0, 1);
    driverList.splice(0, 1);
    currentDid++;

    // update passenger get in and get out off car state to database
    var toServerStr = '{"init": 0, "resetStatus" : 1, "pid":"' + pid + '"}';
    var url = server + 'tracePassengerServer.php?data=' + toServerStr;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {}
    }
    xmlhttp.send();

    // redirect to rating page
    if (!did.length) {
        var rid_str = JSON.stringify(rid);
        //alert('{"id":"' + pid + '","role":"passenger","rid":' + rid_str + '}');
        window.location = local + 'rating.html?data={"id":"' + pid + '","role":"passenger","rid":' + rid_str + '}';
    }
}

function resizeScreen() {
    var docHight = $(document).height();

    // get header height
    var headerHeight = $('.mdl-layout__header').height();

    var mapblockH = docHight - headerHeight;
    $('.map-block').css('height', mapblockH + 'px');
    $('.map-block').css('top', headerHeight + 'px');
}

function ConvertToGoogleLatLng(list) {
    var temp = [];
    for (var i = 0; i < list.length; i++)
        temp.push(new google.maps.LatLng(list[i].at, list[i].ng));
    return temp;
}

// Prototype
Array.prototype.last = function() {
    return this[this.length - 1];
}

//確認device ready
function onDeviceReady() {

    try {
        var push = PushNotification.init({
            "android": {
                "senderID": "72965952119", //"47580372845",
                "image": "http://120.114.186.4:8080/carpool/assets/logo.png"
            },
            "ios": {},
            "windows": {}
        });

        //通知設定
        push.on('notification', function(data) {
            var additional = JSON.stringify(data.additionalData);
            additional = JSON.parse(additional);
            mode = additional.mode;
            tid = additional.tid;
            document.getElementById("dialog_message").innerHTML += data.message;

            if (mode === '5') {
                dnum = additional.dnum;
                setName(tid, 'dialog_name');
                $('#dialog_image').attr('src', 'http://graph.facebook.com/' + tid + '/picture?type=normal');
                $('#dialog').css("display", "table");
            }

        });

        push.on('error', function(e) {
            console.log("push error");
        });

    } catch (err) {
        txt = "There was an error on this page.\n\n";
        txt += "Error description: " + err.message + "\n\n";
        //alert(txt);
    }
}

function setName(data, mode) {
    var url = server + 'get_name.php?data={"id":"' + data + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById(mode).innerHTML = xmlhttp.responseText;
        }
    }
    xmlhttp.send();
}

function getCid(data) {
    var url = server + 'get_cid.php?data={"id":"' + data + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            cid.push(xmlhttp.responseText);
        }
    }
    xmlhttp.send();
}

//document.addEventListener('deviceready', onDeviceReady, true);
