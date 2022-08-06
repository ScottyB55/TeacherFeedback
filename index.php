<?php
    session_cache_limiter("private_no_expire");
    //start the session
    session_start();
    //prevent back button form resubmission
    header("Cache-Control: no cache");
    
    //redirect the user to the https link if needed
    /*if(!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on"){
		//Tell the browser to redirect to the HTTPS URL.
		header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		//Prevent the rest of the script from executing.
		exit;
	}*/
?>
<!DOCTYPE html>
<html>
	<head>
	    <meta charset="UTF-8">
        <meta name="description" content="Review teachers and schools! Communication between students and teachers!">
        <meta name="keywords" content="Feedback,Review,Teacher,School,TeacherFeedback">
        <meta name="author" content="Scott Burnett">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
	    <link rel="stylesheet" href="style.css">
		<script type="text/javascript" src="library.js"></script>
	</head>
	<body onload="removeLogo()">
	<h2>TeacherFeedback</h2>
	<?php
	    require_once 'library.php';//include files that can connect to the database and validate user input
        //this also contains arrays defining the structure of the database and tables displayed
        //create the tables
	    /*sqlExecute("
	        CREATE TABLE Schools (
            SchoolID int NOT NULL AUTO_INCREMENT,
            Name varchar(50),
            PRIMARY KEY (SchoolID)
        )");*/
        sqlExecute("
	        CREATE TABLE Teachers (
            TeacherID int NOT NULL AUTO_INCREMENT,
            SchoolID int,
            Name varchar(50),
            PRIMARY KEY (TeacherID)
        )");
        sqlExecute("
	        CREATE TABLE TeacherResponses (
            TeacherResponseID int NOT NULL AUTO_INCREMENT,
            TeacherID int,
            Review varchar(1000),
            PRIMARY KEY (TeacherResponseID)
        )");
        sqlExecute("
	        CREATE TABLE TeacherImages (
            TeacherImageID int NOT NULL AUTO_INCREMENT,
            TeacherID int,
            Link varchar(255),
            PRIMARY KEY (TeacherImageID),
	    Caption varchar(1000)
        )");
        sqlExecute("
	        CREATE TABLE SchoolResponses (
            SchoolResponseID int NOT NULL AUTO_INCREMENT,
            SchoolID int,
            Review varchar(1000),
            PRIMARY KEY (SchoolResponseID)
        )");
        sqlExecute("
	        CREATE TABLE SchoolImages (
            SchoolImageID int NOT NULL AUTO_INCREMENT,
            SchoolID int,
            Link varchar(255),
            PRIMARY KEY (SchoolImageID),
	    Caption varchar(1000)
        )");
        // Fixed: missing the Caption column for the Image tables! varchar(1000) works good, as well as default null!
        // Make an uploads file too
        // We can troubleshoot the PHP by echoing, period concatenates strings!
        
        /*
        Goals:
            +Don't let the typical users delete information already posted
            +Use textarea rather than input boxes so that users can post longer comments
            +improve go up a level button(go to xxx instead)
            allow users to vote on information(like/thumbs up or dislike/thumbs down) and sort information by this
            improve image gallery with multiple images on one line and blow ups
            +provide keywords so the cite comes up in a google search
            +eliminate the session edit variable so that no form is submitted when a user clicks to start editing a row(only submit when saving changes)
            +fix image bugs(remove error messages when using a link, automatically link the image if it already exists)
            +use as much javascript as possible to reduce the number of referesh(javascript for editing and creating a new row, only php for saving and retrieving)
        */
        
        //define variables about the current page
        $table="";
        $id="";
        $admin="";
        //we need to pass this information along with other information to javascript so that the information can be preserved when it is passed to the next link
        
        //get the table name and ID from the url(get method)
        if(isset($_GET['table'])){
            $table=$_GET['table'];
        }
        if(isset($_GET['id'])){
            $id=$_GET['id'];
        }
        if(isset($_GET['admin'])){
            $admin=$_GET['admin'];
        }
        //define the current defaults: table(default=Schools) and common parentID(default=null) and admin(default=null)
        if($table==""){
            $table=null;
            //if there is no ID specified, go back to the school table
            if($id==""){
                $table="Schools";
            }
        }
        if($id==""){
            $id=null;
        }
        //set the admin to null if incorrect
        if($admin!=3092){
            $admin=null;
        }
        //echo "<script>alert(".$admin.");</script>";
        //cycle through the reference and instance tables
        //$i=0=>reference(schools, teachers), $i=1=>instance(teacherresponses)
        //if you are on the references, the table name is the session table name
        $tableName=$table;//$_SESSION["tableName"];
        $parentName=$parents[$tableName];
        $editing="true";
        for($i=0; $i<3; $i++){
            //by default, dont edit any of the rows
            //$editID=0;
            //$_SESSION["editID".$i]=0;
            //define the current table if it is the second iteration
            if($i==1){
                $tableName=$correspondingTables[$parentName][0];
            }
            else if($i==2){
                $tableName=$correspondingTables[$parentName][1];
            }
            
            //handle the table interface if something was submitted
			if(!is_null($admin)&&isset($_POST['deleteInfo'.$i])){//if the delete selected button was clicked and this is an admin account, delete each row that was selected
			    //get an array of the IDs of the selected row
		        $deleteInfo = $_POST['deleteInfo'.$i];
                //cycle through all of the IDs to be deleted
    			for($j=0; $j<count($deleteInfo); $j++){
    			    //delete this row and its children and their direct instances
    			    deleteChildren($tableName, $deleteInfo[$j], true, $i==0);
    			}
			}
			if(isset($_POST['editInfo'.$i])&&isset($_POST['saveInfo'.$i])){//if the user wants to edit a row with its id in editInfo
			    //get the ID to be edited
			    $editInfo = $_POST['editInfo'.$i][0];
			    //if the ID is 0, we need to insert a new row
			    if(!$editInfo){
    			    //prepare a sql statement for inserting a new row
                    $result="INSERT INTO ".$tableName;
                    //add a where statement to upload the proper parentID if the parents are defined
                    if(!is_null($parentName)){
                        $result=$result." (".$currentIDs[$parentName].") 
    					VALUES ('".$id."')";
                    }
                    else{
                        //if the parents aren't defined, upload the default value
                        $result=$result." (".$tableStruct[$tableName][0][0].") VALUES (NULL)";
                    }
                    //execute the sql command
                    $result=sqlExecute($result);
    				//edit the new row
    				if(!is_null($result)){
    				    $editInfo=$result;
    		        }
			    }
			    //save the changes
		        //get all the columns of the saved row
		        $saveInfo = $_POST['saveInfo'.$i];
		        //see if an image was uploaded
		        if(isset($_POST['image'.$i])){
		            //get the target file name that it will be uploaded to
		            //create a unique file name in the uploads directory
		            $fileName=tempnam('Uploads', 'img');//'Uploads/testImage.png';
		            // echo '<p>Echo:'.$fileName.'</p><br><br>';
		            //see if we got a file name
		            if($fileName!==false){
		                //delete the text file
		                unlink($fileName);
		                //get the filename referenced to index.php
		                //split the string at 'Uploads'
		                $fNameArray=explode('Uploads',$fileName);
		                //add Uploads before and .png to the filename
		                $fileName='Uploads'.$fNameArray[1].'.png';
                        // open the output file for writing
                        $ifp=fopen($fileName, 'wb'); 
                        // split the string on commas
                        // $data[ 0 ] == "data:image/png;base64"
                        // $data[ 1 ] == <actual base64 string>
                        $data = explode(',', $_POST['image'.$i]);
                        // we could add validation here with ensuring count( $data ) > 1
                        fwrite($ifp, base64_decode($data[0]));
                        // clean up the file resource
                        fclose($ifp);
                        //set the link of the image to the file it was uploaded to
                        $saveInfo[0]=$fileName;
                        echo "image uploaded";
		            }
		        }
		        
				/*//see if they want to upload a file
				if(isset($_FILES["fileToUpload"]["name"])&&($_FILES["fileToUpload"]["name"]!=null)&&($_FILES["fileToUpload"]["name"]!="")){
                    $target_dir = "Uploads/";
                    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
                    $uploadOk = true;
                    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
                    // Check if image file is a actual image or fake image
                    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
                    if(!$check){
                        echo "File is not an image.";
                        $uploadOK=false;
                    }
                    // Check if file already exists
                    if (file_exists($target_file)) {
                        //if the file already exists, we need to retrieve the existing link
                        $saveInfo[0]=$target_file;
                        $uploadOk=false;
                    }
                    // Check file size
                    if ($_FILES["fileToUpload"]["size"] > 500000) {
                        echo "Sorry, your file is too large.";
                        $uploadOk=false;
                    }
                    // Allow certain file formats
                    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                    && $imageFileType != "gif" ) {
                        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                        $uploadOk=false;
                    }
                    // Check if $uploadOk is set to 0 by an error
                    if($uploadOk){
                        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                            //change the value in the database
                            $saveInfo[0]=$target_file;
                            echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
                        } else {
                            echo "Sorry, there was an error uploading your file.";
                        }
                    }
			    }*/
			    
		        //cycle through all of the columns
		        for($j=0; $j<count($saveInfo); $j++){
		            //get the new value with html special characters filtered out
		            $newVal=escapeChars($saveInfo[$j]);
		            //update the database
		            sqlExecute("UPDATE ".$tableName.
                    " SET ".$tableStruct[$tableName][$j][0]." = '".$newVal.
                    "' WHERE ".$currentIDs[$tableName]." = '".$editInfo."'");
				}
			}
    	    
    	    //update the table status if it changes based on the user input above
            $tableName=$table;
            $parentName=$parents[$tableName];
            //define the current table if it is the second iteration
            if($i==0){
                //echo the name of the person you are on
                //echo "<h3>".end($_SESSION["parentNameVal"])."</h3>";
                //get the name of the person you are on
                if(!is_null($parentName)){
                    $result=sqlExecute("SELECT ".$tableStruct[$parentName][0][0]." FROM ".$parentName." WHERE ".$currentIDs[$parentName]." = '".$id."'");
                    if(!is_null($result)&&count($result)){
                        //echo the name of the person you are on
                        echo "<h3>".$result[0][$tableStruct[$parentName][0][0]]."</h3>";
                    }
                }
            }
            if($i==1){
                $tableName=$correspondingTables[$parentName][0];
            }
            else if($i==2){
                $tableName=$correspondingTables[$parentName][1];
            }
            
            //display the entries of the current table if it is valid
            if(!is_null($tableName)){
                //prepare the sql statement
                $result="SELECT * FROM ".$tableName;
                //add a where statement if the parents were defined
                if(!is_null($parentName)){
                    $result=$result." WHERE ".$currentIDs[$parentName]." = ".$id;
                }
                //echo $result;
                //get the entries of the current table with the specified parent ID if given
                $result=sqlExecute($result);
                //display the result in a table
                displayTable($result, $tableStruct[$tableName], $currentIDs[$tableName], $i, $i==0);
                //display table navigation tools for making a new entry, deleting, and editing
        	    echo '
            	    <button class="button" type="button" onclick="newRow('.$i.');" style="font-weight:bold; padding:5px; display:inline-block;">New</button>';
            	//if the user is an admin, display the delete button
            	if(!is_null($admin)){
                	echo '
                	    <button class="button" type="button" onclick="submitSelectedRows(\'delete\', '.$i.');" style="font-weight:bold; padding:5px; display:inline-block;">Delete Selected Row</button>';
            	}
            	//determine whether you would be editing or saving
        	    echo '
            		<button id="editBtn'.$i.'" class="button" type="button" onclick="edit('.$i.');" style="font-weight:bold; padding:5px; display:inline-block;">Edit Selected Row</button>';
                //display the back button
            	echo '
            	    <button class="button" type="button" onclick="back();" style="font-weight:bold; padding:5px;">Back</button><br><br>';
            	//display input boxes if necessary
            	/*if($editID){
            	    echo "<script>displayInput(".$i.", ".$editID.")</script>";
            	    $editing="false";
            	}*/
            }
        }
        //echo a general purpose post form
        echo '
            <form id="rowForm" method="post" enctype="multipart/form-data">
    		</form>';
    	//echo a general purpose get form for when the user double clicks to open a row
    	echo '
            <form id="navForm" method="get" action="index.php">
    		</form>';
    	
    	//pass off information to javascript so that the information can be preserved when it is passed to the next link
    	//this includes the current tablename, child tablename, parent tablename, currentID, and grandparentID
    	//get the parent table and the grandparent ID
    	$parentTable=$parents[$table];
    	$grandparentIDName=$currentIDs[$parents[$parentTable]];
    	//by default, set the grandparentID to null
    	$grandparentID=null;
    	//try to select the grandparentID from the parentTable
    	if(!is_null($parentTable)&&!is_null($grandparentIDName)){
    	    $result=sqlExecute("SELECT ".$grandparentIDName." FROM ".$parentTable." WHERE ".$currentIDs[$parentTable]." = '".$id."'");
    	    //see if there are any valid results
    	    if(!is_null($result)&&count($result)){
    	        $grandparentID=$result[0][$grandparentIDName];
    	    }
    	}
    	//create a variable holding whether you will be editing or saving a row(editing by default)
    	echo '
    	    <script>
    	        var tableName="'.$table.'";
    	        var childTable="'.$children[$table].'";
    	        var parentTable="'.$parentTable.'";
    	        var currentID="'.$id.'";
    	        var grandparentID="'.$grandparentID.'";
    	        var editing='.$editing.';
    	        var admin="'.$admin.'";
    	    </script>';
	?>
	<p>Double click on a row to explore! Click and hold for mobile</p>
	<a href="3DViewer.html" target="_blank">Check out another cool website!</a>
	<script>
        //removes the last div element(w3schools logo) once the page has loaded(requires body onload method)
        function removeLogo(){
            var divs = document.getElementsByTagName("div");
            divs[divs.length-1].style.display="none";
            //setup touch screen
            //document.body.addEventListener('touchstart', function(e){alert(e.changedTouches[0].pageX);}, false);
        }
    </script>
	</body>
</html>
