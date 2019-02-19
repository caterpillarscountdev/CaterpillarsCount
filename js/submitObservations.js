			function haveInternet(){
				//return whether or not we have an internet connection that we are allowed to use
				return navigator.onLine;
			}
			
			var hasMoved = false;
			var currentElement = null;
			var autocompleteIsActive = false;
			$(document).ready(function(){
				for(var i = 0; i < $(".noautocomplete").length; i++){
					$(".noautocomplete").eq(i)[0].removeAttribute('readOnly');
				}
				queueManagerRequests();
				
				//clear file upload
				$("#uploadedImageFile")[0].outerHTML = $("#uploadedImageFile")[0].outerHTML;
			});
			function tap(){
				return !hasMoved;
			}
			
			function blurAllNonActive(){
				var inputs = $("input");
				for(var i = 0; i < inputs.length; i++){
					if(!(event.target === inputs.eq(i)[0] && inputs.eq(i)[0] === document.activeElement)){
						inputs.eq(i)[0].blur();
					}
				}
					
				var textareas = $("textarea");
				for(var i = 0; i < textareas.length; i++){
					if(!(event.target === textareas.eq(i)[0] && textareas.eq(i)[0] === document.activeElement)){
						textareas.eq(i)[0].blur();
					}
				}
			}
			
			function b64toBlob(b64Data, contentType, sliceSize) {
	//alert(b64Data);
	//alert(contentType);
				contentType = contentType || '';
				sliceSize = sliceSize || 512;
	//alert(sliceSize);
				var byteCharacters = atob(b64Data);
				var byteArrays = [];
				for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
					var slice = byteCharacters.slice(offset, offset + sliceSize);
					var byteNumbers = new Array(slice.length);
					for (var i = 0; i < slice.length; i++) {
						byteNumbers[i] = slice.charCodeAt(i);
					}
					var byteArray = new Uint8Array(byteNumbers);
					byteArrays.push(byteArray);
				}
				var blob = new Blob(byteArrays, {type: contentType});
				return blob;
			}
			
			var submitPendingSurveyOnStandby = true;
			var successfullySubmittedPendingSurveyIndexes = [];
			var currentSubmitPendingSurveyIndex = -1;
			var currentPendingSurvey = [];
			var readyForRetry = true;
			var fixIfError = true;
			function submitPendingSurvey(index, pendingSurvey){
				if(readyForRetry){
					readyForRetry = false;
					setLoadingButton($("#retryPlantCredentialsButton")[0], "Fix it!", true);
					
					submitPendingSurveyOnStandby = false;
					currentSubmitPendingSurveyIndex = index;
					currentPendingSurvey = pendingSurvey;
					
					//CURRENT
					//[plantCode, sitePassword, dateAndTime, observationMethod, siteNotes, wetLeaves, arthropodDataCopy, plantSpecies, numberOfLeaves, averageLeafLength, herbivoryScore]
					//dateAndTime: [date, time]
					//arthropodData: [[orderType, orderLength, orderQuantity, orderNotes, hairy, leafRoll, silkTent, fileInput]]
					var plantCode = pendingSurvey[0];
					var sitePassword = pendingSurvey[1];
					if($("#retryPlantCredentials")[0].style.display == "block"){
						plantCode = $("#retrySurveyLocationCode")[0].value.replace(/ /g, "").toUpperCase();
						sitePassword = $("#retrySitePassword")[0].value;
					}
					var dateAndTime = pendingSurvey[2];
					var observationMethod = pendingSurvey[3];
					var siteNotes = pendingSurvey[4];
					var wetLeaves = pendingSurvey[5];
					var arthropodDataCopy = pendingSurvey[6];
					var plantSpecies = pendingSurvey[7];
					var numberOfLeaves = pendingSurvey[8];
					var averageLeafLength = pendingSurvey[9];
					var herbivoryScore = pendingSurvey[10];
	//alert("set vars");
					var formData = new FormData();
					for(var i = 0; i < arthropodDataCopy.length; i++){
						var imgData = arthropodDataCopy[i][9];
						if(imgData != ""){
							var b64Data = imgData[1];
							var contentType = imgData[0];
							formData.append(('file' + i), b64toBlob(b64Data, contentType));
							arthropodDataCopy[i][9] = "";
						}
					}
	//alert("converted base64 to blobs");
					formData.append("plantCode", plantCode);
					formData.append("sitePassword", sitePassword);
					formData.append("date", dateAndTime[0]);
					formData.append("time", dateAndTime[1]);
					formData.append("siteNotes", siteNotes);
					formData.append("wetLeaves", wetLeaves);
					formData.append("arthropodData", JSON.stringify(arthropodDataCopy));
	//alert(JSON.stringify(arthropodDataCopy));
					formData.append("numberOfLeaves", numberOfLeaves);
					formData.append("averageLeafLength", averageLeafLength);
					formData.append("herbivoryScore", herbivoryScore);
					formData.append("observationMethod", observationMethod);
					formData.append("plantSpecies", plantSpecies);
					formData.append("submittedThroughApp", false);
					formData.append("email", window.localStorage.getItem("email"));
					formData.append("salt", window.localStorage.getItem("salt"));
	//alert("attached form data");
					
					$.ajax({
						url : "https://caterpillarscount.unc.edu/php/submit.php",
						type : 'POST',
						data : formData,
						processData: false,  // tell jQuery not to process the data
						contentType: false,  // tell jQuery not to set contentType
						success: function(data){
							if(data.indexOf("true|") == 0){
	//alert("survey " + index + " was a success");
								//hide retryPlantCredentials if shown
								hideRetryPlantCredentialsNotice();
								
								//add successful index to be removed from pending box
								successfullySubmittedPendingSurveyIndexes[successfullySubmittedPendingSurveyIndexes.length] = index;
								//queueNotice("confirmation", "Submitted!");
								
								//go to next in queue
								submitPendingSurveyOnStandby = true;
							}
							else{
	//alert("survey " + index + " had an issue");
								var submissionError = data.replace("false|", "");
								if(submissionError.indexOf("Your log in dissolved. Maybe you logged in on another device.") > -1){
									//hide retryPlantCredentials if shown
									hideRetryPlantCredentialsNotice();
									
									//do nothing and continue
									submitPendingSurveyOnStandby = true;
								}
								else if(submissionError.indexOf("Enter a valid survey location code.") > -1 || submissionError.indexOf("Invalid plant.") > -1 || submissionError.indexOf("Enter a valid password.") > -1){
									//TODO: allow correction
									if(fixIfError){
										if($("#retryPlantCredentials")[0].style.display == "block"){
											//tell the user it didnt work
											//RESET PLANT CODE TO THE ORIGINAL SUBMISSION: $("#retrySurveyLocationCode")[0].value = pendingSurvey[0];
											$("#retrySitePassword")[0].value = "";
											$("#retrySurveyLocationCode").stop().animate({backgroundColor:"#ffbbbb"}).animate({backgroundColor:"#fff"});
											$("#retrySitePassword").stop().animate({backgroundColor:"#ffbbbb"}).animate({backgroundColor:"#fff"});
										}
										else{
											queueNotice("retryPlantCredentials", plantCode + "|While you were offline, at " + getTwelveHourTime(dateAndTime[1]) + " on " + getReadableDate(dateAndTime[0]) + ", you tried to submit a " + observationMethod.toLowerCase() + " survey of the " + plantSpecies.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();}) + " species  with some invalid site information. Please provide the correct information below.");
										}
									}
									else{
										//hide retryPlantCredentials if shown
										hideRetryPlantCredentialsNotice();
									
										//and continue
										submitPendingSurveyOnStandby = true;
									}
								}
								else if(submissionError.indexOf("Invalid date of survey.") > -1 || submissionError.indexOf("Invalid time of survey.") > -1 || submissionError.indexOf("Select an observation method.") > -1 || submissionError.indexOf("Invalid plant species.") > -1 || submissionError.indexOf("Number of leaves must be between 1 and 500.") > -1 || submissionError.indexOf("Average leaf length must be between 1cm and 60cm.") > -1 || submissionError.indexOf("Select an herbivory score.") > -1 || submissionError.indexOf("Invalid arthropod group.") > -1 || submissionError.indexOf("length must be between 1mm and 300mm.") > -1 || submissionError.indexOf("quantity must be between 1 and 1000.") > -1){
									//hide retryPlantCredentials if shown
									hideRetryPlantCredentialsNotice();
									
									//remove tainted survey
									successfullySubmittedPendingSurveyIndexes[successfullySubmittedPendingSurveyIndexes.length] = index;
									
									//and continue
									submitPendingSurveyOnStandby = true;
								}
								else{
									//hide retryPlantCredentials if shown
									hideRetryPlantCredentialsNotice();
									
									//alert user of unexpected error
									queueNotice("error", "Oh no! We were automatically collecting your previous offline survey submissions now that you have an internet connection, but we ran into this unexpected error and had to stop:<br/><br/>" + submissionError + "<br/><br/>If this is not the first time you have seen this error, please take a screenshot right now and email it to caterpillarscount@gmail.com so we can fix it. Thank you!");
									
									//and dont continue
								}
							}
						},
						error: function(){
	//alert("survey " + index + " was in error");
							//hide retryPlantCredentials if shown
							hideRetryPlantCredentialsNotice();
									
							//go to next in queue
							submitPendingSurveyOnStandby = true;
						},
						complete: function(){
							readyForRetry = true;
							setLoadingButton($("#retryPlantCredentialsButton")[0], "Fix it!", false);
						}
					});
				}
			}
			
			function retryPlantCredentials(){
				submitPendingSurvey(currentSubmitPendingSurveyIndex, currentPendingSurvey);
			}
			
			function discardPlantCredentials(){
				//remove tainted survey
				successfullySubmittedPendingSurveyIndexes[successfullySubmittedPendingSurveyIndexes.length] = currentSubmitPendingSurveyIndex;
							
				//hide retryPlantCredentials if shown
				hideRetryPlantCredentialsNotice();
						
				//and continue
				submitPendingSurveyOnStandby = true;
			}
			
			function postponeRetryPlantCredentials(){
				//dont alert if there is an error with pending surveys anymore during this session
				fixIfError = false;
				
				//hide retryPlantCredentials if shown
				hideRetryPlantCredentialsNotice();
			}
			
			var queuedPendingSurveys = false;
			function queuePendingSurveys(){
	//alert(window.localStorage.getItem("pendingSurveys") === null);
				if(window.localStorage.getItem("pendingSurveys") === null || !haveInternet()){return false;}
				queuedPendingSurveys = true;
				var index = 0;
				var readyForSubmissionCheck = setInterval(function(){
					if(submitPendingSurveyOnStandby){
						var lastestVersionOfPendingSurveys = JSON.parse(window.localStorage.getItem("pendingSurveys"));
						if(index < lastestVersionOfPendingSurveys.length && haveInternet() && lastestVersionOfPendingSurveys[index][11] == window.localStorage.getItem("email")){
							//NEXT
	//alert("submitPendingSurvey");
							submitPendingSurvey(index, lastestVersionOfPendingSurveys[index++]);
						}
						else{
	//alert("done with queue");
							//DONE
							for(var i = (lastestVersionOfPendingSurveys.length - 1); i >= 0; i--){
								if(successfullySubmittedPendingSurveyIndexes.indexOf(i) > -1){
									lastestVersionOfPendingSurveys.splice(i, 1);
								}
							}
							if(lastestVersionOfPendingSurveys.length > 0){
								window.localStorage.setItem("pendingSurveys", JSON.stringify(lastestVersionOfPendingSurveys));
							}
							else{
								window.localStorage.removeItem("pendingSurveys");
							}
	//alert("updated pending surveys: " + window.localStorage.getItem("pendingSurveys"));
							successfullySubmittedPendingSurveyIndexes = [];
							queuedPendingSurveys = false;
							clearInterval(readyForSubmissionCheck);
						}
					}
				}, 100);
			}
			
			setInterval(function(){
				if(!queuedPendingSurveys){
					queuePendingSurveys();
				}
			},3000);
			
			var noticeQueue = [];
			var showingQueue = false;
			function queueNotice(type, message){
				for(var i = 0; i < noticeQueue.length; i++){
					if(type != "retryPlantCredentials" && noticeQueue[i][0] == type && noticeQueue[i][1] == message){
						//if its already in the queue
						return false;
					}
				}
				
				noticeQueue[noticeQueue.length] = [type, message];
				if(!showingQueue){
					showingQueue = true;
					showNextNoticeInQueue();
				}
			}
			
			function showRetryPlantCredentials(message){
				//show a regular alert message
				$("#retrySurveyLocationCode")[0].value = message.substring(0, message.indexOf("|")).toUpperCase();
				$("#retrySitePassword")[0].value = "";
				$("#retryPlantCredentials .message").eq(0)[0].innerHTML = message.substring(message.indexOf("|") + 1);
				$("#retryPlantCredentials").stop().fadeIn();
				$("#noticeInteractionBlock").stop().fadeIn();
				
				$("#noticeInteractionBlock")[0].onclick = function(){
					postponeRetryPlantCredentials();
				};
			}
			
			function showManagerRequest(message){
				//show a regular alert message
				$("#managerRequestMessage")[0].innerHTML = message;
				$("#managerRequest").stop().fadeIn();
				$("#noticeInteractionBlock").stop().fadeIn();
			}
			
			function hideRetryPlantCredentialsNotice(){
				if($("#retryPlantCredentials")[0].style.display == "block"){
					$("#noticeInteractionBlock")[0].onclick = function(){
						hideNotice();
					};
					hideNotice();
				}
			}
			
			function askForOfficialPlantSpecies(){
				if(haveInternet()){
					var xhttp = new XMLHttpRequest();
					xhttp.onreadystatechange = function() {
						if (this.readyState == 4 && this.status == 200) {
							if(this.responseText.indexOf("true|") == 0){
								var siteNames = this.responseText.replace("true|", "");
								queueNotice("alert", "You still need to set the official plant species for "  + siteNames + ". Doing so will improve the quality of the data your surveys generate and allow you to print proper tags to display on your plants. Otherwise, your tags will say \"N/A\" where the plant species should go. Go to \"Settings\" > \"Manages my sites\" > \"Edit survey plants\" to do this as soon as possible.");
							}
						}
					};
					xhttp.open("GET", "https://caterpillarscount.unc.edu/php/getSitesWithUnsetPlantSpecies.php?email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt"), true);
					xhttp.send();
				}
			}

			function forceLeadingZero(number){
				//for any number less than 100, return the number with a leading 0 if necessary
				var decimal = (Number(number)/100).toFixed(2).toString();
				return decimal.substring(decimal.length - 2, decimal.length);
			}
			
			function getDate(adaptToLocalTime){//to autofill the input
				var date = new Date();
				var hoursOffset = 0;
				if(adaptToLocalTime){
					hoursOffset = (date.getTimezoneOffset() / 60);
				}
				date.setHours(date.getHours() - hoursOffset);
				
				var month = (date.getMonth() + 1);
				var day = date.getDate();
				var year = date.getFullYear();
				return month + "/" + day  + "/" + year;
			}
			
			function pastDate(date){
				date = date.replace(/\D/g, " ").replace(/\s\s+/g, " ").trim();
				var dateItems = date.split(" ");
				if(dateItems.length == 3){
					var month  = Number(dateItems[0]);
					var day  = Number(dateItems[1]);
					var year  = Number(dateItems[2]);
					if(year.toString().length == 2){year += 2000;}
					
					if(year < 1980){return false;}
					if(month < 1 || month > 12){return false;}
					
					var monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
					// Adjust for leap years
					if(year % 400 == 0 || (year % 100 != 0 && year % 4 == 0)){monthLength[1] = 29;}
					if(day < 1 || day > monthLength[month - 1]){return false;}
					
					var d = new Date(year, month - 1, day, 23, 59, 59, 999);
					var now = new Date();
					if(d.getTime() < now.getTime()){
						return month + "/" + day + "/" + year;
					}
				}
				return false;
			}
			
			function getFormattedDate(date){//to autoformat user input
				date = date.replace(/\D/g, " ").replace(/\s\s+/g, " ").trim();
				var dateItems = date.split(" ");
				
				if(dateItems.length == 3){
					var month  = Number(dateItems[0]);
					var day  = Number(dateItems[1]);
					var year  = Number(dateItems[2]);
					if(year.toString().length == 2){year += 2000;}
					
					if(year < 1980){return "";}
					if(month < 1 || month > 12){return "";}
					
					var monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
					// Adjust for leap years
					if(year % 400 == 0 || (year % 100 != 0 && year % 4 == 0)){monthLength[1] = 29;}
					if(day < 1 || day > monthLength[month - 1]){return "";}
					
					var d = new Date(year, month - 1, day, 0, 0, 0, 0);
					var now = new Date();
					if(d.getTime() > now.getTime()){
						queueNotice("error", "Surveys may not be from the future.");
						return "";
					}
					
					return month + "/" + day + "/" + year;
				}
				return "";
			}
			
			function getOrdinal(e){n=e.toString();for(var t="",h=0,d=n.length-1;d>=0;d--)if(1==++h)"0"==n[d]||("1"==n[d]?t="first":"2"==n[d]?t="second":"3"==n[d]?t="third":"4"==n[d]?t="fourth":"5"==n[d]?t="fifth":"6"==n[d]?t="sixth":"7"==n[d]?t="seventh":"8"==n[d]?t="eighth":"9"==n[d]&&(t="ninth"));else if(2==h)"0"==n[d]||("1"==n[d]?"0"==n[d+1]?t="tenth":"1"==n[d+1]?t="eleventh":"2"==n[d+1]?t="twelfth":"3"==n[d+1]?t="thirteenth":"4"==n[d+1]?t="fourteenth":"5"==n[d+1]?t="fifteenth":"6"==n[d+1]?t="sixteenth":"7"==n[d+1]?t="seventeenth":"8"==n[d+1]?t="eighteenth":"9"==n[d+1]&&(t="nineteenth"):"2"==n[d]?t=""==t?"twentieth":"twenty-"+t:"3"==n[d]?t=""==t?"thirtieth":"thirty-"+t:"4"==n[d]?t=""==t?"fortieth":"forty-"+t:"5"==n[d]?t=""==t?"fiftieth":"fifty-"+t:"6"==n[d]?t=""==t?"sixtieth":"sixty-"+t:"7"==n[d]?t=""==t?"seventieth":"seventy-"+t:"8"==n[d]?t=""==t?"eightieth":"eighty-"+t:"9"==n[d]&&(t=""==t?"ninetieth":"ninety-"+t));else if(3==h)"0"==n[d]||("1"==n[d]?t=""==t?"one hundredth":"one hundred and "+t:"2"==n[d]?t=""==t?"two hundredth":"two hundred and "+t:"3"==n[d]?t=""==t?"three hundredth":"three hundred and "+t:"4"==n[d]?t=""==t?"four hundredth":"four hundred and "+t:"5"==n[d]?t=""==t?"five hundredth":"five hundred and "+t:"6"==n[d]?t=""==t?"six hundredth":"six hundred and "+t:"7"==n[d]?t=""==t?"seven hundredth":"seven hundred and "+t:"8"==n[d]?t=""==t?"eight hundredth":"eight hundred and "+t:"9"==n[d]&&(t=""==t?"nine hundredth":"nine hundred and "+t));else if(4==h)return n;return t}
			
			function getReadableDate(databaseFormattedDate){
				//PRE: databaseFormattedDate format is YYYY-MM-DD
				var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
				
				var year = databaseFormattedDate.substring(0, 4);
				var month = months[Number(databaseFormattedDate.substring(5, 7)) - 1];
				var fullOrdinal = getOrdinal(Number(databaseFormattedDate.substring(8)));
				var day = Number(databaseFormattedDate.substring(8)) + fullOrdinal.substring(fullOrdinal.length - 2);
				
				return month + " " + day + ", " + year;
			}
			
			function getTwelveHourTime(militaryTime){
				//PRE: militaryTime format is HH:MM:SS
				var hours = Number(militaryTime.substring(0, 2));
				var minutes = militaryTime.substring(3, 5);
				
				var formattedHours = hours;
				if(hours == 0){formattedHours = 12;}
				else if(hours > 12){formattedHours = hours - 12;}
				var ampm = "am";
				if(hours >= 12){ampm = "pm";}
				return formattedHours + ":" + minutes + " " + ampm;
			}
			
			function getTime(adaptToLocalTime){//to autofill the input
				var date = new Date();
				var hoursOffset = 0;
				if(adaptToLocalTime){hoursOffset = (date.getTimezoneOffset() / 60);}
				
				
				var minutesSince = date.getTime()/1000/60;
				var hoursSince = minutesSince/60;
				var minutes = Math.floor(minutesSince % 60);
				var hours = Math.floor((hoursSince - hoursOffset) % 24);
				
				var formattedHours = hours;
				if(hours == 0){formattedHours = 12;}
				else if(hours > 12){formattedHours = hours - 12;}
				
				var formattedMinutes = (minutes/100).toFixed(2).toString().substring(2);
				
				var ampm = "am";
				if(hours >= 12){ampm = "pm";}
				
				return (formattedHours + ":" + formattedMinutes + " " + ampm);
			}
			
			function getFormattedTime(time, throwAlerts){//to autoformat user input
				time = time.toLowerCase().replace(/ /g, "");
				
				//allow alternative delimiters
				numberTimeString = time.replace(/\D/g, "");
				if(numberTimeString.length > 4 || numberTimeString.length < 3){return "";}
				var minutes = numberTimeString.substring(numberTimeString.length - 2);
				var hours = Number(numberTimeString.substring(0, numberTimeString.length - 2));
				
				if(Number(minutes) > 59 || Number(minutes) < 0 || hours < 1 || hours > 12){return "";}
				
				//force am/pm format
				var ampm = "";
				if(time.indexOf("am") > -1 && time.indexOf("pm") > -1){return "";}
				else if(time.indexOf("am") > -1){ampm = "am";}
				else if(time.indexOf("pm") > -1){ampm = "pm";}
				else{
					if(throwAlerts){queueNotice("alert", "Specify am/pm for time.");}
					return "";
				}
				
				return hours + ":" + minutes + " " + ampm;
			}
			
			function formatDateAndTimeForDatabase(date, time, UTC){
				time = getFormattedTime(time, false);
				date = getFormattedDate(date);
				if(time == "" || date == ""){["", ""];}
				
				var dateItems = date.split("/");
				
				time = time.replace(/ /g, "");
				var colonIndex = time.indexOf(":");
				var hours = Number(time.substring(0, colonIndex));
				var minutes = time.substring(colonIndex + 1, colonIndex + 3);
				var ampm = time.substring(colonIndex + 3, colonIndex + 5);
				if(ampm == "pm" && hours < 12){hours += 12;}
				else if(ampm == "am" && hours == 12){hours = 0;}
				
				var jsdate = new Date();
				var hoursOffset = 0;
				if(UTC){
					hoursOffset = (jsdate.getTimezoneOffset() / 60);
				}
				
				jsdate.setMonth(Number(dateItems[0]) - 1);
				jsdate.setDate(Number(dateItems[1]));
				jsdate.setFullYear(dateItems[2]);
				jsdate.setMinutes(Number(minutes));
				jsdate.setHours(hours + hoursOffset);
				
				return [(jsdate.getFullYear() + "-" + forceLeadingZero(jsdate.getMonth() + 1) + "-" + forceLeadingZero(jsdate.getDate())), (forceLeadingZero(jsdate.getHours()) + ":" + minutes + ":00")];
			}
			
			var gettingTemperature = false;
			function alertTemperature(zip){
				//if we have internet, alert the current fahrenheit temperature in the zip code "zip"
				if(haveInternet() && !gettingTemperature){
					gettingTemperature = true;
					var xhttp = new XMLHttpRequest();
					xhttp.onreadystatechange = function() {
						if (this.readyState == 4 && this.status == 200) {
							gettingTemperature = false;
							var kelvin = Number(JSON.parse(this.responseText)["main"]["temp"]);
							var fahrenheit = kelvin * (9/5) - 459.67;
							if(isNaN(kelvin)){
								queueNotice("error", "Could not automatically detect the temperature. Please fill it in manually and resubmit.");
							}
							else{
								alert(Math.round(fahrenheit));
							}
						}
					};
					xhttp.open("GET", "http://api.openweathermap.org/data/2.5/weather?zip=" + zip + ",us&appid=e74e9ed3dae88f36f447052636fb1787", true);
					xhttp.send();
				}
				else{//otherwise, do the same thing as if the ajax failed
					queueNotice("error", "Could not automatically detect temperature. Please fill it in munually and resubmit.");
				}
			}
			
			function  selectDualOptionButton(buttonElement){
				//highlight one of the .dualOptionButton buttons and unhighlight the other
				buttonElement = $(buttonElement)[0];
				$(buttonElement.parentNode).find(".dualOptionButton").stop().animate({backgroundColor:"#fff", color:"#aaa"}, 200);
				$(buttonElement).stop().animate({backgroundColor:"#333", color:"#fff"}, 200);
			}
			
			var plantSpeciesList = ["Swamp cottonwood","Populus heterophylla","Plains cottonwood","Populus deltoides","Quaking aspen","Populus tremuloides","Black cottonwood","Populus balsamifera","Fremont cottonwood","Populus fremontii","Narrowleaf cottonwood","Populus angustifolia","Silver poplar","Populus alba","Lombardy poplar","Populus nigra","Mesquite spp.","Prosopis spp.","Honey mesquite","Prosopis glandulosa","Velvet mesquite","Prosopis velutina","Screwbean mesquite","Prosopis pubescens","Cherry and plum spp.","Prunus spp.","Pin cherry","Prunus pensylvanica","Black cherry","Prunus serotina","Chokecherry","Prunus virginiana","Peach","Prunus persica","Canada plum","Prunus nigra","American plum","Prunus americana","Bitter cherry","Prunus emarginata","Allegheny plum","Prunus alleghaniensis","Chickasaw plum","Prunus angustifolia","Sweet cherry","Prunus avium","Sour cherry","Prunus cerasus","European plum","Prunus domestica","Mahaleb cherry","Prunus mahaleb","Oak spp.","Quercus spp.","California live oak","Quercus agrifolia","White oak","Quercus alba","Arizona white oak","Quercus arizonica","Swamp white oak","Quercus bicolor","Canyon live oak","Quercus chrysolepis","Scarlet oak","Quercus coccinea","Blue oak","Quercus douglasii","Durand oak","Quercus sinuata","Northern pin oak","Quercus ellipsoidalis","Emory oak","Quercus emoryi","Engelmann oak","Quercus engelmannii","Southern red oak","Quercus falcata","Cherrybark oak","Quercus pagoda","Gambel oak","Quercus gambelii","Oregon white oak","Quercus garryana","Scrub oak","Quercus ilicifolia","Shingle oak","Quercus imbricaria","California black oak","Quercus kelloggii","Turkey oak","Quercus laevis","Laurel oak","Quercus laurifolia","California white oak","Quercus lobata","Overcup oak","Quercus lyrata","Bur oak","Quercus macrocarpa","Blackjack oak","Quercus marilandica","Swamp chestnut oak","Quercus michauxii","Chinkapin oak","Quercus muehlenbergii","Water oak","Quercus nigra","Texas red oak","Quercus texana","Mexican blue oak","Quercus oblongifolia","Pin oak","Quercus palustris","Willow oak","Quercus phellos","Bigtooth aspen","Populus grandidentata","Chestnut oak","Quercus prinus","Northern red oak","Quercus rubra","Shumard oak","Quercus shumardii","Post oak","Quercus stellata","Delta post oak","Quercus similis","Black oak","Quercus velutina","Live oak","Quercus virginiana","Interior live oak","Quercus wislizeni","Dwarf post oak","Quercus margarettiae","Dwarf live oak","Quercus minima","Bluejack oak","Quercus incana","Silverleaf oak","Quercus hypoleucoides","Oglethorpe oak","Quercus oglethorpensis","Dwarf chinkapin oak","Quercus prinoides","Gray oak","Quercus grisea","Netleaf oak","Quercus rugosa","Chisos oak","Quercus graciliformis","Sea torchwood","Amyris elemifera","Pond-apple","Annona glabra","Gumbo limbo","Bursera simaruba","Sheoak spp.","Casuarina spp.","Gray sheoak","Casuarina glauca","Belah","Casuarina lepidophloia","Camphortree","Cinnamomum camphora","Florida fiddlewood","Citharexylum fruticosum","Citrus spp.","Citrus spp.","Tietongue","Coccoloba diversifolia","Soldierwood","Colubrina elliptica","Largeleaf geigertree","Cordia sebestena","Carrotwood","Cupaniopsis anacardioides","Bluewood","Condalia hookeri","Blackbead ebony","Ebenopsis ebano","Great leucaene","Leucaena pulverulenta","Texas sophora","Sophora affinis","Red stopper","Eugenia rhombea","Butterbough","Exothea paniculata","Florida strangler fig","Ficus aurea","Wild banyantree","Ficus citrifolia","Beeftree","Guapira discolor","Manchineel","Hippomane mancinella","False tamarind","Lysiloma latisiliquum","Mango","Mangifera indica","Florida poisontree","Metopium toxiferum","Fishpoison tree","Piscidia piscipula","Octopus tree","Schefflera actinophylla","False mastic","Sideroxylon foetidissimum","White bully","Sideroxylon salicifolium","Paradisetree","Simarouba glauca","Java plum","Syzygium cumini","Tamarind","Tamarindus indica","Black locust","Robinia pseudoacacia","New mexico locust","Robinia neomexicana","Everglades palm","Acoelorraphe wrightii","Florida silver palm","Coccothrinax argentata","Coconut palm","Cocos nucifera","Royal palm spp.","Roystonea spp.","Mexican palmetto","Sabal mexicana","Cabbage palmetto","Sabal palmetto","Key thatch palm","Thrinax morrisii","Florida thatch palm","Thrinax radiata","Other palms","Family arecaceae not listed above","Western soapberry","Sapindus saponaria","Willow spp.","Salix spp.","Peachleaf willow","Salix amygdaloides","Black willow","Salix nigra","Bebb willow","Salix bebbiana","Bonpland willow","Salix bonplandiana","Coastal plain willow","Salix caroliniana","Balsam willow","Salix pyrifolia","White willow","Salix alba","Scouler's willow","Salix scouleriana","Weeping willow","Salix sepulcralis","Sassafras","Sassafras albidum","Mountain-ash spp.","Sorbus spp.","American mountain-ash","Sorbus americana","European mountain-ash","Sorbus aucuparia","Northern mountain-ash","Sorbus decora","West indian mahogany","Swietenia mahagoni","Basswood spp.","Tilia spp.","American basswood","Tilia americana","White basswood","Tilia americana","Carolina basswood","Tilia americana","Elm spp.","Ulmus spp.","Winged elm","Ulmus alata","American elm","Ulmus americana","Cedar elm","Ulmus crassifolia","Siberian elm","Ulmus pumila","Slippery elm","Ulmus rubra","September elm","Ulmus serotina","Rock elm","Ulmus thomasii","California-laurel","Umbellularia californica","Joshua tree","Yucca brevifolia","Black-mangrove","Avicennia germinans","Buttonwood-mangrove","Conocarpus erectus","White-mangrove","Laguncularia racemosa","American mangrove","Rhizophora mangle","Desert ironwood","Olneya tesota","Saltcedar","Tamarix spp.","Melaleuca","Melaleuca quinquenervia","Chinaberry","Melia azedarach","Chinese tallowtree","Triadica sebifera","Tungoil tree","Vernicia fordii","Smoketree","Cotinus obovatus","Russian-olive","Elaeagnus angustifolia","Washington hawthorn","Crataegus phaenopyrum","Fleshy hawthorn","Crataegus succulenta","Dwarf hawthorn","Crataegus uniflora","Berlandier ash","Fraxinus berlandieriana","Avocado","Persea americana","Graves oak","Quercus gravesii","Mexican white oak","Quercus polymorpha","Buckley oak","Quercus buckleyi","Lacey oak","Quercus laceyi","Anacahuita","Cordia boissieri","Fir spp.","Abies spp.","Pacific silver fir","Abies amabilis","Balsam fir","Abies balsamea","Santa lucia or bristlecone fir","Abies bracteata","White fir","Abies concolor","Fraser fir","Abies fraseri","Grand fir","Abies grandis","Corkbark fir","Abies lasiocarpa","Subalpine fir","Abies lasiocarpa","California red fir","Abies magnifica","Shasta red fir","Abies shastensis","Noble fir","Abies procera","White-cedar spp.","Chamaecyparis spp.","Port-orford-cedar","Chamaecyparis lawsoniana","Alaska yellow-cedar","Chamaecyparis nootkatensis","Atlantic white-cedar","Chamaecyparis thyoides","Cypress","Cupressus spp.","Arizona cypress","Cupressus arizonica","Modoc cypress","Cupressus bakeri","Tecate cypress","Cupressus forbesii","Monterey cypress","Cupressus macrocarpa","Sargent's cypress","Cupressus sargentii","Macnab's cypress","Cupressus macnabiana","Redcedar/juniper spp.","Juniperus spp.","Pinchot juniper","Juniperus pinchotii","Redberry juniper","Juniperus coahuilensis","Drooping juniper","Juniperus flaccida","Ashe juniper","Juniperus ashei","California juniper","Juniperus californica","Alligator juniper","Juniperus deppeana","Western juniper","Juniperus occidentalis","Utah juniper","Juniperus osteosperma","Rocky mountain juniper","Juniperus scopulorum","Southern redcedar","Juniperus virginiana","Eastern redcedar","Juniperus virginiana","Oneseed juniper","Juniperus monosperma","Larch spp.","Larix spp.","Tamarack (native)","Larix laricina","Subalpine larch","Larix lyallii","Western larch","Larix occidentalis","Incense-cedar","Calocedrus decurrens","Spruce spp.","Picea spp.","Norway spruce","Picea abies","Brewer spruce","Picea breweriana","Engelmann spruce","Picea engelmannii","White spruce","Picea glauca","Black spruce","Picea mariana","Blue spruce","Picea pungens","Red spruce","Picea rubens","Sitka spruce","Picea sitchensis","Pine spp.","Pinus spp.","Whitebark pine","Pinus albicaulis","Bristlecone pine","Pinus aristata","Knobcone pine","Pinus attenuata","Foxtail pine","Pinus balfouriana","Jack pine","Pinus banksiana","Common pinyon","Pinus edulis","Sand pine","Pinus clausa","Lodgepole pine","Pinus contorta","Coulter pine","Pinus coulteri","Shortleaf pine","Pinus echinata","Slash pine","Pinus elliottii","Apache pine","Pinus engelmannii","Limber pine","Pinus flexilis","Southwestern white pine","Pinus strobiformis","Spruce pine","Pinus glabra","Jeffrey pine","Pinus jeffreyi","Sugar pine","Pinus lambertiana","Chihuahua pine","Pinus leiophylla","Western white pine","Pinus monticola","Bishop pine","Pinus muricata","Longleaf pine","Pinus palustris","Ponderosa pine","Pinus ponderosa","Table mountain pine","Pinus pungens","Monterey pine","Pinus radiata","Red pine","Pinus resinosa","Pitch pine","Pinus rigida","Gray or california foothill pine","Pinus sabiniana","Pond pine","Pinus serotina","Eastern white pine","Pinus strobus","Scotch pine","Pinus sylvestris","Loblolly pine","Pinus taeda","Virginia pine","Pinus virginiana","Singleleaf pinyon","Pinus monophylla","Border pinyon","Pinus discolor","Arizona pine","Pinus arizonica","Austrian pine","Pinus nigra","Washoe pine","Pinus washoensis","Four-leaf or parry pinyon pine","Pinus quadrifolia","Torrey pine","Pinus torreyana","Mexican pinyon pine","Pinus cembroides","Papershell pinyon pine","Pinus remota","Great basin bristlecone pine","Pinus longaeva","Arizona pinyon pine","Pinus monophylla","Honduras pine","Pinus elliottii","Douglas-fir spp.","Pseudotsuga spp.","Bigcone douglas-fir","Pseudotsuga macrocarpa","Douglas-fir","Pseudotsuga menziesii","Redwood","Sequoia sempervirens","Giant sequoia","Sequoiadendron giganteum","Baldcypress spp.","Taxodium spp.","Baldcypress","Taxodium distichum","Pondcypress","Taxodium ascendens","Montezuma baldcypress","Taxodium mucronatum","Yew spp.","Taxus spp.","Pacific yew","Taxus brevifolia","Florida yew","Taxus floridana","Thuja spp.","Thuja spp.","Northern white-cedar","Thuja occidentalis","Western redcedar","Thuja plicata","Torreya spp.","Torreya spp.","California torreya (nutmeg)","Torreya californica","Florida torreya (nutmeg)","Torreya taxifolia","Hemlock spp.","Tsuga spp.","Eastern hemlock","Tsuga canadensis","Carolina hemlock","Tsuga caroliniana","Western hemlock","Tsuga heterophylla","Mountain hemlock","Tsuga mertensiana","Acacia spp.","Acacia spp.","Sweet acacia","Acacia farnesiana","Catclaw acacia","Acacia greggii","Maple spp.","Acer spp.","Florida maple","Acer barbatum","Bigleaf maple","Acer macrophyllum","Boxelder","Acer negundo","Black maple","Acer nigrum","Striped maple","Acer pensylvanicum","Red maple","Acer rubrum","Silver maple","Acer saccharinum","Sugar maple","Acer saccharum","Mountain maple","Acer spicatum","Norway maple","Acer platanoides","Rocky mountain maple","Acer glabrum","Bigtooth maple","Acer grandidentatum","Chalk maple","Acer leucoderme","Buckeye spp.","Aesculus spp.","Ohio buckeye","Aesculus glabra","Yellow buckeye","Aesculus flava","California buckeye","Aesculus californica","Texas buckeye","Aesculus glabra","Red buckeye","Aesculus pavia","Painted buckeye","Aesculus sylvatica","Ailanthus","Ailanthus altissima","Mimosa","Albizia julibrissin","Alder spp.","Alnus spp.","Red alder","Alnus rubra","White alder","Alnus rhombifolia","Arizona alder","Alnus oblongifolia","European alder","Alnus glutinosa","Serviceberry spp.","Amelanchier spp.","Common serviceberry","Amelanchier arborea","Roundleaf serviceberry","Amelanchier sanguinea","Madrone spp.","Arbutus spp.","Pacific madrone","Arbutus menziesii","Arizona madrone","Arbutus arizonica","Texas madrone","Arbutus xalapensis","Pawpaw","Asimina triloba","Birch spp.","Betula spp.","Yellow birch","Betula alleghaniensis","Sweet birch","Betula lenta","River birch","Betula nigra","Water birch","Betula occidentalis","Paper birch","Betula papyrifera","Virginia roundleaf birch","Betula uber","Northwestern paper birch","Betula x utahensis","Gray birch","Betula populifolia","Chittamwood","Sideroxylon lanuginosum","American hornbeam","Carpinus caroliniana","Hickory spp.","Carya spp.","Water hickory","Carya aquatica","Bitternut hickory","Carya cordiformis","Pignut hickory","Carya glabra","Pecan","Carya illinoinensis","Shellbark hickory","Carya laciniosa","Nutmeg hickory","Carya myristiciformis","Shagbark hickory","Carya ovata","Black hickory","Carya texana","Mockernut hickory","Carya alba","Sand hickory","Carya pallida","Scrub hickory","Carya floridana","Red hickory","Carya ovalis","Southern shagbark hickory","Carya carolinae-septentrionalis","Chestnut spp.","Castanea spp.","American chestnut","Castanea dentata","Allegheny chinkapin","Castanea pumila","Ozark chinkapin","Castanea pumila","Chinese chestnut","Castanea mollissima","Giant chinkapin","Chrysolepis chrysophylla","Catalpa spp.","Catalpa spp.","Southern catalpa","Catalpa bignonioides","Northern catalpa","Catalpa speciosa","Hackberry spp.","Celtis spp.","Sugarberry","Celtis laevigata","Hackberry","Celtis occidentalis","Netleaf hackberry","Celtis laevigata","Eastern redbud","Cercis canadensis","Curlleaf mountain-mahogany","Cercocarpus ledifolius","Yellowwood","Cladrastis kentukea","Dogwood spp.","Cornus spp.","Flowering dogwood","Cornus florida","Pacific dogwood","Cornus nuttallii","Hawthorn spp.","Crataegus spp.","Cockspur hawthorn","Crataegus crus-galli","Downy hawthorn","Crataegus mollis","Brainerd's hawthorn","Crataegus brainerdii","Pear hawthorn","Crataegus calpodendron","Fireberry hawthorn","Crataegus chrysocarpa","Broadleaf hawthorn","Crataegus dilatata","Fanleaf hawthorn","Crataegus flabellata","Oneseed hawthorn","Crataegus monogyna","Scarlet hawthorn","Crataegus pedicellata","Eucalyptus spp.","Eucalyptus spp.","Tasmanian bluegum","Eucalyptus globulus","River redgum","Eucalyptus camaldulensis","Grand eucalyptus","Eucalyptus grandis","Swampmahogany","Eucalyptus robusta","Persimmon spp.","Diospyros spp.","Common persimmon","Diospyros virginiana","Texas persimmon","Diospyros texana","Anacua knockaway","Ehretia anacua","American beech","Fagus grandifolia","Ash spp.","Fraxinus spp.","White ash","Fraxinus americana","Oregon ash","Fraxinus latifolia","Black ash","Fraxinus nigra","Green ash","Fraxinus pennsylvanica","Pumpkin ash","Fraxinus profunda","Blue ash","Fraxinus quadrangulata","Velvet ash","Fraxinus velutina","Carolina ash","Fraxinus caroliniana","Texas ash","Fraxinus texensis","Honeylocust spp.","Gleditsia spp.","Waterlocust","Gleditsia aquatica","Honeylocust","Gleditsia triacanthos","Loblolly-bay","Gordonia lasianthus","Ginkgo","Ginkgo biloba","Kentucky coffeetree","Gymnocladus dioicus","Silverbell spp.","Halesia spp.","Carolina silverbell","Halesia carolina","Two-wing silverbell","Halesia diptera","Little silverbell","Halesia parviflora","American holly","Ilex opaca","Walnut spp.","Juglans spp.","Butternut","Juglans cinerea","Black walnut","Juglans nigra","Northern california black walnut","Juglans hindsii","Southern california black walnut","Juglans californica","Texas walnut","Juglans microcarpa","Arizona walnut","Juglans major","Sweetgum","Liquidambar styraciflua","Yellow-poplar","Liriodendron tulipifera","Tanoak","Lithocarpus densiflorus","Osage-orange","Maclura pomifera","Magnolia spp.","Magnolia spp.","Cucumbertree","Magnolia acuminata","Southern magnolia","Magnolia grandiflora","Sweetbay","Magnolia virginiana","Bigleaf magnolia","Magnolia macrophylla","Mountain or fraser magnolia","Magnolia fraseri","Pyramid magnolia","Magnolia pyramidata","Umbrella magnolia","Magnolia tripetala","Apple spp.","Malus spp.","Oregon crab apple","Malus fusca","Southern crab apple","Malus angustifolia","Sweet crab apple","Malus coronaria","Prairie crab apple","Malus ioensis","Mulberry spp.","Morus spp.","White mulberry","Morus alba","Red mulberry","Morus rubra","Texas mulberry","Morus microphylla","Black mulberry","Morus nigra","Tupelo spp.","Nyssa spp.","Water tupelo","Nyssa aquatica","Ogeechee tupelo","Nyssa ogeche","Blackgum","Nyssa sylvatica","Swamp tupelo","Nyssa biflora","Eastern hophornbeam","Ostrya virginiana","Sourwood","Oxydendrum arboreum","Paulownia empress-tree","Paulownia tomentosa","Bay spp.","Persea spp.","Redbay","Persea borbonia","Water-elm planertree","Planera aquatica","Sycamore spp.","Platanus spp.","California sycamore","Platanus racemosa","American sycamore","Platanus occidentalis","Arizona sycamore","Platanus wrightii","Cottonwood and poplar spp.","Populus spp.","Balsam poplar","Populus balsamifera","Eastern cottonwood","Populus deltoides"];
			
			var switchingToPanel = false;
			var currentPanelID = "site";
			function continueToPanel(secondPanelID){
				//once a user is logged in, this function allows them to switch between "site", "arthropod", and "plant" panels.
				if(!switchingToPanel){
					if(secondPanelID != currentPanelID){
						switchingToPanel = true;
						$("#clearInteractionBlock")[0].style.display = "block";
						if(secondPanelID == "arthropod"){
							//check site
							//check client side
							var errors = "";
							if($("#plantCode")[0].value.length == 0 || !/^[a-z]+$/i.test($("#plantCode")[0].value)){
								errors += "Invalid survey location code. ";
							}
							if($("#sitePasswordGroup")[0].style.display != "none" && ($("#sitePassword")[0].value.length < 4 || $("#sitePassword")[0].value.indexOf(" ") > -1)){
								errors += "Invalid site password. ";
							}
							if($("#site .dualOptionButton").eq(0)[0].style.backgroundColor == ""){
								errors += "Select an observation method. ";
							}
							$("#time")[0].value = getFormattedTime($("#time")[0].value, false);
							if($("#time")[0].value == ""){
								errors += "Enter a valid time (for example: 1:00 pm). ";
							}
							$("#date")[0].value = getFormattedDate($("#date")[0].value);
							if($("#date")[0].value == ""){
								errors += "Enter a valid date. ";
							}
						
							if(errors.length > 0){
								switchingToPanel = false;
								queueNotice("error", errors);
								$("#clearInteractionBlock")[0].style.display = "none";
							}
							else if(haveInternet()){
								//check server side
								setLoadingButton($("#continueToArthropodButton")[0], "Continue", true);
								$.get("https://caterpillarscount.unc.edu/php/verifySitePassword.php?email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt") + "&code=" + encodeURIComponent($("#plantCode")[0].value) + "&password=" + encodeURIComponent($("#sitePassword")[0].value), function(data){
									//success
									if(data.indexOf("true") == 0){
										$("#" + currentPanelID + "Icon")[0].className = "accessible panelIcon";
										$("#" + secondPanelID + "Icon")[0].className = "active panelIcon";
							
										scrollToElement($('#iconBar'));
									
										var firstPanel = $("#" + currentPanelID)[0];
										var secondPanel = $("#" + secondPanelID)[0];
										$(firstPanel).stop().fadeOut(300, function(){
											$("#clearInteractionBlock")[0].style.display = "none";
											$(secondPanel).stop().fadeIn(300, function(){
												currentPanelID = secondPanelID;
												switchingToPanel = false;
											});
										});
									}
									else{
										switchingToPanel = false;
										$("#clearInteractionBlock")[0].style.display = "none";
										var siteInformationError = data.replace("false|", "");
										queueNotice("error", siteInformationError);
										if(siteInformationError == "Your log in dissolved. Maybe you logged in on another device."){
											logOut();
										}
									}
								})
								.fail(function(){
									//error
									switchingToPanel = false;
									$("#clearInteractionBlock")[0].style.display = "none";
									queueNotice("error", "Your request did not process. You may have a weak internet connection, or our servers might be busy. Please try again.");
								})
								.always(function() {
									//complete
									setLoadingButton($("#continueToArthropodButton")[0], "Continue", false);
								});
							}
							else{
								//if no internet, settle for client side checking and proceed
								$("#" + currentPanelID + "Icon")[0].className = "accessible panelIcon";
								$("#" + secondPanelID + "Icon")[0].className = "active panelIcon";
									
								scrollToElement($('#iconBar'));
										
								var firstPanel = $("#" + currentPanelID)[0];
								var secondPanel = $("#" + secondPanelID)[0];
								$(firstPanel).stop().fadeOut(300, function(){
									$("#clearInteractionBlock")[0].style.display = "none";
									$(secondPanel).stop().fadeIn(300, function(){
										currentPanelID = secondPanelID;
										switchingToPanel = false;
									});
								});
							}
						}
						else if(secondPanelID == "plant"){
							//check arthropod
							//no need to check. just proceed
							var plantIsAccessible = $("#plantIcon")[0].className.indexOf("accessible") > -1;
							
							$("#" + currentPanelID + "Icon")[0].className = "accessible panelIcon";
							$("#" + secondPanelID + "Icon")[0].className = "active panelIcon";
							
							scrollToElement($('#iconBar'));
								
							var firstPanel = $("#" + currentPanelID)[0];
							var secondPanel = $("#" + secondPanelID)[0];
							$(firstPanel).stop().fadeOut(300, function(){
								$("#clearInteractionBlock")[0].style.display = "none";
								$(secondPanel).stop().fadeIn(300, function(){
									currentPanelID = secondPanelID;
									switchingToPanel = false;
									if(!plantIsAccessible){
										attachAutoCompleteToInput($("#plantSpecies"), plantSpeciesList);
									}
								});
							});
						}
						else{
							$("#clearInteractionBlock")[0].style.display = "none";
							switchingToPanel = false;
						}
					}
				}
			}
			function accessPanel(secondPanelID){
				//once a user is logged in, this function allows them to switch between "site", "arthropod", and "plant" panels.
				if(!switchingToPanel){
					if(secondPanelID != currentPanelID){
						switchingToPanel = true;
						$("#clearInteractionBlock")[0].style.display = "block";
						if($("#" + secondPanelID + "Icon")[0].className.indexOf("accessible") == -1){
							queueNotice("error", "The " + secondPanelID + " information section is not yet accessible. Please continue the form in order.");
							switchingToPanel = false;
							$("#clearInteractionBlock")[0].style.display = "none";
							return false;
						}
						
						if(currentPanelID == "site"){
							//check site
							//check client side
							var errors = "";
							if($("#plantCode")[0].value.length == 0 || !/^[a-z]+$/i.test($("#plantCode")[0].value)){
								errors += "Invalid survey location code. ";
							}
							if($("#sitePasswordGroup")[0].style.display != "none" && ($("#sitePassword")[0].value.length < 4 || $("#sitePassword")[0].value.indexOf(" ") > -1)){
								errors += "Invalid site password. ";
							}
							if($("#site .dualOptionButton").eq(0)[0].style.backgroundColor == ""){
								errors += "Select an observation method. ";
							}
							$("#time")[0].value = getFormattedTime($("#time")[0].value, false);
							if($("#time")[0].value == ""){
								errors += "Enter a valid time (for example: 1:00 pm). ";
							}
							$("#date")[0].value = getFormattedDate($("#date")[0].value);
							if($("#date")[0].value == ""){
								errors += "Enter a valid date. ";
							}
						
							if(errors.length > 0){
								switchingToPanel = false;
								queueNotice("error", errors);
								$("#clearInteractionBlock")[0].style.display = "none";
							}
							else if(haveInternet()){
								//check server side
								$.get("https://caterpillarscount.unc.edu/php/verifySitePassword.php?email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt") + "&code=" + encodeURIComponent($("#plantCode")[0].value) + "&password=" + encodeURIComponent($("#sitePassword")[0].value), function(data){
									//success
									if(data.indexOf("true") == 0){
										$("#" + currentPanelID + "Icon")[0].className = "accessible panelIcon";
										$("#" + secondPanelID + "Icon")[0].className = "active panelIcon";
							
										scrollToElement($('#iconBar'));
										
										var firstPanel = $("#" + currentPanelID)[0];
										var secondPanel = $("#" + secondPanelID)[0];
										$(firstPanel).stop().fadeOut(300, function(){
											$("#clearInteractionBlock")[0].style.display = "none";
											$(secondPanel).stop().fadeIn(300, function(){
												currentPanelID = secondPanelID;
												switchingToPanel = false;
											});
										});
									}
									else{
										switchingToPanel = false;
										$("#clearInteractionBlock")[0].style.display = "none";
										var siteInformationError = data.replace("false|", "");
										queueNotice("error", siteInformationError);
										if(siteInformationError == "Your log in dissolved. Maybe you logged in on another device."){
											logOut();
										}
									}
								})
								.fail(function(){
									//error
									switchingToPanel = false;
									$("#clearInteractionBlock")[0].style.display = "none";
									queueNotice("error", "Your request did not process. You may have a weak internet connection, or our servers might be busy. Please try again.");
								});
							}
							else{
								//if no internet, settle for client side checking and proceed
								$("#" + currentPanelID + "Icon")[0].className = "accessible panelIcon";
								$("#" + secondPanelID + "Icon")[0].className = "active panelIcon";
									
								scrollToElement($('#iconBar'));
										
								var firstPanel = $("#" + currentPanelID)[0];
								var secondPanel = $("#" + secondPanelID)[0];
								$(firstPanel).stop().fadeOut(300, function(){
									$("#clearInteractionBlock")[0].style.display = "none";
									$(secondPanel).stop().fadeIn(300, function(){
										currentPanelID = secondPanelID;
										switchingToPanel = false;
									});
								});
							}
						}
						else{
							//no checking required if theres no chance you changed site info panel
							$("#" + currentPanelID + "Icon")[0].className = "accessible panelIcon";
							$("#" + secondPanelID + "Icon")[0].className = "active panelIcon";
				
							scrollToElement($('#iconBar'));
						
							var firstPanel = $("#" + currentPanelID)[0];
							var secondPanel = $("#" + secondPanelID)[0];
							$(firstPanel).stop().fadeOut(300, function(){
								$("#clearInteractionBlock")[0].style.display = "none";
								$(secondPanel).stop().fadeIn(300, function(){
									currentPanelID = secondPanelID;
									switchingToPanel = false;
								});
							});
						}
					}
				}
			}
			function accessPanelBlindly(secondPanelID){
				//once a user is logged in, this function allows them to switch between "site", "arthropod", and "plant" panels.
				if(!switchingToPanel){
					if(secondPanelID != currentPanelID){
						switchingToPanel = true;
						scrollToElement($('#iconBar'));
						
						var firstPanel = $("#" + currentPanelID)[0];
						var secondPanel = $("#" + secondPanelID)[0];
						
						$(firstPanel).stop().fadeOut(300, function(){
							$(secondPanel).stop().fadeIn(300, function(){
								currentPanelID = secondPanelID;
								switchingToPanel = false;
							});
						});
					}
				}
			}
			
			
			function toggleMaxHeight(elements){
				//switch the max height of all elements specified as "elements" between 0px and 250px
				elements = $(elements);
				for(var i = 0; i < elements.length; i++){
					if(elements.eq(i)[0].style.maxHeight != "250px"){
						elements.eq(i).stop().animate({maxHeight:"250px"});
					}
					else{
						elements.eq(i).stop().animate({maxHeight:"0px"});
					}
				}
			}
			
			function getYPosition(element) {
				element = $(element)[0];
    				var yPosition = 0;
    				while(element) {
        				yPosition += (element.offsetTop - element.scrollTop + element.clientTop);
        				element = element.offsetParent;
    				}
				return yPosition;
			}
			
			var selectToggling = false;
			function autoOpenSelect(selectElement){
				if(!selectToggling){
					selectToggling = true;
					if(getSelectValue(selectElement) == ""){
						if($(selectElement)[0].className.indexOf("active") > -1){
							var activeClassName = $(selectElement)[0].className.substring($(selectElement)[0].className.indexOf("active"));
							if(activeClassName.indexOf(" ") > -1){
								activeClassName = activeClassName.substring(0, activeClassName.indexOf(" "));
							}
							$(selectElement)[0].className = $(selectElement)[0].className.replace(activeClassName, "").trim();
						}
						$(selectElement)[0].className = $(selectElement)[0].className + " active" + (getYPosition(selectElement) - 100);
						elements = $(selectElement).find(".option").animate({maxHeight:"250px"}, "swing", function(){selectToggling = false;});
					}
				}
			}
			
			function selectOption(optionElement){
				if(!selectToggling){
					selectToggling = true;
					//select an option in a custiom .select or open the custom .select
					if(optionElement.parentNode.className.indexOf("active") > -1){
						var preScrollTop = Number(optionElement.parentNode.className.replace(/\D/g, ""));
						optionElement.parentNode.className = optionElement.parentNode.className.replace("active" + preScrollTop, "").trim();
						$('html, body').animate({ scrollTop: preScrollTop }, 400);
					}
					else{
						optionElement.parentNode.className = optionElement.parentNode.className + " active" + $(document).scrollTop();
					}
					
					toggleMaxHeight($(optionElement.parentNode).find(".option"));
					var selectedElement = $(optionElement.parentNode).find(".selected").eq(0)[0];
					selectedElement.className = selectedElement.className.replace("selected", "").trim();
					optionElement.className = optionElement.className + " selected";
					$(optionElement).stop().animate({maxHeight:"250px"}, "swing", function(){selectToggling = false});
				}
			}
			
			function setSelectValue(selectElement, val){
				//set the value of a custom .select and show the selected option
				selectElement = $(selectElement)[0];
				var options = selectElement.getElementsByClassName("option");
				for(var i = 0; i < options.length; i++){
					options[i].className = "option";
					options[i].style.maxHeight = "0px";
					options[i].style.overflow = "hidden";
					if(options[i].getElementsByClassName("value")[0].innerHTML == val){
						options[i].className = "option selected";
						options[i].style.maxHeight = "250px";
						options[i].style.overflow = "hidden";
					}
				}
			}
			
			function getSelectValue(selectElement){
				//return the value of a custom .select
				if($(selectElement).find(".selected .value").length < 1){
					return "";
				}
				return $(selectElement).find(".selected .value")[0].innerHTML;
			}
			
			function getSelectText(selectElement){
				//return the show text of a custom .select
				return $(selectElement).find(".selected .text")[0].innerHTML;
			}
			
			function getSelectTextByValue(selectElement, val){
				//given the value of a custom .select, return that values corresponding text
				selectElement = $(selectElement)[0];
				var options = selectElement.getElementsByClassName("option");
				for(var i = 0; i < options.length; i++){
					if($(options[i]).find(".value").eq(0)[0].innerHTML == val){
						return $(options[i]).find(".text").eq(0)[0].innerHTML;
					}
				}
			}
			
			function getSelectValueByText(selectElement, txt){
				//given the value of a custom .select, return that values corresponding text
				selectElement = $(selectElement)[0];
				var options = selectElement.getElementsByClassName("option");
				for(var i = 0; i < options.length; i++){
					if($(options[i]).find(".text").eq(0)[0].innerHTML == txt){
						return $(options[i]).find(".value").eq(0)[0].innerHTML;
					}
				}
			}
			
			function getSelectImageByText(selectElement, txt){
				//given the value of a custom .select, return that values corresponding text
				selectElement = $(selectElement)[0];
				var options = selectElement.getElementsByClassName("option");
				for(var i = 0; i < options.length; i++){
					if($(options[i]).find(".text").eq(0)[0].innerHTML == txt){
						bgimg = $(options[i]).find(".image").eq(0)[0].style.backgroundImage;
						return bgimg.substring(bgimg.indexOf("(") + 1, bgimg.lastIndexOf(")")).replace(/"/g, "").replace(/'/g, "");
						//TODO: remove this line. "
					}
				}
			}

			function getWordAtIndex(str, index){
				var left = str.slice(0, index + 1).search(/\S+$/);
        		var right = str.slice(index).search(/\s/);
				if (right < 0) {return str.slice(left);}
				return str.slice(left, right + index);
    		}
			function attachAutoCompleteToInput(inputElement, sourceList){
				$(inputElement).autocomplete({
					minLength: 2,
					source: function(request, response) {
						var results = $.ui.autocomplete.filter(sourceList, request.term);
						results.sort(function(a, b){
							a = a.toLowerCase();
							b = b.toLowerCase();
							var term = request.term.toLowerCase();
							
							//how close to the beginning of a word
							var aWord = getWordAtIndex(a, a.indexOf(term));
							var bWord = getWordAtIndex(b, b.indexOf(term));
							var diff = aWord.indexOf(term) - bWord.indexOf(term);
							if(diff !== 0) return diff;
							
							//tiebreaker prioritize first word results
							var aFirstWord = a.split(" ").indexOf(aWord) == 0;
							var bFirstWord = b.split(" ").indexOf(bWord) == 0;
							if(aFirstWord && !bFirstWord) return -1;
							else if(bFirstWord && !aFirstWord) return 1;
							
							//tiebreaker is how close to the beginning of the string
							//var diff = a.indexOf(term) - b.indexOf(term);
							//if(diff !== 0){return diff;}
							
							//tiebreaker alphabetical
							if(a < b) return -1;
    						if(a > b) return 1;
    						return 0;
						});
						response(results);
						//response(results.slice(0, 10));
					},
					open: function(event, ui) {
						var idNumber = 1;//1; instead of: ($("input").index(this) + 1); because theres only 1 autocomplete on the page, so we're always referring to the 1st one.
						$('#ui-id-' + idNumber).off('menufocus hover mouseover mouseenter');
						
            					var left = $('#ui-id-' + idNumber).position().left;
						var leftOffset = 0;
        					$('#ui-id-' + idNumber).css({left: (left - leftOffset) + "px", width: this.clientWidth + "px"});
					}
				});
			}
			
			function incrementCountInput(inputElement, min, max, step){
				$(inputElement)[0].value = Math.max(Number($(inputElement)[0].value) + step, min);
				enforceRange(inputElement, min, max, step);
			}
			
			function decrementCountInput(inputElement, min, max, step){
				$(inputElement)[0].value = Number($(inputElement)[0].value) - step;
				enforceRange(inputElement, min, max, step);
			}
			
			function enforceRange(ele, min, max, step){
				ele = $(ele)[0];
				if(ele.value == ""){
					return false;
				}
				else if(ele.value < min){
					ele.value = min;
				}
				ele.value = (Math.round(Number(ele.value) / step) * step);
				if(min != null && Number(ele.value) < min){
					ele.value='';
				}
				else if(max != null && Number(ele.value) > max){
					ele.value=max;
					//queueNotice("alert", "Value must be between " + min + " and " + max + ".");
				}
			}
			
			function countTo(inputElement, max){
				inputElement = $(inputElement)[0];
				inputElement.value = inputElement.value.substring(0, max);
				$(inputElement.parentNode.parentNode).find('.characterCount').eq(0)[0].innerHTML = inputElement.value.length + "/" + max;
			}
			
			function clearCountInput(inputElement){
				inputElement = $(inputElement)[0];
				inputElement.value = "";
				var charCountDiv = $(inputElement.parentNode.parentNode).find(".characterCount").eq(0)[0];
				charCountDiv.innerHTML = "0" + charCountDiv.innerHTML.substring(charCountDiv.innerHTML.indexOf("/"));
			}
			
			var observationMethod = "";
			var mostUpToDatePlantReturnNumber = 0;
			function getPlant(codeInput, passwordGroup){
				codeInput = $(codeInput)[0];
				codeInput.value = codeInput.value.toUpperCase().replace(/ /g, "").replace(/[^A-Z]/g, "");
				$("#plant input").eq(0)[0].value = "";
				$("#plant input").eq(0)[0].readOnly = false;
				
				if(!haveInternet()){
					codeInput.parentNode.style.color = "";
					codeInput.parentNode.style.borderRadius = "";
					codeInput.parentNode.style.background = "";
					codeInput.parentNode.style.padding = "";
					codeInput.parentNode.style.marginTop = "";
					$(codeInput.parentNode).find("div").eq(0)[0].innerHTML = "";
					return false;
				}
				
				var thisPlantReturnNumber = ++mostUpToDatePlantReturnNumber;
				
				setTimeout(function(){
					if(thisPlantReturnNumber == mostUpToDatePlantReturnNumber) {
						code = codeInput.value;
						$.get("https://caterpillarscount.unc.edu/php/getPlantByCode.php?code=" + encodeURIComponent(code) + "&email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt"), function(data){
							//success
							if(thisPlantReturnNumber == mostUpToDatePlantReturnNumber){
								if(data.indexOf("true|") == 0){
									var plantArray = JSON.parse(data.replace("true|", ""));
									var color = plantArray["color"];
									var siteName = plantArray["siteName"];
									var species = plantArray["species"];
									var circle = plantArray["circle"];
									var validated = plantArray["validated"];
									observationMethod = plantArray["observationMethod"];//refers to global var
									
									codeInput.parentNode.style.color = "#fff";
									codeInput.parentNode.style.borderRadius = "4px";
									codeInput.parentNode.style.background = color;
									codeInput.parentNode.style.padding = "10px 10px 0px 10px";
									codeInput.parentNode.style.marginTop = "10px";
									$(codeInput.parentNode).find("div").eq(0)[0].innerHTML = siteName + ", Circle " + circle + ", " + species;
									
									if(validated){
										$(passwordGroup).stop().hide(300);
									}
									else{
										$(passwordGroup).stop().show(300);
									}
									
									if($(codeInput)[0] == $("#plantCode")[0]){
										if(observationMethod == "Visual"){
											selectDualOptionButton($("#visualDualOptionButton")[0]);
											forceFiftyLeaves();
										}
										else if(observationMethod == "Beat sheet"){
											selectDualOptionButton($("#beatSheetDualOptionButton")[0]);
											relaxFiftyLeaves();
										}
										
										if(species == "N/A"){
											var speciesInput = $("#plant input").eq(0)[0];
											
											//$("#plant .group").eq(0).show();
											speciesInput.value = "";
											speciesInput.readOnly = false;
										}
										else{
											var speciesInput = $("#plant input").eq(0)[0];
												
											//$("#plant .group").eq(0).hide();
											speciesInput.value = species;
											speciesInput.readOnly = true;
										}
									}
								}
								else{
									codeInput.parentNode.style.color = "";
									codeInput.parentNode.style.borderRadius = "";
									codeInput.parentNode.style.background = "";
									codeInput.parentNode.style.padding = "";
									codeInput.parentNode.style.marginTop = "";
									$(codeInput.parentNode).find("div").eq(0)[0].innerHTML = "";
									$(passwordGroup).stop().show(300);
									if(data != "no plant" && $(codeInput)[0] == $("#plantCode")[0]){
										var plantError = data.replace("false|", "");
										queueNotice("error", plantError);
										if(plantError == "Your log in dissolved. Maybe you logged in on another device."){
											$("input").blur();
											$("textarea").blur();
											logOut();
										}
									}
								}
							}
						});
					}
				}, 300);
			}
			
			var compressedBase64 = {};
			function setCompressedBase64(key, fullBase64OrURI, looseBaseDimension, percentQuality){
				var canvas = document.createElement('canvas');
				var ctx = canvas.getContext("2d");
				ctx.clearRect(0, 0, canvas.width, canvas.height);
		
				var image = new Image();
				image.onload = function() {
					var ratio = image.width / image.height;
					while(image.width * image.height > (looseBaseDimension * looseBaseDimension)){
						image.height = image.height - 1;
						image.width = image.height * ratio;
					}
					canvas.width = image.width;
					canvas.height = image.height;
					ctx.drawImage(image, 0, 0, image.width, image.height);
					compressedBase64[key] = canvas.toDataURL("image/jpeg", percentQuality/100);
				};
				image.src = fullBase64OrURI;
			}
    
			function getCompressedBase64(key){
				if(typeof compressedBase64[key] != 'undefined'){
					var tmp = compressedBase64[key];
					delete compressedBase64[key];
					return tmp;
				}
			}
			
			function compressBase64Index(arr, i, looseBaseDimesion, quality, convertToBlob, source){
				source = source || arr[i];
				var key = (Math.random().toString() + Math.random().toString() + Math.random().toString());
				setCompressedBase64(key, source, looseBaseDimesion, quality);
				var compressedCheck = setInterval(function(){
					var localCompressedBase64 = getCompressedBase64(key);
					if(localCompressedBase64 !== null){
						var mimeType = localCompressedBase64.substring((localCompressedBase64.indexOf(":") + 1), localCompressedBase64.indexOf(";"));
						var strippedCompressedBase64 = localCompressedBase64.substring(localCompressedBase64.indexOf(",") + 1);
						if(convertToBlob){
							arr[i] = b64toBlob(strippedCompressedBase64, mimeType);
						}
						else{
							arr[i] = [mimeType, strippedCompressedBase64];
						}
						clearInterval(compressedCheck);
					}
				}, 100);
			}
			
			var arthropodData = [];
			var finishing = false;
			var lastPass = "";
			function finish(){
				if(finishing){return false;}
				var plantCode = $("#plantCode")[0].value.trim();
				var sitePassword = $("#sitePassword")[0].value.trim();
				var dateAndTime = formatDateAndTimeForDatabase($("#date")[0].value, $("#time")[0].value, false);
				var siteNotes = $("#siteNotes")[0].value.trim();
				var wetLeaves = checkboxIsChecked($("#wetLeavesCheckbox")).toString();
				//arthropodData: [[orderType, orderLength, orderQuantity, orderNotes, hairy, leafRoll, silkTent, fileInput]]
				var plantSpecies = $("#plantSpecies")[0].value.trim();
				var numberOfLeaves = $("#numberOfLeaves")[0].value.trim();
				var averageLeafLength = $("#averageLeafLength")[0].value.trim();
				var herbivoryScore = getSelectValue($("#herbivoryScore"));
					
				//front end checking of plant vals
				var errors = "";
				/*COMMENTED OUT TO ALLOW CUSTOM INPUT
				if(plantSpeciesList.indexOf(plantSpecies) == -1){
					errors += "Enter an approved plant species. ";
				}
				*/
				if(numberOfLeaves.length != numberOfLeaves.replace(/\D/g, "").length || numberOfLeaves.length == 0 || Number(numberOfLeaves) > 500 || Number(numberOfLeaves) < 1){
					errors += "Enter a number of leaves between 1 and 500. ";
				}
				if(averageLeafLength.length != averageLeafLength.replace(/\D/g, "").length || averageLeafLength.length == 0 || Number(averageLeafLength) > 60 || Number(averageLeafLength) < 1){
					errors += "Enter an average leaf length between 1 and 60 centimeters. ";
				}
				if(herbivoryScore == ""){
					errors += "Select an herbivory score. ";
				}
					
				if(errors.length > 0){
					queueNotice("error", errors);
					return false;
				}
					
				if(!haveInternet()){
					finishing = true;
					$("#clearInteractionBlock")[0].style.display = "block";
					setLoadingButton($("#finishButton"), "Finish", true);
				
					//compress base 64 image strings
					var arthropodDataCopy = [];
					for(var i  = 0; i < arthropodData.length; i++){
						arthropodDataCopy[i] = arthropodData[i].slice();
						if(arthropodDataCopy[i][9].length > 0){
							compressBase64Index(arthropodDataCopy[i], 9, 500, 24, false);
						}
					}
		//alert("copied data");
					//save in local storage when ready
					var savedCheck = setInterval(function(){
						var allImageDataLoaded = true;
						for(var i  = 0; i < arthropodDataCopy.length; i++){
		//alert("arthropodDataCopy[i][9] != '': " + (arthropodDataCopy[i][9] != "").toString());
		//alert("arthropodDataCopy[i][9].constructor !== Array: " + (arthropodDataCopy[i][9].constructor !== Array).toString());
		//alert("image" + i + " still processing: " + (arthropodDataCopy[i][9] != "" && arthropodDataCopy[i][9].constructor !== Array).toString());
							if(arthropodDataCopy[i][9] != "" && arthropodDataCopy[i][9].constructor !== Array){
								allImageDataLoaded = false;
								break;
							}
						}
						if(allImageDataLoaded){
							clearInterval(savedCheck);
		//alert("1");
							var existingPendingSurveys = window.localStorage.getItem("pendingSurveys");
		//alert("2");
							if(!existingPendingSurveys){existingPendingSurveys = [];}
							else{existingPendingSurveys = JSON.parse(existingPendingSurveys);}
		//alert("3");
							existingPendingSurveys[existingPendingSurveys.length] = [plantCode, sitePassword, dateAndTime, observationMethod, siteNotes, wetLeaves, arthropodDataCopy, plantSpecies, numberOfLeaves, averageLeafLength, herbivoryScore, window.localStorage.getItem("email")];
		//alert("4");
		//alert(JSON.stringify(existingPendingSurveys));
		
							var savedToOfflineSurveyBox = true;
							try{
								//submit normally
								window.localStorage.setItem("pendingSurveys", JSON.stringify(existingPendingSurveys));
							}
							catch(e){
								var pendingSurveyEmails = [];
								for(var i = 0; i < existingPendingSurveys.length; i++){
									if(pendingSurveyEmails.indexOf(existingPendingSurveys[11]) == -1){
										pendingSurveyEmails[pendingSurveyEmails.length] = existingPendingSurveys[11];
									}
								}
								pendingSurveyEmails = pendingSurveyEmails.join(", ");
								var lastDelimiter = ", and";
								if(pendingSurveyEmails.lastIndexOf(", ") == pendingSurveyEmails.indexOf(", ")){
									lastDelimiter = " and";
								}
								pendingSurveyEmails = pendingSurveyEmails.substring(0, pendingSurveyEmails.lastIndexOf(", ")) + lastDelimiter + pendingSurveyEmails.substring(pendingSurveyEmails.lastIndexOf(", ") + 2);
								try{
									//submit without photos
									var arthropodSightings = existingPendingSurveys[existingPendingSurveys.length][6];
									for(var i = 0; i < arthropodSightings.length; i++){
										arthropodSightings[9] = "";
									}
									window.localStorage.setItem("pendingSurveys", JSON.stringify(existingPendingSurveys));
									queueNotice("alert", "Wow, you've sure submitted a lot of data offline! We just can't quite fit it all in your offline survey box! We had to throw out your arthropod photos for this survey to make everything fit, but don't worry- everything else on this survey is safe. We automatically clear your box out every time you log in with an internet connection, so it's a good idea to do that regularly. You should use this device to log in to " + pendingSurveyEmails + " WITH AN INTERNET CONNECTION for at least one minute soon so we can automatically clear your box out behind the scenes for you. Otherwise, this will happen again.");
								}
								catch(e){
									//cant submit anything
									savedToOfflineSurveyBox = false;
									queueNotice("error", "Oh no! Your offline survey box has gotten so full that you can't submit even one more offline survey! We automatically clear your box out every time you log in with an internet connection, so it's a good idea to do that regularly. You need to use this device to log in to " + pendingSurveyEmails + " WITH AN INTERNET CONNECTION for at least one minute soon so we can automatically clear your box out behind the scenes for you. Otherwise, this will happen again.");
								}
							}
		//alert("5");
		//alert(JSON.stringify(existingPendingSurveys));
		//alert("6");
							if(savedToOfflineSurveyBox){
								restart();
								queueNotice("confirmation", "Thanks for saving a survey in offline mode! We'll get this data once you open this app and sign in with an internet connection. Keep in mind that if you update to a newer version of this app before doing so, this data will be deleted. So please sign in with an internet connection soon!");
							}
					
							setLoadingButton($("#finishButton"), "Finish", false);
							$("#clearInteractionBlock")[0].style.display = "none";
							finishing = false;
						}
					}, 100);
				}
				else{
					finishing = true;
					$("#clearInteractionBlock")[0].style.display = "block";
					setLoadingButton($("#finishButton"), "Finish", true);
					
					var formData = new FormData();
					var arthropodBlobs = [];
					for(var i = 0; i < arthropodData.length; i++){
						if(arthropodData[i][9].length > 0){
							compressBase64Index(arthropodBlobs, i, 1750, 70, true, arthropodData[i][9]);
						}
						else{
							arthropodBlobs[i] = null;
						}
					}
					
					//check arthropod blobs for completeness and loop:
					var savedCheck = setInterval(function(){
						var allImageDataLoaded = true;
						for(var i  = 0; i < arthropodData.length; i++){
							if(typeof arthropodBlobs[i] == 'undefined'){
								allImageDataLoaded = false;
								break;
							}
						}
						if(allImageDataLoaded){
							clearInterval(savedCheck);
							for(var i  = 0; i < arthropodData.length; i++){
								if(arthropodBlobs[i] !== null){
									formData.append(('file' + i), arthropodBlobs[i]);
								}
							}
							
							var temporaryArthropodData = [];
							for(var i = 0; i < arthropodData.length; i++){
								temporaryArthropodData[i] = arthropodData[i].slice();
								temporaryArthropodData[i].splice(9, 1);
							}
							
							formData.append("plantCode", plantCode);
							formData.append("sitePassword", sitePassword);
							formData.append("date", dateAndTime[0]);
							formData.append("time", dateAndTime[1]);
							formData.append("siteNotes", siteNotes);
							formData.append("wetLeaves", wetLeaves);
							formData.append("arthropodData", JSON.stringify(temporaryArthropodData));
	//alert(JSON.stringify(temporaryArthropodData));
							formData.append("numberOfLeaves", numberOfLeaves);
							formData.append("averageLeafLength", averageLeafLength);
							formData.append("herbivoryScore", herbivoryScore);
							formData.append("observationMethod", observationMethod);
							formData.append("plantSpecies", plantSpecies);
							formData.append("submittedThroughApp", false);
							formData.append("email", window.localStorage.getItem("email"));
							formData.append("salt", window.localStorage.getItem("salt"));
												
							$.ajax({
								url : "https://caterpillarscount.unc.edu/php/submit.php",
								type : 'POST',
								data : formData,
								processData: false,  // tell jQuery not to process the data
								contentType: false,  // tell jQuery not to set contentType
								success : function(data) {
									if(data.indexOf("true|") == 0){
										queueNotice("confirmation", "Submitted!");
										restart();
									}
									else{
										var submissionError = data.replace("false|", "");
										queueNotice("error", submissionError);
										if(submissionError == "Your log in dissolved. Maybe you logged in on another device."){
											$("input").blur();
											$("textarea").blur();
											logOut();
										}
									}
								},
								complete: function() {
									setLoadingButton($("#finishButton"), "Finish", false);
									$("#clearInteractionBlock")[0].style.display = "none";
									finishing = false;
								}
							});
						}
					}, 100);
				}
			}
			
			function toggleSamePass(){
				if($("#samePass .checkBox").eq(0)[0].className.indexOf("checked") > -1){
					uncheckCheckbox($("#samePass .checkBox").eq(0));
					$("#sitePassword")[0].value = "";
					$("#sitePassword")[0].focus();
				}
				else{
					checkCheckbox($("#samePass .checkBox").eq(0));
					$("#sitePassword")[0].value = lastPass;
				}
			}
			
			function rewordMoreArthropodsQuestion(){
				if(arthropodData.length == 0){
					$("#addAnArthropodButtonGroup").stop().show(300);//[0].style.display = "block";
					$("#addAnotherArthropodButton").stop().hide(300);//[0].style.display = "none";
				}
				else{
					$("#addAnotherArthropodButton").stop().show(300);//[0].style.display = "block";
					$("#addAnArthropodButtonGroup").stop().hide(300);//[0].style.display = "none";
				}
			}
			
			var currentlyShownIndex = -1;
			function signalNewData(){
				currentlyShownIndex = arthropodData.length;
			}
			
			function signalOldData(i){
				currentlyShownIndex = i;
			}
			
			function signalNoData(){
				currentlyShownIndex = -1;
			}
            
			function saveArthropodData(){
				if(currentlyShownIndex > -1){
					//verify data before saving
					var orderType = getSelectValue($("#orderType"));
					var orderLength = $("#orderLength")[0].value;
					var orderQuantity = $("#orderQuantity")[0].value;
					var orderNotes = $("#orderNotes")[0].value;
					
					var errors = "";
					if(orderType == ""){errors += "Select an arthropod group. ";}
					if(orderLength == "" || isNaN(orderLength) || Number(orderLength) < 1 || Number(orderLength) > 300){errors += "Enter a length between 1mm and 300mm. ";}
					if(orderQuantity == "" || isNaN(orderQuantity) || Number(orderQuantity) < 1 || Number(orderQuantity) > 1000){errors += "Enter a quantity between 1 and 1000. ";}
					if((orderType == "unidentified" || orderType == "other") && orderNotes == ""){errors += "Notes are required for \"" + orderType + "\" arthropod groups, and photos are requested but optional.";}
					
					if(errors.length > 0){
						queueNotice("error", errors);
						return false;
					}
					
					//save data
					var hairy = checkboxIsChecked($("#hairyCheckbox"));
					var leafRoll = checkboxIsChecked($("#leafRollCheckbox"));
					var silkTent = checkboxIsChecked($("#silkTentCheckbox"));
					var sawfly = checkboxIsChecked($("#sawflyCheckbox"));
					var beetleLarva = checkboxIsChecked($("#beetleLarvaCheckbox"));
					var base64 = "";
					if($("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage.indexOf("#") == -1){
						base64 = $("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage.replace("url(", "").replace(/'/g, "").replace(/"/g, "").replace(")", "");
					}
					arthropodData[currentlyShownIndex] = [orderType, orderLength, orderQuantity, orderNotes, hairy, leafRoll, silkTent, sawfly, beetleLarva, base64];
					updateArthropodCards();
					return true;
				}
			}
            
			function deleteArthropodData(i){
				arthropodData[i][9].outerHTML = "";
				arthropodData.splice(i, 1);
				updateArthropodCards();
				
				if(i == currentlyShownIndex){
					hideArthropodFormDiv();
				}
				else if(currentlyShownIndex != -1){
					currentlyShownIndex--;
				}
			}
            
			function showArthropodFormDiv(){
				$("#moreArthropods").stop().hide(300);
				$("#arthropodFormDiv").stop().fadeIn(300, function(){
					if(currentlyShownIndex == arthropodData.length){
						autoOpenSelect($('#orderType'));
					}
				});
			}
            
			function hideArthropodFormDiv(){
				//clear input values
				setSelectValue($("#orderType")[0], "");
				uncheckCheckbox($("#hairyCheckbox"));
				uncheckCheckbox($("#leafRollCheckbox"));
				uncheckCheckbox($("#silkTentCheckbox"));
				uncheckCheckbox($("#sawflyCheckbox"));
				uncheckCheckbox($("#beetleLarvaCheckbox"));
				$("#caterpillarOptionsGroup")[0].style.display = "none";
				$("#beeOptionsGroup")[0].style.display = "none";
				$("#beetleOptionsGroup")[0].style.display = "none";
				$("#orderLength")[0].value = $("#orderLength")[0].defaultValue;
				$("#orderQuantity")[0].value = $("#orderQuantity")[0].defaultValue;
				$("#orderNotes")[0].value = "";
				$("#fileInputRemoveLink")[0].style.display = "none";
				$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage = "url('#')";
				$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.height = "0px";
				$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.margin = "";
				$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.padding = "";
				$("#arthropodFileInputHolder .snapIcon").eq(0)[0].src = "../images/camera.png";
				
				
				selectDualOptionButton($("#moreArthropods .dualOptionButton").eq(1));
				$("#moreArthropods").stop().show(300);
				$("#arthropodFormDiv").stop().fadeOut(300);
				
				signalNoData();
			}
			
			function tryToPopulateArthropodGroup(i){
				//continue without saving? prompt
				//orderType, orderLength, orderQuantity, orderNotes, hairy, leafRoll, silkTent, fileInput
				var oldDataIsShown = (currentlyShownIndex > -1 && currentlyShownIndex < arthropodData.length);
				var oldDataHasChanged = false;
				if(oldDataIsShown){
					oldDataHasChanged = (getSelectValue($("#orderType")[0]) != arthropodData[currentlyShownIndex][0] || 
					$("#orderLength")[0].value != arthropodData[currentlyShownIndex][1] || 
					$("#orderQuantity")[0].value != arthropodData[currentlyShownIndex][2] || 
					$("#orderNotes")[0].value != arthropodData[currentlyShownIndex][3] || 
					checkboxIsChecked($("#hairyCheckbox")[0]) != arthropodData[currentlyShownIndex][4] || 
					checkboxIsChecked($("#leafRollCheckbox")[0]) != arthropodData[currentlyShownIndex][5] || 
					checkboxIsChecked($("#silkTentCheckbox")[0]) != arthropodData[currentlyShownIndex][6] ||
					checkboxIsChecked($("#sawflyCheckbox")[0]) != arthropodData[currentlyShownIndex][7] ||
					checkboxIsChecked($("#beetleLarvaCheckbox")[0]) != arthropodData[currentlyShownIndex][8] ||
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage.indexOf(arthropodData[currentlyShownIndex][9]) == -1);
				}
				
				var newDataIsShown = (currentlyShownIndex == arthropodData.length);
				var newDataHasChanged = false;
				if(newDataIsShown){
					newDataHasChanged = (getSelectValue($("#orderType")[0]) != "" || 
					$("#orderLength")[0].value != $("#orderLength")[0].defaultValue || 
					$("#orderQuantity")[0].value != $("#orderQuantity")[0].defaultValue || 
					$("#orderNotes")[0].value != "" || 
					checkboxIsChecked($("#hairyCheckbox")[0]) || 
					checkboxIsChecked($("#leafRollCheckbox")[0]) || 
					checkboxIsChecked($("#silkTentCheckbox")[0]) ||
					checkboxIsChecked($("#sawflyCheckbox")[0]) ||
					checkboxIsChecked($("#beetleLarvaCheckbox")[0]) ||
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage.indexOf("#") == -1);
				}
				
				if((oldDataIsShown && oldDataHasChanged) || (newDataIsShown && newDataHasChanged)){
					promptConfirm("Your changes to the current arthropod group will not be saved if you view another group.", "cancel", "proceed", function(){}, function(){populateArthropodGroup(i);signalOldData(i);showArthropodFormDiv();});
				}
				else{
					populateArthropodGroup(i);
					signalOldData(i);
					showArthropodFormDiv();
				}
			}
			
			function populateArthropodGroup(i){
				setSelectValue($("#orderType"), arthropodData[i][0]);
				
				if(arthropodData[i][4]){checkCheckbox($("#hairyCheckbox"));}
				else{uncheckCheckbox($("#hairyCheckbox"));}
				
				if(arthropodData[i][5]){checkCheckbox($("#leafRollCheckbox"));}
				else{uncheckCheckbox($("#leafRollCheckbox"));}
				
				if(arthropodData[i][6]){checkCheckbox($("#silkTentCheckbox"));}
				else{uncheckCheckbox($("#silkTentCheckbox"));}
				
				if(arthropodData[i][6]){checkCheckbox($("#sawflyCheckbox"));}
				else{uncheckCheckbox($("#sawflyCheckbox"));}
				
				if(arthropodData[i][6]){checkCheckbox($("#beetleLarvaCheckbox"));}
				else{uncheckCheckbox($("#beetleLarvaCheckbox"));}
				
				if(arthropodData[i][0] == "caterpillar"){$("#caterpillarOptionsGroup")[0].style.display = "block";}
				else{$("#caterpillarOptionsGroup")[0].style.display = "none";}
				
				if(arthropodData[i][0] == "bee"){$("#beeOptionsGroup")[0].style.display = "block";}
				else{$("#beeOptionsGroup")[0].style.display = "none";}
				
				if(arthropodData[i][0] == "beetle"){$("#beetleOptionsGroup")[0].style.display = "block";}
				else{$("#beetleOptionsGroup")[0].style.display = "none";}
				
				$("#orderLength")[0].value = arthropodData[i][1];
				$("#orderQuantity")[0].value = arthropodData[i][2];
				
				if(arthropodData[i][9] != ""){
					$("#arthropodFileInputHolder .snapIcon").eq(0)[0].src = "../images/inputCheckIcon.png";
					showUploadedImage(arthropodData[i][9]);
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.height = "80px";
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.margin = "-20px -20px 16px -20px";
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.padding = "0px 20px";
				}
				else{
					$("#arthropodFileInputHolder .snapIcon").eq(0)[0].src = "../images/camera.png";
					$("#fileInputRemoveLink")[0].style.display = "none";
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage = "url('#')";
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.height = "0px";
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.margin = "";
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.padding = "";
				}
				
				$("#orderNotes")[0].value = arthropodData[i][3];
			}
			
			function retroIncrementCount(i){
				if(arthropodData[i][2] < 1000){
					arthropodData[i][2]++;
					updateArthropodCards();
					if(i == currentlyShownIndex){
						$("#orderQuantity")[0].value = Number($("#orderQuantity")[0].value) + 1;
					}
				}
			}
			
			function retroDecrementCount(i){
				if(arthropodData[i][2] > 1){
					arthropodData[i][2]--;
					updateArthropodCards();
					if(i == currentlyShownIndex){
						$("#orderQuantity")[0].value = Number($("#orderQuantity")[0].value) - 1;
					}
				}
			}
            
			function updateArthropodCards(){
				//update arthropod cards
				var htmlToAdd = "";
				for(var i = 0; i < arthropodData.length; i++){
					var txt = getSelectTextByValue($("#orderType"), arthropodData[i][0]);
					var img = getSelectImageByText($("#orderType"), txt);
					var titlePrefix = "";
					if(arthropodData[i][0] == "caterpillar"){
						if(arthropodData[i][4]){
							titlePrefix += "Hairy, ";
						}
						if(arthropodData[i][5]){
							titlePrefix += "Rolled, ";
						}
						if(arthropodData[i][6]){
							titlePrefix += "Tented, ";
						}
						titlePrefix = titlePrefix.substring(0, titlePrefix.lastIndexOf(", "));
					}
					
					htmlToAdd += "<div class='orderTableHolder'>";
					htmlToAdd += 	"<div class='deleteButtonOverlay' id='deleteButtonOverlay" + i + "'>";
					htmlToAdd += 		"<div onclick=\"hideDeleteArthropodData('#deleteButtonOverlay" + i + "');\"></div>";
					htmlToAdd += 		"<div onclick=\"deleteArthropodData(" + i + ");\"></div>";
					htmlToAdd += 		"<div class='clearBoth'></div>";
					htmlToAdd += 	"</div>";
					htmlToAdd += 	"<table class=\"orderTable\">";
					htmlToAdd +=		"<tr>";
					htmlToAdd += 			"<td onclick=\"tryToPopulateArthropodGroup(" + i + ");\"><div style=\"background-image:url('" + img + "');\"></div></td>";//image
					htmlToAdd += 			"<td onclick=\"tryToPopulateArthropodGroup(" + i + ");\">";
					htmlToAdd +=				arthropodData[i][2];//count
					htmlToAdd +=				" " + titlePrefix + " " + txt;//name
					htmlToAdd += 				" (" + arthropodData[i][1] + "mm)";//length
					htmlToAdd +=			"</td>";
					htmlToAdd +=			"<td>";
					htmlToAdd +=				"<div onclick=\"retroIncrementCount(" + i + ");\"></div>";//increment
					htmlToAdd +=				"<div onclick=\"retroDecrementCount(" + i + ");\"></div>";//decrement
					htmlToAdd +=			"</td>";
					htmlToAdd +=			"<td onclick=\"showDeleteArthropodData('#deleteButtonOverlay" + i + "');\"><div style=\"opacity:0.26;background-image:url('../images/delete.png');\"></div></td>";
					htmlToAdd +=		"</tr>";
					htmlToAdd += 	"</table>";
					htmlToAdd += "</div>";
				}
				document.getElementById("arthropodCards").innerHTML = htmlToAdd;
				
				for(var i = 0; i < arthropodData.length; i++){
					if(arthropodData[i][9] != ""){
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.backgroundImage = "url('" + arthropodData[i][9] + "')";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.backgroundSize = "cover";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.padding = "5px";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.margin = "-5px -10px -5px -5px";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.borderRadius = "4px";
						if($("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.backgroundImage.length <= 22){
							var cardPhotoCheck = setInterval(function(){
								$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.backgroundImage = "url('" + arthropodData[i][9] + "')";
								if($("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.backgroundImage.length > 22){
									clearInterval(cardPhotoCheck);
								}
							}, 100);
						}
					}
					else{
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.backgroundSize = "";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.padding = "";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.margin = "";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div").eq(0)[0].style.borderRadius = "";
					}
				}
				
				rewordMoreArthropodsQuestion();
			}
			
			var beforeForce = "";
			function forceFiftyLeaves(){
				beforeForce = document.getElementById("numberOfLeaves").value;
				document.getElementById("numberOfLeaves").value = "50";
				document.getElementById("numberOfLeaves").readOnly = true;
			}
			
			function relaxFiftyLeaves(){
				document.getElementById("numberOfLeaves").value = beforeForce;
				document.getElementById("numberOfLeaves").readOnly = false;
			}
			
			function showDeleteArthropodData(deleteButtonOverlayElement){
				$(deleteButtonOverlayElement).stop().animate({maxWidth:"100%"}, 100);
			}
			
			function hideDeleteArthropodData(deleteButtonOverlayElement){
				$(deleteButtonOverlayElement).stop().animate({maxWidth:"0%"}, 100);
			}

			function showUploadedImageFromFile(file){
				$("#clearInteractionBlock")[0].style.display = "block";
			   	var reader = new FileReader();
			   	reader.readAsDataURL(file);
			   	reader.onload = function(){
					showUploadedImage(reader.result, true);
			   	};
			   	reader.onerror = function(error){
					$("#clearInteractionBlock")[0].style.display = "none";
			   		queueNotice("error", error);
			   	};
			}
			
			function showUploadedImage(base64OrURI, forceCompression){
				forceCompression = forceCompression || false;
				if(!forceCompression && (typeof base64OrURI === 'string' || base64OrURI instanceof String) && base64OrURI.indexOf("data:") == 0){
					//base64 provided
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage = "url('" + base64OrURI + "')";
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.height = "80px";
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.margin = "-20px -20px 16px -20px";
					$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.padding = "0px 20px";
					$("#fileInputRemoveLink")[0].style.display = "block";
				}
				else{
					//URI provided
					$("#clearInteractionBlock")[0].style.display = "block";
					var data = [base64OrURI];
					compressBase64Index(data, 0, 1750, 100, false);
					var compressedCheck = setInterval(function(){
						if(data != [base64OrURI]){
							$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage = "url('data:" + data[0][0] + ";base64," + data[0][1] + "')";
							if($("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage.length > 22){
								$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.height = "80px";
								$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.margin = "-20px -20px 16px -20px";
								$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.padding = "0px 20px";
								$("#fileInputRemoveLink")[0].style.display = "block";
								$("#clearInteractionBlock")[0].style.display = "none";
							}
							else{
								var backgroundImageSetCheck = setInterval(function(){
									$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage = "url('data:" + data[0][0] + ";base64," + data[0][1] + "')";
									if($("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage.length > 22){
										$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.height = "80px";
										$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.margin = "-20px -20px 16px -20px";
										$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.padding = "0px 20px";
										$("#fileInputRemoveLink")[0].style.display = "block";
										$("#clearInteractionBlock")[0].style.display = "none";
										clearInterval(backgroundImageSetCheck);
									}
								}, 100);
							}
							clearInterval(compressedCheck);
						}
					},100);
				}
			}
			
			function removeUploadedFile(){
				$("#arthropodFileInputHolder .snapIcon").eq(0)[0].src = "../images/camera.png";
				$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.backgroundImage = "url('#')";
				$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.height = "0px";
				$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.margin = "";
				$("#arthropodFileInputHolder .uploadedImage").eq(0)[0].style.padding = "";
				$("#fileInputRemoveLink")[0].style.display = "none";
			}
			
			function expandTextarea(textareaElement){
				textareaElement = $(textareaElement)[0];
				textareaElement.style.overflow = "scroll";
				$(textareaElement).stop().animate({height:'150px'});
				$(textareaElement.parentNode).find('.textareaOtherLinesCover').stop().fadeOut();
			}
			
			function compressTextarea(textareaElement){
				textareaElement = $(textareaElement)[0];
				textareaElement.style.overflow = "hidden";
				$(textareaElement).stop().animate({height:'54px', scrollTop:'0'});
				$(textareaElement.parentNode).find('.textareaOtherLinesCover').stop().fadeIn();
			}
			
			function restart(){
				lastPass = $("#sitePassword")[0].value;
				$("#sitePassword")[0].value = "";
				if($("#sitePasswordGroup")[0].style.display != "none"){
					$("#samePass")[0].style.display = "block";
				}
				$("#sitePasswordGroup").stop().show(0);
				
				$("#plantCode")[0].value = "";
				$("#plantCode")[0].parentNode.style.color = "";
				$("#plantCode")[0].parentNode.style.borderRadius = "";
				$("#plantCode")[0].parentNode.style.background = "";
				$("#plantCode")[0].parentNode.style.padding = "";
				$("#plantCode")[0].parentNode.style.marginTop = "";
				$($("#plantCode")[0].parentNode).find("div").eq(0)[0].innerHTML = "";
				
				$("#date")[0].value = "";
				$("#time")[0].value = "";
				
				$("#siteNotes")[0].value = "";
				uncheckCheckbox($("#wetLeavesCheckbox"));
				
				for(var i = (arthropodData.length - 1); i >= 0; i--){
					deleteArthropodData(i);
				}
				
				$("#plant input").val("");
				$("#plant input").eq(0)[0].readOnly = false;
				if(observationMethod == "Visual"){forceFiftyLeaves();}
				else{relaxFiftyLeaves();}
				setSelectValue($("#herbivoryScore"), "");
							
				accessPanelBlindly("site");
				//$("#plantCode")[0].focus();
				$("#siteIcon")[0].className = "active panelIcon";
				$("#arthropodIcon")[0].className = "panelIcon";
				$("#plantIcon")[0].className = "panelIcon";
			}
			
			function autosetDateAndTimeInputs(){
				$('#time')[0].value = getTime(true);
				$('#date')[0].value = getDate(true);
			}
			
			function populateSites(){
				$.get("https://caterpillarscount.unc.edu/php/getOwnedSitesLIGHT.php?email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt"), function(data){
					//success
					if(data.indexOf("true") == 0){
						var ownedSites = JSON.parse(data.replace("true|", ""));
							
						var htmlToAdd = "<div class=\"select\">";
						htmlToAdd += "<div class=\"option selected\" onclick=\"selectOption(this);\">	<div class=\"value\"></div>			<div class=\"shown\"><div class=\"image\" style=\"background-image:url('../images/selectIcons/notselected.png');\"></div>		<div class=\"text\">Not selected</div></div></div>";
						for(var i = 0; i < ownedSites.length; i++){
							htmlToAdd += "<div class=\"option\" onclick=\"selectOption(this);\">	<div class=\"value\">" + ownedSites[i]["id"] + "</div>			<div class=\"shown\"><div class=\"image\"></div>		<div class=\"text\">" + ownedSites[i]["name"] + " (" + ownedSites[i]["region"] + ")</div></div></div>";
							//htmlToAdd += "<option value=\"" + ownedSites[i]["id"] + "\">" + ownedSites[i]["name"] + " (" + ownedSites[i]["region"] + ")</option>";
						}
						htmlToAdd += "<div class=\"option\" onclick=\"showRestrictedDropDown('create a new site', $('#createSiteIntro'), false);\"><div class=\"shown\"><div class=\"image\" style=\"background-image:url('../images/plus.png');background-size:50% auto;background-position:right center;\"></div>		<div class=\"text italic\">CREATE NEW SITE</div></div></div>";
						htmlToAdd += "</div>";
						$("#sites")[0].innerHTML = htmlToAdd;
					}
					else{
						closeAllTopDropDowns();
						var getSitesError = data.replace("false|", "");
						queueNotice("error", getSitesError);
						if(getSitesError == "Your log in dissolved. Maybe you logged in on another device."){
							logOut();
						}
					}
				})
				.fail(function(){
					//error
					closeAllTopDropDowns();
					queueNotice("error", "We could not retrieve your sites because your request did not process. You may have a weak internet connection, or our servers might be busy. Please try again.");
				});
			}
			
			function inArrayIgnoreCaseAndWhitespace(needle, haystack){
				for(var i = 0; i < haystack.length; i++){
					if(haystack[i].replace(/\s\s+/g, ' ').trim().toLowerCase() == needle.replace(/\s\s+/g, ' ').trim().toLowerCase()){return true;}
				}
				return false;
			}
			
			var customPlantSpeciesConfirmed = false;
			function setCustomPlantSpeciesConfirmed(confirmBoolean){
				customPlantSpeciesConfirmed = confirmBoolean;
			}
			
			var plantSpeciesInputIsActiveOverride = false;
			function plantSpeciesInputIsActive(){
				if(plantSpeciesInputIsActiveOverride){
					return true;
				}
				if($('#plantSpecies')[0].readOnly || $('#plantSpecies')[0].value.replace(/ /g, '') == '' || inArrayIgnoreCaseAndWhitespace($('#plantSpecies')[0].value, plantSpeciesList) || customPlantSpeciesConfirmed){
					return false;
				}
				return true;
			}
			
			function uploadImageFromLibrary(){
				navigator.camera.getPicture(showUploadedImage, imageUploadFailed, { 
    				quality: 100,
    				sourceType: Camera.PictureSourceType.PHOTOLIBRARY, 
                	allowEdit: true,
    				destinationType: Camera.DestinationType.FILE_URI
				});
			}
			
			function uploadImageFromCamera(){
				navigator.camera.getPicture(showUploadedImage, imageUploadFailed, { 
    				quality: 100,
    				correctOrientation: true,
    				destinationType: Camera.DestinationType.FILE_URI
				});
			}
			
			function imageUploadFailed(message){
				if(message.toLowerCase().indexOf("no image selected") < 0){
					queueNotice("alert", message);
				}
			}
			
			function openPictureUploadMethod(){
				$("#pictureUploadMethod>div").eq(2).stop().animate({width:"40px", right:"-40px"}, 200);
				$("#pictureUploadMethod").stop().animate({left:"-16px", marginLeft:"0px", right:"24px"}, 200);
				$("#uploadedImageGrayOutCover").stop().fadeIn(200);
			}
			
			function closePictureUploadMethod(){
				$("#pictureUploadMethod>div").eq(2).stop().animate({width:"0px", right:"0px"}, 200);
				$("#pictureUploadMethod").stop().animate({left:"100%", marginLeft:"16px", right:"-16px"}, 200);
				$("#uploadedImageGrayOutCover").stop().fadeOut(200);
			}
