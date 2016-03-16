<?php
/*
Plugin Name: Yaml Post Types and Meta Boxes
Version:1.0-alpha
Description: Enables the creation of posts types and meta boxes ( via CMB2 ) using simple yaml configuration files
Author: Mike Van Winkle
Author URI: http://www.mikevanwinkle.com
Plugin URI: http://www.mikevanwinkle.com/project/yaml-post-types
Text Domain: yaml-config
Domain Path: /languages
*/
Class Yamlfy {
  const version = '1.0';
  const textdomain = 'yaml-config';
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
    add_action('current_screen', array($this,'maybeRefreshConfig'));
    add_action('admin_menu', array($this,'settings_page'));
  }
  
  public function maybeRefreshConfig() {
      $screen = get_current_screen();
      if(!$screen->id == 'tools_page_yaml-config') return;
      if(isset($_GET['refresh'])) {
          delete_transient('yaml-post-types-configs');
          $cookie = array(['class'=>'notice-success','message'=>'Configuration refreshed!']);
          setcookie('yamlconfigmessage', json_encode($cookie), time()+3);
          wp_redirect('tools.php?page=yaml-config');
      }
      if (isset($_COOKIE['yamlconfigmessage'])) {
          $this->notice = json_decode(stripslashes($_COOKIE['yamlconfigmessage']));
          $this->notice = $this->notice[0];
          add_action('admin_notices', array($this,'successMessage'));
      }
  }
  
  public function successMessage() {
      echo '<div class="notice '.$this->notice->class.' is-dismissible">
            <p>'.__( $this->notice->message, self::textdomain ).'</p></div>';
  }
  
  public function settings_page() {
      add_submenu_page('tools.php','Yaml Config', 'Yaml Config', 'administrator', 'yaml-config', array($this, 'settings_callback'));
  }
  
  public function settings_callback() {
      $parser = new \Symfony\Component\Yaml\Parser(); 
      $finder = new \Symfony\Component\Finder\Finder();
      $dumper = new \Symfony\Component\Yaml\Dumper();
      $files = [];
      $dirs = apply_filters('yamlfy-config-dirs', array(dirname(__FILE__)));
      $finder->name('*.yaml')->files()->in($dirs)->notPath('tests');
      foreach( $finder as $file ) {
          $files[] = $file->getPath().'/'.$file->getFilename();
      }
      // set some variables 
      $td = self::textdomain;
      $configs = $this->loadConfigs();
      include_once "settings-page.php";    
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

