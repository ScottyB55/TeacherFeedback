<?php
	//set the timezone to compensate for the communist default time zone
	date_default_timezone_set("America/New_York");
	
    //define the structure of the database
    //define the structure of the display table(name of database column, column title displayed in user interface, data type)
    //datatype: undefined(length:2, default)-> text
    //datatype: 1(length:3)-> image
    $tableStruct = array(   "Schools"=> array(array("Name", "School Name")),
	                        "Teachers"=> array(array("Name", "Teacher Name")),
	                        null=>null,
	                        
	                        "SchoolResponses"=> array(array("Review", "School Review")),
    			            "SchoolImages"=> array(array("Link", "Image Link", 1), array("Caption", "Caption")),
	                        
    			            "TeacherResponses"=> array(array("Review", "Teacher Review")),
    			            "TeacherImages"=> array(array("Link", "Image Link", 1), array("Caption", "Caption")));
    			            
    $currentIDs = array(    "Schools"=>"SchoolID",
                            "Teachers"=>"TeacherID",
                            null=>null,
                            
                            "SchoolResponses"=>"SchoolResponseID",
                            "SchoolImages"=>"SchoolImageID",
                            
                            "TeacherResponses"=>"TeacherResponseID",
                            "TeacherImages"=>"TeacherImageID");
                            
    $parents = array(       "Schools"=>null,
                            "Teachers"=>"Schools",
                            null=>"Teachers",
                            
                            "SchoolResponses"=>null,
                            "SchoolImages"=>null,
                            
                            "TeacherResponses"=>null,
                            "TeacherImages"=>null);
                            
    $children = array(      "Schools"=>"Teachers",
                            "Teachers"=>null,
                            null=>null,
                            
                            "SchoolResponses"=>null,
                            "SchoolImages"=>null,
                            
                            "TeacherResponses"=>null,
                            "TeacherImages"=>null);
    
    $correspondingTables = array("Schools"=>array("SchoolResponses", "SchoolImages"),
                            "Teachers"=>array("TeacherResponses", "TeacherImages"),
                            null=>null,
                            
                            "SchoolResponses"=>array("Schools"),
                            "SchoolImages"=>array("Schools"),
                            
                            "TeacherResponses"=>array("Teachers"),
                            "TeacherImages"=>array("Teachers"));
    
    
	//tries to connect to the database
	//if successful, the function returns a connection to the database
	//if the connection fails, the function echos an error statement and returns null
	function connect() {
		$servername = "localhost";
		$username = "id19377340_scottyee23";//"id3785816_user";//use this username to login to the database
		$password = "LargeBarge!1";//"computers123";//use this password to login to the database
		$database = "id19377340_teacherfeedbackdb";//"id3785816_db";
		try {//try to setup the connection
			//set up connection
			$conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			//if the code reaches this point, the connection is successful. Return a connection to the database
			return $conn;
		}
		catch(PDOException $e) {//if the connection fails
			//echo the connection message
			echo "Connection failed: " . $e->getMessage();
			//return null
			return NULL;
		}
	}
	
	//echos a table of values specified in $result
	//$result is an array of rows to be displayed containing an array of values
	//$columns is an array of column names, specifing the order in which columns are selected and displayed
	//$columnID contains which column value will be submitted during the click event of the row
	//$i specifies the number of the table(unique number appended to the table id)
	//$dblClick specifies whether double click events are enabled
	function displayTable($result, $columns, $columnID, $i, $dblClick){
		//create display table
		echo "<table class='dataTable' id='navTable".$i."'>";
		echo "<tr>";
		//display the columns
	    foreach($columns as $title){
	        //echo the title cell with its name specifying its data type(image, text, etc)
            //check the format saved in $tableStruct(image, date, etc)
            if(count($title)>2){
                //if there is a special format
                if($title[2]==1){
                    //display image if the format is code 1
                    echo "<th name='img'>".$title[1]."</th>";
                }
            }
            else{
                //display text by default
                echo "<th name='txt'>".$title[1]."</th>";
            }
	        //echo "<th>".$title[1]."</th>";
	    }
	    echo "</tr>";
	    $events="onclick='rowSelect(this);'";
	    //add a double click event if specified
	    if($dblClick){
	        $events=$events." ondblclick='rowSetup(this);'";
	    }
		//cycle through the rows
		for($i=0; $i<count($result); $i++){
		    //give the row an id
		    echo "<tr id='".$result[$i][$columnID]."' ".$events.">";
		    foreach($columns as $title){
	            //echo the cell with the value
	            //check the format saved in $tableStruct(image, date, etc)
	            if(count($title)>2){
	                //if there is a special format
	                if($title[2]==1){
	                    //display image if the format is code 1
	                    echo "<td name='img'><img src='".$result[$i][$title[0]]."'></td>";
	                }
	            }
	            else{
	                //display text by default
	                echo "<td name='txt'>".$result[$i][$title[0]]."</td>";
	            }
		    }
		    echo "</tr>";
    		//set up the touch event for phones if spefieid
    		if($dblClick){
    		    //add a touchstart event to the row that starts a timer for about 1 second
    	        echo "<script>document.getElementById(".$result[$i][$columnID].").addEventListener('touchstart', function(){longTouch=false;clearTimeout(timeout);timeout=setTimeout(function(){longTouch=true;}, 500);}, false);</script>";
    	        //add a touchend event to the row that sees if the timer hit about 1 second and opens up the row if it did. This also clears the timer
    	        echo "<script>document.getElementById(".$result[$i][$columnID].").addEventListener('touchend', function(){if(longTouch&&enableTouch){rowSetup(this);}longTouch=false;clearTimeout(timeout);}, false);</script>";
    	    }
		}
		echo "</table>";
	}
	
	//executes an sql command
	//returns an associative array of rows if you use a select statement
	//returns the last id inserted if you dont use a select statement
	//returns null if unable to connect
	function sqlExecute($sqlCommand){
		$conn = connect();//try to connect to the database. If successful, connect() returns the connection to the database. Otherwise, connect() returns null
		if(!is_null($conn)) {//if the connection is successful(isn't null), then select all of the rows from the table containing whereInfo
			if(strpos($sqlCommand, "SELECT")===0){
			    $stmt = $conn->prepare($sqlCommand);
    			$stmt->execute();//execute the sql with a return
    			// set the resulting array to associative to display the data in a table
    			$result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    			$result = $stmt->fetchAll();
    			$conn = null;//close the connection
    			return $result;
			}
			else{
			    $conn->exec($sqlCommand);
			    $returnLast=$conn->lastInsertId();
			    $conn = null;//close the connection
			    //signify that the command was successfully completed
			    return $returnLast;
			}
		}
		//signify that an error occurred
		return null;
	}
	
	//deletes the entry and all of its children in $table with Id: $idDelete
	//also deletes all direct instances of the entries deleted, this function will not work by itself only if the instances have any children that aren't referenced to the reference parent's children
	//if $startCurrent is true, the first entry is selected by the current ID
	//if $startCurrent is false, the first entry is selected by its parent ID
	//$isReference holds whether the current object is a reference
	//all of the children are selected by the parent ID
	function deleteChildren($table, $idDelete, $startCurrent, $isReference){
	    global $parents, $currentIDs, $children, $correspondingTables;
	    //get the id to delete
	    $idsToDelete=array($idDelete);
	    //get $startCurrent
	    $doCurrent=$startCurrent;
	    //delete the parent and all of the children
	    for($tableName=$table; !is_null($tableName); $tableName=$children[$tableName]){
	        //get the parent of whatever is deleted
    	    $parent=$parents[$tableName];
	        //get the selector based on whether doCurrent is true
			//if doCurrent is false, select based on the ID of the parent
	        $selector=$currentIDs[$parent];
			//if doCurrent is true, select based on the ID of the current
	        if($doCurrent){
	            $selector=$currentIDs[$tableName];
	        }
    	    //delete the parents and find their children that need to be deleted
    	    //cycle through the parents
    	    $length=count($idsToDelete);
    	    for($i=0; $i<$length; $i++){
    	        //find the ID of the parent (the ID that its children will have)
	            $result=sqlExecute("SELECT ".$currentIDs[$tableName]." FROM ".$tableName." WHERE ".$selector." = '".$idsToDelete[0]."'");
                //add these ids to the idsToDelete array
                if(!is_null($result)){
                    //get the corresponding table that will contain the instances of the parent
                    $correspondingTable=$correspondingTables[$tableName];
                    //cycle through all of the parent IDs
                    for($j=0; $j<count($result); $j++){
                        //delete the instances of the parent
            	        if(!is_null($correspondingTable)&&$isReference){
            	            for($k=0; $k<count($correspondingTable); $k++){
                	            //delete the direct instances of the parent
                	            sqlExecute("DELETE FROM ".$correspondingTable[$k]." WHERE ".$currentIDs[$tableName]." = '".$result[$j][$currentIDs[$tableName]]."'");
            	            }
            	        }
                        //add these IDs to the array for the next iteration
                        array_push($idsToDelete, $result[$j][$currentIDs[$tableName]]);
                    }
                }
    	        //delete the parent
    	        $result=sqlExecute("DELETE FROM ".$tableName." WHERE ".$selector." = '".$idsToDelete[0]."'");
    	        //delete the parent from the $idsToDelete array
    	        array_shift($idsToDelete);
    	    }
    	    $doCurrent=false;
	    }
	}
    /*
    & (ampersand) becomes &amp;
    " (double quote) becomes &quot;
    ' (single quote) becomes &#039;
    < (less than) becomes &lt;
    > (greater than) becomes &gt;
    */
    function escapeChars($string){
        //$string=str_replace("&","&amp",$string);
        $string=str_replace('"',"&quot",$string);
        $string=str_replace("'","&#039",$string);
        $string=str_replace("<","&lt",$string);
        $string=str_replace(">","&gt",$string);
        return $string;
    }
?>