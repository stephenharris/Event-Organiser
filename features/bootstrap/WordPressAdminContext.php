<?php

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\TranslatedContextInterface,
	Behat\Behat\Context\Context,
	Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;

class WordPressAdminContext extends Johnbillion\WordPressExtension\Context\WordPressAdminContext implements Context, SnippetAcceptingContext {

	/**
	 * @Then the event summary should read :summary
	 */
	public function theEventSummaryShouldRead( $summary )
	{
		
		$summary_el = $this->getSession()->getPage()->find( 'css', '#eo-event-summary' );
		
		if ( ! $summary_el ) {
			throw new \Exception( 'Event schedule summary could not be found.' );
		} elseif ( $summary != $summary_el->getText() ) {
			throw new \Exception( sprintf( 'Event schedule summary reads "%s"', $summary_el->getText() ) );
		}
		
	}

	/**
	 * @Then I should be on the :admin_page page
	 */
	public function iShouldBeOnThePage($admin_page)
	{
		$header2 = $this->getSession()->getPage()->find( 'css', '.wrap > h2' );
		$header1 = $this->getSession()->getPage()->find( 'css', '.wrap > h1' );
		
		if ( $header1 ) {
			if ( $header1->getText() != $admin_page ) {
				throw new PendingException( sprintf( 'Actual page: %s',  $header1->getText() ) );
			} 
		} else {
			if ( $header2->getText() != $admin_page ) {
				throw new PendingException( sprintf( 'Actual page: %s',  $header2->getText() ) );
			}
		}
		
	}
	
	
	
}

