// cuando cargue la ventana

	window.onload = function(){
		// recupera el elemento con id "place_form_caratula", este elemento es el input
		// de tipo file (revisad que tenga esa id con "inspeccionar" en el navegador)
		document.getElementById("place_form_caratula").onchange = function(e) {
	
		// si el fichero no es de los tipos adecuados, no se hacen cambios
		if(!e.target.files[0].name.match(/\. (jpe?g|png|gif)$/i)){
		alert('El tipo del fichero debe ser JPG, PNG o GIF');
	
			// pone de nuevo la imagen original
			document.getElementBylId('preview').src = document.getElementById('actual').src;
	
			e.target.value = ''; // borra el contenido del input
	
			}else{
	
				// si el fichero si es del tipo adecuado
				let reader = new FileReader(); // nuevo objeto FileReader
				reader.readAsDataURL(e.target.files[0]); // lee el fichero
			
				reader.onload = function(){ // cuando esté listo...
				//toma la imagen y le cambia el src
				let image = document.getElementBylId( 'preview' );
				image.src = reader.result;
				}
			}
		}
		// recupera el elemento con id "actor_form_retrato", este elemento es el input
		// de tipo file (revisad que tenga esa id con "inspeccionar" en el navegador)
		document.getElementById("actor_form_retrato").onchange = function(e) {
	
		// si el fichero no es de los tipos adecuados, no se hacen cambios
		if(!e.target.files[0].name.match(/\. (jpe?g|png|gif)$/i)){
		alert('El tipo del fichero debe ser JPG, PNG o GIF');
	
			// pone de nuevo la imagen original
			document.getElementBylId('preview').src = document.getElementById('actual').src;
	
			e.target.value = ''; // borra el contenido del input
	
			}else{
	
				// si el fichero si es del tipo adecuado
				let reader = new FileReader(); // nuevo objeto FileReader
				reader.readAsDataURL(e.target.files[0]); // lee el fichero
			
				reader.onload = function(){ // cuando esté listo...
				//toma la imagen y le cambia el src
				let image = document.getElementBylId( 'preview' );
				image.src = reader.result;
				}
			}
		}
	}