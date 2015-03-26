<?php

class TestYamlfyInstance extends PHPUnit_Framework_TestCase {

	public function testYamlfyInstance() {
		$object = Yamlfy::instance();
		$this->assertInstanceOf('Yamlfy',$object);
	}

	public function testVersion() {
		$yamlfy = Yamlfy::instance();
		$data = get_plugin_data( __DIR__.'/../yaml-post-types.php' );
		$this->assertEquals( $yamlfy::version, $data['Version'] );
	}

	public function testConfigs() {	
		delete_transient('yaml-post-types-configs');
		add_filter('yamlfy-config-dirs', function( $dirs ) { return array( __DIR__ ); });
		$yamlfy = Yamlfy::instance();
		$configs = $yamlfy->loadConfigs();
		foreach ($configs as $config) {
			$this->assertArrayHasKey('post_types',$config);
		}
	}

}
