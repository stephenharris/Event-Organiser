<?php

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\TranslatedContextInterface,
	Behat\Behat\Context\Context,
	Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;

class WordPressPostListContext extends Johnbillion\WordPressExtension\Context\WordPressPostListContext implements Context, SnippetAcceptingContext {

	/**
	 * @When I sort events by :column :order
	 */
	public function iFollowSortEventsByDatetime($column, $order)
	{
		
		$order = ( strtolower( substr( $order, 0, 3 ) ) == 'asc' ? 'asc' : 'desc' );
		
		switch( strtolower( $column ) ) {
			case 'start date':
				$orderby = 'eventstart';
				break;
			case 'end date':
				$orderby = 'eventend';
				break;		
			case 'title':
				$orderby = 'title';
				break;
			
		}
		
		$this->visitPath( 
			sprintf( '/wp-admin/edit.php?post_type=event&orderby=%s&order=%s', $orderby, $order ) 
		);
		
	}
	
	
	
}
