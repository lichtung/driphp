# Include
This directory store the script that is selectable which is designed for saving performance with less code loading in a runtime if it's not necessary.
As we known, the php engine will read the whole file and them parse the content within it.The smaller the file is,the less time to loan and parse.
So if there is some code will run in particular condition and it will not be loaned frequently, I put it into an individual file.
