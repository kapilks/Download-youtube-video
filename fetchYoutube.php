<?php
	

	/*
	 *
	 *  This script fetches the link for youtube video files to download
	 *	Pass link to youtube video with 'url' as parameter
	 *
	 *	fetchYoutube.php?url=https://www.youtube.com/watch?v=2gLq4Ze0Jq4
	 *  Also have playlist support to link from video in playlist
	 *  
	 *  It returns the link in json format
	 *  Also gives the quality of the video and size of video
	 *	 
	 *	{1 : { link:..., quality:..., size:...},
	 *	 2 : { link:..., quality:..., size:...},... }
	 *
	 *	It does not fetches link link to copyright videos
	 *
	 */


	include 'httpRequest.php';
	
	if( isset( $_GET['url'] ) )
	{
		/*
		 *	When any url is passed
		 */

		// Implementing Serevr-Sent Event
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');

		$maxTime = 20 * 60; // 20 min
		ini_set ( 'max_execution_time', $maxTime );
		
		$url = $_GET['url'];
		$url = urldecode( $url );
		
		if( strpos( $url, 'http' ) === false )
		{
			// making in format http://xyz....
			$url = "http://$url";
		}
		
		if( strpos( $url, 'www' ) === false )
		{
			// making in format http://www.xyz....
			$totalReplacement = 1;
			$url = str_replace( 'http://', 'http://www.', $url, $totalReplacement );
		}

		if( isset($_GET['list'] ) )
		{
			$url = "$url&list=".$_GET['list'];
		}
		
		$youtubeFormat = '#^https?://www.youtube.com/#';
		preg_match( $youtubeFormat, $url,$matches );
		
		if( preg_match( $youtubeFormat, $url ) !== 1 )
		{
			errorMessage( "Not a valid Youtube link" );
		}


		if( strpos( $url, 'watch?v=') !== false )
		{
			if( strpos( $url, 'list=') !== false && isset( $_GET['all'] ) && $_GET['all'] === 'true' )
			{
				/*
				 *	All link from playlist
				 */

				getPlaylistLinks( $url );
			}
			else
			{
				/*
				 *	Get only single video specified by id
				 */

				getIdLinks( $url );
			}

		}
		elseif( strpos( $url, 'list=') !== false )
		{
			/*
			 *	All link from playlist
			 */

			getPlaylistLinks( $url );
		}
		else
		{
			/*
			 *	Not valid url
			 */

			errorMessage( "This is not a valid Youtube link" );
		}
	}


	


	function getPlaylistLinks( $url )
	{
		/*
		 *	Start fetching from playlist
		 */

		$id = getPlaylistId( $url );

		$allVideoIds = fetchIdFromPlaylist( $id );

		foreach ( $allVideoIds as $key => $value ) 
		{
			$youtubeUrl = "http://www.youtube.com/watch?v=$value";
		
			getIdLinks( $youtubeUrl );
		}
	}

	function getIdLinks( $url )
	{
		/*
		 *	Start fetching grom id
		 */

		$id = getId( $url );
		$jsonString = fetchLinksFromId( $id );

		echo "event: singleLink\n";
		echo "data: $jsonString\n\n";

		// Flushes output as soon as it get links for a single video
		ob_flush();
		flush();
	}

	function getId( $url )
	{
		preg_match( '#v=([^&]*)#', $url, $matches );
		
		return $matches[1];
	}

	function getPlaylistId( $url )
	{
		preg_match( '#list=([^&]*)#', $url, $matches );

		return $matches[1];
	}

	function fetchLinksFromId( $videoId )
	{
		/*
		 *	Return links to specified video id in JSON string format
		 */

		$requestUrl = "http://youtube.com/get_video_info?video_id=$videoId";

		$request = new HTTPRequest();
		
		if( $request->open( 'GET', $requestUrl ) || $request->send() )
		{
			errorMessage( "Something went wrong try again later" );
		}

		$response = $request->getResponseBody();

		parse_str( $response, $parseResponse );
		

		if( isset( $parseResponse['status'] ) && $parseResponse['status'] !== 'ok' )
		{
			errorMessage( $parseResponse['reason'] );
		}

		$assocItags = getItags( $parseResponse['fmt_list'] );
		
		$thumbnail = '';
		if( isset( $parseResponse['iurlmaxres'] ) )
		{
			$thumbnail = $parseResponse['iurlmaxres'];
		}
		elseif( isset( $parseResponse['iurl'] ) )
		{
			$thumbnail = $parseResponse['iurl'];
		}
		elseif( isset( $parseResponse['iurlhq'] ) )
		{
			$thumbnail = $parseResponse['iurlhq'];
		}
		elseif( isset( $parseResponse['iurlsd'] ) )
		{
			$thumbnail = $parseResponse['iurlsd'];
		}
		elseif( isset( $parseResponse['iurlmq'] ) )
		{
			$thumbnail = $parseResponse['iurlmq'];
		}
	

		$author = '';
		if( isset( $parseResponse['author'] ) )
		{
			$author = $parseResponse['author'];
		}

		$title = '';
		if( isset( $parseResponse['title'] ) )
		{
			$title = $parseResponse['title'];
		}

		$duration = 0;
		if( isset( $parseResponse['length_seconds'] ) )
		{
			$duration = $parseResponse['length_seconds'];
			$duration = timeTo( $duration );
		}
		
		/*
		 *  preparing output
		 */

		$output = "{";

		$title 		!== '' && $output = "$output\"title\":\"$title\"";
		$author 	!== '' && $output = "$output, \"author\":\"$author\"";
		$thumbnail 	!== '' && $output = "$output, \"thumbnail\":\"$thumbnail\"";
		
		$output .= ", \"duration\":\"$duration\"";

		if( isset( $parseResponse['url_encoded_fmt_stream_map'] ) )
		{
			$jsonLink = parseVideoUrl( $parseResponse['url_encoded_fmt_stream_map'], $assocItags, $title );
			$output .= ", \"links\":{$jsonLink}";
		}

		$output .= "}";

		return $output;
		 

	}

	function fetchIdFromPlaylist( $playlistId )
	{
		/*
	 	 *	Return all video id from the playlist in an ARRAY
		 */

		$allIds = [];
		$myApiKey = "AIzaSyC1gw4tthomnXU3updw0NDuEkJyYMr4omQ";
		$pageToken = "";

		while( true )
		{
			$matches = [];

			$playlistUrl = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId=$playlistId&key=$myApiKey&pageToken=$pageToken";

			$request = new HTTPRequest();
			if( $request->open( 'GET', $playlistUrl ) )
			{
				errorMessage( "Something went wrong" );
			}
			if( $request->send() )
			{
				errorMessage( "Something went wrong" );
			}

			$response = $request->getResponseBody();

			preg_match_all("#videoId\": \"([^\"]+)#", $response, $matches);
			
			$allIds = array_merge( $allIds, $matches[1] );
			
			////////////////////////////////////////////////////
			// Requesting next page
			$pageToken = [];
			preg_match("#nextPageToken\": \"([^\"]+)#", $response, $pageToken);
			if(isset($pageToken[1]))
			{
				$pageToken = $pageToken[1];
				//var_dump($pageToken);
			}
			else
			{
				//echo "Done";
				break;
			}
		}
		
		return $allIds;
		
	}

	function getItags( $allTags )
	{
		$delimiter = ',';
		$allTags = explode( $delimiter, $allTags );

		$assocItags = array();
		
		foreach ( $allTags as $key => $value )
		{
			$parts = explode( '/', $value );
			$assocItags[$parts[0]] = substr( $parts[1], strpos( $parts[1], 'x' ) + 1 ).'p';
		}

		return $assocItags;
	}

	function parseVideoUrl( $link, $assocItags, $title )
	{		
		$url = explode( ',', $link );
		
		$allLinks = '';

		foreach ( $url as $key => $value )
		{
			// itags
			preg_match( '#itag=([^&]*)#', $value, $matches );

			$itag = $matches[1];
			$quality = $assocItags[$itag];
			
			// type
			preg_match( '#type=([^&]*)#', $value, $matches );

			$type = $matches[1];
			$type = explode( ';', urldecode( $type ) );
			$type = getFileType( $type[0] );
			//var_dump($type);

			// location
			preg_match( '#url=([^&]*)#', $value, $matches );
			$location = $matches[1];
			$location = urldecode( $location );
			//var_dump($location);

			// size
			$request = new HTTPRequest();
			if( $request->open( 'HEAD', $location ) )
			{
				errorMessage( "Something went wrong" );
			}

			if( $request->send() )
			{
				errorMessage( "Something went wrong" );
			}

			$size = $request->getContentLength();

			$size = byteTo( $size, 3 );

			// making JSON
			$location .= "&title=$title";

			$allLinks .= ", \"$key\":{ \"url\":\"$location\", \"type\":\"$type\", \"quality\":\"$quality\", \"size\":\"$size\" }";
		}
		$allLinks = substr( $allLinks, 2 /* Removing first comma */);
		$allLinks = "{".$allLinks."}";
		
		return $allLinks;
	}

	function timeTo( $seconds )
	{
		/*
		 *	Convert x second into 'a hr b min' or 'a min b sec' or 'x sec' format
		 */
		
		$seconds = ( int )$seconds;
		$min = 0;
		$hr = 0;
		$output = '';

		if( $seconds / 3600 >= 1 )
		{
			$hr = (int)( $seconds / 3600 );
			$seconds = $seconds - $hr * 3600;
		}
		if( $seconds / 60 >= 1 )
		{
			$min = (int)( $seconds / 60 );
			$seconds = $seconds - $min * 60;
		}
		
		if( $hr > 0 )
		{
			$output .= "$hr hr ";
		}
		if( $min > 0 )
		{
			$output .= "$min min ";
			if( $hr > 0 )
			{
				return rtrim( $output );
			}
		}
		if( $seconds >= 0 )
		{
			$output .= "$seconds sec";
			return $output;
		}
	}

	function byteTo( $bytes, $decimalPlaces )
	{
		if( $bytes < 0 )
		{
			return "Unknown";
		}

		$unit = 'bytes';
		
		if( $bytes >= 1000 )
		{
			$bytes /= 1024;
			$unit = 'KB';
		}

		if( $bytes >= 1000 )
		{
			$bytes /= 1024;
			$unit = 'MB';
		}

		if( $bytes >= 1000 )
		{
			$bytes /= 1024;
			$unit = 'GB';
		}

		return sprintf('%.'.$decimalPlaces.'f', $bytes ).' '.$unit;
	}


	function getFileType( $mime )
	{
		/*
		 * Get file extension from video mime type
		 */

		$mime = strtolower($mime);
		$type = '';

		switch( $mime )
		{
			case 'video/mp4'	:	$type = 'mp4';
									break;

			case 'video/webm'	:	$type = 'webm';
									break;

			case 'video/3gpp'	:	$type = '3gp';
									break;

			case 'video/x-flv'	:	$type = 'flv';
									break;
		}

		return $type;
	}

	function errorMessage( $error )
	{
		echo "event: myError\n";

		echo "data: $error\n\n";

		exit;
	}
?>