<?php

class BBM_Protection_Model_ThreadWatch extends XFCP_BBM_Protection_Model_ThreadWatch
{
	public function sendNotificationToWatchUsersOnReply(array $reply, array $thread = null, array $noAlerts = array())
	{
		if (isset($reply['message']))
		{
			/*
				HIDE IN THREAD/POST EMAIL ALERT
				The argument false of the parsingProtection function must be used here.
			*/
			$reply['message'] = BBM_Protection_Helper_ContentProtection::parsingProtection($reply['message'], false);
		}

		return parent::sendNotificationToWatchUsersOnReply($reply, $thread, $noAlerts);
	}
}