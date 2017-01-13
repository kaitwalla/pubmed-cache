<?php
header("Content-Type: application/xml; charset=utf-8"); 
require_once('inc/class_Pubmed.php');

$pubmed = new Pubmed;
$pubmed->feed_output();