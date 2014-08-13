// form for url input
var form = document.querySelector('.form').children[0];

form.addEventListener( 'submit', getLink, false );


function getLink( event )
{
	event.preventDefault();

	document.querySelector( '.loading' ).classList.remove( 'hidden' );
	document.querySelector( '.allVideo' ).innerHTML = '';
	
	var value = document.querySelector( '.form input[name="url"]' ).value;

	var url = "fetchYoutube.php?url=" + value;
	
	//console.log( url );
	
	var request = new XMLHttpRequest;
	request.open( 'GET', url );
	request.send( null );

	var output = '';

	request.onreadystatechange = function()
		{
			if( request.readyState === 4 && request.status == 200 )
			{
				var contentType = "application/json";
				
				//console.log( request.response );
				if( request.getResponseHeader( 'Content-Type' ) !== contentType )
				{
					commonError();
					
					return;
				}
				
				var data;
				try
				{
					data = JSON.parse( request.response );
				}
				catch( err )
				{
					commonError();
					
					return;
				}
				
				//console.log(data);
				if( Object.keys( data )[0] === '0' )
				{
					// playlist

					for( var each in data )
					{
						if( data.hasOwnProperty( each ) )
						{
							display( data[each] );
						}
					}
				}
				else if( 'error' in data )
				{
					// error
					
					displayError( data.error );
				}
				else
				{
					// single video

					display( data );
				}

				render();
				
			}
		}
	
	request.onerror = commonError;
	
	request.ontimeout = function()
		{
			var msg = "Request timed out. Try again";
			displayError( msg );
			
			render();
		}
	
	function commonError()
	{
		var msg = "Something went wrong. Try again";
		displayError( msg );
			
		render();
	}
	
	function render()
	{
		document.querySelector( '.loading' ).classList.add( 'hidden' );

		document.querySelector('.allVideo').innerHTML = output;
		output = '';
	}
	
	function displayError( msg )
	{
		output = '<div class="error">'+msg+'</div>';
	}
	
	function display( object )
	{

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
	}
}