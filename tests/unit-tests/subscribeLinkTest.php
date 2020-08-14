<?php

class subscribeLinkTest extends EO_UnitTestCase
{

	public function testWebcalSSL()
	{
		$value = isset( $_SERVER['HTTPS'] ) ? $_SERVER['HTTPS'] : null;
		$_SERVER['HTTPS'] = 1;

		$actual   = do_shortcode( '[eo_subscribe type="webcal"]Subscribe[/eo_subscribe]' );
		$expected = '<a href="webcal://example.org/?feed=eo-events" target="_blank"  title="Subscribe to calendar"  >Subscribe</a>';
		$this->assertEquals( $expected, $actual );

		$_SERVER['HTTPS'] = $value;
		if ( is_null( $value ) ) {
			unset( $_SERVER['HTTPS'] );
		}

	}

	public function testWebcal()
	{
		$actual   = do_shortcode( '[eo_subscribe type="webcal"]Subscribe[/eo_subscribe]' );
		$expected = '<a href="webcal://example.org/?feed=eo-events" target="_blank"  title="Subscribe to calendar"  >Subscribe</a>';
		$this->assertEquals( $expected, $actual );
	}

	public function testIcalSubscribe()
	{
		$actual   = do_shortcode( '[eo_subscribe type="ical"]Subscribe[/eo_subscribe]' );
		$expected = '<a href="http://example.org/?feed=eo-events" target="_blank"  title="Subscribe to calendar"  >Subscribe</a>';
		$this->assertEquals( $expected, $actual );
	}

	public function testGoogleSubscribe()
	{
		$actual   = do_shortcode( '[eo_subscribe type="google"]Subscribe[/eo_subscribe]' );
		$expected = '<a href="https://www.google.com/calendar/render?cid=http%3A%2F%2Fexample.org%2F%3Ffeed%3Deo-events" target="_blank"  title="Subscribe to calendar"  >Subscribe</a>';
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Google doesn't support https urls in its cid parameter, so
	 * we have to strip them out. These should represent a low risk as no
	 * credentials are sent, and only Google should be attempting to use the
	 * http:// protocol.
	 * @see https://github.com/stephenharris/Event-Organiser/issues/328
	 * @link  http://stackoverflow.com/a/21218052/932391
	 */
	public function testGoogleSubscribeSSL()
	{
		//Enable SSL
		$_SERVER['HTTPS'] = 'on';
		$actual   = do_shortcode( '[eo_subscribe type="google"]Subscribe[/eo_subscribe]' );

		$expected = '<a href="https://www.google.com/calendar/render?cid=http%3A%2F%2Fexample.org%2F%3Ffeed%3Deo-events" target="_blank"  title="Subscribe to calendar"  >Subscribe</a>';

		//Disable SSL again
		$_SERVER['HTTPS'] = 'off';

		$this->assertEquals( $expected, $actual );
	}

	public function testAttributes()
	{
		$actual   = do_shortcode( '[eo_subscribe type="ical" id="subscribe" class="btn btn-primary" style="float:right" title="Subscribe" ]Subscribe to my calendar[/eo_subscribe]' );
		$expected = '<a href="http://example.org/?feed=eo-events" target="_blank" class="btn btn-primary" title="Subscribe" id="subscribe" style="float:right">Subscribe to my calendar</a>';
		$this->assertEquals( $expected, $actual );
	}

	public function testCategory()
	{
		$cat_id = $this->factory->event_category->create( array(
			'name' => 'Foo Bar',
			'slug' => 'foobar',
		) );
		$actual   = do_shortcode( '[eo_subscribe type="ical" category="foobar" id="subscribe" class="btn btn-primary" style="float:right" title="Subscribe" ]Subscribe to my calendar[/eo_subscribe]' );
		$expected = '<a href="http://example.org/?feed=eo-events&amp;event-category=foobar" target="_blank" class="btn btn-primary" title="Subscribe" id="subscribe" style="float:right">Subscribe to my calendar</a>';
		$this->assertEquals( $expected, $actual );
	}


	public function testVenue()
	{
		$cat_id = $this->factory->event_venue->create( array(
			'name' => 'The Bar',
			'slug' => 'the-bar',
		) );
		$actual   = do_shortcode( '[eo_subscribe type="ical" venue="the-bar" id="subscribe" class="btn btn-primary" style="float:right" title="Subscribe" ]Subscribe to my calendar[/eo_subscribe]' );
		$expected = '<a href="http://example.org/?feed=eo-events&amp;event-venue=the-bar" target="_blank" class="btn btn-primary" title="Subscribe" id="subscribe" style="float:right">Subscribe to my calendar</a>';
		$this->assertEquals( $expected, $actual );
	}
}
