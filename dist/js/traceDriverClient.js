$(document).ready(function() {
    // RESIZE SCREEN HEIGHT
    resizeScreen();

    // INITIALIZE GOOGLE MAPS
    InitializeMap();

    InitializeDriver();

    DetectCurPoint();
    setInterval(DetectCurPoint, 6000);

    $('#ok').click(function() {
        // $('#info').modal('hide');
        $('#info').css("display", "none");
        $('#mbody').attr('style', 'background-color: #FFFFFF;');
    });
});

//global variables
var map;

// var serverDomain = "http://vu6m3lio.ddns.net/";
var local = "file:///android_asset/www/";
var serverDomain = "http://120.114.186.4/";
var server = serverDomain + "carpool/api/";
var COLOR = ["#176ae6", "#ff0000", "#6a3906", "#800080"];

var global_url = window.location.toString();
// driver is
var did = null;
var tid = ""; //confirmCarpool，updateHistory 會用到
var path_index = ""; //store the index of passenger path
// TEST ID 1046779538684826 1046779538684827 1046779538684828
// var did = "1046779538684826";
// passengers' id
var pid = [];
var pidPathIdx = [];
var rid = [];
var ttsName = ""; // 紀錄乘客姓名給TTS
var ttsText2 = "";
var ttscheck = false;

// driver variables
var driver = null;
var DriverObj = function() {
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

// passenger list and passenger object
var passList = [];
var PassengerObj = function() {
    return {
        'Name': null,
        'CarpoolPath': null,
        'Point': {
            'Getin': null,
            'Getoutoff': null,
            'Current': null
        },
        'Marker': {
            'CarpoolPath': null,
            'Getin': null,
            'Getoutoff': null,
            'Current': null,
            'InfoWindow': null
        }
    }
};

function InitializeMap() {
    //set map options
    var mapOptions = {
        zoom: 16,
        center: new google.maps.LatLng(22.975375, 120.218936)
    };

    //set mapc
    map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
}

function InitializeDriver() {
    var thestr = global_url.substring(global_url.indexOf("{"), global_url.length);
    var thejson = JSON.parse(decodeURIComponent(thestr));
    did = thejson.id;
    driver = new DriverObj();

    var toServerStr = '{"init": 1,"role": 1, "did":"' + did + '"}';
    var url = server + 'traceDriverServer.php?data=' + toServerStr;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var result = JSON.parse(xmlhttp.responseText);
            driver.Name = result.driver.Name;
            driver.Path = ConvertToGoogleLatLng(result.driver.Path);
            driver.Point.Current = new google.maps.LatLng(result.driver.CurPoint.at, result.driver.CurPoint.ng);
            driver.Point.Start = driver.Path[0];
            driver.Point.End = driver.Path.last();

            // set map path marker
            driver.Marker.Path = new google.maps.Polyline({
                path: driver.Path,
                geodesic: true,
                strokeColor: '#000',
                strokeOpacity: 0.7,
                strokeWeight: 8
            });
            driver.Marker.Path.setMap(map);

            // set map start point marker
            driver.Marker.Start = new google.maps.Marker({
                position: driver.Point.Start,
                map: map,
                icon: "img/start_pin.png",
                title: driver.Point.Start.lat() + ", " + driver.Point.Start.lng()
            });

            // set map end point marker
            driver.Marker.End = new google.maps.Marker({
                position: driver.Point.End,
                map: map,
                icon: "img/end_pin.png",
                title: driver.Point.End.lat() + ", " + driver.Point.End.lng()
            });

            // set map current point marker
            driver.Marker.Current = new google.maps.Marker({
                position: driver.Point.Current,
                map: map,
                icon: "img/driver_1.png",
                title: driver.Point.Current.lat() + ", " + driver.Point.Current.lng(),
                zIndex: 802
            });

            // set map center to current point
            map.setCenter(driver.Point.Current);
        }
    }
    xmlhttp.send();

    getName();
    setURL();
}

