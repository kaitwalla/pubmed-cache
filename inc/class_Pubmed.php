<?php
require_once('env.php');
require_once('simple_dom.php');



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
				$parameters = array('slug','name','pubmed_url');
			break;
			case 'edit':
				$parameters = array('id','slug','name','pubmed_url');
			break;
			case 'refresh';
				$parameters = array('id','pubmed_url');
			break;
			case 'delete':
				$parameters = array('id');
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
			if ($this->parameters->action !== 'delete') {
				$this->pubmed_url = $this->parameters->pubmed_url;
				$this->get_pubmed_data();
			}
			switch($this->parameters->action) {
			case 'add':
				$this->query = $this->db->prepare('INSERT INTO pubmed (name,slug,pubmed_url,data) VALUES (:name,:slug,:pubmed_url,:data)');
				$this->query_parameters = array(
					'name' => $this->parameters->name, 
					'slug' => $this->parameters->slug,
					'pubmed_url' => $this->parameters->pubmed_url,
					'data' => json_encode($this->pubmed_data)
				);
				if($this->query_execute()) {
					unset($this->query_parameters['data']);
					$this->query_parameters['id'] = $this->db->lastInsertId();
					$this->ajax_type = 'add_row';
					$this->ajax_content = $this->query_parameters;
					$this->return_ajax();
				}
			break;
			case 'edit':
				$this->get_pubmed_data();
				$this->query = $this->db->prepare('UPDATE pubmed SET name=:name,slug=:slug,pubmed_url=:pubmed_url,data=:data WHERE id=:id');
				$this->query_parameters = array(
						'id' => $this->parameters->id,
						'name' => $this->parameters->name,
						'slug' => $this->parameters->slug,
						'pubmed_url' => $this->parameters->pubmed_url,
						'data' => json_encode($this->pubmed_data)
				);
				if ($this->query_execute()) {
					unset($this->query_parameters['data']);
					$this->ajax_type = 'update_row';
					$this->ajax_content = $this->query_parameters;
					$this->return_ajax();
				}
			break;
			case 'refresh':
				$this->query = $this->db->prepare('UPDATE pubmed SET data=:data WHERE id=:id');
				$this->query_parameters = array(
					'id' => $this->parameters->id,
					'data' => json_encode($this->pubmed_data)
				);
				if ($this->query_execute()) {
					$this->ajax_type = 'alert_update';
					$this->ajax_content = $this->parameters->id;
					$this->return_ajax();
				}
			break;
			case 'delete':
				$this->query = $this->db->prepare('DELETE FROM pubmed WHERE id=:id');
				$this->query_parameters = array('id'=>$this->parameters->id);
				if ($this->query_execute()) {
					$this->ajax_type = 'remove_row';
					$this->ajax_content = $this->query_parameters;
					$this->return_ajax();
				}
			break;
		}
	}
	
	public function get_pubmed_data() {
		if (!$this->pubmed_url) {
			$this->throw_ajax_error('Missing PubMed URL');
		}
		$html = file_get_html($this->pubmed_url);
		$items = array();
		foreach ($html->find('.rprt') as $data) {
			$item = new stdClass();
			$link = $data->find('.title a',0);
			$item->link = 'https://www.ncbi.nlm.nih.gov'.$link->href;
			$item->title = $link->plaintext;
			$details = $data->find('.supp',0);
			$item->authors = $details->find('.desc',0)->plaintext;
			$item->publication = $details->find('.details',0)->plaintext;
			$item->pubmed_id = $data->find('.rprtid dd',0)->plaintext;
			array_push($items,$item);
		}
		$this->pubmed_data = $items;
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
			?><ul class="list-group"> <?php
			foreach ($this->results as $result) {
			 ?><li data-id="<?php print $result['id']; ?>" data-url="<?php print $result['pubmed_url']; ?>" data-slug="<?php print $result['slug']; ?>" class="list-group-item">
				<p><?php print $result['name']; ?></p>
				<div class="ml-auto">
					<button data-action="edit-item" class="btn btn-warning"><i class="fa fa-pencil"></i></button>
					<button data-action="refresh-item" class="btn btn-success"><i class="fa fa-refresh"></i></button>
					<button data-action="delete-item" class="btn btn-danger"><i class="fa fa-remove"></i></button>
				</div>
			</li> <?php
			}
			?></ul><?php
		}
	}
	
	public function query_execute() {
		try {
			$this->query->execute($this->query_parameters);
			$this->results = $this->query->fetchAll();
			return true;
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