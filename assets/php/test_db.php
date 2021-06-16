<?php
  session_start();
  include_once("controller.php");
  echo "<b>Insert new record</b></br>";
  $result = db_call("insert", "subscribers", "email, status", "mabasodaniel2@gmail.com, subscribed");
  if($result != 1){
    echo $result.'</br>';
  }else{
    echo "Record inserted</br>";
  }
  echo "</br><b>Select all the inserted records</b></br>";
  $result = db_call("select", "*", "subscribers");
  while($row = $result -> fetch_row()){
    echo "Subscriber with email ".$row[0]." and status ".$row[1].'</br>';
    }

  echo "</br><b>Update the inserted record (With condition)</b></br>";
  $result = db_call("update", "subscribers", "status", "unsubscribed", "`email` = 'mabasodaniel2@gmail.com'");
  if($result != 1){
    echo $result.'</br>';
  }else{
    echo "Record updated</br>";
  }

  echo "</br><b>Select updated record (With condition)</b></br>";
  $result = db_call("select", "*", "subscribers", "`email` = 'mabasodaniel2@gmail.com'");
  while($row = $result -> fetch_row()){
    echo "Subscriber ".$row[0]. ' has status ' . $row[1]. '</br>';
    }

  echo "</br><b>Delete the inserted record (With condition)</b></br>";
  $result = db_call("delete", "subscribers", "`email` = 'mabasodaniel2@gmail.com'");
  if($result != 1){
    echo $result.'</br>';
  }else{
    echo "Record deleted</br>";
  }
  echo "</br><b>Select the deleted record (With condition)</b></br>";
  $result = db_call("select", "*", "subscribers", "`email` = 'mabasodaniel2@gmail.com'");
  $count = 0;
  while($row = $result -> fetch_row()){
    $count++;
    }
  if($count == 0){
    echo "No results found!<br>";
  }

?>