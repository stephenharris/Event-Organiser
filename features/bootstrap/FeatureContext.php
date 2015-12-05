<?php

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\TranslatedContextInterface,
	Behat\Behat\Context\Context,
	Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Johnbillion\WordPressExtension\Context\WordPressContext;

//TODO fix sendmail

class FeatureContext extends WordPressContext implements Context, SnippetAcceptingContext {

	/**
	 * Add these events to this wordpress installation
	 *
	 * @see eo_insert_event
	 *
	 * @Given /^there are events$/
	 */
	public function thereAreEvents(TableNode $table)
	{
		$tz = eo_get_blog_timezone();
		foreach ($table->getHash() as $postData) {
			
			foreach ( $postData as $key => $value ) {
				switch( $key ) {
					case 'start':
					case 'end':
					case 'until':
						$postData[$key] = new DateTime( $value, $tz );
						break;
				}
			}
			
			$event_id = eo_insert_event($postData);

			if (!is_int($event_id)) {
				
				$message = 'Invalid event information schema';
				
				if (is_wp_error($event_id)) {
					$message .= ': ' . $event_id->get_error_message();	
				}
				throw new \InvalidArgumentException( $message );
			}
		}
	}
	
	/**
	 * @Given there are venues
	 * 
	 * @see eo_insert_venue
	 */
	public function thereAreVenues(TableNode $venues)
	{
		foreach ($venues->getHash() as $venueData) {
			$return = eo_insert_venue($venueData['name'],$venueData);
			if (is_wp_error($return)) {
				throw new \InvalidArgumentException(sprintf("Invalid venue information schema: %s", $return->get_error_message()));
			}
		}
	}
	
	
	/**
	 * @Then the event :title should have the following schedule
	 */
	public function theEventShouldHaveTheFollowingSchedule($title, TableNode $fields)
	{
		
		$event = get_page_by_title( $title, OBJECT, 'event' );
		if ( ! $event ) {
			throw new \InvalidArgumentException( sprintf( 'Event "%s" was  not found', $title ) );
		}
		
		$correct = true;
		
		$schedule = eo_get_event_schedule( $event->ID );
		$tz       = eo_get_blog_timezone();
		$actual   = array();
		
		foreach ( $fields->getRowsHash() as $field => $value ) {
			
			switch( $field ) {
				case 'start':
				case 'end':
				case 'until':
					$actual_value = $schedule[$field]->format( 'Y-m-d h:ia' );	
					break;
				case 'recurrence':
					$actual_value = $schedule['schedule'];
					break;
				case 'recurrence_meta':
					$actual_value = $schedule['schedule_meta'];
					break;
				case 'frequency':
					$actual_value = $schedule[$field];
					break;
				default:
					$actual_value = false;
			}
			$actual[] = array( $field, $actual_value );
		}
		
		$actual_table = new TableNode( $actual );
		
		if ( $actual_table->getTableAsString() != $fields->getTableAsString() ) {
			throw new \Exception( sprintf(
				"Actual schedule:\n %s",
				$actual_table->getTableAsString()
			) );
		}
	
	}

	/**
	 * @Then there should be :num ":element" elements visible
	 */
	public function iThereShouldBeElementsVisible( $num, $element ) {

		$nodes = $this->getSession()->getPage()->findAll( 'css', $element );

		foreach ( $nodes as $index => $node ) {
			if ( ! $node->isVisible() ) {
				unset( $nodes[$index] );
			}
		}

		if ( count( $nodes ) != $num ) {
			throw new \Exception( sprintf(
				'%d %s found on the page, but should be %d.',
				count( $nodes ),
				$this->getMatchingElementRepresentation( 'css', $element, count( $nodes ) !== 1 ),
				$num
			));
		}

	}


	/**
	 * @Then the checkbox in :container for :label should be selected
	 */
	public function theCheckboxForShouldBeSelected( $container, $label ) {

		$page        = $this->getSession()->getPage();
		$container   = $page->find( 'css', $container );
		$label_nodes = $container->findAll( 'css', 'label' );

		$input = false;

		foreach ( $label_nodes as $label_node ) {

			if ( $label_node->getText() !== $label ) {
				continue;
			}

			if ( $label_node->hasAttribute( 'for' )  ) {
				$for   = $label_node->getAttribute( 'for' );
				$input = $container->find( 'css', '#' . $for );

				if ( ! $input ) {
					throw new \Exception(
						'A matching label was found but its for attribute did not point to an existing element: "%s"',
						$for
					);
				}
			} else {
				$input = $label_node->find( 'css', 'input[type=radio]' );
			}

			if ( ! $input ) {
				throw new \Exception( 'An input could not be found' );
			}
		}

		if ( ! $input->isChecked() ) {
			throw new Exception( 'Checkbox with label ' . $label . ' is not checked' );
		}
	}