function setURL() {
    var temp = '?data={"id":"' + did + '"}';

    $('#board').attr('href', local + 'board.html' + temp);
    $('#wall').attr('href', local + 'wall.html' + temp);
    $('#friendlist').attr('href', local + 'friendlist.html' + temp);
    $('#about').attr('href', local + 'about.html' + temp);
    $('#setting').attr('href', local + 'setting.html' + temp);
    $('#edit').attr('href', local + 'edit.html' + temp);
    $('#logo').attr('href', local + 'index.html' + temp);
    $('#dsgr').attr('href', local + 'index.html' + temp);
    $('#user_image').attr('src', 'http://graph.facebook.com/' + did + '/picture?type=large');
}

function getName() {
    var url = server + 'get_name.php?data={"id":"' + did + '"}';
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
    var url = server + 'get_phone.php?data={"id":"' + did + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            phone = xmlhttp.responseText;
            $('#image').attr('src', 'http://graph.facebook.com/' + did + '/picture?type=large');
            $('#state').html('已登入');
            $('#tel').html(phone);
        }
    }
    xmlhttp.send();
}

function AddPassenger(id, index) {
    ttscheck = true;
    // be sure that id is string
    if (!isNaN(id)) {
        id = id.toString();
    }

    rid.push(id);
    pid.push(id);
    pidPathIdx.push(index);
    var thePassIndex = pid.length - 1;
    passList.push(new PassengerObj());

    // get the passengers info
    var toServerStr = '{"init": 1, "role": 0, "pid":"' + id + '", "carpoolidx": ' + index + '}';

    var url = server + 'traceDriverServer.php?data=' + toServerStr;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var result = JSON.parse(xmlhttp.responseText);

            // set info to obj
            passList[thePassIndex].Name = result.passenger.Name;
            passList[thePassIndex].CarpoolPath = ConvertToGoogleLatLng(result.passenger.Path);
            passList[thePassIndex].Point.Current = new google.maps.LatLng(result.passenger.CurPoint.at, result.passenger.CurPoint.ng);
            passList[thePassIndex].Point.Getin = passList[thePassIndex].CarpoolPath[0];
            passList[thePassIndex].Point.Getoutoff = passList[thePassIndex].CarpoolPath.last();

            // set map carpoolpath marker
            passList[thePassIndex].Marker.CarpoolPath = new google.maps.Polyline({
                path: passList[thePassIndex].CarpoolPath,
                geodesic: true,
                strokeColor: COLOR[thePassIndex],
                strokeOpacity: 0.7,
                strokeWeight: 8,
                zIndex: 800
            });
            passList[thePassIndex].Marker.CarpoolPath.setMap(map);

            // set map getin point marker
            passList[thePassIndex].Marker.Getin = new google.maps.Marker({
                position: passList[thePassIndex].Point.Getin,
                map: map,
                icon: "img/pstart" + (thePassIndex + 1) + ".png",
                title: passList[thePassIndex].Point.Getin.lat() + ", " + passList[thePassIndex].Point.Getin.lng()
            });

            // set map Getoutoff point marker
            passList[thePassIndex].Marker.Getoutoff = new google.maps.Marker({
                position: passList[thePassIndex].Point.Getoutoff,
                map: map,
                icon: "img/pend" + (thePassIndex + 1) + ".png",
                title: passList[thePassIndex].Point.Getoutoff.lat() + ", " + passList[thePassIndex].Point.Getoutoff.lng()
            });

            // set map current point marker
            passList[thePassIndex].Marker.Current = new google.maps.Marker({
                position: passList[thePassIndex].Point.Current,
                map: map,
                icon: "img/passenger_1.png",
                title: passList[thePassIndex].Point.Current.lat() + ", " + passList[thePassIndex].Point.Current.lng(),
                zIndex: 801
            });

            // set info window number
            passList[thePassIndex].Marker.InfoWindow = new google.maps.InfoWindow();
            passList[thePassIndex].Marker.InfoWindow.setOptions({
                content: (thePassIndex + 1).toString(),
                position: passList[thePassIndex].Point.Current,
                disableAutoPan: true,
                zIndex: 803
            });
            passList[thePassIndex].Marker.InfoWindow.open(map, passList[thePassIndex].Marker.Current);
        }
    }
    xmlhttp.send();
}

