	var url = window.location.toString();
	var id = "";
	var role = "";
	var json = "";
	var file = "";
	var passenger_json = "";
	var his_json = "";			//紀錄history json

	var server = "http://127.0.0.1/car/api/";
	var local = "file:///android_asset/www/";

	function initialize()
	{
		var str = url.substring(url.indexOf("{"), url.length);
		file = str;
		json = JSON.parse(decodeURIComponent(str));
		
		id = json.id;
		role = json.role;
		file = decodeURIComponent(file);
		//condition = json.condition;
		
		GetCurrentPos(id, role);
		setURL();
	}
	function setURL()
	{
		document.getElementById("logo").href = 'http://52.68.75.40/carpool/index.html?data=' + '{"id":"' + id + '"}';
	}
	function requestAPI(url, data, gid)//傳資料給php
	{
		url += '?data=' + data;
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.open("GET", url, true);
		xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xmlhttp.onreadystatechange = function()
		{
			if(xmlhttp.readyState == 4 && xmlhttp.status == 200)
			{
				if(gid === "match"){
					var jstr = xmlhttp.responseText;											//php回傳的媒合結果--json string
					console.log("Jstr: ", jstr);

					if(jstr.match("NoDriver") != null){
						alert("沒有司機符合評價門檻");
					}else if(jstr.match("NoOverlap") != null){
						alert("沒有重疊路徑");
					}else if(jstr.match("NoMatch") != null){
						alert("沒有司機符合媒合條件");
					}else{
						window.location = 'file:///android_asset/www/result.html?data=' + jstr;	//跳轉到result頁面
					}
				}
			}
		}
		xmlhttp.send();
	}
	function nextStep(pathJSON, PathLength)
	{
		his_json = "";
		var driver_json = "";
		var temp_json = "";
		var pathTemp = JSON.parse(pathJSON);
		temp_json = JSON.stringify(json);
				
		//history json
		his_json = file;
		//passenger json
		passenger_json = file.substring(0, file.length - 1) + ',"path":' + pathJSON + ',"total":' + PathLength
		+ ',"start":' + '{"latitude":"' + (new Number(StartPoint.lat())).toFixed(14) + '","longitude":"' + (new Number(StartPoint.lng())).toFixed(14) + '"}'
		+ ',"end":' + '{"latitude":"' + (new Number(pathTemp[pathTemp.length - 1].at)).toFixed(14) + '","longitude":"' + (new Number(pathTemp[pathTemp.length - 1].ng)).toFixed(14) + '"}}';
		//driver json
		driver_json = temp_json.substring(0, temp_json.length - 1) + ',"path":' + pathJSON + '}';
		
		console.log("passenger_json: ", passenger_json);
		
		if(role == "driver")
		{
			var url = "";
			url = server + 'driver.php?data=' + driver_json;
			console.log("driver: " + url);
			var xmlhttp = new XMLHttpRequest();
			xmlhttp.open("POST", url, true);
			xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xmlhttp.onreadystatechange = function()
			{
				if(xmlhttp.readyState == 4 && xmlhttp.status == 200)
				{
					window.location = local + 'traceDriverPage.html?data={"role":"driver","id":"' + id + '"}';
				}
			}
			xmlhttp.send();
		}
		else if(role == "passenger")
		{
			requestAPI(server + "path.php", passenger_json, "match");	//取得評價篩選後的司機路徑 => 88行
		}
	}