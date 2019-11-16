			var HIGH_TRAFFIC_MODE = true;
			var noticeQueue = [];
			var showingQueue = false;
			function queueNotice(type, message){
				for(var i = 0; i < noticeQueue.length; i++){
					if(noticeQueue[i][0] == type && noticeQueue[i][1] == message){
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
			
			function showNextNoticeInQueue(){
				if(noticeQueue.length > 0){
					setTimeout(function(){
						if(noticeQueue[0][0] == "error"){
							showError(noticeQueue[0][1]);
							//$('html, body').animate({scrollTop: 0}, 500);
						}
						else if(noticeQueue[0][0] == "confirmation"){
							showConfirmation(noticeQueue[0][1]);
							//$('html, body').animate({scrollTop: 0}, 500);
						}
						else if(noticeQueue[0][0] == "alert"){
							showAlert(noticeQueue[0][1]);
							//$('html, body').animate({scrollTop: 0}, 500);
						}
						else if(noticeQueue[0][0] == "managerRequest"){
							showManagerRequest(noticeQueue[0][1]);
							//$('html, body').animate({scrollTop: 0}, 500);
						}
					}, 1);
				}
				else{
					showingQueue = false;
				}
			}
			
			function showError(message){
				//show an error message
				$("#error")[0].innerHTML = message;
				$("#error").stop().fadeIn();
				$("#noticeInteractionBlock").stop().fadeIn();
			}
			
			function showConfirmation(message){
				//show a confirmation message
				$("#confirmation")[0].innerHTML = message;
				$("#confirmation").stop().fadeIn();
				$("#noticeInteractionBlock").stop().fadeIn();
			}
			
			function showAlert(message){
				//show a regular alert message
				$("#alert")[0].innerHTML = message;
				$("#alert").stop().fadeIn();
				$("#noticeInteractionBlock").stop().fadeIn();
			}
			
			function showManagerRequest(message){
				//show a regular alert message
				$("#managerRequestMessage")[0].innerHTML = message;
				$("#managerRequest").stop().fadeIn();
				$("#noticeInteractionBlock").stop().fadeIn();
			}
			
			function hideNotice(){
				$("#noticeInteractionBlock").stop().fadeOut(200);
				$("#alert").stop().fadeOut(200);
				$("#error").stop().fadeOut(200);
				$("#managerRequest").stop().fadeOut(200);
				$("#confirmation").stop().fadeOut(200, function(){
					noticeQueue.splice(0, 1);
					showNextNoticeInQueue();
				});
			}
			
			function promptConfirm(message, cancelMessage, confirmMessage, cancelFunction, confirmFunction){
				if(cancelMessage == ""){cancelMessage = "cancel";}
				if(confirmMessage == ""){cancelMessage = "ok";}
				cancelMessage = cancelMessage.charAt(0).toUpperCase() + cancelMessage.substring(1);
				confirmMessage = confirmMessage.toUpperCase();
				
				$("#promptInteractionBlock").stop().fadeIn(150);
				$("#confirm div").eq(0)[0].innerHTML = message;
				$("#confirm button").eq(0)[0].innerHTML = confirmMessage;
				$("#confirm button").eq(1)[0].innerHTML = cancelMessage;
				$("#confirm").stop().fadeIn(150);
				$("#confirm button").eq(0)[0].onclick = function(){confirmFunction();$("#confirm").stop().fadeOut(150);$("#promptInteractionBlock").stop().fadeOut(150);};
				$("#confirm button").eq(1)[0].onclick = function(){cancelFunction();$("#confirm").stop().fadeOut(150);$("#promptInteractionBlock").stop().fadeOut(150);};
			}
			
			function setLoadingButton(buttonElement, defaultInnerHTML, setToLoading){
				//show rolling loading animation in a button if setToLoading is true.
				//otherwise, restore the innerHTML of the button to defaultInnerHTML.
				buttonElement = $(buttonElement)[0];
				if(setToLoading){
					buttonElement.innerHTML = "<img src=\"../images/rolling.svg\" alt=\"Loading...\"/>";
				}
				else{
					buttonElement.innerHTML = defaultInnerHTML;
				}
			}

			function getDeviceType(){
				var check = false;
				(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
				if(check){return "phone";}
				(function(a){if(/android|ipad|playbook|silk/i.test(a)) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
				if(check){return "tablet";}
				return "desktop";
			}
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			var requiringLogIn = false;
			function requireLogIn(){
				if(!requiringLogIn){
					requiringLogIn = true;
					
					if(window.localStorage.getItem("email") === null || window.localStorage.getItem("salt") === null){
						requiringLogIn = false;
						window.location = "../signIn/index.html?p=../" + window.location.toString().substring(window.location.toString().indexOf("unc.edu") + 8);
					}
				
					//if we have credentials saved to localStorage and arent already doing so, verify them and redirect to sign in page if invalid
					var xhttp = new XMLHttpRequest();
					xhttp.onreadystatechange = function() {
						if (this.readyState == 4 && this.status == 200) {
							requiringLogIn = false;
							if(this.responseText == "false"){
								window.location = "../signIn/index.html?p=../" + window.location.toString().substring(window.location.toString().indexOf("unc.edu") + 8);
							}
						}
					};
					xhttp.open("GET", "../php/autoLogIn.php?email=" + window.localStorage.getItem("email") + "&salt=" + window.localStorage.getItem("salt"), true);
					xhttp.send();
				}
			}
			
			var showingLoggedInNav = false;
			function showLoggedInNav(){
				if(!showingLoggedInNav){
					if(window.localStorage.getItem("email") === null || window.localStorage.getItem("salt") === null){
						$("nav").eq(0)[0].className = "";
						return false;
					}
					
					showingLoggedInNav = true;
					//if we have credentials saved to localStorage and arent already doing so, verify them and show logged in nav if valid
					var xhttp = new XMLHttpRequest();
					xhttp.onreadystatechange = function() {
						if (this.readyState == 4 && this.status == 200) {
							showingLoggedInNav = false;
							if(this.responseText == "true"){
								$("nav>ul>li:last-of-type").eq(0)[0].onclick = function(){
									accessSubMenu($("nav>ul>li:last-of-type").eq(0)[0]);
								}
								$("nav>ul>li:last-of-type").eq(0).find("span").eq(0)[0].innerHTML = "My Account";
							}
							$("nav").eq(0)[0].className = "";
						}
					};
					if($("h1").eq(0)[0].innerHTML == "Caterpillars Count!"){
						xhttp.open("GET", "php/autoLogIn.php?email=" + window.localStorage.getItem("email") + "&salt=" + window.localStorage.getItem("salt"), true);
					}
					else if($("h1").eq(0)[0].innerHTML.indexOf("../../") > -1){
						xhttp.open("GET", "../../php/autoLogIn.php?email=" + window.localStorage.getItem("email") + "&salt=" + window.localStorage.getItem("salt"), true);
					}
					else{
						xhttp.open("GET", "../php/autoLogIn.php?email=" + window.localStorage.getItem("email") + "&salt=" + window.localStorage.getItem("salt"), true);
					}
					xhttp.send();
				}
			}
			
			function logOut(){
				//clear the locally save log in credentials and log out
				window.localStorage.removeItem("email");
				window.localStorage.removeItem("salt");
				window.location = window.location;
			}











			
			var shownManagerRequestSiteNames = [];
			managerRequestsPaused = false;
			managerRequestsQueuing = true;
			function queueManagerRequests(){
				if(window.localStorage.getItem("email") === null || window.localStorage.getItem("salt") === null){
					setTimeout(queueManagerRequests, 1000);
					return false;
				}
				
				var url = "../php/getManagerRequests.php?email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt");
				if($("h1").eq(0)[0].innerHTML == "Caterpillars Count!"){
					url = "php/getManagerRequests.php?email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt");
				}
				else if($("h1").eq(0)[0].innerHTML.indexOf("../../") > -1){
					url = "../../php/getManagerRequests.php?email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt");
				}
				$.get(url, function(data){
					//success
					if(data.indexOf("true|") == 0){
						var managerRequests = JSON.parse(data.replace("true|", ""));
						for(var i = 0; i < managerRequests.length; i++){
							//managerRequests[i];
							if(shownManagerRequestSiteNames.indexOf(managerRequests[i]["siteName"]) == -1){
								shownManagerRequestSiteNames[shownManagerRequestSiteNames.length] = managerRequests[i]["siteName"];
								queueNotice("managerRequest", managerRequests[i]["requester"] + " wants you to become a manager for the <a href=\"https://maps.google.com/?q=" + managerRequests[i]["siteCoordinates"].replace(/ /g, "") + "\" target=\"_blank\">\"" + managerRequests[i]["siteName"] + "\" site in " + managerRequests[i]["siteRegion"] + "</a>.<span style=\"display:none;\">" + managerRequests[i]["id"] + "</span>");
							}
						}
					}
				})
				.always(function() {
					//complete
					if(managerRequestsPaused){
						managerRequestsQueuing = false;
					}
					else{
						if(!HIGH_TRAFFIC_MODE){
							setTimeout(queueManagerRequests, 30000);
						}
					}
				});
			}

			function pauseManagerRequests(){
				managerRequestsPaused = true;
			}

			function resumeManagerRequests(){
				managerRequestsPaused = false;
				if(!managerRequestsQueuing){
					managerRequestsQueuing = true;
					queueManagerRequests();
				}
			}
			
			function respondToManagerRequest(response){
				var path = "../";
				if($("h1").eq(0)[0].innerHTML == "Caterpillars Count!"){
					path = "";
				}
				else if($("h1").eq(0)[0].innerHTML.indexOf("../../") > -1){
					path = "../../";
				}
				$.get(path + "php/respondToManagerRequest.php?managerRequestID=" + Number($("#managerRequestMessage span:last-of-type").eq(0)[0].innerHTML) + "&response=" + response + "&email=" + encodeURIComponent(window.localStorage.getItem("email")) + "&salt=" + window.localStorage.getItem("salt"), function(data){
					//success
					if(data.indexOf("true|") == 0){
						if(response == "approve"){
							queueNotice("confirmation", "You are now a manager of the \"" + data.replace("true|", "") + "\" site! You may visit your <a href='" + path + "manageMySites'>Manage My Sites</a> page when you're ready to explore what you can now do with this site.");
						}
					}
					else{
						queueNotice("error", data.replace("false|", ""));
					}
				})
				.fail(function(){
					//error
					queueNotice("error", "Your request to deny a site manager request did not process. You may have a weak internet connection, or our servers might be busy. Please try again.");
				});
			}
			
			
			
			
			
			
			
			
			
			
			
			
			
			var animatingMenu = false;
			
			$(document).ready(function(){
				showLoggedInNav();
				setHeaderCollapse();
				showScrollAnimationElements();
				optimizeVideoSize();
				queueManagerRequests();
				
			});
			
			$(window).scroll(function(){
				setHeaderCollapse();
				showScrollAnimationElements();
			});
			
			var lastWindowWidth = $(window).width();
			$(window).resize(function(){
				setHeaderCollapse();
				if((lastWindowWidth > 900 && $(window).width() <= 900) || (lastWindowWidth <= 900 && $(window).width() > 900)){
					lastWindowWidth = $(window).width();
					resetMenu(true);
				}
				
				optimizeVideoSize();
			});
			
			function loadBackgroundImage(element, backgroundImageURL){
				element = $(element)[0];
				var bgImg = new Image();
				bgImg.onload = function(){
					element.style.backgroundImage = 'url(' + backgroundImageURL + ')';
					$(element).stop().fadeIn(700);
					
				};
				bgImg.src = backgroundImageURL;
			}
			
			function optimizeVideoSize(){
                		$(".video").css({height:$(".video").width() * .564});
        		}
			
			var headerCollapsed = false;
			function setHeaderCollapse(){
				if($(window).width() <= 900){
					headerCollapsed = true;
					$("header").stop().animate({backgroundColor:"#333", paddingTop:"0px", paddingBottom:"0px"});
					return true;
				}
				
				if($(window).scrollTop() > 100 && !headerCollapsed){
					headerCollapsed = true;
					$("header").stop().animate({backgroundColor:"#333", paddingTop:"0px", paddingBottom:"0px"});
					$("nav>ul>li>ul").stop().animate({backgroundColor:"#222", opacity:"1"});
					$("nav>ul>li>ul>li").stop().animate({borderColor:"transparent"});
				}
				else if($(window).scrollTop() <= 100 && headerCollapsed){
					headerCollapsed = false;
					$("header").stop().animate({backgroundColor:"transparent", paddingTop:"25px", paddingBottom:"25px"});
					$("nav>ul>li>ul").stop().animate({backgroundColor:"transparent", opacity:".9"});
					$("nav>ul>li>ul>li").stop().animate({borderColor:"rgba(255,255,255,.1)"});
				}
			}
			
			function showScrollAnimationElements(){
				var scrollAnimationElements = $(".scrollAnimationElement");
				for(var i = 0; i < scrollAnimationElements.length; i++){
					var element = scrollAnimationElements[i];
					
					var elementTopLine = $(element).offset().top;
					var elementBottomLine = (elementTopLine + element.clientHeight);
					var topLine = $(window).scrollTop();
					var bottomLine = (topLine + window.innerHeight);
					
					if(((elementTopLine - 100) > topLine && elementTopLine < bottomLine) || ((elementBottomLine + 50) < bottomLine && elementBottomLine > topLine) || (elementTopLine < topLine && elementBottomLine > bottomLine)){
						if(element.className.indexOf("fadeInOnScroll") > -1){
							var delay = 0;
							if($(window).width() > 1018 && element.className.indexOf("delay") > -1){
								var startingAtDelay = element.className.substring(element.className.indexOf("delay"))  + " ";
								delay = Number(startingAtDelay.substring(5, startingAtDelay.indexOf(" ")));
							}
							$(element).delay(delay).animate({opacity:"1"}, 1000);
						}
					}
				}
			}
			
			function scrollToPanel(number){
				scrollToElement($(".panel").eq(number - 1))
			}
			
			function scrollToElement(element){
				$('html, body').animate({
					scrollTop: $(element).offset().top - 51
				}, 500);
			}
			
			function accessSubMenu(parentElement){
				if(animatingMenu){
					return false;
				}
				animatingMenu = true;
				
				if($(window).width() > 900){
					//close clicked if open and return
					if($(parentElement).find("ul").eq(0)[0].style.display == "block"){
						$($(parentElement).find("ul")).stop().fadeOut(300, function(){
							animatingMenu = false;
						});
						return true;
					}
					
					//otherwise, close open if any
					var submenus = $("nav>ul>li>ul");
					var wait = false;
					for(var i = 0; i < submenus.length; i++){
						if($(submenus[i]) != $(parentElement) && $(submenus[i])[0].style.display == "block"){
							$(submenus[i]).stop().fadeOut(300);
							wait = true;
						}
					}
					
					//and open clicked, after waiting for any to finish closing
					if(wait){
						setTimeout(function(){
							$(parentElement).find("ul").stop().eq(0).fadeIn(300, function(){
								animatingMenu = false;
							});
						}, 300);
					}
					else{
						$(parentElement).find("ul").stop().eq(0).fadeIn(300, function(){
							animatingMenu = false;
						});
					}
				}
				else{
					$("nav span").stop().fadeOut(200);
					
					var mainMenuElements = $("nav>ul>li");
					for(var i = 0; i < mainMenuElements.length; i++){
						$(mainMenuElements[i]).stop().animate({padding:"0px"}, 200);
						if(mainMenuElements[i] != parentElement){
							$(mainMenuElements[i]).animate({maxHeight:"0px"}, 200);
						}
					}
					
					setTimeout(function(){
						$(parentElement).find("ul>li").css({padding:"20px"});
						$(parentElement).find("ul").stop().css({display:"block", maxHeight:"0px"});
						$(parentElement).find("ul").animate({maxHeight:"10000px"}, 3500);
						
						$("#navBack").stop().fadeIn(300, function(){
							animatingMenu = false;
						});
					}, 200);
				}
			}
			
			function closeSubmenu(submenuElement){
				if(animatingMenu){
					return false;
				}
				animatingMenu = true;
				
				$(submenuElement).stop().fadeOut(300, function(){
					animatingMenu = false;
				});
			}
			
			function resetMenu(forceMenuClosed){
				if(animatingMenu){
					return false;
				}
				animatingMenu = true;
				
				if($(window).width() > 900){
					$("nav").css({display:"", marginRight:"", overflow:"", maxHeight:""});
					$("nav span").css({display:""});
					$("#navBack").css({display:""});
					$("nav>ul>li").css({maxHeight:"", display:"", padding:""});
					$("nav>ul>li>ul").css({display:"", maxHeight:"", overflow:""});
					$("nav>ul>li>ul>li").css({padding:""});
					
					animatingMenu = false;
				}
				else{
					if(forceMenuClosed){
						$("nav").stop().css({overflow:"hidden", maxHeight:"0px"});
						$("nav").animate({marginRight:"-20px"});
						$("#navBack").stop().fadeOut(0);
						$("nav").fadeIn(0);
					}
					else{
						$("nav").stop().animate({marginRight:"-20px"})
						$("#navBack").stop().fadeOut(300);
						$("nav").fadeIn(200);
					}
					
					
					var submenuElements = $("nav>ul>li>ul");
					for(var i = 0; i < submenuElements.length; i++){
						$(submenuElements[i])[0].style.maxHeight = $(submenuElements[i])[0].clientHeight;
						$(submenuElements[i])[0].style.overflow = "hidden";
					}
					
					if(forceMenuClosed){
						$("nav>ul>li>ul").stop().css({maxHeight:"0px"});
						var submenuElements = $("nav>ul>li>ul");
						for(var i = 0; i < submenuElements.length; i++){
							$(submenuElements[i])[0].style.display = "";
						}
						
						$("nav span").stop().fadeIn(0);
						
						var mainMenuListElements = $("nav>ul>li");
						for(var i = 0; i < mainMenuListElements.length; i++){
							$(mainMenuListElements[i])[0].style.maxHeight = "";
							$(mainMenuListElements[i])[0].style.display = "";
							$(mainMenuListElements[i])[0].style.padding = "20px 40px";
							var maxHeight = $(mainMenuListElements[i])[0].clientHeight;
							$(mainMenuListElements[i])[0].style.maxHeight = "0px";
							$(mainMenuListElements[i])[0].style.padding = "0px";
							$(mainMenuListElements[i]).stop().css({padding:"20px 40px", maxHeight:""});
							animatingMenu = false;
						}
					}
					else{
						$("nav>ul>li>ul").stop().animate({maxHeight:"0px"}, 300, "swing", function(){
							var submenuElements = $("nav>ul>li>ul");
							for(var i = 0; i < submenuElements.length; i++){
								$(submenuElements[i])[0].style.display = "";
							}
						
							$("nav span").stop().fadeIn(200);
						
							var mainMenuListElements = $("nav>ul>li");
							for(var i = 0; i < mainMenuListElements.length; i++){
								$(mainMenuListElements[i])[0].style.maxHeight = "";
								$(mainMenuListElements[i])[0].style.display = "";
								$(mainMenuListElements[i])[0].style.padding = "20px 40px";
								var maxHeight = $(mainMenuListElements[i])[0].clientHeight;
								$(mainMenuListElements[i])[0].style.maxHeight = "0px";
								$(mainMenuListElements[i])[0].style.padding = "0px";
								$(mainMenuListElements[i]).stop().animate({padding:"20px 40px", maxHeight:maxHeight}, 300, "swing", function(){
									this.style.maxHeight = "";
									animatingMenu = false;
								});
							}
						});
					}
				}
			}
			
			
			function toggleNav(){
				if(animatingMenu){
					return false;
				}
				
				if($("nav").eq(0)[0].style.maxHeight == "0px" || $("nav").eq(0)[0].style.maxHeight == ""){
					$("nav").eq(0).stop().animate({maxHeight:"10000px"}, 3500);
				}
				else{
					$("nav").eq(0).stop()[0].style.maxHeight = $("nav").eq(0)[0].clientHeight;
					$("#navBack").stop().fadeOut(200);
					$("nav").eq(0).animate({maxHeight:"0px"}, 300, "swing", function(){
						$("nav").css({marginRight:"-20px", display:"block"});
						$("nav>ul>li>ul").stop().css({overflow:"hidden", maxHeight:"0px", display:""});
						$("nav span").stop().css({display:"block"});
						$("nav>ul>li").css({maxHeight:"", display:"", padding:"20px 40px", maxHeight:""});
					});
				}
			}
