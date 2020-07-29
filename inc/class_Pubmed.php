<?php
require_once(__DIR__ . '/env.php');
require_once(__DIR__ . '/../vendor/autoload.php');

class Pubmed {
	public function __construct() {
		global $creds;
		$this->creds = $creds;
		try {
			$this->db = new PDO($creds->dsn,$creds->user,$creds->password);
		} catch (PDOException $e) {
			Pubmed::throw_error($e->getMessage());
		}
	}
	
	public function ajax_check_parameters() {
		$v = (!empty($_POST)) ? $_POST : $_GET;
		if (!empty($v)) {
			if (!isset($v['type'])) {
				$this->throw_ajax_error('Invalid type');
			}
		} else {
			$this->throw_ajax_error('Invalid request type');
		}
		
		if ($v['security_token'] !== $this->creds->security_token) {
			$this->throw_ajax_error('Invalid security token');
		} else {
			unset($v['security_token']);
		}
		
		$this->parameters = new stdClass();
		$this->parameters->action = $v['type'];
		switch ($this->parameters->action) {
			case 'refresh_all':
				$parameters = array();
			break;
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
			if ($this->parameters->action !== 'delete' && $this->parameters->action !== 'refresh_all') {
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
				case 'refresh_all':
					$this->query = $this->db->prepare('SELECT * FROM pubmed');
					$this->query_parameters = array();
					error_log('refresh_all running');
					if ($this->query_execute()) {
						if (count($this->results) > 0) {
							foreach ($this->results as $result) {
								$this->pubmed_url = $result['pubmed_url'];
								$this->get_pubmed_data();
								$this->query = $this->db->prepare('UPDATE pubmed SET data=:data WHERE id=:id');
								$this->query_parameters = array(
									'id' => $result['id'],
									'data' => json_encode($this->pubmed_data)
								);
								$this->query_execute();
							}
						}
					}
					$this->ajax_type = 'alert_update';
					$this->ajax_content = 'All feeds';
					$this->return_ajax();
				break;
		}
	}
	
	public function feed_check_parameters() {
		if (!isset($_GET['slug'])) {
			$this->throw_feed_error('No slug specified');
		}
	}
	
	public function feed_output() {
		global $rss_description;
		$this->feed_check_parameters();
		$this->query = $this->db->prepare('SELECT * FROM pubmed WHERE slug=:slug');
		$this->query_parameters = array('slug' => $_GET['slug']);
		if ($this->query_execute()) {
			if (count($this->results) == 0) {
				$this->throw_feed_error('Improper feed / no results found');
			} else {
print '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"
		xmlns:content="http://purl.org/rss/1.0/modules/content/"
		xmlns:wfw="http://wellformedweb.org/CommentAPI/"
		xmlns:dc="http://purl.org/dc/elements/1.1/"
		xmlns:atom="http://www.w3.org/2005/Atom"
		xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
		xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
		xmlns:ev="http://purl.org/rss/1.0/modules/event/">

	<channel>
	<title>PubMed Cache for '.$this->results[0]['name']."</title>
	<description>$rss_description</description>
	<link>http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]</link>\n";
	$data = json_decode($this->results[0]['data']);
	foreach ($data as $item) {
		?>
	<item>
		<guid isPermaLink="false"><?php print $item->pubmed_id; ?></guid>
		<title><?php print $item->title; ?></title>
		<description><?php print $item->authors; ?> <?php print $item->publication; ?></description>
		<link><?php print $item->link; ?></link>
	</item>
<?php
	}
print "\n</channel>
</rss>";
			}
		}
	}
	
	public function get_pubmed_data() {
		if (!$this->pubmed_url) {
			$this->throw_ajax_error('Missing PubMed URL');
		}
		$scraper = new Goutte\Client();
		$crawler = $scraper->request('GET', $this->pubmed_url);
		$items = [];
		$itemCount = 0;
		$crawler->filter('.docsum-content')->each(function($node) use (&$items) {
			$item = new stdClass();
			$link = $node->filter('.docsum-title')->eq(0);
			$item->link = 'https://www.ncbi.nlm.nih.gov'.explode('?',$link->attr('href'))[0];
			$item->title = $link->text();
			$item->authors = $node->filter('.full-authors')->eq(0)->text();
			$publication = $node->filter('.full-journal-citation')->eq(0)->text();
			$pub = explode('. doi:',str_replace(' .','',$publication));
			if (count($pub) > 1) { 
				$item->publication = (preg_match('/.*[\d*]\.$/',$pub[1])) ? $pub[0] : $pub[0].' '.preg_replace('/.*[\d*]\.\s(.*)$/','$1',$pub[1]);
			} else {
				$item->publication = $pub[0];
			}
			$item->pubmed_id = $node->filter('.docsum-pmid')->eq(0)->text();
			array_push($items, $item);
		});
		$this->pubmed_data = $items;
	}
	
	public function print_top_buttons() {
		?>
		<div id="action-buttons">
			<button class="btn btn-primary" data-action="add"><i class="fa fa-plus"></i> Add New</button>
			<button class="btn btn-success" data-action="refresh_all"><i class="fa fa-recycle"></i> Refresh all</button>
		</div>
		<?php
	}
	
	public function print_list_of_existing_feeds() {
		$this->query = $this->db->prepare('SELECT * FROM pubmed ORDER BY slug ASC');
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
					<button data-action="show_url" class="btn btn-info"><i class="fa fa-link"></i></button>
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
			$this->throw_ajax_error($e->getMessage());
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
	
	public function throw_feed_error($message) {
	?>
		<html><head><title>PubMed Cache Error</title></head><body><p><?php print $message; ?></p></body></html>
	<?php
	exit();
	}
	
	public static function throw_error($message) {
		print '<div class="user-alert alert alert-danger alert-dismissible fade show"><button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button><p>'.$message.'</p></div>';
	}
}