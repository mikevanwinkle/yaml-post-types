<?php
/*
Plugin Name: Yaml Post Types and Meta Boxes
Version: 0.1.0-alpha
Description: Enables the creation of posts types and meta boxes ( via CMB2 ) using simple yaml configuration files
Author: Mike Van Winkle
Author URI: http://www.mikevanwinkle.com
Plugin URI: http://www.mikevanwinkle.com/project/yaml-post-types
Text Domain: yamlfy
Domain Path: /languages
*/
ini_set('display_errors',1);
Class Yamlfy {
  const version = '0.1.0-alpha';
  static $instance;
  
  public function __construct() {
    ini_set('display_errors',1);
    self::$instance = $this;
    require_once dirname(__FILE__) .'/vendor/autoload.php';
    if (!class_exists('cmb2_bootstrap_202')) {
      require_once dirname(__FILE__) .'/vendor/cmb2/init.php';
			require_once dirname(__FILE__) .'/vendor/cmb2-attached-posts/cmb2-attached-posts-field.php';
    }
    add_action('init',array( $this, 'init' ));
  }

  static function instance() {
    if ( !self::$instance ) {
      self::$instance = new Yamlfy();
    }
    return self::$instance;
  }

	public function loadConfigs() {
      $parser = new \Symfony\Component\Yaml\Parser(); 
      $finder = new \Symfony\Component\Finder\Finder();
			if( !$configs = get_transient('yaml-post-types-configs') ) {
	      $dirs = apply_filters('yamlfy-config-dirs', array(dirname(__FILE__)));
  	    $finder->name('*.yaml')->files()->in($dirs)->notPath('tests');
    	  $configs = array();
      	foreach ( $finder as $file ) {
        	  $configs[] = $parser->parse( $file->getContents() );
      	}
				set_transient('yaml-post-types-configs', $configs);
			}
			return $configs;
	}

  public function init() {
    try {
			$configs = $this->loadConfigs();
			foreach($configs as $config) {
  			$post_types = $taxonomies = $meta_boxes = $index = false;
  			if (array_key_exists('post_types', $config)) {
  				$this->register_post_types($config['post_types']);	
	  		}

		  	if (array_key_exists('taxonomies',$config)) {
			  	$this->register_taxonomies($config['taxonomies']);
			  }
	  		if (array_key_exists('meta_boxes',$config)) {
		  		$this->load_meta_boxes($config['meta_boxes']);
		  	}

            if (array_key_exists('relationships', $config) AND function_exists('p2p_register_connection_type')) {
                $this->load_relationships($config['relationships']);
            }
      }
    } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
        $this->error("Could not parse config file(s):{$e->getMessage()}");
    }  catch(\Exception $e) {
      $this->error($e->getMessage());
    }
  }

	public function register_post_types($post_types) {
		foreach($post_types as $post_type => $args) {
			register_post_type($post_type,$args);
		}
	}

	public function register_taxonomies($taxonomies) {
		foreach ($taxonomies as $taxonomy => $info) {
			register_taxonomy($taxonomy, $info['object_type'], $info['args']);
		}
	}

  public function load_meta_boxes($meta_boxes) {
    $this->mbs = $meta_boxes;
	add_filter("cmb2_meta_boxes", array($this, 'merge_mbs'));
  }

	public function load_relationships($relationships) {
		foreach($relationships as $rel) {
			p2p_register_connection_type($rel);
		}
	}

	public function merge_mbs($mbs) {
		return array_merge($mbs, $this->mbs);
	}

  public function error($message) {
    $this->error = $message;
    add_action('admin_notices', function() {
      echo '<div class="error">';
      echo '<p><strong>Fatal:</strong>'.__($this->error, 'yamlfy').'</p>';
      echo '</div>';
    });
  } 
}

Yamlfy::instance();

