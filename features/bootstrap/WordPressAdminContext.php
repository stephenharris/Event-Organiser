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
		
		//h2s were used prior to 4.4 and h1s after
		$header2 = $this->getSession()->getPage()->find( 'css', '.wrap > h2' );
		$header1 = $this->getSession()->getPage()->find( 'css', '.wrap > h1' );
		
		if ( $header1 ) {
			$header_text  = $header1->getText();
			$add_new_link = $header1->find( 'css', 'a' )->getText();
		} else {
			$header_text  = $header2->getText();
			$add_new_link = $header2->find( 'css', 'a' )->getText();
		}			

		//The page headers can often incude an 'add new link'. Strip that out of the header text.
		$header_text  = trim( str_replace( $add_new_link, '', $header_text ) );

		if ( $header_text != $admin_page ) {
			throw new \Exception( sprintf( 'Actual page: %s',  $header_text ) );
		} 
	}
	
	
}

