<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
|                                         |
|   [BBM] Bb Codes & Buttons Manager v.2  |
|                                         |
>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>


**********************************
*      Addon Presentation        *
**********************************
This addon will allow you to create some Bb Codes & Buttons and will help you to configure XenForo default Bb Codes.


> Bb Codes Creation
	# Three parsing methods to create your Bb Codes: 
		1) direct replacement with fallbacks (not recommended to deal with options)
		2) Template (callbacks available)
		3) Php callback

	# XenForo original parser options (do not parse nested Bb Codes, do not parse smilies, etc...) and four new parsing options: 
		1) Stop AutoLinking
		2) Parse tag options (To enable them, see here: http://xenforo.com/community/threads/43787)
		3) empty content check (don't parse if content empty)
		4) wrapping tag

	# Parser permissions (who can use the Bb Code)
		This feature is experimental. Retrieve posts datas (needed to get permissions) can not be easily done: the way XenForo parses messages is not post by post but at once by page with all posts information provided also at once. These posts information are used to create a map of the tags and each time a Bb Code is processed, a function tries to locate it on the map. Even if this feature has been tested, be sure to check if this feature is working fine with your forum (try to perform a check in a thread with many posts)

	# View permissions (who can see the Bb Code content)
		A BbCode Content Protection System (BCPS) has been included (you must activate it first and carefully read the disclaimer). This BCPS is needed in some areas of your website to securise the content of your Bb Codes

	# Editor button: configure a button for your Bb Code (css or direct image)

	# Compatible with orphan button: a orphan button is a fake Bb Code that will be used as a pre-defined button. Concretly, it allows to create buttons for the TinyMCE plugins

> Bb Codes Import/Export system (support bulk import/export) - Bulk export utility provided

> Editor layout configuration & Buttons Manager
	# left to right/right to left language configuration
	# create your own editor configuration
	# Configure your editor for mobiles deviced
	# Configure your editor for tablets (requires an extra addon: http://xenforo.com/community/resources/tinymce-fix.1162/)
	# Configure your editor by Controller & View => this will allow you for example to have another layout for the fast reply editor 
	# Configure your editor by forum 

> Configure XenForo default Bb Codes
	# enable/disable them
	# add them a wrapper (callback available - see demo in the extras directory)
	# empty content check (don't parse if content empty)

> Bb Codes available (provided as a demo in the extras directory)
	# highlighter
	# h2 paragraph
	# spoiler
	# protected (uses the view permissions)
	# raw (html) (!experimental! => use the parser permissions)

> Tools for developers to use in callbacks
(will be documentated later)
	

**********************************
*    Installation/Update         *
**********************************
> Automatic  Method
This addon is compatible with the Auto installer of Chris: http://xenforo.com/community/resources/automatic-add-on-installer-and-upgrader.960

> Manual Method
If you don't use it, just upload the CONTENT of the archive upload forum in your forum directory and THEN import the addon xml file

Important
To prevent any errors on your forum, this addon will not be active on the public section if you have on your server the Custom BB Code Manager addon. This system lets you have temporarily both addon installed so you can take your time to migrate from one to another one. For more information, read the red notice on the main page of the administration.


**********************************
*        Configuration           *
**********************************
Configure the addon in 
> ADMIN->HOME->Bb Codes & Buttons Manager (will redirected to all available sections)
> ADMIN->Appearance->Style Properties->BBM Bb Codes (to controle the appearance of some Bb Codes)


**********************************
*           License              *
**********************************
Creative Commons BY 3.0 license (http://creativecommons.org/licenses/by/3.0/)