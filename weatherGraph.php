<!DOCTYPE> 
<!-- ==SUMMARY OF THIS PROGRAM==
1. It takes db info collected from the pi with the sensor in php
2. The last-added info date is compared with the computer's datetime in php ("5 min old info" alert)
3. DB Info is echoed out into an associative array in javascript
4. The javascript creates a list and graph using the array
5. The resulting html page allows you to view the humidity and temperature in various forms
- THIS CODE WAS MADE BEFORE THE APEX CHARTS
- It's a very scuffed, but does the minimum requirments
- If the graph is messed up, maximize the window and refresh
-->
<html>
	<head>
		<style>
		body {
			overflow: hidden;
		}
		#basicCanvas {
			z-index: -5;
			position: absolute;
			left: 0;
			top: 0;
			width: 100vw;
			height: 100vh;
		}
		#table {
			max-width: 370px;
			max-height: 50vh;
			overflow-y: scroll;
			margin-top: 3px;
			margin-left: 96px;
			visibility: hidden;
			background-color:rgb(240, 240, 240);
		}
		#graph {
			z-index: 2;
			position: absolute;
			left: 0;
			top: 0;
		}
		#leftBttn {
			position: absolute;
			left: 12px;
			top: 50vh;
		}
		#rightBttn {
			position: absolute;
			right: 12px;
			top: 50vh;
		}
		#minMaxContainter {
			position: absolute;
			top: 65vh;
			left: 12px;
		}
		.taBttn {
			color: rgb(0, 0, 0);
			background-color:rgba(255, 255, 255, 1);
			border:2px solid rgb(0, 0, 0);
		}
		.pulledInfo:first-of-type {
			margin-top: -2px;
		}
		.pulledInfo {
			font-size: 13px;
			margin-bottom: -8px;
		}
		.pnt {
			user-select: none;
			line-height: 2.5;
			position: absolute;
			background-color: rgb(0, 189, 166);
			font-size: 13px;
			text-align: right;
			width: 8px;
			height: 8px;
			margin-left: -4px;
			margin-top: -4px;
			border-radius: 50px;
		}
		.pnt .pntDate {
			user-select: none;
			width: 80px;
			line-height: normal;
			background-color: rgba(80, 80, 80, 0.5);
			color: white;
			padding: 3px 0;
			border:1px solid rgba(255, 255, 255, 0.5);
			text-align: center;
			font-size: 13px;
			visibility: hidden;
			position: absolute;
			top: -45px;
			left: 105%;
		}
		</style>
		<script>
		<?php
			function pullSensorDb() {
				//Connect to pi with sensor
				$servername = "192.168.0.210"; //192.168.0.210
				$username = "humChecker"; //humChecker
				$password = "raspberry"; //raspberry
				$dbname = "projects"; //projects, table: humTemp
				$conn = new mysqli($servername, $username, $password, $dbname);
				$timeStmpCatcher;
				$dateTimeNow = date_create(date('Y-m-d h:i:s'));
				$isFirst = true;
			
				if ($conn->connect_error) {
					die("Connection failed: " . $conn->connect_error);
				}
				$sql = "SELECT * FROM humTemp ORDER BY id DESC";
				$result = $conn->query($sql);
			
				if ($result->num_rows > 0) {
					//Creates a associative array with timeStamp, humidity, temperature (Reformatting timeStamp in the process)
					echo "var pulledTable = [";
					while($row = $result->fetch_array()) {
						$dateTime = date_create($row["timeStamp"]);
						if ($row["id"] == 1) {
							echo "{time:'".date_format($dateTime, "M d, h:i:s")."',hum:".$row["humidity"].",temp:".$row["temperature"]."}";
						} else {
							echo "{time:'".date_format($dateTime, "M d, h:i:s")."',hum:".$row["humidity"].",temp:".$row["temperature"]."},";
						}
						if ($isFirst) {
							$timeStmpCatcher = date_create($row["timeStamp"]);
							$isFirst = false;
						}
					}
					echo "];";
					$diff = date_diff($dateTimeNow, $timeStmpCatcher);
					$difYear = $diff->format('%y');
					$difMonth = $diff->format('%m');
					$difDay = $diff->format('%d');
					$difHour = $diff->format('%h');
					$difMin = $diff->format('%i');
					if ($difYear or $difMonth or $difDay or $difHour > 1 or $difMin > 5) {
						echo "window.alert('Data has not been added in at least 5 minutes. Current information may be old');";
					}
				} else {
					echo "0 results";
				}
			}
			
			pullSensorDb();
		?>
		
		var d = document;
		var w = window.innerWidth;
		var h = window.innerHeight;
		var graphDisplayAmt = 50;
		var graphMaxPages = 0;
		var graphCurPage = 0;
		var maxHum;
		var minHum;
		var maxTemp;
		var minTemp;
		var isButtonsHidden = false;
		
		//Does varying things when the page is loaded
		function loadFunc() {
			pulledTable.reverse();
			var orderedTableHum = [];
			var orderedTableTemp = [];
			var pageGraph = 1;
			var domBttnCont = d.getElementById("bttnContainer");
			var domTable = d.getElementById("table");
			var canvas = d.getElementById("basicCanvas");
			//Aligning the coordinates of canvas with page
			canvas.width = w;
			canvas.height = h; 
			//Getting the ordered array for humidiy and temperature (max and min)
			for (var a = 0; a < pulledTable.length; a++) {
				orderedTableHum.push(pulledTable[a].hum);
				orderedTableTemp.push(pulledTable[a].temp);
			}
			orderedTableHum.sort(function(a, b){return a - b;});
			orderedTableTemp.sort(function(a, b){return a - b;});
			maxHum = orderedTableHum[orderedTableHum.length - 1];
			minHum = orderedTableHum[0];
			maxTemp = orderedTableTemp[orderedTableTemp.length - 1];
			minTemp = orderedTableTemp[0];
			
			d.getElementById("humMax").innerHTML = "Highest Humidity: " + maxHum + "%";
			d.getElementById("humMin").innerHTML = "Lowest Humidity: " + minHum + "%";
			d.getElementById("tempMax").innerHTML = "Highest Temperature: " + maxTemp + "°C";
			d.getElementById("tempMin").innerHTML = "Lowest Temperature: " + minTemp + "°C";
			
			//Making list in "table" and pages for graph
			for (var i = 0; i < pulledTable.length; i++) {
				var p = d.createElement("p");
				var pText = d.createTextNode(pulledTable[i].time + " = Humidity: " + pulledTable[i].hum + "% Temperature: " + pulledTable[i].temp + "°C");
				var pClas = d.createAttribute("class");
				pClas.value = "pulledInfo";
				p.setAttributeNode(pClas);
				p.appendChild(pText);
				domTable.appendChild(p);
				
				if (i == graphDisplayAmt * pageGraph) {
					graphMaxPages++;
					pageGraph++;
				}
			}
			
			showGraph(graphCurPage);
		}
		
		//Hides the buttons in "bttnContainer" and the data table
		function hideBttn() {
			if (!isButtonsHidden) {
				d.getElementById("bttnContainer").style.visibility = "hidden";
				showTable(false);
				d.getElementById("hideBttn").innerHTML = "Show Buttons";
				isButtonsHidden = true;
			} else {
				d.getElementById("bttnContainer").style.visibility = "visible";
				d.getElementById("hideBttn").innerHTML = "Hide Buttons";
				isButtonsHidden = false;
			}
			d.getElementById("hideBttn").style.visibility = "visible";
		}
		
		//Clears out divs' child elements (Made Modular)
		function clearElements(attId, attClas) {
			var parentEle = d.getElementById(attId);
			var ele = d.getElementsByClassName(attClas);
			var eleLen = ele.length;
			
			for (var i = 0; i < eleLen; i++) {
				parentEle.removeChild(ele[0]);
			}
		}
		
		//Shows the data list from databasse
		function showTable(isShowing) {
			if (isShowing) {
				d.getElementById("graph").style.zIndex = "-1";
				d.getElementById("table").style.visibility = "visible";
			} else if (!isShowing) {
				d.getElementById("graph").style.zIndex = "2";
				d.getElementById("table").style.visibility = "hidden"; 
			}
		}
		
		//Establishes database data in a point graph
		function showGraph(pageNum) {
			//Goes through pages of graph (Only necessary if tableDisplayAmt is not pulledTable.length)
			if (graphCurPage < 0) {graphCurPage = 0; pageNum = 0;}
			else if (graphCurPage > graphMaxPages) {graphCurPage = graphMaxPages; pageNum = graphMaxPages;}
			clearElements("graph", "pnt");
			
			var graphAmt = 2;
			var pntIndex = 0;
			var horiShift = 5;
			var canvas = d.getElementById("basicCanvas");
			var ctx = canvas.getContext("2d");
			ctx.clearRect(0, 0, canvas.width, canvas.height);

			//Making X-value Lines
			var lineSpacing = 0.1;
			ctx.strokeStyle = "#d1d1d1";
			for (var ii = 0; ii < w; ii += w * lineSpacing) {
				ctx.beginPath();
				ctx.moveTo(0, ii);
				ctx.lineTo(h * 3, ii);
				ctx.stroke();
			} 

			//For-loop for each line
			for (var a = 0; a < graphAmt; a++) {
				var pntCounter = 0;
				var pntSpacing = w / graphDisplayAmt;
				var pntValue;
				var pntValueLabeled;
				var pntColor;
				var domGraph = d.getElementById("graph");
				var graphScaling = 250;
				//Page Variables
				var pageMax = graphDisplayAmt * (pageNum + 1);
				var pageMin = graphDisplayAmt * pageNum;
				var dsply = pageMax; 
									
				if (pageMin > 0) {
					pageMin++;
				}
				if (pulledTable.length - pageMax < graphDisplayAmt) {
					dsply = pulledTable.length;
				}
				//Creating Point and line graph
				for (var i = 0 + pageMin; i < dsply; i++) {
					//Variable setup for humidity/temperature coordinates
					var nextI = i + 1;
					if (i == dsply - 1) {
						nextI = i;
					}
					if (a == 0) {
						pntValue = pulledTable[i].hum;
						var endLineY = w * (pulledTable[nextI].hum / graphScaling);
						pntValueLabeled = pulledTable[i].hum + "%";
						pntColor = "0, 181, 166";
						if (pntValue == maxHum) {
							pntColor = "7, 0, 69";
						} else if (pntValue == minHum) {
							pntColor = "255, 52, 235";
						}
						ctx.strokeStyle = "#00b5a6";
					} else if (a == 1) {
						pntValue = pulledTable[i].temp;
						var endLineY = w * (pulledTable[nextI].temp / graphScaling);
						pntValueLabeled = pulledTable[i].temp + "°C";
						pntColor = "255, 106, 10";
						if (pntValue == maxTemp) {
							pntColor = "251, 255, 0";
						} else if (pntValue == minTemp) {
							pntColor = "255, 0, 0";
						}
						ctx.strokeStyle = "#ff0a0a";
					}
					var endLineX = (pntSpacing * (pntCounter + 1)) + horiShift;
					pntPosX = (pntSpacing * pntCounter) + horiShift;
					pntPosY = w * (pntValue / graphScaling);
					
					
					//Making divs(points)
					var pnt = d.createElement("div");
					var date = d.createElement("span");
					var pntClas = d.createAttribute("class");
					pntClas.value = "pnt";
					var dateClas = d.createAttribute("class");
					dateClas.value = "pntDate";
					var pntStyle = d.createAttribute("style");
					pntStyle.value = "top:" + pntPosY + "px;left:" + pntPosX + "px;background-color:rgb("+ pntColor +")";
					var pntTxt = d.createTextNode(pntValueLabeled);
					var dateTxt = d.createTextNode(pulledTable[i].time);
					var pntEventOn = d.createAttribute("onmouseover");
					var pntEventOff = d.createAttribute("onmouseout");
					pntEventOn.value = "showPntDate(" + pntIndex + ", true)";
					pntEventOff.value = "showPntDate(" + pntIndex + ", false)";
					pnt.setAttributeNode(pntClas);
					pnt.setAttributeNode(pntStyle);
					pnt.setAttributeNode(pntEventOn);
					pnt.setAttributeNode(pntEventOff);
					date.setAttributeNode(dateClas);
					pnt.appendChild(pntTxt);
					date.appendChild(dateTxt);
					pnt.appendChild(date);
					domGraph.appendChild(pnt);
					pntCounter++;
					pntIndex++;
					
					//Making Point Lines
					ctx.beginPath();
					ctx.moveTo(pntPosX, pntPosY);
					ctx.lineTo(endLineX, endLineY);
					ctx.stroke();					
				}
			}
		}
		
		//Shows the date of the points when mouse overed
		function showPntDate(index, isMouseOn) {
			if (isMouseOn) {
				d.getElementsByClassName("pnt")[index].style.width = "10px";
				d.getElementsByClassName("pnt")[index].style.height = "10px";
				d.getElementsByClassName("pntDate")[index].style.visibility = "visible";
			} else if (!isMouseOn) {
				d.getElementsByClassName("pnt")[index].style.width = "8px";
				d.getElementsByClassName("pnt")[index].style.height = "8px";
				d.getElementsByClassName("pntDate")[index].style.visibility = "hidden";
			}
		}
		
		</script>
	</head>
	<body onload="loadFunc()">
		<canvas id="basicCanvas"></canvas>
		<button class="taBttn" id="leftBttn" onclick="showGraph(graphCurPage-=1)">&lt;</button>
		<button class="taBttn" id="rightBttn" onclick="showGraph(graphCurPage+=1)">&gt;</button>
		<div id="bttnContainer">
			<button id="hideBttn" class="taBttn" onclick="hideBttn()">Hide Buttons</button>
			<button class="taBttn" onclick="showTable(false)">Hide Table</button>
			<button class="taBttn" onclick="showTable(true)">Show Table</button>
		</div>
		<div id="table"></div>
		<div id="graph"></div>
		<div id="minMaxContainter">
			<p id="humMax"></p>
			<p id="humMin"></p>
			<p id="tempMax"></p>
			<p id="tempMin"></p>
			<h3>- Mouse over the points for dates</h3>
		</div>
	</body>
</html>
