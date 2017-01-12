<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('env.php');

class Pubmed {
	public function __construct() {
		global $creds;
		try {
			$this->db = new PDO($creds->dsn,$creds->user,$creds->password);
		} catch (PDOException $e) {
			$self::throw_error($e->getMessage());
		}
	}
	
	public function ajax_check_parameters() {
		$v = (!empty($_POST)) ? $_POST : $_GET;
//		$v = $_POST;
		if (!empty($v)) {
			if (!isset($v['type'])) {
				$this->throw_ajax_error('Invalid type');
			}
		} else {
			$this->throw_ajax_error('Invalid request type');
		}
		$this->parameters = new stdClass();
		$this->parameters->action = $v['type'];
		switch ($this->parameters->action) {
			case 'add':
			case 'update':
				$parameters = array('slug','name','pubmed_url');
			break;
			case 'slug':
			case 'refresh';
				$parameters = array('slug');
			break;
		}
		$error = false;
		foreach ($parameters as $param) {
			if (!isset($v[$param])) {
				$error .= $param.'; ';
			} else {
				$this->parameters->{$param} = $v[$param];
			}
		}
		if ($error) {
			$this->throw_ajax_error('Missing '.$error);
		}
	}
	
	public function ajax_do_action() {
		$this->ajax_check_parameters();
		switch($this->parameters->action) {
			$this->pubmed_url = $this->parameters->pubmed_url;
			$this->get_pubmed_data();
			case 'add':
				$this->query = $this->db->prepare('INSERT INTO pubmed (name,slug,pubmed_url,data) VALUES (:name,:slug,:pubmed_url,:data)');
				$this->query_parameters = array(
																		'name' => $this->parameters->name, 
																		'slug' => $this->parameters->slug,
																		'pubmed_url' => $this->parameters->pubmed_url
																		'data' => $this->pubmed_data;
																	);
			break;
			case 'update':
				
			break;
			case 'refresh':
				
			break;
			case 'delete':
				
			break;
		}
	}
	
	public function get_pubmed_data() {
		if (!$this->pubmed_url) {
			throw_ajax_error('Missing PubMed URL');
		}
	
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $this->pubmed_url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$html = curl_exec($ch); 
		curl_close($ch); 
		$dom->loadHTML($html);
		$this->pubmed_data = false;
	}
	
	public function print_top_buttons() {
		?>
		<div id="action-buttons">
			<button class="btn btn-primary" data-action="add"><i class="fa fa-plus"></i> Add New</button>			
		</div>
		<?php
	}
	
	public function print_list_of_existing_feeds() {
		$this->query = $this->db->prepare('SELECT * FROM pubmed');
		$this->query_parameters = array();
		$this->query_execute();
		if (count($this->results) == 0) {
			print '<p>No feeds cached.</p>';
		} else {
			//
		}
	}
	
	public function query_execute() {
		try {
			$this->query->execute($this->query_parameters);
			$this->results = $this->query->fetchAll();
		} catch (PDOException $e) {
			$self::throw_error($e->getMessage());
		}
	}
				
	public function return_ajax() {
		$message = new stdClass();
		$message->type = $this->ajax_type;
		$message->content = $this->ajax_content;
		print json_encode($message);
		exit;
	}
				
	public function throw_ajax_error($message) {
		$this->ajax_type = 'error';
		$this->ajax_content = $message;
		$this->return_ajax();
	}
	
	public static function throw_error($message) {
		print '<div class="user-alert alert alert-danger alert-dismissible fade show"><button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button><p>'.$message.'</p></div>';
	}
}