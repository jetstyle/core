var image_i = 1;

function addImageInput()	{
	var ic = document.getElementById('image_control');
	
	if(ic)	{
		var row = ic.insertRow(image_i);
		image_i++;
		var cell = row.insertCell(0);
		cell.innerHTML = "Новое изображение " + image_i;
		cell = row.insertCell(1);
		cell.innerHTML = '<input type="file" name="file_new-' + image_i + '"> - изображение<br /><b>Порядок</b><br /><input type="text" name="order_new-'+image_i+'" class="w100" value="">';
	}
}