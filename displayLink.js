// form for url input
var form = document.querySelector('.form').children[0];

form.addEventListener( 'submit', getLink, false );


function getLink( event )
{
	
	event.preventDefault();

	showLoading();

	document.querySelector( '.allVideo' ).innerHTML = '';
	
	var value = document.querySelector( '.form input[name="url"]' ).value;

	var url = "fetchYoutube.php?url=" + value;
	
	//console.log( url );
	

	var request = new EventSource(url);

	showLoading();

	var timeOutInterval = 3 * 60 * 1000; // 5 min for each single video
	var timer = setTimeout(timeOut, timeOutInterval); 

	var output = '';


	request.addEventListener('singleLink', displaySingleLink);
	request.addEventListener('error', displayErrorDetail);
	request.addEventListener('myError', displayCustomError);

	function displaySingleLink(e)
	{
		clearTimeout(timer);

		display(e.data);

		timer = setTimeout(timeOut, timeOutInterval);
	}


	function displayErrorDetail(e)
	{
		request.close();

		clearTimeout(timer);
		hideLoading();	// For single download

		if(e.readyState != EventSource.CLOSED)
		{
			displayCommonError();
		}
	}


	function displayCustomError(e)
	{
		request.close();

		clearTimeout(timer);

		displayError(e.data);
	}
	
	function timeOut()
	{
		request.close();

		displayError("Server Is Taking Too Much Time To Respond");
	}

	function displayCommonError()
	{
		var msg = "Something went wrong. Try again";
		displayError( msg );
	}
	
	function hideLoading()
	{
		document.querySelector( '.loading' ).classList.add( 'hidden' );
	}

	function showLoading()
	{
		document.querySelector( '.loading' ).classList.remove( 'hidden' );
	}

	function displayError( msg )
	{
		hideLoading();

		var output = '<div class="error">'+msg+'</div>';

		document.querySelector( '.allVideo' ).innerHTML = output;
	}
	
	function display( object )
	{
		console.log(object);

		var output = '';

		output 	+=	 '<div class="eachVideo afterClear">';
		output 	+= 	 	'<div class="head afterClear">'+
							'<div class="thumbnail left">'+
								'<img class="left" src="'+object.thumbnail+'">'+
							'</div>'+
							'<div class="meta left">'+
								'<h3 class="title">'+object.title+'</h3>'+
								'<h3 class="author">'+object.author+'</h3>'+
								'<h3 class="duration">'+object.duration+'</h3>'+
							'</div>'+
						'</div>';

		for( var each in object.links )
		{
			if( object.links.hasOwnProperty( each ) )
			{

				var data = object.links[each];

				output +=	'<div class="eachLink left">'+
								'<p class="type">'+data.type+' Format</p>'+
								'<p class="quality">'+data.quality+'</p>'+
								'<p class="size">'+data.size+'</p>'+
								'<a href="'+data.url+'" target="_blank">Download</a>'+
							'</div>';

			}
		}					
						
		output +=	'</div>';

		// Appending Output
		document.querySelector( '.allVideo' ).innerHTML += output;
	}
}