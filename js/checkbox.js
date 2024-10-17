      			function toggleCheckbox(checkbox){
				if(checkboxIsChecked(checkbox)){
					uncheckCheckbox(checkbox);
				}
				else{
					checkCheckbox(checkbox);
				}
			}
			
			function checkCheckbox(checkbox){
			        $(checkbox).addClass("checked").trigger("checked");
			}
			
			function uncheckCheckbox(checkbox){
			        $(checkbox).removeClass("checked").trigger("unchecked");
			}
			
			function checkboxIsChecked(checkbox){
				return $(checkbox).hasClass("checked");
			}