function UpdateView(re, pcurpoints) {
    var reNum = re.length;
    var pIndex;
    //popInfo(re[0].id, 400, 0);
    // update page bottom text and tts text
    var ttsText = '距離';
    alert("line 294: re[0].type: " + re[0].type);
    if (re[0].type) {
        // type 0 is driver end point
        pIndex = pid.indexOf(re[0].id);
        ttsText += '乘客' + passList[pIndex].Name + '的';

        if (re[0].type == 1) {
            ttsText += '上車點約';
            popInfo(re[0].id, re[0].gdm.distance.val, 1);
        } else {
            ttsText += '下車點約';
            popInfo(re[0].id, re[0].gdm.distance.val, 0);
        }
        ttsText += re[0].gdm.time.text + '，' + re[0].gdm.distance.text;
    } else {
        // redirect to rating page
        if (re[0].gdm.distance.val <= 25) {
            var rid_str = JSON.stringify(rid);
            //alert('{"id":"' + did + '","role":"driver","rid":' + rid_str + '}');
            window.location = local + 'rating.html?data={"id":"' + did + '","role":"driver","rid":' + rid_str + '}';
        }

        pIndex = -1;
        ttsText += '終點約' + re[0].gdm.time.text + '，' + re[0].gdm.distance.text;
    }
    $('#footer').html(ttsText);
    if (ttscheck) {
        TTS
            .speak({
                text: ttsText,
                locale: 'zh-TW',
                rate: 1
            }, function() {
                //alert('success');
            }, function(reason) {
                alert(reason);
            });
    }

    // update driver current point marker
    if (driver.Marker.Current != null)
        driver.Marker.Current.setMap(null);

    driver.Marker.Current = new google.maps.Marker({
        position: driver.Point.Current,
        map: map,
        icon: "img/driver_1.png",
        title: driver.Point.Current.lat() + ", " + driver.Point.Current.lng(),
        zIndex: 802
    });

    // set map center to current point
    map.setCenter(driver.Point.Current);
    if (pIndex != -1) {
        // update the passenger current point marker
        passList[pIndex].Point.Current = new google.maps.LatLng(re[0].curpoint.at, re[0].curpoint.ng);

        if (passList[pIndex].Marker.Current != null)
            passList[pIndex].Marker.Current.setMap(null);

        passList[pIndex].Marker.Current = new google.maps.Marker({
            position: passList[pIndex].Point.Current,
            map: map,
            icon: "img/passenger_1.png",
            title: passList[pIndex].Point.Current.lat() + ", " + passList[pIndex].Point.Current.lng(),
            zIndex: 801
        });

        // set info window number
        passList[pIndex].Marker.InfoWindow = new google.maps.InfoWindow();
        passList[pIndex].Marker.InfoWindow.setOptions({
            content: (pIndex + 1).toString(),
            position: passList[pIndex].Point.Current,
            disableAutoPan: true,
            zIndex: 803
        });
        passList[pIndex].Marker.InfoWindow.open(map, passList[pIndex].Marker.Current);
    }

    // update other passengers' current point
    for (var i = 0; i < pcurpoints.length; i++) {
        var thisIndex = pid.indexOf(pcurpoints[i].id);

        passList[thisIndex].Point.Current = new google.maps.LatLng(pcurpoints[i].curpoint.at, pcurpoints[i].curpoint.ng);

        if (passList[thisIndex].Marker.Current != null)
            passList[thisIndex].Marker.Current.setMap(null);

        passList[thisIndex].Marker.Current = new google.maps.Marker({
            position: passList[thisIndex].Point.Current,
            map: map,
            icon: "img/passenger_1.png",
            title: passList[thisIndex].Point.Current.lat() + ", " + passList[thisIndex].Point.Current.lng(),
            zIndex: 801
        });

        // set info window number
        passList[thisIndex].Marker.InfoWindow = new google.maps.InfoWindow();
        passList[thisIndex].Marker.InfoWindow.setOptions({
            content: (thisIndex + 1).toString(),
            position: passList[thisIndex].Point.Current,
            disableAutoPan: true,
            zIndex: 803
        });
        passList[thisIndex].Marker.InfoWindow.open(map, passList[thisIndex].Marker.Current);
    }

    // if re length = 2, remove passenger get in point maker or get out off point marker with this passenger
    if (reNum == 2) {
        var depIndex = pid.indexOf(re[1].id);
        if (re[1].type == 1) {
            // get in point
            if (passList[depIndex].Marker.Getin)
                passList[depIndex].Marker.Getin.setMap(null);
        } else {
            // carpoolpath marker
            if (passList[depIndex].Marker.CarpoolPath)
                passList[depIndex].Marker.CarpoolPath.setMap(null);

            // current point marker
            if (passList[depIndex].Marker.Current)
                passList[depIndex].Marker.Current.setMap(null);

            // infowindow
            if (passList[depIndex].Marker.InfoWindow)
                passList[depIndex].Marker.InfoWindow.setMap(null);

            // get out off point
            if (passList[depIndex].Marker.Getoutoff)
                passList[depIndex].Marker.Getoutoff.setMap(null);

            // remove passenger id in pid and passList
            pid.splice(depIndex, 1);
            passList.splice(depIndex, 1);
        }
    }
}

