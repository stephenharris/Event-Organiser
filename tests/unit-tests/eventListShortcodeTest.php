<?php

class eventListShortcodeTest extends EO_UnitTestCase
{

	protected function setUp(): void {
		parent::setUp();

		$this->cat_id = $this->factory->event_category->create( array(
			'name' => 'Entertainment',
			'slug' => 'entertainment',
		) );

		$venue = array(
			'name'        => 'The Bar',
		 	'description' => 'Description',
			'address'     => '1 Test Road',
			'city'        => 'Testville',
			'state'       => 'Testas',
			'country'     => 'United States of Tests',
 			'latitude'    => 0,
			'longitude'  => 0,
		);

		$this->venue_id = eo_insert_venue( $venue['name'], $venue );

		$this->user_id = $this->factory->user->create( array( 
			'user_login' => 'alice' ,
			'user_url'   => "www.example.co.uk"
		) );


		$this->event = array(
			'post_author' => $this->user_id,
			'post_title' => 'My event',
			'start'	     => new DateTime( '2114-07-09 13:02:00', eo_get_blog_timezone() ),
			'end'	     => new DateTime( '2114-07-09 14:02:00', eo_get_blog_timezone() ),
			'all_day'    => 0,
			'schedule'   => 'once',
			'post_content' => 'Event content'
		);


		$this->event_id = $this->factory->event->create( $this->event );
		wp_set_post_terms($this->event_id, [$this->venue_id['term_id']], 'event-venue');
		wp_set_post_terms($this->event_id, [$this->cat_id], 'event-category');

		update_post_meta($this->event_id, "foo", "bar");
	}

	function templateProvider() {

		return [
			[ '%event_title%', 'My event'],
			//'%start)({(?P<date>[^{}]*)})?({(?P<time>[^{}]*)})?%',
			//'%end)({(?P<date>[^{}]*)})?({(?P<time>[^{}]*)})?%',
			//'%end)({(?P<date>[^{}]*)})?({(?P<time>[^{}]*)})?%',
			//'%end)({(?P<date>[^{}]*)})?({(?P<time>[^{}]*)})?%',
			//'%end)({(?P<date>[^{}]*)})?({(?P<time>[^{}]*)})?%',
			//'%schedule_start)({(?P<date>[^{}]*)})?({(?P<time>[^{}]*)})?%',
			//'%schedule_last)({(?P<date>[^{}]*)})?({(?P<time>[^{}]*)})?%',
			//'%schedule_end)({(?P<date>[^{}]*)})?({(?P<time>[^{}]*)})?%',
			//'%event_range)({(?P<date>[^{}]*)})?({(?P<time>[^{}]*)})?%',
			[ '%event_venue%', 'The Bar'],
			[ '%event_venue_url%', 'http://example.org/?event-venue=the-bar'],
			[ '%event_cats%', '<a href="http://example.org/?event-category=entertainment" rel="tag">Entertainment</a>'],
			[ '%event_tags%', ''],
			[ '%event_venue_address%', '1 Test Road'],
			[ '%event_venue_postcode%', ''],
			[ '%event_venue_city%', 'Testville'],
			[ '%event_venue_country%', 'United States of Tests'],
			[ '%event_venue_state%', 'Testas'],
			[ '%event_organiser%', 'alice'],
			[ '%event_organiser_url%', 'http://www.example.co.uk'],
			//'%event_thumbnail)(?:{([^{}]+)})?(?:{([^{}]+)})?%',
			[ '%event_url%', 'http://example.org/?event=my-event'],
			[ '%event_custom_field{foo}%', 'bar'],
			//'%event_venue_map)({[^{}]+})?%',
			//'%event_excerpt)(?:{(\d+)})?%',
			//'%cat_color%',
			[ '%event_title_attr%', 'My event'],
			//'%event_duration){([^{}]+)}%',
			[ '%event_content%', 'Event content'],
		];
	}

	/**
	 * @dataProvider templateProvider
	 */
	public function testShortcodeTemplate($template, $expected_content)
	{
		$wrap = '<ul  class="eo-event-list"><li class="eo-event-venue-the-bar eo-event-cat-entertainment eo-event-future">%s</li></ul>';
		$expected = sprintf($wrap, $expected_content);

		$actual = eventorganiser_list_events(array(), array(
			"template" => $template
		), false);
		$this->assertEquals($expected, $actual);
	}

}
