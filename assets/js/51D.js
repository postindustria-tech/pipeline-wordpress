
    jQuery(document).ready(function($) {

	    window.addEventListener( "load", function() {

		$("input[name='fiftyonedegrees_ga_update_cd_indices']").hide();
		var selected_values = getSelectedListValues();
		localStorage.removeItem('selectedValues');
		localStorage.setItem('selectedValues', selected_values);       
		});
	
		$('.51DPropertiesList select').change(function() {
		
			const selected_values_str = localStorage.getItem('selectedValues');
			var selected_values = selected_values_str.split(',');

			var curr_selected_values = getSelectedListValues();

			if(enabledButton === "enabled" && arrayMatch(curr_selected_values, selected_values) === false) {
				$("input[name='fiftyonedegrees_ga_update_cd_indices']").show();
			}else {
				$("input[name='fiftyonedegrees_ga_update_cd_indices']").hide();
			}

		}).trigger('change');
    });

    var getSelectedListValues = function() {

    var selected_values = new Array();
    var selected_arr =  document.getElementsByTagName('select');
    for(k=0;k< selected_arr.length;k++)
    {
        sel = selected_arr[k];
        if(sel.name.indexOf('51D_') === 0){
            selected_values.push(sel.value);
        }
    }

    return selected_values;

    }

    var arrayMatch = function (arr1, arr2) {

    // Check if the arrays are the same length
    if (arr1.length !== arr2.length) return false;

    // Check if all items exist and are in the same order
    for (var i = 0; i < arr1.length; i++) {

        if (arr1[i] !== arr2[i]) return false;
    }

    return true;
    };