function addHistory(pindex) {
    var data = '{"did":"' + did + '","pid":"' + tid + '","index":"' + pindex + '"}';
    var url = server + 'update_history.php?data=' + data;
    //alert("history: " + url);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            console.log(xmlhttp.responseText);
        }
    }
    xmlhttp.send();
}

//同意 GCM msg
function confirmCarpool() {

    $('#dialog').attr('style', 'display:none');
    $('.wrapperInside').attr('style', 'background-color: #FFFFFF;');
    var data = '{"id":"' + did + '","tid":"' + tid + '","mode":"2"}';
    //{"role":"driver","id":"10467795386484826","tid":"838717559541922","mode":"2"}
    var xmlhttp = new XMLHttpRequest();
    //alert("data: " + data);
    url = server + 'gcm_server.php?data=' + data;
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            //alert("+乘客&&更新history");
            AddPassenger(tid, path_index);
            addHistory(path_index);
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}

//拒絕 GCM msg
function cancelCarpool() {
    deleteHistory();
    window.location = local + 'initialization.html?data={"id":"' + id + '"}';
}

var BE_DELTA_NUMBER = 150;
var BE_STATE_POINT = 25;
var BE_ARRIVING = 500;

function popInfo(id, dis, type) {
    setTimeout(function() {
        var passIndex = pid.indexOf(id);

        if (type) {
            // type is 1, passenger get in car
            if (dis <= BE_STATE_POINT) {
                //$("#info").modal('show');
                ttsText2 = '抵達乘客' + passList[passIndex].Name + '的上車點';
                TTS
                    .speak({
                        text: ttsText2,
                        locale: 'zh-TW',
                        rate: 1
                    }, function() {
                        //alert('success');
                    }, function(reason) {
                        alert(reason);
                    });
                $('.wrapperInside').attr('style', 'background-color: #666666;');
                $("#info").css("display", "table");
                $('#info_message').html('抵達乘客上車點');
                $('#info_name').html(passList[passIndex].Name);
                $('#info_image').attr('src', "http://graph.facebook.com/" + id + "/picture?type=large");
            } else if ((BE_ARRIVING - BE_DELTA_NUMBER) <= dis && dis <= BE_ARRIVING) {
                //$("#info").modal('show');
                ttsText2 = '即將抵達乘客' + passList[passIndex].Name + '的上車點';
                TTS
                    .speak({
                        text: ttsText2,
                        locale: 'zh-TW',
                        rate: 1
                    }, function() {
                        //alert('success');
                    }, function(reason) {
                        alert(reason);
                    });
                $('.wrapperInside').attr('style', 'background-color: #666666;');
                $("#info").css("display", "table");
                $('#info_message').html('即將抵達乘客上車點');
                $('#info_name').html(passList[passIndex].Name);
                $('#info_image').attr('src', "http://graph.facebook.com/" + id + "/picture?type=large");
            }
        } else {
            // type is 0, passenger get out off car
            if (dis <= BE_STATE_POINT) {
                //$("#info").modal('show');
                ttsText2 = '抵達乘客' + passList[passIndex].Name + '的下車點';
                TTS
                    .speak({
                        text: ttsText2,
                        locale: 'zh-TW',
                        rate: 1
                    }, function() {
                        //alert('success');
                    }, function(reason) {
                        alert(reason);
                    });
                $('.wrapperInside').attr('style', 'background-color: #666666;');
                $("#info").css("display", "table");
                $('#info_message').html('抵達乘客下車點');
                $('#info_name').html(passList[passIndex].Name);
                $('#info_image').attr('src', "http://graph.facebook.com/" + id + "/picture?type=large");
            } else if ((BE_ARRIVING - BE_DELTA_NUMBER) <= dis && dis <= BE_ARRIVING) {
                //$("#info").modal('show');
                ttsText2 = '即將抵達乘客' + passList[passIndex].Name + '的下車點';
                TTS
                    .speak({
                        text: ttsText2,
                        locale: 'zh-TW',
                        rate: 1
                    }, function() {
                        //alert('success');
                    }, function(reason) {
                        alert(reason);
                    });
                $('.wrapperInside').attr('style', 'background-color: #666666;');
                $("#info").css("display", "table");
                $('#info_message').html('即將抵達乘客下車點');
                $('#info_name').html(passList[passIndex].Name);
                $('#info_image').attr('src', "http://graph.facebook.com/" + id + "/picture?type=large");
            }
        }
    }, 0);
}

