function showNotifyOfflineSubmit() {
  showNotifyOffline();
  let existing = document.querySelector('#iconBar .offlineIcon');
  if (existing) { existing.remove()};
  let count = hasOfflineSurveys();
  if (count) {
    let el = document.createElement('span');
    el.classList.add('panelIcon');
    el.classList.add('offlineIcon');
    el.title = "Offline observations waiting to upload.";
    el.onclick = (event) => {
      event.stopPropagation();
      queueNotice('alert', 'Offline observations waiting to upload. ' + offlineSurveysSummary());
    };
    el.textContent = count;
    document.querySelector('#iconBar').prepend(el);
  }
}


                        function haveInternet(){
				//return whether or not we have an internet connection that we are allowed to use
				return navigator.onLine;
			}
			
			var hasMoved = false;
			var currentElement = null;
			var autocompleteIsActive = false;
			$(document).ready(function(){
				var lastPlantSpecies = "";
				
				for(var i = 0; i < $(".noautocomplete").length; i++){
					$(".noautocomplete").eq(i)[0].removeAttribute('readOnly');
				}
				queueManagerRequests();
				
				//clear file upload
				$("#uploadedImageFile")[0].outerHTML = $("#uploadedImageFile")[0].outerHTML;

                                // set plant code if in URL
                          let plantCode = window.location.search.indexOf("plantCode");
                          if (plantCode > -1) {
                            $("#plantCode").val(window.location.search.slice(plantCode+10, plantCode+13)).trigger("input");
                          }
                          loadSurveyFlaggingRules();
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
					//arthropodData: [[orderType, orderLength, orderQuantity, orderNotes, pupa, hairy, leafRoll, silkTent, sawfly, beetle larva, fileInput]]
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
					var averageNeedleLength = pendingSurvey.length > 12 ? pendingSurvey[12] : "-1";
					var linearBranchLength = pendingSurvey.length > 13 ? pendingSurvey[13] : "-1";
	//alert("set vars");
					var formData = new FormData();
					for(var i = 0; i < arthropodDataCopy.length; i++){
						var imgData = arthropodDataCopy[i][10];
						if(imgData != ""){
							var b64Data = imgData[1];
							var contentType = imgData[0];
							formData.append(('file' + i), b64toBlob(b64Data, contentType));
							arthropodDataCopy[i][10] = "";
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
					formData.append("averageNeedleLength", averageNeedleLength);
					formData.append("linearBranchLength", linearBranchLength);
					formData.append("observationMethod", observationMethod);
					formData.append("plantSpecies", plantSpecies);
					formData.append("submittedThroughApp", false);
					formData.append("email", window.localStorage.getItem("email"));
					formData.append("salt", window.localStorage.getItem("salt"));
	//alert("attached form data");
					
					$.ajax({
						url : "/php/submit.php",
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
                                                        showNotifyOfflineSubmit();
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
				$("#retryPlantCredentials .message")[0].innerHTML = message.substring(message.indexOf("|") + 1);
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
					xhttp.open("GET", "/php/getSitesWithUnsetPlantSpecies.php?email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt"), true);
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

                 var plantSpeciesList;
                 getPlantSpeciesList().then((v) => plantSpeciesList = v );
		 var coniferSpeciesList;
                 getConiferSpeciesList().then((v) => coniferSpeciesList = v );

			
			var switchingToPanel = false;
			var currentPanelID = "site";
			function continueToPanel(secondPanelID){
                          if(!haveInternet()) {
                            $("#plantSpecies").attr('placeholder', 'offline? leave this blank to use site plant data');
                          }
                          
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
							if($("#site .dualOptionButton")[0].style.backgroundColor == ""){
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
								$.get("/php/verifySitePassword.php?email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt") + "&code=" + encodeURIComponent($("#plantCode")[0].value) + "&password=" + encodeURIComponent($("#sitePassword")[0].value), function(data){
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
							if($("#site .dualOptionButton")[0].style.backgroundColor == ""){
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
								$.get("/php/verifySitePassword.php?email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt") + "&code=" + encodeURIComponent($("#plantCode")[0].value) + "&password=" + encodeURIComponent($("#sitePassword")[0].value), function(data){
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
                                  showNotifyOfflineSubmit();
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
					var selectElement = $(optionElement).parents(".select").eq(0);
					var selectedValue = getSelectValue(selectElement);
					
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
					var selectedElement = $(optionElement.parentNode).find(".selected")[0];
					selectedElement.className = selectedElement.className.replace("selected", "").trim();
					optionElement.className = optionElement.className + " selected";
					$(optionElement).stop().animate({maxHeight:"250px"}, "swing", function(){selectToggling = false});
					
					if(selectedValue !== getSelectValue(selectElement)){
						eval($(selectElement).data("onChange"));
					}
				}
			}
			
			function setSelectValue(selectElement, val){
				//set the value of a custom .select and show the selected option
				selectElement = $(selectElement)[0];
				var selectedValue = getSelectValue(selectElement);
				
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
				
				if(selectedValue !== getSelectValue(selectElement)){
					eval($(selectElement).data("onChange"));
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
					if($(options[i]).find(".value")[0].innerHTML == val){
						return $(options[i]).find(".text")[0].innerHTML;
					}
				}
			}
			
			function getSelectValueByText(selectElement, txt){
				//given the value of a custom .select, return that values corresponding text
				selectElement = $(selectElement)[0];
				var options = selectElement.getElementsByClassName("option");
				for(var i = 0; i < options.length; i++){
					if($(options[i]).find(".text")[0].innerHTML == txt){
						return $(options[i]).find(".value")[0].innerHTML;
					}
				}
			}
			
			function getSelectImageByText(selectElement, txt){
				//given the value of a custom .select, return that values corresponding text
				selectElement = $(selectElement)[0];
				var options = selectElement.getElementsByClassName("option");
				for(var i = 0; i < options.length; i++){
					if($(options[i]).find(".text")[0].innerHTML == txt){
						bgimg = $(options[i]).find(".image")[0].style.backgroundImage;
						return bgimg.substring(bgimg.indexOf("(") + 1, bgimg.lastIndexOf(")")).replace(/"/g, "").replace(/'/g, "");
						//TODO: remove this line. "
					}
				}
			}

                        surveyFlaggingRules = {};

                        async function loadSurveyFlaggingRules() {
                          let response = await fetch("../php/cacheSurveyFlaggingRules.php");
                          surveyFlaggingRules = JSON.parse((await response.text()));
                        }

                        function promptWithNotes(notesInput, message, cancelMessage, confirmMessage, cancelFunction, confirmFunction){
                          let notes = document.createElement('textarea')
                          let confirm = function() {
                            if (newNotes = $(notes).val()) {
                              $(notesInput).val((i, val) => {return val + "\n" + newNotes});
                            }
                            confirmFunction();
                          }
                          promptConfirm(message, cancelMessage, confirmMessage, cancelFunction, confirm);
                          $("#confirm div").eq(0).append(notes);
                        }

			function askToConfirmOrderLength(){
				var orderTypeValue = getSelectValue($("#orderType"));
				if(orderTypeValue == "" || !surveyFlaggingRules["arthropodGroupFlaggingRules"][orderTypeValue]){
					return false;
				}

			        var maxOrderLength = surveyFlaggingRules["arthropodGroupFlaggingRules"][orderTypeValue]["maxSafeLength"];
				if(Number($('#orderLength')[0].value) > maxOrderLength){
				  promptWithNotes("#orderNotes", 'Wow, "' + getSelectText($("#orderType")) + '" measurements aren\'t usually that long! Are you sure ' + $('#orderLength')[0].value + 'mm is accurate? Remember that length does not include legs or antennae. Add a note for review if sure:', 'Whoops!', 'Yes, I am sure!', function(){
				    $('#orderLength')[0].focus();
				    $('#orderLength')[0].select(0, 9999);
				  }, function(){
				    
				  });
				}
			}

			function askToConfirmOrderQuantity(){
				var orderTypeValue = getSelectValue($("#orderType"));
				if(orderTypeValue == "" || !surveyFlaggingRules["arthropodGroupFlaggingRules"][orderTypeValue]){
					return false;
				}
				
			        var maxOrderQuantity = surveyFlaggingRules["arthropodGroupFlaggingRules"][orderTypeValue]["maxSafeQuantity"];

			        if(Number($('#orderQuantity')[0].value) > maxOrderQuantity){
				  promptWithNotes("#orderNotes", 'Wow, that is a lot of "' + getSelectText($("#orderType")) + '"! Are you sure ' + $('#orderQuantity')[0].value + ' is accurate? Add a note for review if sure:', 'Whoops!', 'Yes, I am sure!', function(){
				    $('#orderQuantity')[0].focus();
				    $('#orderQuantity')[0].select(0, 9999);
				  }, function(){
				    
				  });
				}
			}

			function askToConfirmLeafLength(){
			  var species = $("#plantSpecies").val();
			  
			  var maxLength = surveyFlaggingRules["leafLengthExceptions"][species] || surveyFlaggingRules["maxSafeLeafLength"];
                          var speciesCompound = surveyFlaggingRules["compoundLeafExceptions"][species] || false;
			  let entry = Number($('#averageLeafLength')[0].value);
                          let prompt;
			  if(entry > maxLength) {
                            prompt = 'Wow, that is a long leaf! Are you sure you\'re measuring in centimeters?';
                            if (speciesCompound) {
                              prompt += ' Measure just one leaflet for compound leaves on this species.';
                            }
                            prompt += ' Add a note for review if sure:';
                          
			    promptWithNotes("#siteNotes", prompt, 'Whoops!', 'Yes, I am sure!', function(){
			      $('#averageLeafLength')[0].focus();
			      $('#averageLeafLength')[0].select(0, 9999);
			    }, function(){
			      
			    });
                          }
			}

			function askToConfirmLeafQuantity(){
			  var species = $("#plantSpecies").val();
			  
			  var maxQuantity = surveyFlaggingRules["maxSafeLeaves"];
                          var minQuantity = surveyFlaggingRules["minSafeLeaves"];
			  let entry = Number($('#numberOfLeaves')[0].value);
                          let prompt;
			  if(entry > maxQuantity) {
                            prompt = 'Wow, that\'s a lot of leaves! Are you sure ' + entry + ' leaves were <strong><em>over the beat sheet</em></strong> while beating?'
                          }
                          if(entry < minQuantity) {
                            prompt = 'Wow, that\'s not many leaves.'
                          }
                          if (prompt) {
                            prompt += ' Add a note for review if sure:';
			    promptWithNotes("#siteNotes", prompt, 'Whoops!', 'Yes, I am sure!', function(){
			      $('#numberOfLeaves')[0].focus();
			      $('#numberOfLeaves')[0].select(0, 9999);
			    }, function(){
			      
			    });
                          }
                        }


			function getWordAtIndex(str, index){
				var left = str.slice(0, index + 1).search(/\S+$/);
        		var right = str.slice(index).search(/\s/);
				if (right < 0) {return str.slice(left);}
				return str.slice(left, right + index);
    		}
			function attachAutoCompleteToInput(inputElement, sourceList, bindSetConifer){
				bindSetConifer = bindSetConifer || false;
				
				var autoCompleteOptions = {
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
				};
				
				if(bindSetConifer){
					autoCompleteOptions["close"] = function(event, ui){
						setConifer(this);
					}
				}
				
				$(inputElement).autocomplete(autoCompleteOptions);
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
				$(inputElement.parentNode.parentNode).find('.characterCount')[0].innerHTML = inputElement.value.length + "/" + max;
			}
			
			function clearCountInput(inputElement){
				inputElement = $(inputElement)[0];
				inputElement.value = "";
				var charCountDiv = $(inputElement.parentNode.parentNode).find(".characterCount")[0];
				charCountDiv.innerHTML = "0" + charCountDiv.innerHTML.substring(charCountDiv.innerHTML.indexOf("/"));
			}
			
			var observationMethod = "";
			var mostUpToDatePlantReturnNumber = 0;
			function getPlant(codeInput, passwordGroup){
				codeInput = $(codeInput)[0];
				codeInput.value = codeInput.value.toUpperCase().replace(/ /g, "").replace(/[^A-Z]/g, "");
				$("#plant input")[0].value = "";
				$("#plant input")[0].readOnly = false;
				
				if(!haveInternet()){
					codeInput.parentNode.style.color = "";
					codeInput.parentNode.style.borderRadius = "";
					codeInput.parentNode.style.background = "";
					codeInput.parentNode.style.padding = "";
					codeInput.parentNode.style.marginTop = "";
					$(codeInput.parentNode).find("div")[0].innerHTML = "";
					return false;
				}
				
				var thisPlantReturnNumber = ++mostUpToDatePlantReturnNumber;
				
				setTimeout(function(){
					if(thisPlantReturnNumber == mostUpToDatePlantReturnNumber) {
						code = codeInput.value;
						$.get("/php/getPlantByCode.php?code=" + encodeURIComponent(code) + "&email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt"), function(data){
							//success
							if(thisPlantReturnNumber == mostUpToDatePlantReturnNumber){
								if(data.indexOf("true|") == 0){
									var plantArray = JSON.parse(data.replace("true|", ""));
									var color = plantArray["color"];
									var siteName = plantArray["siteName"];
									var species = plantArray["species"];
									var circle = plantArray["circle"];
									var isConifer = plantArray["isConifer"];
									var validated = plantArray["validated"];
									observationMethod = plantArray["observationMethod"];//refers to global var
									setConiferInputs(isConifer);
									
									codeInput.parentNode.style.color = "#fff";
									codeInput.parentNode.style.borderRadius = "4px";
									codeInput.parentNode.style.background = color;
									codeInput.parentNode.style.padding = "10px 10px 0px 10px";
									codeInput.parentNode.style.marginTop = "10px";
									$(codeInput.parentNode).find("div")[0].innerHTML = siteName + ", Circle " + circle + ", " + species;
									
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
											var speciesInput = $("#plant input")[0];
											
											//$("#plant .group").eq(0).show();
											speciesInput.value = "";
											speciesInput.readOnly = false;
										}
										else{
											var speciesInput = $("#plant input")[0];
												
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
									$(codeInput.parentNode).find("div")[0].innerHTML = "";
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
			function finish(){
				if(finishing){return false;}
				var plantCode = $("#plantCode")[0].value.trim();
				var sitePassword = $("#sitePassword")[0].value.trim();
				var dateAndTime = formatDateAndTimeForDatabase($("#date")[0].value, $("#time")[0].value, false);
				var siteNotes = $("#siteNotes")[0].value.trim();
				var wetLeaves = checkboxIsChecked($("#wetLeavesCheckbox")).toString();
				//arthropodData: [[orderType, orderLength, orderQuantity, orderNotes, pupa, hairy, leafRoll, silkTent, sawfly, beetle larva, fileInput]]
				var plantSpecies = $("#plantSpecies")[0].value.trim();
				var isConifer = $("#coniferInputs")[0].style.display == "block";
				var numberOfLeaves = isConifer ? -1 : $("#numberOfLeaves")[0].value.trim();
				var averageLeafLength = isConifer ? -1 : $("#averageLeafLength")[0].value.trim();
				var herbivoryScore = isConifer ? -1 : getSelectValue($("#herbivoryScore"));
				var averageNeedleLength = isConifer ? $("#averageNeedleLength")[0].value.trim() : -1;
				var linearBranchLength = isConifer ? $("#linearBranchLength")[0].value.trim() : -1;
					
				//front end checking of plant vals
				var errors = "";
				/*COMMENTED OUT TO ALLOW CUSTOM INPUT
				if(plantSpeciesList.indexOf(plantSpecies) == -1){
					errors += "Enter an approved plant species. ";
				}
				*/
				if(isConifer){
					if(averageNeedleLength.length != averageNeedleLength.replace(/\D/g, "").length || averageNeedleLength.length == 0 || Number(averageNeedleLength) > 60 || Number(averageNeedleLength) < 1){
						errors += "Enter an average needle length between 1 and 60 centimeters. ";
					}
					if(linearBranchLength.length != linearBranchLength.replace(/\D/g, "").length || linearBranchLength.length == 0 || Number(linearBranchLength) > 500 || Number(linearBranchLength) < 1){
						errors += "Enter a linear branch length between 1 and 500 centimeters. ";
					}
				}
				else{
					if(herbivoryScore == ""){
						errors += "Select an herbivory score. ";
					}
				}
				
				if(errors.length > 0){
					queueNotice("error", errors);
					return false;
				}

                                window.localStorage.setItem("lastSitePassword", sitePassword);
					
				if(!haveInternet()){
					finishing = true;
					$("#clearInteractionBlock")[0].style.display = "block";
					setLoadingButton($("#finishButton"), "Finish", true);
				
					//compress base 64 image strings
					var arthropodDataCopy = [];
					for(var i  = 0; i < arthropodData.length; i++){
						arthropodDataCopy[i] = arthropodData[i].slice();
						if(arthropodDataCopy[i][10].length > 0){
							compressBase64Index(arthropodDataCopy[i], 10, 500, 24, false);
						}
					}
		//alert("copied data");
					//save in local storage when ready
					var savedCheck = setInterval(function(){
						var allImageDataLoaded = true;
						for(var i  = 0; i < arthropodDataCopy.length; i++){
		//alert("arthropodDataCopy[i][10] != '': " + (arthropodDataCopy[i][10] != "").toString());
		//alert("arthropodDataCopy[i][10].constructor !== Array: " + (arthropodDataCopy[i][10].constructor !== Array).toString());
		//alert("image" + i + " still processing: " + (arthropodDataCopy[i][10] != "" && arthropodDataCopy[i][10].constructor !== Array).toString());
							if(arthropodDataCopy[i][10] != "" && arthropodDataCopy[i][10].constructor !== Array){
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
							existingPendingSurveys[existingPendingSurveys.length] = [plantCode, sitePassword, dateAndTime, observationMethod, siteNotes, wetLeaves, arthropodDataCopy, plantSpecies, numberOfLeaves, averageLeafLength, herbivoryScore, window.localStorage.getItem("email"), averageNeedleLength, linearBranchLength];
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
									if(pendingSurveyEmails.indexOf(existingPendingSurveys[i][11]) == -1){
										pendingSurveyEmails[pendingSurveyEmails.length] = existingPendingSurveys[i][11];
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
									var arthropodSightings = existingPendingSurveys[existingPendingSurveys.length - 1][6];
									for(var i = 0; i < arthropodSightings.length; i++){
										arthropodSightings[i][10] = "";
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
					var numberOfPhotosUploaded = 0;
					for(var i = 0; i < arthropodData.length; i++){
						if(arthropodData[i][10].length > 0){
							numberOfPhotosUploaded++;
							compressBase64Index(arthropodBlobs, i, 1750, 70, true, arthropodData[i][10]);
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
								temporaryArthropodData[i].splice(10, 1);
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
							formData.append("averageNeedleLength", averageNeedleLength);
							formData.append("linearBranchLength", linearBranchLength);
							formData.append("observationMethod", observationMethod);
							formData.append("plantSpecies", plantSpecies);
							formData.append("submittedThroughApp", false);
							formData.append("email", window.localStorage.getItem("email"));
							formData.append("salt", window.localStorage.getItem("salt"));
							
							$.ajax({
								url : "/php/submit.php",
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
								error: function(request, status, error){
									//check for success?
									var extraError = "";
									if(numberOfPhotosUploaded == 1){
										extraError = " If necessary, you can also delete your arthropod photo to lighten the load.";
									}
									else if(numberOfPhotosUploaded > 1){
										extraError = " If necessary, you can also delete a couple arthropod photos to lighten the load.";
									}
									queueNotice("error", "We had trouble uploading this survey. It's possible that a part of this survey submitted but some was left out. We've been notified of this error and will address it. To make sure a full copy of the survey submits, you can try switching to a stronger wifi network if one is available and then tapping the \"Finish\" button again." + extraError);
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
                                let lastPass = window.localStorage.getItem("lastSitePassword");
				if($("#samePass .checkBox")[0].className.indexOf("checked") > -1){
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
				        if(orderLength == "" || isNaN(orderLength) || Number(orderLength) < 1){errors += "Enter a length between 1mm and 300mm. ";}
				        if(orderQuantity == "" || isNaN(orderQuantity) || Number(orderQuantity) < 1){errors += "Enter a quantity between 1 and 1000. ";}
					if((orderType == "unidentified" || orderType == "other") && orderNotes == ""){errors += "Notes are required for \"" + orderType + "\" arthropod groups, and photos are requested but optional.";}
					
					if(errors.length > 0){
						queueNotice("error", errors);
						return false;
					}
					
					//save data
					var pupa = checkboxIsChecked($("#pupaCheckbox"));
					var hairy = checkboxIsChecked($("#hairyCheckbox"));
					var leafRoll = checkboxIsChecked($("#leafRollCheckbox"));
					var silkTent = checkboxIsChecked($("#silkTentCheckbox"));
					var sawfly = checkboxIsChecked($("#sawflyCheckbox"));
					var beetleLarva = checkboxIsChecked($("#beetleLarvaCheckbox"));
					var base64 = "";
					if($("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage.indexOf("#") == -1){
						base64 = $("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage.replace("url(", "").replace(/'/g, "").replace(/"/g, "").replace(")", "");
					}
					arthropodData[currentlyShownIndex] = [orderType, orderLength, orderQuantity, orderNotes, pupa, hairy, leafRoll, silkTent, sawfly, beetleLarva, base64];
					updateArthropodCards();
					return true;
				}
			}
            
			function deleteArthropodData(i){
				arthropodData[i][10].outerHTML = "";
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
				uncheckCheckbox($("#pupaCheckbox"));
				uncheckCheckbox($("#hairyCheckbox"));
				uncheckCheckbox($("#leafRollCheckbox"));
				uncheckCheckbox($("#silkTentCheckbox"));
				uncheckCheckbox($("#sawflyCheckbox"));
				uncheckCheckbox($("#beetleLarvaCheckbox"));
				$("#caterpillarOptionsGroup")[0].style.display = "none";
				$("#butterflyOptionsGroup")[0].style.display = "none";
				$("#beeOptionsGroup")[0].style.display = "none";
				$("#beetleOptionsGroup")[0].style.display = "none";
				$("#orderLength")[0].value = $("#orderLength")[0].defaultValue;
				$("#orderQuantity")[0].value = $("#orderQuantity")[0].defaultValue;
				$("#orderNotes")[0].value = "";
				$("#fileInputRemoveLink")[0].style.display = "none";
				$("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage = "url('#')";
				$("#arthropodFileInputHolder .uploadedImage")[0].style.height = "0px";
				$("#arthropodFileInputHolder .uploadedImage")[0].style.margin = "";
				$("#arthropodFileInputHolder .uploadedImage")[0].style.padding = "";
				$("#arthropodFileInputHolder .snapIcon")[0].src = "../images/camera.png";
				
				
				selectDualOptionButton($("#moreArthropods .dualOptionButton").eq(1));
				$("#moreArthropods").stop().show(300);
				$("#arthropodFormDiv").stop().fadeOut(300);
				
				signalNoData();
			}
			
			function tryToPopulateArthropodGroup(i){
				//continue without saving? prompt
				//orderType, orderLength, orderQuantity, orderNotes, pupa, hairy, leafRoll, silkTent, sawfly, beetle larva, fileInput
				var oldDataIsShown = (currentlyShownIndex > -1 && currentlyShownIndex < arthropodData.length);
				var oldDataHasChanged = false;
				if(oldDataIsShown){
					oldDataHasChanged = (getSelectValue($("#orderType")[0]) != arthropodData[currentlyShownIndex][0] || 
					$("#orderLength")[0].value != arthropodData[currentlyShownIndex][1] || 
					$("#orderQuantity")[0].value != arthropodData[currentlyShownIndex][2] || 
					$("#orderNotes")[0].value != arthropodData[currentlyShownIndex][3] || 
					checkboxIsChecked($("#pupaCheckbox")[0]) != arthropodData[currentlyShownIndex][4] || 
					checkboxIsChecked($("#hairyCheckbox")[0]) != arthropodData[currentlyShownIndex][5] || 
					checkboxIsChecked($("#leafRollCheckbox")[0]) != arthropodData[currentlyShownIndex][6] || 
					checkboxIsChecked($("#silkTentCheckbox")[0]) != arthropodData[currentlyShownIndex][7] ||
					checkboxIsChecked($("#sawflyCheckbox")[0]) != arthropodData[currentlyShownIndex][8] ||
					checkboxIsChecked($("#beetleLarvaCheckbox")[0]) != arthropodData[currentlyShownIndex][9] ||
					$("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage.indexOf(arthropodData[currentlyShownIndex][10]) == -1);
				}
				
				var newDataIsShown = (currentlyShownIndex == arthropodData.length);
				var newDataHasChanged = false;
				if(newDataIsShown){
					newDataHasChanged = (getSelectValue($("#orderType")[0]) != "" || 
					$("#orderLength")[0].value != $("#orderLength")[0].defaultValue || 
					$("#orderQuantity")[0].value != $("#orderQuantity")[0].defaultValue || 
					$("#orderNotes")[0].value != "" || 
					checkboxIsChecked($("#pupaCheckbox")[0]) || 
					checkboxIsChecked($("#hairyCheckbox")[0]) || 
					checkboxIsChecked($("#leafRollCheckbox")[0]) || 
					checkboxIsChecked($("#silkTentCheckbox")[0]) ||
					checkboxIsChecked($("#sawflyCheckbox")[0]) ||
					checkboxIsChecked($("#beetleLarvaCheckbox")[0]) ||
					$("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage.indexOf("#") == -1);
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
				
				if(arthropodData[i][4]){checkCheckbox($("#pupaCheckbox"));}
				else{uncheckCheckbox($("#pupaCheckbox"));}
				
				if(arthropodData[i][5]){checkCheckbox($("#hairyCheckbox"));}
				else{uncheckCheckbox($("#hairyCheckbox"));}
				
				if(arthropodData[i][6]){checkCheckbox($("#leafRollCheckbox"));}
				else{uncheckCheckbox($("#leafRollCheckbox"));}
				
				if(arthropodData[i][7]){checkCheckbox($("#silkTentCheckbox"));}
				else{uncheckCheckbox($("#silkTentCheckbox"));}
				
				if(arthropodData[i][8]){checkCheckbox($("#sawflyCheckbox"));}
				else{uncheckCheckbox($("#sawflyCheckbox"));}
				
				if(arthropodData[i][9]){checkCheckbox($("#beetleLarvaCheckbox"));}
				else{uncheckCheckbox($("#beetleLarvaCheckbox"));}
				
				if(arthropodData[i][0] == "caterpillar"){$("#caterpillarOptionsGroup")[0].style.display = "block";}
				else{$("#caterpillarOptionsGroup")[0].style.display = "none";}
				
				if(arthropodData[i][0] == "moths"){$("#butterflyOptionsGroup")[0].style.display = "block";}
				else{$("#butterflyOptionsGroup")[0].style.display = "none";}
				
				if(arthropodData[i][0] == "bee"){$("#beeOptionsGroup")[0].style.display = "block";}
				else{$("#beeOptionsGroup")[0].style.display = "none";}
				
				if(arthropodData[i][0] == "beetle"){$("#beetleOptionsGroup")[0].style.display = "block";}
				else{$("#beetleOptionsGroup")[0].style.display = "none";}
				
				$("#orderLength")[0].value = arthropodData[i][1];
				$("#orderQuantity")[0].value = arthropodData[i][2];
				
				if(arthropodData[i][10] != ""){
					$("#arthropodFileInputHolder .snapIcon")[0].src = "../images/inputCheckIcon.png";
					showUploadedImage(arthropodData[i][10]);
					$("#arthropodFileInputHolder .uploadedImage")[0].style.height = "80px";
					$("#arthropodFileInputHolder .uploadedImage")[0].style.margin = "-20px -20px 16px -20px";
					$("#arthropodFileInputHolder .uploadedImage")[0].style.padding = "0px 20px";
				}
				else{
					$("#arthropodFileInputHolder .snapIcon")[0].src = "../images/camera.png";
					$("#fileInputRemoveLink")[0].style.display = "none";
					$("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage = "url('#')";
					$("#arthropodFileInputHolder .uploadedImage")[0].style.height = "0px";
					$("#arthropodFileInputHolder .uploadedImage")[0].style.margin = "";
					$("#arthropodFileInputHolder .uploadedImage")[0].style.padding = "";
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
						if(arthropodData[i][5]){
							titlePrefix += "Hairy, ";
						}
						if(arthropodData[i][6]){
							titlePrefix += "Rolled, ";
						}
						if(arthropodData[i][7]){
							titlePrefix += "Tented, ";
						}
					}
					else if(arthropodData[i][0] == "bee"){
						if(arthropodData[i][8]){
							titlePrefix += "Larva-Stage, ";
						}
					}
					else if(arthropodData[i][0] == "beetle"){
						if(arthropodData[i][9]){
							titlePrefix += "Larva-Stage, ";
						}
					}
					else if(arthropodData[i][0] == "moths"){
						if(arthropodData[i][4]){
							titlePrefix += "Pupa-Stage, ";
						}
					}
					titlePrefix = titlePrefix.substring(0, titlePrefix.lastIndexOf(", "));
					
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
					if(arthropodData[i][10] != ""){
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.backgroundImage = "url('" + arthropodData[i][10] + "')";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.backgroundSize = "cover";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.padding = "5px";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.margin = "-5px -10px -5px -5px";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.borderRadius = "4px";
						if($("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.backgroundImage.length <= 22){
							var cardPhotoCheck = setInterval(function(){
								$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.backgroundImage = "url('" + arthropodData[i][10] + "')";
								if($("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.backgroundImage.length > 22){
									clearInterval(cardPhotoCheck);
								}
							}, 100);
						}
					}
					else{
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.backgroundSize = "";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.padding = "";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.margin = "";
						$("#arthropodCards .orderTable").eq(i).find("td").eq(0).find("div")[0].style.borderRadius = "";
					}
				}
				
				rewordMoreArthropodsQuestion();
			}
			
			function setConiferInputs(isConifer){
				//this function is called whenever value of #plantSpecies changes
				if(isConifer){
					$("#coniferInputs").css({display:"block"});
					$("#leafInputs").css({display:"none"});
					$("#herbivoryGroup").css({display:"none"});
					$("#leavesText")[0].innerHTML = "needles";
				}
				else{
					$("#coniferInputs").css({display:"none"});
					$("#leafInputs").css({display:"block"});
					$("#herbivoryGroup").css({display:"block"});
					$("#leavesText")[0].innerHTML = "leaves";
				}
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
					$("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage = "url('" + base64OrURI + "')";
					$("#arthropodFileInputHolder .uploadedImage")[0].style.height = "80px";
					$("#arthropodFileInputHolder .uploadedImage")[0].style.margin = "-20px -20px 16px -20px";
					$("#arthropodFileInputHolder .uploadedImage")[0].style.padding = "0px 20px";
					$("#fileInputRemoveLink")[0].style.display = "block";
				}
				else{
					//URI provided
					$("#clearInteractionBlock")[0].style.display = "block";
					var data = [base64OrURI];
					compressBase64Index(data, 0, 1750, 100, false);
					var compressedCheck = setInterval(function(){
						if(data != [base64OrURI]){
							$("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage = "url('data:" + data[0][0] + ";base64," + data[0][1] + "')";
							if($("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage.length > 22){
								$("#arthropodFileInputHolder .uploadedImage")[0].style.height = "80px";
								$("#arthropodFileInputHolder .uploadedImage")[0].style.margin = "-20px -20px 16px -20px";
								$("#arthropodFileInputHolder .uploadedImage")[0].style.padding = "0px 20px";
								$("#fileInputRemoveLink")[0].style.display = "block";
								$("#clearInteractionBlock")[0].style.display = "none";
							}
							else{
								var backgroundImageSetCheck = setInterval(function(){
									$("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage = "url('data:" + data[0][0] + ";base64," + data[0][1] + "')";
									if($("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage.length > 22){
										$("#arthropodFileInputHolder .uploadedImage")[0].style.height = "80px";
										$("#arthropodFileInputHolder .uploadedImage")[0].style.margin = "-20px -20px 16px -20px";
										$("#arthropodFileInputHolder .uploadedImage")[0].style.padding = "0px 20px";
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
				$("#arthropodFileInputHolder .snapIcon")[0].src = "../images/camera.png";
				$("#arthropodFileInputHolder .uploadedImage")[0].style.backgroundImage = "url('#')";
				$("#arthropodFileInputHolder .uploadedImage")[0].style.height = "0px";
				$("#arthropodFileInputHolder .uploadedImage")[0].style.margin = "";
				$("#arthropodFileInputHolder .uploadedImage")[0].style.padding = "";
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

                        function resetLastSitePassword() {
				let lastPass = $("#sitePassword")[0].value;
                                if (lastPass) {
                                  window.localStorage.setItem("lastSitePassword", lastPass);
                                } else {
                                  lastPass = window.localStorage.getItem("lastSitePassword");
                                }
				$("#sitePassword")[0].value = "";

			        if(!haveInternet() && lastPass && $("#sitePasswordGroup")[0].style.display != "none"){
					$("#samePass")[0].style.display = "block";
					uncheckCheckbox($("#samePass .checkBox").eq(0));
				}
				$("#sitePasswordGroup").stop().show(0);
                          
                        }
			
			function restart(){
                                showNotifyOfflineSubmit();
                                resetLastSitePassword();
                          				
				$("#plantCode")[0].value = "";
				$("#plantCode")[0].parentNode.style.color = "";
				$("#plantCode")[0].parentNode.style.borderRadius = "";
				$("#plantCode")[0].parentNode.style.background = "";
				$("#plantCode")[0].parentNode.style.padding = "";
				$("#plantCode")[0].parentNode.style.marginTop = "";
				$($("#plantCode")[0].parentNode).find("div")[0].innerHTML = "";
				
				$("#date")[0].value = "";
				$("#time")[0].value = "";
				
				$("#siteNotes")[0].value = "";
				uncheckCheckbox($("#wetLeavesCheckbox"));
				
				for(var i = (arthropodData.length - 1); i >= 0; i--){
					deleteArthropodData(i);
				}
				
				$("#plant input").val("");
				$("#plant input")[0].readOnly = false;
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
				$.get("/php/getOwnedSitesLIGHT.php?email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt"), function(data){
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
			
			function isKnownConifer(plantSpeciesName){
				plantSpeciesName = plantSpeciesName.replace(/[^A-Za-z]+/g," ").replace(/\s\s+/g, ' ').trim().toLowerCase();
				
				if(plantSpeciesName == ""){
					return false;
				}
				
				if(coniferSpeciesList.indexOf(plantSpeciesName) > -1){
					return true;
				}
				
				var prefixes = ["abies", "chamaecyparis", "juniperus", "larix", "picea", "pinus", "pseudotsuga", "taxodium", "taxus", "thuja", "tsuga"];
				for(var i = 0; i < prefixes.length; i++){
					if((" " + plantSpeciesName).indexOf(" " + prefixes[i])> -1){
						return true;
					}
				}
				
				var suffixes = ["fir", "cedar", "juniper", "larch", "spruce", "pine", "cypress", "yew", "hemlock"];
				var plantSpeciesNameParts = plantSpeciesName.split(" ");
				return suffixes.indexOf(plantSpeciesNameParts[plantSpeciesNameParts.length - 1]) > -1;
			}
			
			lastManualConiferSettings = {};
			function setConifer(plantSpeciesInput){
				//this function is called whenever value of #plantSpecies changes
				var plantSpecies = plantSpeciesInput.value;
				
				//if we recognize it
				if(isKnownConifer(plantSpecies)){
					//check and gray out
					$("#checkboxTableOverlay" + plantSpeciesInput.id).css({display: "block"});
					$("#checkboxTable" + plantSpeciesInput.id).css({opacity: ".5"});
					checkCheckbox($("#coniferCheckbox" + plantSpeciesInput.id));
				}
				else if(inArrayIgnoreCaseAndWhitespace(plantSpecies, plantSpeciesList)){
					//uncheck and gray out
					$("#checkboxTableOverlay" + plantSpeciesInput.id).css({display: "block"});
					$("#checkboxTable" + plantSpeciesInput.id).css({opacity: ".5"});
					uncheckCheckbox($("#coniferCheckbox" + plantSpeciesInput.id));
				}
				else{
					//ungray
					$("#checkboxTableOverlay" + plantSpeciesInput.id).css({display: "none"});
					$("#checkboxTable" + plantSpeciesInput.id).css({opacity: "1"});
					
					//revert to last manual selection
					if(lastManualConiferSettings[plantSpeciesInput.id] === true){
						if(!checkboxIsChecked($("#coniferCheckbox" + plantSpeciesInput.id))){
							checkCheckbox($("#coniferCheckbox" + plantSpeciesInput.id));
						}
					}
					else if(checkboxIsChecked($("#coniferCheckbox" + plantSpeciesInput.id))){
						uncheckCheckbox($("#coniferCheckbox" + plantSpeciesInput.id));
					}
				}
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
