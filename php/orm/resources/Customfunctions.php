<?php

// new function to replace filter_var(,FILTER_VALIDATE_BOOLEAN) which is returning empty strings in php 8
function custom_filter_var_bool($checkvar) {
		// as of php 8 filter_var is returning '' for empty values 
		if (filter_var($checkvar, FILTER_VALIDATE_BOOLEAN)===true) {
           return 1;
		} else {
           return 0;
		}			
	}
	
?>