function DetectCurPoint() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
                // pass current location to server
                var pidsStr = '';
                var pidPathIdxStr = '';
                if (pid.length > 0) {
                    pidsStr = '"' + pid.join('","') + '"';
                    pidPathIdxStr = '"' + pidPathIdx.join('","') + '"';
                }
                alert("pidPathIdxStr: " + pidPathIdxStr);
                var toServerStr = '{"init": 0, "did":"' + did + '", "pids": [' + pidsStr + '], "carpoolidx": [' + pidPathIdxStr + '], "curpoint": {"at":' + position.coords.latitude.toFixed(5) + ', "ng": ' + position.coords.longitude.toFixed(5) + '}}';
                var url = server + 'traceDriverServer.php?data=' + toServerStr;
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.open("GET", url, true);
                xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xmlhttp.onreadystatechange = function() {
                    if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                        var result = JSON.parse(xmlhttp.responseText);
                        driver.Point.Current = new google.maps.LatLng(position.coords.latitude.toFixed(5), position.coords.longitude.toFixed(5));
                        // update screen infomation
                        UpdateView(result.calResult, result.passCurpoints);
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
        alert("Not support geolocation");
    }
}

function resizeScreen() {
    var docHight = $(document).height();

    // get header height
    var headerHeight = $('.mdl-layout__header').height();
    var footerHeight = $('#footer').height();

    var mapblockH = docHight - headerHeight - footerHeight;
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

function setName(data, mode) {
    var url = server + 'get_name.php?data={"id":"' + data + '"}';
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            ttsText2 = '您有一則來自乘客' + xmlhttp.responseText + '的共乘請求!';
            $('#dialog_image').attr('src', 'http://graph.facebook.com/' + data + '/picture?type=large');
            document.getElementById(mode).innerHTML = xmlhttp.responseText;
            TTS
                .speak({
                    text: ttsText2,
                    locale: 'zh-TW',
                    rate: 1
                }, function() {
                    //alert('success');
                    confirmCarpool();
                }, function(reason) {
                    alert('TTS:' + reason);
                });
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
                    // "image": "http://120.114.186.4/carpool/assets/logo.png"
            },
            "ios": {},
            "windows": {}
        });
        //通知設定
        push.on('notification', function(data) {
            // TTS
            //     .stop(function() {
            //         //alert('success');
            //     }, function(reason) {
            //         alert(reason);
            //     });
            var additional = JSON.stringify(data.additionalData);
            additional = JSON.parse(additional);
            document.getElementById("dialog_message").innerHTML += data.message;
            tid = additional.tid;
            path_index = additional.pindex;
            alert("additional.pindex: " + additional.pindex);
            if (additional.foreground) {
                setName(tid, 'dialog_name');
                $('#dialog').css("display", "table");
                $('.wrapperInside').attr('style', 'background-color: #666666;');
            } else {
                setName(tid, 'dialog_name');
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
