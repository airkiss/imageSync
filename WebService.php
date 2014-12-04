<?php
class WebService {
	public function PostWebService($url,$postField,$proxy_flag=false)
	{
		$link = curl_init();
		curl_setopt($link,CURLOPT_URL,$url);
		curl_setopt($link,CURLOPT_VERBOSE,0);
		curl_setopt($link,CURLOPT_HEADER,0);
		curl_setopt($link,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($link,CURLOPT_TIMEOUT,5);
		if($proxy_flag)
			curl_setopt($link,CURLOPT_PROXY,"111.235.134.217:80");
		curl_setopt($link,CURLOPT_POST,true);
		curl_setopt($link,CURLOPT_POSTFIELDS,http_build_query($postField));
		$Result = curl_exec($link);
		if(!curl_errno($link))
		{ 
			$info = curl_getinfo($link); 
			//echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . "<BR>"; 
		}
		else
		{ 
			echo 'Curl error: ' . curl_error($link) . "<BR>";
		} 
		curl_close($link);
		unset($link);
		return $Result;
	}

	function GetWebService($url,$proxy_flag=false)
	{
		$link = curl_init();
		curl_setopt($link,CURLOPT_URL,$url);
		curl_setopt($link,CURLOPT_VERBOSE,0);
		curl_setopt($link,CURLOPT_HEADER,0);
		curl_setopt($link,CURLOPT_RETURNTRANSFER,true);
		if($proxy_flag)
			curl_setopt($link,CURLOPT_PROXY,"111.235.134.217:80");
		$Result = curl_exec($link);
		if(!curl_errno($link))
		{ 
			$info = curl_getinfo($link); 
			//echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . "<BR>"; 
		}
		else
		{ 
			echo 'Curl error: ' . curl_error($link) . "<BR>";
		} 
		curl_close($link);
		unset($link);
		return $Result;
	}
}

?>
