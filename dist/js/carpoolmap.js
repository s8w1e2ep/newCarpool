$(document).ready(function() {
    url = window.location.toString();
    //set map block size
    resizeScreen();

    // initialize map
    InitialMap();

    // path.js
    initialize();

    $('#btnBoundEP').click(function() {
        $('#btnBoundEP').children('i').addClass("hidden");
        SetMarkerStatus(2);
    });

    //cal path
    $('#btnPathCal').click(function() {
        if (StartPoint && EndPoint)
            calRoute();
        else {
            alert("偵測不到目前位置，請確認是否開啟GPS或在空曠的地方");
        }
    });

    //btn path compare
    $('#next').click(function() {
        if (pathJSON != null && PathLength != null) {
            nextStep(pathJSON, PathLength);
        } else {
            alert("尚未選取路線");
        }
    });

    //click set marker event
    google.maps.event.addListener(map, 'click', function(event) {
        $('#btnBoundEP').children('i').removeClass("hidden");
        SetMarkerForMap(event.latLng);
    });
});

//global variables
var map;
var directionsRender = null;

var StartPoint = null;
var StartPointMarker = null;
var StartPointStatus = false;

var EndPoint = null;
var EndPointMarker = null;
var EndPointStatus = false;

var CurrentSelectedMarkerType = null;

//calculate route
var PathDirectionRender = null;
var PathDirectionsService = null;
var PathDots = [];
var PathLength = null;
var pathJSON = null;

function InitialMap() {
    //initail map
    directionsRender = new google.maps.DirectionsRenderer();

    //set map options
    var mapOptions = {
        zoom: 18,
        center: new google.maps.LatLng(22.975228, 120.218398)
    };

    //set map
    map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

    //directions set map
    directionsRender.setMap(map);
}

function updateLocation(id, latitude, longitude, role) {
    if (role == "passenger")
        role = 1;
    else if (role == "driver")
        role = 2;
    var data = '{"id":"' + id + '", "role":"' + role + '","curpoint":[{"at":"' + latitude + '","ng":"' + longitude + '"}]}';
    var server = "http://120.114.186.4:8080/carpool/api/";
    var url = server + 'update_location.php?data=' + data;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            // console.log(xmlhttp.responseText);
        }
    }
    xmlhttp.send();
}

function calRoute() {
    StartPointMarker.setMap(null);
    EndPointMarker.setMap(null);

    //add service
    PathDirectionsService = new google.maps.DirectionsService();

    var PathRequest = {
        origin: StartPoint,
        destination: EndPoint,
        travelMode: google.maps.TravelMode.DRIVING,
        optimizeWaypoints: true
    };

    var PathPolylineOptions = {
        strokeColor: "#1565C0",
        strokeOpacity: 0.9,
        strokeWeight: 8
    };

    PathDirectionsService.route(PathRequest, function(Response, Status) {
        if (Status == google.maps.DirectionsStatus.OK) {
            PathDirectionRender = new google.maps.DirectionsRenderer({
                polylineOptions: PathPolylineOptions,
                preserveViewport: true
            });

            PathDirectionRender.setMap(map);
            PathDirectionRender.setDirections(Response);
            OutputPathPoints(Response);
        }
    });

    ResetAllParameter();
}

function OutputPathPoints(response) {
    var thisPath = GetPathPoint(response);

    //record path dots
    PathDots = thisPath;
    PathLength = response.routes[0].legs[0].distance.value;
    pathJSON = JSON.stringify(PathDots);
}

//r is response, p is path number
function GetPathPoint(r) {
    var localPath = [];
    var steps = r.routes[0].legs[0].steps;

    //initail
    localPath.push({
        'at': steps[0].path[0].lat(),
        'ng': steps[0].path[0].lng()
    });

    for (var i = 0; i < steps.length; i++) {
        var innerStepPath = steps[i].path;

        for (var j = 1; j < innerStepPath.length; j++) {
            localPath.push({
                'at': innerStepPath[j].lat(),
                'ng': innerStepPath[j].lng()
            });
        }
    }
    return localPath;
}

function ResetAllParameter() {
    EndPoint = null;

    CurrentSelectedMarkerType = null;

    if (PathDirectionRender != null)
        PathDirectionRender.setMap(null);

    PathDots = [];
}

function SetMarkerForMap(latLng) {
    if (CurrentSelectedMarkerType == null)
        return false;

    switch (CurrentSelectedMarkerType) {
        case 2:
            if (EndPointMarker != null)
                EndPointMarker.setMap(null);

            EndPointMarker = new google.maps.Marker({
                position: latLng,
                map: map,
                icon: 'img/end_pin.png',
                title: 'End Point'
            });

            EndPoint = latLng;
            EndPointStatus = true;
            SetMarkerStatus(0);

            if (StartPoint)
                calRoute();
            else {
                EndPointMarker.setMap(null);
                alert("偵測不到目前位置，請確認是否開啟GPS或在空曠的地方");
            }
            break;
    }
}

function SetMarkerStatus(type) {
    switch (type) {
        case 2:
            CurrentSelectedMarkerType = 2;
            $('#btnBoundEP > img').addClass('hidden');
            $('#btnBoundSP > img').removeClass('hidden');
            break;
        case 0:
            CurrentSelectedMarkerType = 0;
            $('#btnBoundEP > img').removeClass('hidden');
            $('#btnBoundSP > img').removeClass('hidden');
            break;
    }
}

//functions for work==============================================================
//
//
//=================================== DIVIDE =====================================
//
//
//functions for work==============================================================
function resizeScreen() {
    //set map block height and width
    var docHight = $(document).height();
    var headerHight = $('.mdl-layout__header').height();
    var btnHight = $('#btnBoundEP').height();
    var mapblockH = docHight - headerHight - btnHight;

    $('#map-block').css('height', mapblockH + 'px');
    $('.map-block').css('top', headerHight + 'px');
}

function GetCurrentPos(id, role) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
                updateLocation(id, position.coords.latitude, position.coords.longitude, role);
                if (StartPointMarker != null)
                    StartPointMarker.setMap(null);

                StartPoint = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

                if (StartPoint != null)
                    StartPointStatus = true;

                StartPointMarker = new google.maps.Marker({
                    position: StartPoint,
                    map: map,
                    icon: 'img/start_pin.png',
                    title: 'Start Point'
                });

                map.setCenter(StartPoint);
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
