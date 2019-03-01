<?php

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\TranslatedContextInterface,
	Behat\Behat\Context\Context,
	Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;

class WordPressAdminContext extends RawMinkContext implements Context, SnippetAcceptingContext {

	/**
	 * @Then the event summary should read :summary
	 */
	public function theEventSummaryShouldRead( $summary )
	{
		
		//$this->getSession()->getPage()->findblur();
		$summary_el = $this->getSession()->getPage()->find( 'css', '#eo-event-summary' );
		$summary_el->focus();
		
		if ( ! $summary_el ) {
			throw new \Exception( 'Event schedule summary could not be found.' );
		} elseif ( $summary != $summary_el->getText() ) {
			throw new \Exception( sprintf( 'Event schedule summary reads "%s"', $summary_el->getText() ) );
		}
		
	}

}

