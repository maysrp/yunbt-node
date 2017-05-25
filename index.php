<?php
	require  './class/medoo.php';
	require  './class/Aria2.php';
	require  './class/multi.php';

	$post=isset($_POST)?$_POST:"";
	if($post){
		$node=new multi($post);
	}
