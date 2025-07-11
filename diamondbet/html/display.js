function activate_input(obj){
	var value = obj.value;
	if (obj.style.color != "black")
	{
		obj.value = "";
		obj.style.color = "black";
	}
}
function deactivate_input(obj, text){
	var value = obj.value;
	if (value == "")
	{
		obj.value = text;
		obj.style.color = "gray";
	}
}
function submit_input(obj_name)
{
	obj = document.getElementById(obj_name);
	if (obj.value == "")
		return false;
	else
		return true;
}

function submitForm(id){
	document.getElementById(id).submit();
}