<?php

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\TranslatedContextInterface,
	Behat\Behat\Context\Context,
	Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;

class WordPressAdminContext extends Johnbillion\WordPressExtension\Context\WordPressAdminContext implements Context, SnippetAcceptingContext {

}
