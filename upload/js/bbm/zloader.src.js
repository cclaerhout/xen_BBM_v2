!function($, window, document, undefined)
{
	var xenBbmExtLoader = XenForo.ExtLoader;

	XenForo.ExtLoader = function(data, success, failure){
		if(data.js !== undefined){
			var jsToLoad = [],
				bbmJsFirst = [],
				bbmJsLast = [],
				bbmJsFirstTemp = [],
				otherJs = [],
				regex = /bbm_([\dz])_.*?\.js/,
				zLoaderEnabled = false;

			for (var i=0;i<10;i++){
				bbmJsFirstTemp[i] = [];
			}

			$.each(data.js, function(i, url){
				if (regex.test(url)){
					zLoaderEnabled = true;
					var match = url.match(regex);
					
					if(match[1] == 'z'){
						bbmJsLast.push(url);
					}else{
						bbmJsFirstTemp[parseInt(match[1])].push(url);
					}
				}else{
					otherJs.push(url);
				}
			});
			
			if(zLoaderEnabled){
				$.each(bbmJsFirstTemp, function(i, arr){
					$.each(arr, function(i, url){
						bbmJsFirst.push(url);
					});
				});
	
				$.each([bbmJsFirst, otherJs, bbmJsLast], function(i, arr){
					$.each(arr, function(i, url){
						jsToLoad.push(url);				
					});
				});
				
				data.js = jsToLoad;
			}
		}

		return new xenBbmExtLoader(data, success, failure);
	}
}
(jQuery, this, document);