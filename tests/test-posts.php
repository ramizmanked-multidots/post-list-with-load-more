<?php

class Post_List_Post_Test extends WP_UnitTestCase{
	public function test_has_post_types(){
		$postTypes = get_post_types( array( 'public' => true ) );
		$this->assertNotCount( 0, $postTypes );
	}
}