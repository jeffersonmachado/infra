function vh_sceneLoaded(){
	d = new Date();
	mins = d.getMinutes();
	hours = d.getHours();
	if(hours < 12){
		sayAudio('goodMorning');
	}else if (hours < 17){
		sayAudio('goodAfternoon');
	}else{
		sayAudio('goodEvening');
    }
      //the scene begins playing, add actions here
	//sayText('...Welcome to the virtual host text to speech...',1,1,3);
alert("OK");
}
