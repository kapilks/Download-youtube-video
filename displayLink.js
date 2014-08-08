var form = document.querySelector('.form').children[0];

form.addEventListener( 'submit', getLink, false );


function getLink( event )
{
	event.preventDefault();

	document.querySelector( '.loading' ).classList.remove( 'hidden' );
	document.querySelector('.allVideo').innerHTML = '';
	
	var value = document.querySelector( '.form input[name="url"]' ).value;

	var url = "fetchYoutube.php?url=" + value;
	console.log( url);
	var request = new XMLHttpRequest;
	request.open( 'GET', url );
	request.send( null );

	var output = '';

	request.onreadystatechange = function()
		{
			if( request.readyState === 4 && request.status == 200 )
			{
				console.log( request.response );
				var data = JSON.parse( request.response );
				//console.log(data);
				if( Object.keys( data )[0] === '0' )
				{
					// playlist
					//console.log('playlist');

					for( var each in data )
					{
						if( data.hasOwnProperty( each ) )
						{
							display( data[each] );
							console.log('hi');
						}
					}
				}
				else if( 'error' in data )
				{
					//console.log('error');

					output = '<div class="error">'+data.error+'</div>';
				}
				else
				{
					// single video
					//console.log('single video');

					display( data );
				}

				document.querySelector( '.loading' ).classList.add( 'hidden' );

				document.querySelector('.allVideo').innerHTML = output;
				output = '';
			}
		}

	function display( object )
	{
		console.log(output.length);

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