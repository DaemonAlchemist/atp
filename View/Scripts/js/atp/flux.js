var Flux = Flux || {};

Flux.getUpdates = function(options) {
	var jobId = options.jobId;
	var callback = options.callback;
	
	$.ajax({
		"url": "<?=$this->baseUrl("flux/job-status")?>?options=" + JSON.stringify(options),
		"success" : function(data) {
			var parsedData = null;
			try {
				parsedData = $.parseJSON(data);
			} catch(e) {
				alert("Error parsing JSON data: " + data);
			}
			if(parsedData.error != null) {
				alert(parsedData.error + encodeURIComponent(JSON.stringify(options)));
				return;
			}
			callback(parsedData);
		},
		"error" : function(jqXHR, textStatus, errorThrown ) {
			alert("Flux update error: " + errorThrown + ":" + JSON.stringify(options));
		}
	});
}
