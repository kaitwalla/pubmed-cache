<?php
header("Content-Type: application/xml; charset=utf-8"); 
header("Access-Control-Allow-Origin: *");
require_once(__DIR__ . '/inc/class_Pubmed.php');

$pubmed = new Pubmed;
$pubmed->feed_output();