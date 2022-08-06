var selectColor = "rgb(204, 204, 204)";//global color of selected rows
//add information for phones about the length of a touch to enable something similar to a double click
var longTouch=false;
var timeout=null;
var enableTouch=true;
//element.value responds to user input
//element.getAttribute("value") should be used otherwise

//posts the row that the user selects to open when they double click
function rowSetup(clkRow){
	tableName=childTable;
	currentID=clkRow.id;
	submitForm();
}
//returns to the parent table
function back(){
    tableName=parentTable;
    currentID=grandparentID;
    submitForm();
}
//creates a new row in table i
function newRow(i){
    //use javascript to insert a new row into the table with id=0 without refreshing the page
    //then, display inputs on this row
    //gets the table that contains the rows
    var table=document.getElementById("navTable"+i);
    //get a template row(the column title row) that will provide the framework of the new row
    var refRows=table.getElementsByTagName('tr');
    //make a new row
    var newRow=document.createElement('tr');
    //if we got a row
    if(refRows.length){
        //get the header cells in the row
        //this line generates an error message for some reason at the bottom
        var refCells=refRows[0].getElementsByTagName('th');
        //cycle through the header cells and add a cell in the new row for each one
        for(var j=0; j<refCells.length; j++){
            //make a new cell
            var newCell=document.createElement('td');
            //set the datatype of the new cell to the datatype of the old cell
            newCell.setAttribute("name", refCells[j].getAttribute("name"));
            //add the new cell to the new row
            newRow.appendChild(newCell);
        }
        //newRow.style.backgroundColor=selectColor;
        //set the row's id to zero
        newRow.setAttribute("id", 0);
        //add a click event
        newRow.setAttribute("onclick", "rowSelect(this);");
        //add the new row to the table
        table.appendChild(newRow);
        //select the row
        rowSelect(newRow);
        //edit the new row
        edit(i);
    }
}
//puts input fields in table i on row id
function displayInput(i, id){
    //get the row in table i with row id
    var row=getRow(i, id);
    if(row!==null){
        //add input boxes on the row
        //get an array of the cells in the row
        var cells=row.getElementsByTagName('td');
        //cycle through the cells of the row and add input boxes
        for(var j=0; j<cells.length; j++){
            //create an input boxp
            var inputBox = document.createElement("textarea");
            //set the input to text type
            inputBox.setAttribute("type", "text");
            //set a mex length of the input
            inputBox.setAttribute("maxlength", 1000);
            //see if the cell contains an image
            //var image=cells[j].getElementsByTagName('img');
            //if(image.length){
            //alert(cells[j].getAttribute("name"));
            //see if the cell is an image
            if(cells[j].getAttribute("name")=="img"){
                //if it is an image, see if the image already has a link
                var image=cells[j].getElementsByTagName('img');
                //get the source of the image
                var imageSource="";
                if(image.length){
                    imageSource=image[0].getAttribute('src');
                }
                //if the cell contained an image, allow them to edit the link
                //set the input box's text to the image link
                //inputBox.setAttribute("value", imageSource);
                inputBox.value=imageSource;
                //make the input box update the image when its text value is changed
                inputBox.setAttribute("onchange", "updateImage(this);");
                //clear out the cell's text with the image
                cells[j].innerHTML='<img src="'+imageSource+'">';
                //update the cell's text
                var paragraph1 = document.createElement("p");
                paragraph1.innerText="Link an image from the internet:";
                cells[j].appendChild(paragraph1);
                //add the input box to the row
                cells[j].appendChild(inputBox);
                
                //allow the user to upload an image from their computer
                var paragraph2 = document.createElement("p");
                paragraph2.innerText="Upload an image from your computer:";
                cells[j].appendChild(paragraph2);
                //create an input box that allows the file upload
                var inputFile = document.createElement("input");
                //set the input properties
                inputFile.setAttribute("type", "file");
                //since the javascript will now compress the image and submit it as a base64 string, the input file is no longer directly tied to the form
                //inputFile.setAttribute("form", "rowForm");
                inputFile.setAttribute("name", "fileToUpload");
                inputFile.setAttribute("id", "fileToUpload");
                inputFile.setAttribute("style", "width:auto;");
                inputFile.setAttribute("onchange", "updateFile(this);");
                //add the file input to the form
                cells[j].appendChild(inputFile);
            }
            else{
                //if the cell doesn't contain an image(text)
                //set the input box's text to the text value of the cell
                //inputBox.setAttribute("value", cells[j].innerText);
                inputBox.value=cells[j].innerText;
                //clear the cell's text
                cells[j].innerText="";
                //add the input box to the row
                cells[j].appendChild(inputBox);
            }
        }
        //change the status to saving
        editing=false;
        //remove the double click event from the row
        row.setAttribute("ondblclick", "");
        //disable longclick events
        enableTouch=false;
        //update the button text
        document.getElementById("editBtn"+i).innerText="Save Selected Rows";
    }
}
//updates the sister image of the input box when the input box's value is changed
function updateImage(input){
    //get the parent element
    var cell=input.parentElement;
    //get the images in the parent element
    var images=cell.getElementsByTagName('img');
    //update the image source with the input text field if there are any
    if(images.length){
        images[0].setAttribute('src', input.value);
    }
}
//updates the sister image of the input file when the input file's value is changed
function updateFile(input){
    //get the parent element
    var cell=input.parentElement;
    //get the images in the parent element
    var images=cell.getElementsByTagName('img');
    //update the image source with the input text field if there are any and if there is an uploaded file
    if(images.length&&input.files.length){
        //create an image reader
        var reader = new FileReader();
        //update the image when it loads
        reader.onload = function (e) {
            images[0].setAttribute('src', e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}
//handles the input boxes for editing when the user clicks the edit/save button
function edit(i){
    if(editing){
        //we will display the edit boxes now
        var tblRows = getSelected(i);//get the selected rows
        //see if there are any rows selected
        if(tblRows.length){
            //display the input boxes
            displayInput(i, tblRows[0].getAttribute('id'));
        }
    }
    //if it is time to save the changes, save them
    else{
        saveRow(i);
    }
}
//posts all row ids selected in an array called (var name)Info
function submitSelectedRows(name, i){
	var tblRows = getSelected(i);//get the selected rows
	for (var j=0; j<tblRows.length; j++){//cycle through the selected rows
		addToRowForm("rowForm", name+"Info"+i+"[]", tblRows[j].id);
	}
	submitForm();
}
function saveRow(i){//posts the entries when the user clicks the save button
	var inputBoxes = document.getElementById('navTable'+i).getElementsByTagName("textarea");//get an array of input boxes in the table to be updated
	addToRowForm("rowForm", "editInfo"+i+"[]", inputBoxes[0].parentElement.parentElement.id);//setup to post the row id
	saveInputs(inputBoxes, i);
	saveInputs(document.getElementById('navTable'+i).getElementsByTagName("input"), i);
	//submit the form
	submitForm();
}
function saveInputs(inputBoxes, i){
    for(j=0; j<inputBoxes.length; j++){
	    //see whether the input is a file or text
	    if(inputBoxes[j].getAttribute("type")=="text"){
	        //setup to post the entered text values in order
		    addToRowForm("rowForm", "saveInfo"+i+"[]", inputBoxes[j].value);
	    }
	    //see if the input is a file and there is a file selected
	    else if((inputBoxes[j].getAttribute("type")=="file")&&(inputBoxes[j].files.length)){
	        //if the user is trying to upload a file, put the image in a canvas object
	        //get the parent element
            var cell=inputBoxes[j].parentElement;
            //get the images in the parent element
            var images=cell.getElementsByTagName('img');
            //if there is an image, put it in a canvas object
            if(images.length){
                //make a canvas
                var canvas=document.createElement("canvas");
                //get the canvas content
                var ctx=canvas.getContext("2d");
                //make the canvas the desired size of the image
                canvas.height=images[0].height;
                canvas.width=images[0].width;
                //get the scale factor(ratio of desired height to actual image height)
                var scaleFactor = images[0].height/images[0].naturalHeight;
                //alert(images[0].naturalWidth+", "+images[0].naturalHeight);
                //scale the canvas
                ctx.scale(scaleFactor, scaleFactor);
                //draw the image in the canvas
                ctx.drawImage(images[0], 0, 0);
                //convert the canvas into a png image file
                var dataURL=canvas.toDataURL("image/png");
                //set the image to this file
                images[0].src=dataURL;
                //convert this into base64 code that can be submitted in a form
                var base64=dataURL.replace(/^data:image\/(png|jpg);base64,/, "");
                //for testing purposes, convert this base64 code back into an image just to see if the base64 works
                /*var imageTest=document.createElement("img");
                imageTest.src='data:image/png;base64,'+base64;//dataURL;
                cell.appendChild(imageTest);
                alert(base64);*/
                //add the base64 string to the form
                addToRowForm("rowForm", "image"+i, base64);
            }
	    }
	}
}
//handles when a row is clicked once
//if the row is unselected, this function selects the row
//if the row is selected, this function unselects the row
//all other rows are unselected
function rowSelect(clkRow){
    //get the parent element
    table=clkRow.parentElement;
    //get all of the rows in the table
    rows=table.getElementsByTagName('tr');
    //cycle through the rows
    for(var j=0; j<rows.length; j++){
        //if the row is selected or unequal to the row clicked, unselect it
        if(/*(rows[j].getAttribute("id")!=clkRow.getAttribute("id"))*/(rows[j]!=clkRow)||(rows[j].style.backgroundColor==selectColor)){//if the row is selected
		    rows[j].style.backgroundColor="";//set the backgroundColor to default css
    	}
    	else{//if the row is unselected, set it to the selected colors
    		rows[j].style.backgroundColor=selectColor;
    	}
    }
}
function getSelected(i){//returns the selected rows in an array
	var tblRows = [];
	var table = document.getElementById("navTable"+i);//gets the table that contains the rows
	for (var j=0; j<table.rows.length; j++) {//cycle through the rows and see which ones are selected
		if(table.rows[j].style.backgroundColor== selectColor){//if the row is selected
			tblRows.push(table.rows[j]);
		}
	}
	return tblRows;
}
//get the row in table i with row id
function getRow(i, id){
	var table = document.getElementById("navTable"+i);//gets the table that contains the rows
	for (var j=0; j<table.rows.length; j++) {//cycle through the rows and see which ones are selected
		if(table.rows[j].getAttribute('id')==id){//if this is the row
		    //return the row
			return table.rows[j];
		}
	}
	//return null on failure
	return null;
}
function addToRowForm(formName, name, value){//adds hidden inputs of the specified name and value to rowForm
	var parent = document.getElementById(formName);//get the form that will post the data
	var node = document.createElement("input");
	node.name = name;
	node.type = "hidden";
	node.value = value;
	parent.appendChild(node);
}
//submits rowForm with the proper table and id in its link
function submitForm(){
    //alert(admin);
    var form = document.getElementById("rowForm");
    //example
    //http://rateteacher.000webhostapp.com/index.php?table=Teachers&id=1
    form.action="index.php?table="+tableName+"&id="+currentID;
    //post the admin password if you are logged in
    if(admin!==""){
        form.action+="&admin="+admin;
    }
    form.submit();
}