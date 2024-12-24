Welcome inside my project; 
If you are here, that mean that you already know about this project, so I'll just describe how to launch it,

REQUIRMENT: 

1. WINDOWS or WINDOWS with WSL or LINUX
2. php
3. SQL database (I personaly use postgresql)
4. Your brain (yes my code isn't perfect, glhf)
5. A petit good computer, like if you want a lot of data (1000 MAX) with my amd r5 5700x it takes like 8 minutes to load
so yes it's better with a good computer
6. A free connection to dblp !!! If you have some restriction in your network, this code should not be efficient!

HOW TO LAUNCH IT : 

1. you have to changes connectbdd.php with your personnal bdd setup
2. Try the script create_db_and_run 
	- if you use windows, use the .bat
	- if you use linux, use the .sh
3. If the first script doesnt work:
	you have to create the database alone, then use bddexe.sql to create and fill the database
4.then use the second script : MapConferencesExe_[OS].[bat or sh]
	normaly it will start the local serv with the port 8888 so if this port is already used, you have to change the port and open a google chrome page.
	
5.if it doesnt work, you have to lauch a local serv by your own , and then open the index with your web app (chrome, firefox, edge, ...)


HOW TO USE IT : 

You can search a subject with the text input
You can choice how many result you want (more result = more time for loading)
THEN click on "Search" for loading the data

You firstly get a map with every conference of our years (2024 when i create it)
Each point corresponds to a location hosting a conference, where the width represents the number of conferences in the city, and the color corresponds to the highest rank of the conferences.
Then you can navigate in time with the arrows at the bottom
You can move inside the map (tipse: use your keyboard arrows)

If you want to see every conference in the same time, you can click on "Show all sphere", the high and the color depends of the years of the conferences