	/**
	 * @Then /^(?:|I )should see "(?P<text>.+)" in the "(?P<selector>\w+)" element$/
	 */
	public function assertElementText( $text, $selector ) {
		foreach ( $this->getSession()->getPage()->findAll( 'css', $selector ) as $element ) {
			if ( strpos( strtolower( $text ), strtolower( $element->getText() ) === false ) ) {
				throw new \Exception( "Text '{$text}' is not found in the '{$selector}' element." );
			}
		}
	}

	/**
	 * @Then /^(?:|I )should not see "(?P<text>.+)" in the "(?P<selector>\w+)" element$/
	 */
	public function assertElementNotText( $text, $selector ) {
		foreach ( $this->getSession()->getPage()->findAll( 'css', $selector ) as $element ) {
			if ( strpos( strtolower( $text ), strtolower( $element->getText() ) !== false ) ) {
				throw new \Exception( "Text '{$text}' is found in the '{$selector}' element." );
			}
		}
	}


	/**
	 * @Then I should see the following in the repeated :element element
	 */
	public function iShouldSeeTheFollowingInTheRepeatedElementWithinTheContextOfTheElement2( $element, TableNode $table ) {

		$elements = $this->getSession()->getPage()->findAll( 'css', $element );
		$hash = $table->getHash();

		foreach ( $elements as $index => $element ) {
			try {
				if ( ! $element->isVisible() ) {
					unset( $elements[$index] );
				}
			} catch ( \Exception $e ) {
				//do nothing.
			}
		}

		$actual = array(
			array( 'text' => 'text' ),
		);
		foreach ( $elements as $n => $element ) {
			$actual[] = array( 'text' => $elements[$n]->getText() );
		}
		$actual_table = new TableNode( $actual );

		if ( $actual_table->getTableAsString() != $table->getTableAsString() ) {
			throw new \Exception( sprintf(
				"Found elements:\n %s",
				$actual_table->getTableAsString()
			) );
		}

	}

	/**
	 * Wait for AJAX to finish.
	 *
	 * @Then /^I wait for AJAX to finish$/
	 */
	public function iWaitForAjaxToFinish() {
		$this->getSession()->wait( 10000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))' );
	}



	/**
	 * Checks a checkbox/radio with specified label.
	 *
	 * @When /^(?:|I )check the element in "(?P<container>[^"]*)" with label "(?P<label>[^"]*)"$/
	 */
	public function assertTypedFormElementOnPage( $container, $label ) {

		$page        = $this->getSession()->getPage();
		$container   = $page->find( 'css', $container );
		$label_nodes = $container->findAll( 'css', 'label' );

		$input = false;

		foreach ( $label_nodes as $label_node ) {

			if ( $label_node->getText() !== $label ) {
				continue;
			}

			if ( $label_node->hasAttribute( 'for' )  ) {
				$for   = $label_node->getAttribute( 'for' );
				$input = $container->find( 'css', '#' . $for );

				if ( ! $input ) {
					throw new \Exception(
						'A matching label was found but its for attribute did not point to an existing element: "%s"',
						$for
					);
				}
			} else {
				$input = $label_node->find( 'css', 'input[type=radio]' );
			}

			if ( ! $input ) {
				throw new \Exception( 'An input could not be found' );
			}
		}

		$input->selectOption( $input->getAttribute( 'value' ), false );

	}

	/**
	 * @When I wait :seconds seconds
	 */
	public function iWaitSeconds( $seconds ) {
		$this->getSession()->wait( $seconds * 1000 );
	}


	/**
	 * @When /^I hover over the element "([^"]*)"$/
	 */
	public function iHoverOverTheElement($locator)
	{
		$session = $this->getSession(); // get the mink session
		$element = $session->getPage()->find('css', $locator); // runs the actual query and returns the element

		// errors must not pass silently
		if (null === $element) {
			throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $locator));
		}

		// ok, let's hover it
		$element->mouseOver();
	}

	/**
	 * @When /^I focus on the element "([^"]*)"$/
	 */
	public function iFocusOnTheElement($locator)
	{
		$session = $this->getSession(); // get the mink session
		$element = $session->getPage()->find('css', $locator); // runs the actual query and returns the element

		// errors must not pass silently
		if (null === $element) {
			throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $locator));
		}

		// ok, let's hover it
		$element->focus();
	}

}
