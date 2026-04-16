var global_startTime = 0;

function sayIt(){
	window.global_startTime = performance.now();
	sayText(myForm.textToSay.value,6,2,4)
}

function vh_audioStarted(){
	if (window.global_startTime === 0) return;
	var milliseconds = performance.now() - window.global_startTime;
	document.getElementById("timeDisplay").innerHTML = Math.floor(milliseconds) + "ms";
}

alert("Aqui");
