<?php
/**
 * Class definition for Wikia\Search\Test\Controller\ControllerTest
 */
namespace Wikia\Search\Test\Controller;
use Wikia, WikiaSearchController, ReflectionMethod, ReflectionProperty, SearchEngine, Exception, F;
/**
 * Tests WikiaSearchController, currently in global namespace
 */
class SearchControllerTest extends Wikia\Search\Test\BaseTest {

	public function setUp() {
		$this->searchController = $this->getMockBuilder( 'WikiaSearchController' )
										->disableOriginalConstructor();
		$this->mockFactory = $this->getMockBuilder( 'Wikia\Search\QueryService\Factory' )
		                          ->setMethods( array( 'get', 'getFromConfig' ) )
		                          ->getMock();
		
		$this->proxyClass( 'Wikia\Search\QueryService\Factory', $this->mockFactory ); 
		parent::setUp();
	}

	/**
	 * @covers WikiaSearchController::index
	 */
	public function testIndex() {
		
		$methods = array( 'handleSkinSettings', 'getSearchConfigFromRequest', 
				'handleArticleMatchTracking', 'setPageTitle', 'setResponseValuesFromConfig' );
		$mockController = $this->searchController->setMethods( $methods )->getMock();
		
		$mockConfig = $this->getMock( 'Wikia\Search\Config', array( 'getQuery' ) );
		$mockQuery = $this->getMock( 'Wikia\Search\Query\Select', array( 'hasTerms' ), array( 'foo' ) );
		
		$mockSearch = $this->getMockBuilder( 'Wikia\Search\QueryService\Select\OnWiki' )
		                   ->setMethods( array( 'search', 'getMatch' ) )
		                   ->disableOriginalConstructor()
		                   ->getMock();
		
		$mockFactory = $this->getMockBuilder( 'Wikia\Search\QueryService\Factory' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'getFromConfig' ) )
		                    ->getMock();
		
		$mockController
		    ->expects( $this->once() )
		    ->method ( 'handleSkinSettings' )
		;
		$mockController
		    ->expects( $this->once() )
		    ->method ( 'getSearchConfigFromRequest' )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'getQuery' )
		    ->will   ( $this->returnValue( $mockQuery ) )
		;
		$mockQuery
		    ->expects( $this->once() )
		    ->method ( 'hasTerms' )
		    ->will   ( $this->returnValue( true ) )
		;
		$mockFactory
		    ->expects( $this->once() )
		    ->method ( 'getFromConfig' )
		    ->with   ( $mockConfig )
		    ->will   ( $this->returnValue( $mockSearch ) )
		;
		$mockSearch
		    ->expects( $this->once() )
		    ->method ( 'getMatch' )
		;
		$mockController
		    ->expects( $this->once() )
		    ->method ( 'handleArticleMatchTracking' )
		    ->with   ( $mockConfig )
		;
		$mockSearch
		    ->expects( $this->once() )
		    ->method( 'search' )
		;
		$mockController
		    ->expects( $this->once() )
		    ->method ( 'setPageTitle' )
		    ->with   ( $mockConfig )
		;
		$mockController
		    ->expects( $this->once() )
		    ->method ( 'setResponseValuesFromConfig' )
		    ->with   ( $mockConfig )
		;
		$reflProperty = new ReflectionProperty( 'WikiaSearchController', 'queryServiceFactory' );
		$reflProperty->setAccessible( true );
		$reflProperty->setValue( $mockController, $mockFactory );
		$mockController->index();
	}

	public function testHandleArticleMatchTrackingPage2() {
		$mockController = $this->searchController->setMethods( null )->getMock();
		$mockConfig = $this->getMock( 'Wikia\Search\Config', array( 'getPage' ) );
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'getPage' )
		    ->will   ( $this->returnValue( 2 ) )
		;
		$method = new ReflectionMethod( 'WikiaSearchController', 'handleArticleMatchTracking' );
		$method->setAccessible( true );
		$this->assertFalse(
				$method->invoke( $mockController, $mockConfig ),
				"WikiaSearchController::handleArticleMatchTracking should return false if not on page 1"
				);
		
	}

	/**
	 * @covers WikiaSearchController::handleArticleMatchTracking
	 */
	public function testArticleMatchTrackingWithMatch() {
		$mockController = $this->searchController->setMethods( array( 'getVal' ) )->getMock();
		$searchConfig = $this->getMock( 'Wikia\Search\Config', array( 'getQuery', 'getPage', 'hasArticleMatch', 'getArticleMatch' ) );
		$mockQuery = $this->getMock( 'Wikia\Search\Query', array( 'getSanitizedQuery' ) );
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getFullUrl' ) )
		                  ->getMock();
		$mockResponse = $this->getMock( 'WikiaResponse', array( 'redirect' ), array( 'html' ) );
		$mockMatch = $this->getMockBuilder( 'Wikia\Search\Match\Article' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getId' ) )
		                  ->getMock();
		$mockArticle = $this->getMockBuilder( 'Article' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'getTitle' ) )
		                    ->getMock();
		$mockTrack = $this->getMock( 'Track', array( 'event' ) );
		$mockWrapper = $this->getMockBuilder( 'WikiaFunctionWrapper' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'RunHooks' ) )
		                    ->getMock();

		$originalQuery = 'foo';
		$redirectUrl = 'http://foo.wikia.com/Wiki/foo';
		
		$searchConfig
			->expects	( $this->any() )
			->method	( 'getPage' )
			->will		( $this->returnValue( 1 ) )
		;
		$searchConfig
		    ->expects( $this->any() )
		    ->method ( 'getQuery' )
		    ->will   ( $this->returnValue( $mockQuery ) )
	    ;
		$mockQuery
		    ->expects( $this->once() )
		    ->method ( 'getSanitizedQuery' )
		    ->will   ( $this->returnValue( 'foo' ) )
		;
		$searchConfig
		    ->expects( $this->any() )
		    ->method ( 'getArticleMatch' )
		    ->will   ( $this->returnValue( $mockMatch) )
		;
		$mockMatch
		    ->expects( $this->once() )
		    ->method ( 'getId' )
		    ->will   ( $this->returnValue( 123 ) )
		;
		$mockArticle
		    ->expects( $this->once() )
		    ->method ( 'getTitle' )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$searchConfig
			->expects	( $this->any() )
			->method	( 'hasArticleMatch' )
			->will		( $this->returnValue( true ) )
		;
		$mockController
			->expects	( $this->once() )
			->method	( 'getVal' )
			->with		( 'fulltext', '0' )
			->will		( $this->returnValue( '0' ) )
		;
		$mockWrapper
		    ->expects( $this->once() )
		    ->method ( 'RunHooks' )
		    ->with   ( 'SpecialSearchIsgomatch', array( $mockTitle, $originalQuery ) )
		;
		$mockTrack
		    ->staticExpects( $this->once() )
		    ->method ( 'event' )
		    ->with   ( 'search_start_gomatch', array( 'sterm' => $originalQuery, 'rver' => 0 ) )
		;
		$mockTitle
			->expects	( $this->any() )
			->method	( 'getFullURL' )
			->will		( $this->returnValue( $redirectUrl ) )
		;
		$mockResponse
			->expects	( $this->once() )
			->method	( 'redirect' )
			->with      ( $redirectUrl )
			->will		( $this->returnValue( true ) )
		;

		$responserefl = new ReflectionProperty( 'WikiaSearchController', 'response' );
		$responserefl->setAccessible( true );
		$responserefl->setValue( $mockController, $mockResponse );

		$wfrefl = new ReflectionProperty( 'WikiaSearchController', 'wf' );
		$wfrefl->setAccessible( true );
		$wfrefl->setValue( $mockController, $mockWrapper );

		$this->mockClass( 'Article', $mockArticle );
		$this->proxyClass( 'Article', $mockArticle, 'newFromID' );
		$this->mockClass( 'Track', $mockTrack );
		$this->proxyClass( 'Track', $mockTrack );
		$this->mockApp();

		$method = new ReflectionMethod( 'WikiaSearchController', 'handleArticleMatchTracking' );
		$method->setAccessible( true );

		$this->assertTrue(
				$method->invoke( $mockController, $searchConfig ),
				'WikiaSearchController::handleArticleMatchTracking should return true.'
		);
	}

	/**
	 * @covers WikiaSearchController::handleArticleMatchTracking
	 */
	public function testArticleMatchTrackingWithoutMatch() {

		$mockController = $this->searchController->setMethods( array( 'getVal' ) )->getMock();
		$searchConfig = $this->getMock( 'Wikia\Search\Config', array( 'getQuery', 'getPage', 'hasArticleMatch' ) );
		$mockQuery = $this->getMock( 'Wikia\Search\Query', array( 'getSanitizedQuery' ) );
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getFullUrl' ) )
		                  ->getMock();
		$mockResponse = $this->getMock( 'WikiaResponse', array( 'redirect' ), array( 'html' ) );
		$mockWrapper = $this->getMockBuilder( 'WikiaFunctionWrapper' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'RunHooks' ) )
		                    ->getMock();

		$originalQuery = 'foo';
		$redirectUrl = 'http://foo.wikia.com/Wiki/foo';
		
		$searchConfig
			->expects	( $this->any() )
			->method	( 'getPage' )
			->will		( $this->returnValue( 1 ) )
		;
		$searchConfig
		    ->expects( $this->any() )
		    ->method ( 'getQuery' )
		    ->will   ( $this->returnValue( $mockQuery ) )
		;
		$mockQuery
		    ->expects( $this->once() )
		    ->method ( 'getSanitizedQuery' )
		    ->will   ( $this->returnValue( $originalQuery ) )
		;
		$searchConfig
			->expects	( $this->any() )
			->method	( 'hasArticleMatch' )
			->will		( $this->returnValue( false ) )
		;
		$mockWrapper
		    ->expects( $this->once() )
		    ->method ( 'RunHooks' )
		    ->with   ( 'SpecialSearchNogomatch', array( $mockTitle ) )
		;


		$responserefl = new ReflectionProperty( 'WikiaSearchController', 'response' );
		$responserefl->setAccessible( true );
		$responserefl->setValue( $mockController, $mockResponse );

		$wfrefl = new ReflectionProperty( 'WikiaSearchController', 'wf' );
		$wfrefl->setAccessible( true );
		$wfrefl->setValue( $mockController, $mockWrapper );

		$this->mockClass( 'Title', $mockTitle );
		$this->proxyClass( 'Title', $mockTitle, 'newFromText' );
		$this->mockApp();

		$method = new ReflectionMethod( 'WikiaSearchController', 'handleArticleMatchTracking' );
		$method->setAccessible( true );

		$this->assertTrue(
				$method->invoke( $mockController, $searchConfig ),
				'WikiaSearchController::handleArticleMatchTracking should return true.'
		);
	}

	/**
	 * @covers WikiaSearchController::handleArticleMatchTracking
	 */
	public function testHandleArticleMatchTrackingWithoutGoSearch() {
		$mockController = $this->searchController->setMethods( array( 'getVal' ) )->getMock();
		$searchConfig = $this->getMock( 'Wikia\Search\Config', array( 'getQuery', 'getPage', 'hasArticleMatch', 'getArticleMatch' ) );
		$mockQuery = $this->getMock( 'Wikia\Search\Query', array( 'getSanitizedQuery' ) );
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getFullUrl' ) )
		                  ->getMock();
		$mockMatch = $this->getMockBuilder( 'Wikia\Search\Match\Article' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getId' ) )
		                  ->getMock();
		$mockArticle = $this->getMockBuilder( 'Article' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'getTitle' ) )
		                    ->getMock();
		$mockResponse = $this->getMock( 'WikiaResponse', array( 'redirect' ), array( 'html' ) );
		$mockTrack = $this->getMock( 'Track', array( 'event' ) );
		$mockWrapper = $this->getMockBuilder( 'WikiaFunctionWrapper' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'RunHooks' ) )
		                    ->getMock();

		$originalQuery = 'foo';
		$redirectUrl = 'http://foo.wikia.com/Wiki/foo';
		
		$searchConfig
		    ->expects( $this->once() )
		    ->method ( 'getArticleMatch' )
		    ->will   ( $this->returnValue( $mockMatch ) )
		;
		$mockMatch
		    ->expects( $this->once() )
		    ->method ( 'getId' )
		    ->will   ( $this->returnValue( 123 ) )
		;
		$mockArticle
		    ->expects( $this->once() )
		    ->method ( 'getTitle' )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$searchConfig
			->expects	( $this->any() )
			->method	( 'getPage' )
			->will		( $this->returnValue( 1 ) )
		;
		$searchConfig
		    ->expects( $this->any() )
		    ->method ( 'getQuery' )
		    ->will   ( $this->returnValue( $mockQuery ) )
		;
		$mockQuery
		    ->expects( $this->once() )
		    ->method ( 'getSanitizedQuery' )
		    ->will   ( $this->returnValue( $originalQuery ) )
		;
		$searchConfig
			->expects	( $this->any() )
			->method	( 'hasArticleMatch' )
			->will		( $this->returnValue( true ) )
		;
		$mockController
			->expects	( $this->once() )
			->method	( 'getVal' )
			->with		( 'fulltext', '0' )
			->will		( $this->returnValue( 'search' ) )
		;
		$mockTrack
		    ->staticExpects( $this->once() )
		    ->method ( 'event' )
		    ->with   ( 'search_start_match', array( 'sterm' => $originalQuery, 'rver' => 0 ) )
		;

		$responserefl = new ReflectionProperty( 'WikiaSearchController', 'response' );
		$responserefl->setAccessible( true );
		$responserefl->setValue( $mockController, $mockResponse );

		$wfrefl = new ReflectionProperty( 'WikiaSearchController', 'wf' );
		$wfrefl->setAccessible( true );
		$wfrefl->setValue( $mockController, $mockWrapper );

		$this->proxyClass( 'Article', $mockArticle, 'newFromID' );
		$this->mockClass( 'Track', $mockTrack );
		$this->proxyClass( 'Track', $mockTrack );
		$this->mockApp();

		$method = new ReflectionMethod( 'WikiaSearchController', 'handleArticleMatchTracking' );
		$method->setAccessible( true );

		$this->assertTrue(
				$method->invoke( $mockController, $searchConfig, $mockTrack ),
				'WikiaSearchController::handleArticleMatchTracking should return true.'
		);
	}


	
	/**
	 * @covers WikiaSearchController::pagination
	 */
	public function testPaginationWithoutConfig() {

		$mockController		=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockTitle			=	$this->getMockBuilder( 'Title' )->disableOriginalConstructor()->getMock();
		$mockResponse		=	$this->getMock( 'WikiaResponse', array( 'redirect', 'setVal' ), array( 'html' ) );
		$mockRequest		=	$this->getMock( 'WikiaRequest', array( 'getVal' ), array( array() ) );
		$configMethods		=	array( 'getResultsFound', 'getPage', 'getQuery', 'getNumPages', 'getIsInterWiki',
										'getSkipCache', 'getDebug', 'getNamespaces', 'getAdvanced', 'getIncludeRedirects', 'getLimit' );
		$mockConfig			=	$this->getMock( 'Wikia\Search\Config', $configMethods );

		$mockWgRefl = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$mockWgRefl->setAccessible( true );
		$mockWgRefl->setValue( $mockController, (object) array( 'Title' => $mockTitle ) );

		$mockController
			->expects	( $this->any() )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( false ) )
		;
		$e = null;
		try {
			$mockController->pagination();
			$this->assertFalse(
					true,
					'WikiaSearchController::pagination should throw an exception if the "config" is not set in the request.'
			);
		} catch ( Exception $e ) { }

		$this->assertInstanceOf(
				'Exception',
				$e,
				'WikiaSearchController::pagination should throw an exception if an instance of Wikia\Search\Config is not set in the request'
		);
	}

	/**
	 * @covers WikiaSearchController::pagination
	 */
	public function testPaginationMalformedConfig() {

		$mockController		=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockTitle			=	$this->getMockBuilder( 'Title' )->disableOriginalConstructor()->getMock();
		$mockResponse		=	$this->getMock( 'WikiaResponse', array( 'redirect', 'setVal' ), array( 'html' ) );
		$mockRequest		=	$this->getMock( 'WikiaRequest', array( 'getVal' ), array( array() ) );
		$configMethods		=	array( 'getResultsFound', 'getPage', 'getQuery', 'getNumPages', 'getIsInterWiki',
										'getSkipCache', 'getDebug', 'getNamespaces', 'getAdvanced', 'getIncludeRedirects', 'getLimit' );
		$mockConfig			=	$this->getMock( 'Wikia\Search\Config', $configMethods );

		$mockWgRefl = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$mockWgRefl->setAccessible( true );
		$mockWgRefl->setValue( $mockController, (object) array( 'Title' => $mockTitle ) );

		$mockController
			->expects	( $this->any() )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( 'foo' ) )
		;
		$e = null;
		try {
			$mockController->pagination();
			$this->assertFalse(
					true,
					'WikiaSearchController::pagination should throw an exception if the "config" is not set in the request.'
			);
		} catch ( Exception $e ) { }

		$this->assertInstanceOf(
				'Exception',
				$e,
				'WikiaSearchController::pagination should throw an exception if an instance of Wikia\Search\Config is not set in the request'
		);
	}


	/**
	 * @covers WikiaSearchController::pagination
	 */
	public function testPaginationWithConfigNoResults1() {
		$mockController		=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockTitle			=	$this->getMockBuilder( 'Title' )->disableOriginalConstructor()->getMock();
		$mockResponse		=	$this->getMock( 'WikiaResponse', array( 'redirect', 'setVal' ), array( 'html' ) );
		$mockRequest		=	$this->getMock( 'WikiaRequest', array( 'getVal' ), array( array() ) );
		$configMethods		=	array( 'getResultsFound', 'getPage', 'getQuery', 'getNumPages', 'getIsInterWiki',
										'getSkipCache', 'getDebug', 'getNamespaces', 'getAdvanced', 'getIncludeRedirects', 'getLimit' );
		$mockConfig			=	$this->getMock( 'Wikia\Search\Config', $configMethods );

		$mockWgRefl = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$mockWgRefl->setAccessible( true );
		$mockWgRefl->setValue( $mockController, (object) array( 'Title' => $mockTitle ) );

		$mockController
			->expects	( $this->at( 0 ) )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( $mockConfig ) )
		;
		$mockConfig
			->expects	( $this->at( 0 ) )
			->method	( 'getResultsFound' )
			->will		( $this->returnValue( false ) )
		;
		$this->assertFalse(
				$mockController->pagination(),
				'WikiaSearchController::pagination should return false if search config set in the request does not have its resultsFound value set, or that value is 0.'
		);
	}
	
	/**
	 * @covers WikiaSearchController::pagination
	 */
	public function testPaginationWithConfigNoResults2() {
		$mockController		=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockTitle			=	$this->getMockBuilder( 'Title' )->disableOriginalConstructor()->getMock();
		$mockResponse		=	$this->getMock( 'WikiaResponse', array( 'redirect', 'setVal' ), array( 'html' ) );
		$mockRequest		=	$this->getMock( 'WikiaRequest', array( 'getVal' ), array( array() ) );
		$configMethods		=	array( 'getResultsFound', 'getPage', 'getQuery', 'getNumPages', 'getIsInterWiki',
										'getSkipCache', 'getDebug', 'getNamespaces', 'getAdvanced', 'getIncludeRedirects', 'getLimit' );
		$mockConfig			=	$this->getMock( 'Wikia\Search\Config', $configMethods );

		$mockWgRefl = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$mockWgRefl->setAccessible( true );
		$mockWgRefl->setValue( $mockController, (object) array( 'Title' => $mockTitle ) );

		$mockController
			->expects	( $this->at( 0 ) )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( $mockConfig ) )
		;
		$mockConfig
			->expects	( $this->at( 0 ) )
			->method	( 'getResultsFound' )
			->will		( $this->returnValue( 0 ) )
		;
		$this->assertFalse(
				$mockController->pagination(),
				'WikiaSearchController::pagination should return false if search config set in the request does not have its resultsFound value set, or that value is 0.'
		);
	}

	/**
	 * @covers WikiaSearchController::pagination
	 */
	public function testPaginationWithConfig() {
		$mockController		=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockTitle			=	$this->getMockBuilder( 'Title' )->disableOriginalConstructor()->getMock();
		$mockResponse		=	$this->getMock( 'WikiaResponse', array( 'redirect', 'setVal' ), array( 'html' ) );
		$mockRequest		=	$this->getMock( 'WikiaRequest', array( 'getVal' ), array( array() ) );
		$configMethods		=	array( 'getResultsFound', 'getPage', 'getQuery', 'getNumPages', 'getIsInterWiki',
										'getSkipCache', 'getDebug', 'getNamespaces', 'getAdvanced', 'getIncludeRedirects',
										'getLimit', 'getPublicFilterKeys', 'getRank' );
		$mockConfig			=	$this->getMock( 'Wikia\Search\Config', $configMethods );
		$mockQuery = $this->getMock( 'Wikia\Search\Query\Select', array( 'getSanitizedQuery' ), array( 'foo' ) );

		$mockWgRefl = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$mockWgRefl->setAccessible( true );
		$mockWgRefl->setValue( $mockController, (object) array( 'Title' => $mockTitle ) );

		$mockController
			->expects	( $this->at( 0 ) )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( $mockConfig ) )
		;
		$incr = 0;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getResultsFound' )
			->will		( $this->returnValue( 200 ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getPage' )
			->will		( $this->returnValue( 2 ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getNumPages' )
			->will		( $this->returnValue( 10 ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getQuery' )
			->will		( $this->returnValue( $mockQuery ) )
		;
		$mockQuery
		    ->expects( $this->once() )
		    ->method ( 'getSanitizedQuery' )
		    ->will   ( $this->returnValue( 'foo' ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getNumPages' )
			->will		( $this->returnValue( 10 ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getIsInterWiki' )
			->will		( $this->returnValue( false ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getResultsFound' )
			->will		( $this->returnValue( 200 ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getSkipCache' )
			->will		( $this->returnValue( false ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getDebug' )
			->will		( $this->returnValue( false ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getNamespaces' )
			->will		( $this->returnValue( array( NS_MAIN ) ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getAdvanced' )
			->will		( $this->returnValue( false ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getIncludeRedirects' )
			->will		( $this->returnValue( false ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getLimit' )
			->will		( $this->returnValue( 20 ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getPublicFilterKeys' )
			->will		( $this->returnValue( array( 'is_image' ) ) )
		;
		$mockConfig
			->expects	( $this->at( $incr++ ) )
			->method	( 'getRank' )
			->will		( $this->returnValue( 'default' ) )
		;
		$incr2 = 1;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'query', 'foo' )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'pagesNum', '10' )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'currentPage', 2 )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'windowFirstPage', 1 )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'windowLastPage', 7 )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'pageTitle', $mockTitle )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'crossWikia', false )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'resultsCount', 200 )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'skipCache', false )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'debug', false )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'namespaces', array( NS_MAIN ) )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'advanced', false )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'redirs', false )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'limit', 20 )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'filters', array( 'is_image' ) )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'rank', 'default' )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'getVal' )
			->with		( 'by_category', false )
			->will		( $this->returnValue( false ) )
		;
		$mockController
			->expects	( $this->at( $incr2++ ) )
			->method	( 'setVal' )
			->with		( 'by_category', false )
		;


		$mockController->pagination();
	}

	/**
	 * @covers WikiaSearchController::tabs
	 */
	public function testTabsWithoutConfig() {
		$mockController		=	$this->searchController->setMethods( array( 'getVal' ) )->getMock();
		$mockController
			->expects	( $this->any() )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( false ) )
		;
		$e = null;
		$this->mockApp();
		try {
		    $mockController->tabs();
		    $this->assertFalse(
		            true,
		            'WikiaSearchController::tabs should throw an exception if the "config" is not set in the request.'
		    );
		} catch ( Exception $e ) { }
		$this->assertInstanceOf(
				'Exception',
				$e,
				'WikiaSearchController::tabs should throw an exception if search config is not set'
		);
	}

	/**
	 * @covers WikiaSearchController::tabs
	 */
	public function testTabsWithBadConfig() {
		$mockController		=	$this->searchController->setMethods( array( 'getVal' ) )->getMock();
		$mockController
			->expects	( $this->any() )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( 'foo' ) )
		;
		$e = null;
		$this->mockApp();
		try {
		    $mockController->tabs();
		    $this->assertFalse(
		            true,
		            'WikiaSearchController::tabs should throw an exception if the "config" is not set in the request.'
		    );
		} catch ( Exception $e ) { }
		$this->assertInstanceOf(
				'Exception',
				$e,
				'WikiaSearchController::tabs should throw an exception if search config is set incorrectly'
		);
	}

	/**
	 * @covers WikiaSearchController::tabs
	 */
	public function testTabs() {
		$mockController		=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockSearchConfig	=	$this->getMockBuilder( 'Wikia\Search\Config' )
									->disableOriginalConstructor()
									->setMethods( array( 'getNamespaces', 'getQuery', 'getSearchProfiles', 'getIncludeRedirects', 'getActiveTab', 'getFilterQueries', 'getRank' ) )
									->getMock();
		
		$mockQuery = $this->getMock( 'Wikia\Search\Query\Select', array( 'getSanitizedQuery' ), array( 'foo' ) );
		

		$this->mockGlobalVariable( 'wgDefaultSearchProfile', SEARCH_PROFILE_DEFAULT );

		$defaultNamespaces = array( NS_MAIN, NS_CATEGORY );

		$searchProfileArray = array(
	            SEARCH_PROFILE_DEFAULT => array(
	                    'message' => 'wikiasearch2-tabs-articles',
	                    'tooltip' => 'searchprofile-articles-tooltip',
	                    'namespaces' => $defaultNamespaces,
	                    'namespace-messages' => SearchEngine::namespacesAsText( $defaultNamespaces ),
	            ),
	            SEARCH_PROFILE_IMAGES => array(
	                    'message' => 'wikiasearch2-tabs-photos-and-videos',
	                    'tooltip' => 'searchprofile-images-tooltip',
	                    'namespaces' => array( NS_FILE ),
	            ),
	            SEARCH_PROFILE_USERS => array(
	                    'message' => 'wikiasearch2-users',
	                    'tooltip' => 'wikiasearch2-users-tooltip',
	                    'namespaces' => array( NS_USER )
	            ),
	            SEARCH_PROFILE_ALL => array(
	                    'message' => 'searchprofile-everything',
	                    'tooltip' => 'searchprofile-everything-tooltip',
	                    'namespaces' => array( NS_MAIN, NS_TALK, NS_CATEGORY, NS_CATEGORY_TALK, NS_FILE, NS_USER ),
	            ),
	            SEARCH_PROFILE_ADVANCED => array(
	                    'message' => 'searchprofile-advanced',
	                    'tooltip' => 'searchprofile-advanced-tooltip',
	                    'namespaces' => array( NS_MAIN, NS_CATEGORY ),
	                    'parameters' => array( 'advanced' => 1 ),
	            )
		);

		$form = array(
				'no_filter' =>          false,
				'by_category' =>        false,
				'cat_videogames' =>     false,
				'cat_entertainment' =>  false,
				'cat_lifestyle' =>      false,
				'is_hd' =>              false,
				'is_image' =>           false,
				'is_video' =>           false,
				'sort_default' =>       true,
				'sort_longest' =>       false,
				'sort_newest' =>        false,
				'no_filter' =>          true,
		);

		$incr = 0;

		$wg = (object) array( 'CityId' => 123 );

		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( $mockSearchConfig ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getFilterQueries' )
			->will		( $this->returnValue( array() ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getRank' )
			->will		( $this->returnValue( 'default' ) )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'getVal' )
			->with		( 'by_category', false )
			->will		( $this->returnValue( false ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getQuery' )
			->will		( $this->returnValue( $mockQuery ) )
		;
		$mockQuery
		    ->expects   ( $this->once() )
		    ->method    ( 'getSanitizedQuery' )
		    ->will      ( $this->returnValue( 'foo' ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getSearchProfiles' )
			->will		( $this->returnValue( $searchProfileArray ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getIncludeRedirects' )
			->will		( $this->returnValue( false ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getActiveTab' )
			->will		( $this->returnValue( 'default' ) )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'bareterm', 'foo' )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'searchProfiles', $searchProfileArray )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'redirs', false )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'activeTab', 'default' )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'form', $form )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'is_video_wiki', false )
		;

		$this->mockApp();

		$reflWg = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$reflWg->setAccessible( true );
		$reflWg->setValue( $mockController, $wg );

		$mockController->tabs();
	}

	/**
	 * @covers WikiaSearchController::tabs
	 */
	public function testTabsVideoWithNoFilter() {
		$mockController		=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockSearchConfig	=	$this->getMockBuilder( 'Wikia\Search\Config' )
									->disableOriginalConstructor()
									->setMethods( array( 'getNamespaces', 'getQuery', 'getSearchProfiles', 'getIncludeRedirects', 'getActiveTab', 'getFilterQueries', 'getRank' ) )
									->getMock();

		$mockQuery = $this->getMock( 'Wikia\Search\Query\Select', array( 'getSanitizedQuery' ),  array( 'foo' ) );
		
		$this->mockGlobalVariable( 'wgDefaultSearchProfile', SEARCH_PROFILE_DEFAULT );

		$defaultNamespaces = array( NS_MAIN, NS_CATEGORY );

		$searchProfileArray = array(
	            SEARCH_PROFILE_DEFAULT => array(
	                    'message' => 'wikiasearch2-tabs-articles',
	                    'tooltip' => 'searchprofile-articles-tooltip',
	                    'namespaces' => $defaultNamespaces,
	                    'namespace-messages' => SearchEngine::namespacesAsText( $defaultNamespaces ),
	            ),
	            SEARCH_PROFILE_IMAGES => array(
	                    'message' => 'wikiasearch2-tabs-photos-and-videos',
	                    'tooltip' => 'searchprofile-images-tooltip',
	                    'namespaces' => array( NS_FILE ),
	            ),
	            SEARCH_PROFILE_USERS => array(
	                    'message' => 'wikiasearch2-users',
	                    'tooltip' => 'wikiasearch2-users-tooltip',
	                    'namespaces' => array( NS_USER )
	            ),
	            SEARCH_PROFILE_ALL => array(
	                    'message' => 'searchprofile-everything',
	                    'tooltip' => 'searchprofile-everything-tooltip',
	                    'namespaces' => array( NS_MAIN, NS_TALK, NS_CATEGORY, NS_CATEGORY_TALK, NS_FILE, NS_USER ),
	            ),
	            SEARCH_PROFILE_ADVANCED => array(
	                    'message' => 'searchprofile-advanced',
	                    'tooltip' => 'searchprofile-advanced-tooltip',
	                    'namespaces' => array( NS_MAIN, NS_CATEGORY ),
	                    'parameters' => array( 'advanced' => 1 ),
	            )
		);

		$form = array(
				'no_filter' =>          0,
				'by_category' =>        false,
				'cat_videogames' =>     false,
				'cat_entertainment' =>  false,
				'cat_lifestyle' =>      false,
				'is_hd' =>              false,
				'is_image' =>           false,
				'is_video' =>           1,
				'sort_default' =>       true,
				'sort_longest' =>       false,
				'sort_newest' =>        false,
		);

		$incr = 0;

		$wg = (object) array( 'CityId' => Wikia\Search\QueryService\Select\Video::VIDEO_WIKI_ID );

		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( $mockSearchConfig ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getFilterQueries' )
			->will		( $this->returnValue( array() ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getRank' )
			->will		( $this->returnValue( 'default' ) )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'getVal' )
			->with		( 'by_category', false )
			->will		( $this->returnValue( false ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getQuery' )
			->will		( $this->returnValue( $mockQuery ) )
		;
		$mockQuery
		    ->expects   ( $this->once() )
		    ->method    ( 'getSanitizedQuery' )
		    ->will      ( $this->returnValue( 'foo' ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getSearchProfiles' )
			->will		( $this->returnValue( $searchProfileArray ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getIncludeRedirects' )
			->will		( $this->returnValue( false ) )
		;
		$mockSearchConfig
			->expects	( $this->once() )
			->method	( 'getActiveTab' )
			->will		( $this->returnValue( 'default' ) )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'bareterm', 'foo' )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'searchProfiles', $searchProfileArray )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'redirs', false )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'activeTab', 'default' )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'form', $form )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'is_video_wiki', true )
		;

		$this->mockApp();

		$reflWg = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$reflWg->setAccessible( true );
		$reflWg->setValue( $mockController, $wg );

		$mockController->tabs();
	}

	/**
	 * @covers WikiaSearchController::advancedBox
	 */
	public function testAdvancedBoxWithoutConfig() {
		$mockController			=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();

		$mockController
			->expects	( $this->any() )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( false ) )
		;
		$e = null;
		try {
		    $mockController->advancedBox();
		    $this->assertFalse(
		            true,
		            'WikiaSearchController::advancedBox should throw an exception if the "config" is not set in the request.'
		    );
		} catch ( Exception $e ) { }
		$this->assertInstanceOf(
				'Exception',
				$e,
				'WikiaSearchController::advancedBox should throw an exception if there is no search config set'
		);

	}

	/**
	 * @covers WikiaSearchController::advancedBox
	 */
	public function testAdvancedBoxWithBadConfig() {
		$mockController			=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();

		$mockController
			->expects	( $this->any() )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( 'foo' ) )
		;
		$e = null;
		try {
		    $mockController->advancedBox();
		    $this->assertFalse(
		            true,
		            'WikiaSearchController::advancedBox should throw an exception if the "config" is set incorrectly in the request.'
		    );
		} catch ( Exception $e ) { }
		$this->assertInstanceOf(
				'Exception',
				$e,
				'WikiaSearchController::advancedBox should throw an exception if there is an improper search config set'
		);

	}

	/**
	 * @covers WikiaSearchController::advancedBox
	 */
	public function testAdvancedBox() {
		$mockController			=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockResponse			=	$this->getMock( 'WikiaResponse', array( 'redirect', 'setVal' ), array( 'html' ) );
		$mockRequest			=	$this->getMock( 'WikiaRequest', array( 'getVal' ), array( array() ) );
		$mockSearchConfig		=	$this->getMock( 'Wikia\Search\Config', array( 'getNamespaces', 'getIncludeRedirects', 'getAdvanced' ) );
		$mockSearchEngine		=	$this->getMock( 'SearchEngine', array( 'searchableNamespaces' ) );
		$searchableNamespaces	=	array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11);

		$mockController
			->expects	( $this->any() )
			->method	( 'getVal' )
			->with		( 'config', false )
			->will		( $this->returnValue( $mockSearchConfig ) )
		;
		$mockSearchEngine
			->staticExpects	( $this->any() )
			->method		( 'searchableNamespaces' )
			->will			( $this->returnValue( $searchableNamespaces ) )
		;
		$mockController
			->expects	( $this->at( 1 ) )
			->method	( 'setVal' )
			->with		( 'namespaces', array( 0, 14 ) )
		;
		$mockController
			->expects	( $this->at( 2 ) )
			->method	( 'setVal' )
			->with		( 'searchableNamespaces', $searchableNamespaces )
		;
		$mockController
			->expects	( $this->at( 3 ) )
			->method	( 'setVal' )
			->with		( 'redirs', true )
		;
		$mockController
			->expects	( $this->at( 4 ) )
			->method	( 'setVal' )
			->with		( 'advanced', true )
		;
		$mockSearchConfig
			->expects	( $this->any() )
			->method	( 'getNamespaces' )
			->will		( $this->returnValue( array( 0, 14) ) )
		;
		$mockSearchConfig
			->expects	( $this->any() )
			->method	( 'getIncludeRedirects' )
			->will		( $this->returnValue( true ) )
		;
		$mockSearchConfig
			->expects	( $this->any() )
			->method	( 'getAdvanced' )
			->will		( $this->returnValue( true ) )
		;

		$this->mockClass( 'SearchEngine', $mockSearchEngine );

		$this->mockApp();

		F::setInstance( 'SearchEngine', $mockSearchEngine );

		$mockController->advancedBox();

	}

	/**
	 * @covers WikiaSearchController::isCorporateWiki
	 */
	public function testIsCorporateWiki() {

		$method = new ReflectionMethod( 'WikiaSearchController', 'isCorporateWiki' );
		$method->setAccessible( true );

		$this->mockGlobalVariable( 'wgEnableWikiaHomePageExt', false );
		$this->mockApp();

		$this->assertFalse(
				$method->invoke( $this->searchController->getMock() ),
				'WikiaSearchController::isCorporateWiki should return false if wgEnableWikiaHomePageExt is empty.'
		);

		$this->mockGlobalVariable( 'wgEnableWikiaHomePageExt', null );
		$this->mockApp();

		$this->assertFalse(
		        $method->invoke( $this->searchController->getMock() ),
		        'WikiaSearchController::isCorporateWiki should return false if wgEnableWikiaHomePageExt is empty.'
		);

		$this->mockGlobalVariable( 'wgEnableWikiaHomePageExt', true );
		$this->mockApp();

		$this->searchController->getMock()->setApp( F::app() );

		$this->assertFalse(
		        $method->invoke( $this->searchController->getMock() ),
		        'WikiaSearchController::isCorporateWiki should return false if wgEnableWikiaHomePageExt is not empty.'
		);

	}

	/**
	 * @see WikiaSearch
	 *
	public function testSkinSettings() {

		$mockSearchController	=	$this->getMockBuilder( 'WikiaSearchController' )
										->disableOriginalConstructor()
										->setMethods( array( 'overrideTemplate', 'isCorporateWiki' ) )
										->getMock();

		$mockSkinMonoBook		=	$this->getMockBuilder( 'SkinMonoBook' )
										->disableOriginalConstructor()
										->getMock();
		$mockSkinOasis			=	$this->getMockBuilder( 'SkinOasis' )
										->disableOriginalConstructor()
										->getMock();
		$mockSkinWikiaMobile	=	$this->getMockBuilder( 'SkinWikiaMobile' )
										->disableOriginalConstructor()
										->getMock();
		$mockResponse			=	$this->getMockBuilder( 'WikiaResponse' )
										->disableOriginalConstructor()
										->setMethods( array( 'addAsset' ) )
										->getMock();
		$mockRequestContext		=	$this->getMockBuilder( 'RequestContext' )
										->setMethods( array( 'getSkin' ) )
										->disableOriginalConstructor()
										->getMock();
		
		$mockOut = $this->getMockBuilder( 'OutputPage' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'addHTML' ) )
		                ->getMock();
		
		$mockUser = $this->getMockBuilder( 'User' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'getSkin' ) )
		                 ->getMock();
		
		$mockUser
		    ->expects( $this->any() )
		    ->method ( 'getSkin' )
		    ->will   ( $this->onConsecutiveCalls( $mockSkinMonoBook, $mockSkinOasis, $mockSkinWikiaMobile ) )
		;
		$mockResponse
			->expects	( $this->at( 0 ) )
			->method	( 'addAsset' )
			->with		( 'extensions/wikia/Search/monobook/monobook.scss' )
		;
		$mockResponse
			->expects	( $this->at( 1 ) )
			->method	( 'addAsset' )
			->with		( 'extensions/wikia/Search/css/WikiaSearch.scss' )
		;
		$mockSearchController
			->expects	( $this->once() )
			->method	( 'overrideTemplate' )
			->with		( 'WikiaMobileIndex' )
		;
		$mockSearchController
			->expects	( $this->any() )
			->method	( 'isCorporateWiki' )
			->will      ( $this->returnValue( true ) )
		;
		
		$wg = (object) array( 'Out' => $mockOut, 'SuppressRail' => false, 'User' => $mockUser );
		
		$mockWg = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$mockWg->setAccessible( true );
		$mockWg->setValue( $mockSearchController, $wg );

		$method = new ReflectionMethod( 'WikiaSearchController', 'handleSkinSettings' );
		$method->setAccessible( true );

		$mockSearchController->setResponse( $mockResponse );

		$this->assertTrue(
				$method->invoke( $mockSearchController, $mockSkinMonoBook ),
				'WikiaSearchController::handleSkinSettings should always return true.'
		);
		$this->assertTrue(
		        $method->invoke( $mockSearchController, $mockSkinOasis ),
		        'WikiaSearchController::handleSkinSettings should always return true.'
		);
		$this->assertTrue(
		        $method->invoke( $mockSearchController, $mockSkinWikiaMobile ),
		        'WikiaSearchController::handleSkinSettings should always return true.'
		);
		$this->assertTrue(
				$mockSearchController->wg->SuppressRail,
				'WikiaSearchController::handleSkinSettings should set wgSuppressRail to true.'
		);
	}*/

	/**
	 * @covers WikiaSearchController::setNamespacesFromRequest
	 */
	public function testSetNamespacesFromRequestHasNamespaces() {
		$mockController		=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockSearchEngine	=	$this->getMock( 'SearchEngine', array( 'searchableNamespaces', 'DefaultNamespaces' ) );
		$searchableArray	=	array( 0 => 'Article', 14 => 'Category', 6 => 'File' );
		$defaultArray		=	array( 0, 14 );
		$mockRequest		=	$this->getMock( 'WikiaRequest', array( 'getVal' ), array( array() ) );
		$mockUser			=	$this->getMock( 'User', array( 'getOption' ) );
		$mockSearchConfig	=	$this->getMock( 'Wikia\Search\Config', array( 'setNamespaces', 'getSearchProfiles' ) );

		$mockSearchEngine
			->staticExpects	( $this->any() )
			->method		( 'searchableNamespaces' )
			->will			( $this->returnValue( $searchableArray ) )
		;
		$incr = 0;
		foreach ( $searchableArray as $ns => $name ) {
			$bool = $ns == 14;
			$mockController
				->expects		( $this->at( $incr++ ) )
				->method		( 'getVal' )
				->with			( 'ns'.$ns, false )
				->will			( $this->returnValue( $bool ) )
			;
		}
		$mockSearchConfig
			->expects	( $this->at( 0 ) )
			->method	( 'setNamespaces' )
			->with		( array( 14 ) )
		;

		$this->mockClass( 'SearchEngine', $mockSearchEngine );
		$this->mockApp();


		$method = new ReflectionMethod( 'WikiaSearchController', 'setNamespacesFromRequest' );
		$method->setAccessible( true );

		$this->assertTrue(
				$method->invoke( $mockController, $mockSearchConfig, $mockUser ),
				'WikiaSearchController::setNamespacesFromRequest should return true.'
		);
	}

	/**
	 * @covers WikiaSearchController::setNamespacesFromRequest
	 */
	public function testSetNamespacesFromRequestAllNamespaces() {
		$mockController		=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockSearchEngine	=	$this->getMock( 'SearchEngine', array( 'searchableNamespaces', 'DefaultNamespaces' ) );
		$searchableArray	=	array( 0 => 'Article', 14 => 'Category', 6 => 'File' );
		$defaultArray		=	array( 0, 14 );
		$mockRequest		=	$this->getMock( 'WikiaRequest', array( 'getVal' ), array( array() ) );
		$mockUser			=	$this->getMock( 'User', array( 'getOption' ) );
		$mockSearchConfig	=	$this->getMock( 'Wikia\Search\Config', array( 'setNamespaces', 'getSearchProfiles' ) );

		$mockSearchEngine
			->staticExpects	( $this->any() )
			->method		( 'searchableNamespaces' )
			->will			( $this->returnValue( $searchableArray ) )
		;
		$mockController
			->expects		( $this->any() )
			->method		( 'getVal' )
			->will			( $this->returnValue( false ) )
		;
		$mockUser
			->expects		( $this->at( 0 ) )
			->method		( 'getOption' )
			->with			( 'searchAllNamespaces' )
			->will			( $this->returnValue( true ) )
		;
		$mockSearchConfig
			->expects	( $this->at( 0 ) )
			->method	( 'setNamespaces' )
			->with		( array_keys($searchableArray) )
		;

		$this->mockClass( 'SearchEngine', $mockSearchEngine );
		$this->mockApp();


		$method = new ReflectionMethod( 'WikiaSearchController', 'setNamespacesFromRequest' );
		$method->setAccessible( true );

		$this->assertTrue(
				$method->invoke( $mockController, $mockSearchConfig, $mockUser ),
				'WikiaSearchController::setNamespacesFromRequest should return true.'
		);
	}

	/**
	 * @covers WikiaSearchController::setNamespacesFromRequest
	 */
	public function testSetNamespacesFromRequestDefaultNamespaces() {
		$mockController		=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockSearchEngine	=	$this->getMock( 'SearchEngine', array( 'searchableNamespaces', 'DefaultNamespaces' ) );
		$searchableArray	=	array( 0 => 'Article', 14 => 'Category', 6 => 'File' );
		$defaultArray		=	array( 0, 14 );
		$mockRequest		=	$this->getMock( 'WikiaRequest', array( 'getVal' ), array( array() ) );
		$mockUser			=	$this->getMock( 'User', array( 'getOption' ) );
		$mockSearchConfig	=	$this->getMock( 'Wikia\Search\Config', array( 'setNamespaces', 'getSearchProfiles' ) );

		$mockSearchEngine
			->staticExpects	( $this->any() )
			->method		( 'searchableNamespaces' )
			->will			( $this->returnValue( $searchableArray ) )
		;
		$mockController
			->expects		( $this->any() )
			->method		( 'getVal' )
			->will			( $this->returnValue( false ) )
		;
		$mockUser
			->expects		( $this->at( 0 ) )
			->method		( 'getOption' )
			->with			( 'searchAllNamespaces' )
			->will			( $this->returnValue( false ) )
		;
		$mockSearchConfig
			->expects	( $this->at( 0 ) )
			->method	( 'getSearchProfiles' )
			->will		( $this->returnValue( array( 'default' => array( 'namespaces' => $defaultArray ) ) ) )
		;
		$mockSearchConfig
			->expects	( $this->at( 1 ) )
			->method	( 'setNamespaces' )
			->with		( $defaultArray )
		;

		$this->mockClass( 'SearchEngine', $mockSearchEngine );
		$this->mockApp();


		$method = new ReflectionMethod( 'WikiaSearchController', 'setNamespacesFromRequest' );
		$method->setAccessible( true );

		$this->assertTrue(
				$method->invoke( $mockController, $mockSearchConfig, $mockUser ),
				'WikiaSearchController::setNamespacesFromRequest should return true.'
		);
	}

	/**
	 * @covers WikiaSearchController::videoSearch
	 */
	public function testVideoSearch() {
		$mockConfig		=	$this->getMock( 'Wikia\Search\Config', array( 'setCityId', 'setQuery', 'setNamespaces', 'setVideoSearch', 'getResults' ) );
		$mockController	=	$this->searchController->setMethods( array( 'getResponse', 'getVal' ) )->getMock();
		$mockSearch		=	$this->getMockBuilder( 'Wikia\Search\QueryService\Select\Video' )
								->setMethods( array( 'search' ) )
								->disableOriginalConstructor()
								->getMock();
		$mockFactory = $this->getMockBuilder( 'Wikia\Search\QueryService\Factory' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'getFromConfig' ) )
		                    ->getMock();
		$mockResults	=	$this->getMockBuilder( 'Wikia\Search\ResultSet\Base' )
								->disableOriginalConstructor()
								->setMethods( array( 'toArray' ) )
								->getMock();
		$mockResponse	=	$this->getMockBuilder( 'WikiaResponse' )
								->setMethods( array( 'setData', 'setFormat' ) )
								->disableOriginalConstructor()
								->getMock();

		$mockWgRefl = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$mockWgRefl->setAccessible( true );
		$mockWgRefl->setValue( $mockController, (object) array( 'CityId' => 123 ) );

		$responseArr = array( 'foo' => 'bar' );

		$mockConfig
			->expects	( $this->at( 0 ) )
			->method	( 'setCityId' )
			->with		( 123 )
			->will		( $this->returnValue( $mockConfig ) )
		;
		$mockController
			->expects	( $this->at( 0 ) )
			->method	( 'getVal' )
			->with		( 'q' )
			->will		( $this->returnValue( 'query' ) )
		;
		$mockConfig
			->expects	( $this->at( 1 ) )
			->method	( 'setQuery' )
			->with		( 'query' )
			->will		( $this->returnValue( $mockConfig ) )
		;
		$mockConfig
			->expects	( $this->at( 2 ) )
			->method	( 'setNamespaces' )
			->with		( array( NS_FILE ) )
			->will		( $this->returnValue( $mockConfig ) )
		;
		$mockConfig
			->expects	( $this->at( 3 ) )
			->method	( 'setVideoSearch' )
			->with		( true )
			->will		( $this->returnValue( $mockConfig ) )
		;
		$mockFactory
		    ->expects( $this->once() )
		    ->method ( 'getFromConfig' )
		    ->will   ( $this->returnValue( $mockSearch ) )
		;
		$mockSearch
			->expects	( $this->at( 0 ) )
			->method	( 'search' )
			->will		( $this->returnValue( $mockResults ) )
		;
		$mockResults
			->expects	( $this->at( 0 ) )
			->method	( 'toArray' )
			->will		( $this->returnValue( $responseArr ) )
		;
		$mockController
			->expects	( $this->any() )
			->method	( 'getResponse' )
			->will		( $this->returnValue( $mockResponse ) )
		;
		$mockResponse
			->expects	( $this->at( 0 ) )
			->method	( 'setFormat' )
			->with		( 'json' )
		;
		$mockResponse
			->expects	( $this->at( 1 ) )
			->method	( 'setData' )
			->with		( $responseArr )
		;

		$respRefl = new ReflectionProperty( 'WikiaSearchController', 'response' );
		$respRefl->setAccessible( true );
		$respRefl->setValue( $mockController, $mockResponse );

		$searchRefl = new ReflectionProperty( 'WikiaSearchController', 'queryServiceFactory' );
		$searchRefl->setAccessible( true );
		$searchRefl->setValue( $mockController, $mockFactory );

		$this->proxyClass( 'Wikia\Search\Config', $mockConfig );
		$this->mockApp();

		$mockController->videoSearch();
	}
	
	/**
	 * @covers WikiaSearchController::searchVideosByTitle
	 */
	public function testSearchVideosByTitle() {
		$mockConfig		=	$this->getMock( 'Wikia\Search\Config', array( 'setVideoTitleSearch', 'setQuery' ) );
		$mockController	=	$this->searchController->setMethods( array( 'getResponse', 'getVal' ) )->getMock();
		$mockSearch		=	$this->getMockBuilder( 'Wikia\Search\QueryService\Select\VideoTitle' )
								->setMethods( array( 'searchAsApi' ) )
								->disableOriginalConstructor()
								->getMock();
		$mockFactory = $this->getMockBuilder( 'Wikia\Search\QueryService\Factory' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'getFromConfig' ) )
		                    ->getMock();
		$mockResults	=	$this->getMockBuilder( 'Wikia\Search\ResultSet\Base' )
								->disableOriginalConstructor()
								->setMethods( array( 'toArray' ) )
								->getMock();
		$mockResponse	=	$this->getMockBuilder( 'WikiaResponse' )
								->setMethods( array( 'setData', 'setFormat' ) )
								->disableOriginalConstructor()
								->getMock();
		
		$mockException = $this->getMockBuilder( 'Exception' )
		                      ->disableOriginalConstructor()
		                      ->getMock();

		$mockController
			->expects	( $this->at( 0 ) )
			->method	( 'getVal' )
			->with		( 'title' )
			->will		( $this->returnValue( null ) )
		;
		try {
			$mockController->searchVideosByTitle();
		} catch ( \Exception $e ) {}
		$this->assertInstanceOf(
				'Exception',
				$e
		);
		$mockController
			->expects	( $this->at( 0 ) )
			->method	( 'getVal' )
			->with		( 'title' )
			->will		( $this->returnValue( 'title' ) )
		;
		$mockConfig
			->expects	( $this->at( 0 ) )
			->method	( 'setVideoTitleSearch' )
			->with		( true )
			->will		( $this->returnValue( $mockConfig ) )
		;
		$mockConfig
			->expects	( $this->at( 1 ) )
			->method	( 'setQuery' )
			->with		( 'title' )
			->will		( $this->returnValue( $mockConfig ) )
		;
		$mockFactory
		    ->expects( $this->once() )
		    ->method ( 'getFromConfig' )
		    ->will   ( $this->returnValue( $mockSearch ) )
		;
		$mockSearch
			->expects	( $this->at( 0 ) )
			->method	( 'searchAsApi' )
			->will		( $this->returnValue( array( 'my results' ) ) )
		;
		$mockController
			->expects	( $this->any() )
			->method	( 'getResponse' )
			->will		( $this->returnValue( $mockResponse ) )
		;
		$mockResponse
			->expects	( $this->at( 0 ) )
			->method	( 'setFormat' )
			->with		( 'json' )
		;
		$mockResponse
			->expects	( $this->at( 1 ) )
			->method	( 'setData' )
			->with		( array( 'my results' ) )
		;

		$searchRefl = new ReflectionProperty( 'WikiaSearchController', 'queryServiceFactory' );
		$searchRefl->setAccessible( true );
		$searchRefl->setValue( $mockController, $mockFactory );

		$this->proxyClass( 'Wikia\Search\Config', $mockConfig );
		$this->mockApp();

		$mockController->searchVideosByTitle();
	}

	/**
	 * @covers WikiaSearchController::getPages
	 */
	public function testGetPages() {
		$mockController	=	$this->searchController->setMethods( array( 'getVal', 'getResponse' ) )->getMock();
		$mockIndexer	=	$this->getMockBuilder( 'Wikia\Search\Indexer' )
								->setMethods( array( 'getPages' ) )
								->disableOriginalConstructor()
								->getMock();
		$mockResponse	=	$this->getMockBuilder( 'WikiaResponse' )
								->setMethods( array( 'setData', 'setFormat' ) )
								->disableOriginalConstructor()
								->getMock();

		$mockRetVal = array( 'foo' => 'bar' );

		$mockController
			->expects	( $this->once() )
			->method	( 'getVal' )
			->with		( 'ids' )
			->will		( $this->returnValue( '123|321' ) )
		;
		$mockIndexer
			->expects	( $this->once() )
			->method	( 'getPages' )
			->with		( array( '123', '321' ) )
			->will		( $this->returnValue( $mockRetVal ) )
		;
		$mockController
		    ->expects( $this->any() )
		    ->method ( 'getResponse' )
		    ->will   ( $this->returnValue( $mockResponse ) )
		;
		$mockResponse
			->expects	( $this->at( 0 ) )
			->method	( 'setData' )
			->with		( $mockRetVal )
		;
		$mockResponse
			->expects	( $this->at( 1 ) )
			->method	( 'setFormat' )
			->with		( 'json' )
		;

		$mockWgRefl = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$mockWgRefl->setAccessible( true );
		$mockWgRefl->setValue( $mockController, (object) array( 'AllowMemcacheWrites' => true ) );

		$this->proxyClass( 'Wikia\Search\Indexer', $mockIndexer );
		$this->mockApp();
		$mockController->getPages();

		$this->assertFalse(
				$mockController->wg->AllowMemcacheWrites,
				'WikiaSearchController::getPages should set wgAllowMemcacheWrites to false'
		);
	}

	/**
	 * @covers WikiaSearchController::advancedTabLink
	 */
	public function testAdvancedTabLink() {

		$term = 'foo';
		$namespaces = array( 0, 14 );
		$label = 'bar';
		$class = str_replace( ' ', '-', strtolower( $label ) );
		$tooltip = 'tooltip';
		$params = array( 'filters' => array('is_video') );
		$redirs = false;
		$href = 'foo.com';

		$mockController		=	$this->searchController->setMethods( array( 'getVal', 'setVal' ) )->getMock();
		$mockSpecialPage	=	$this->getMockBuilder( 'SpecialPage' )
									->disableOriginalConstructor()
									->setMethods( array( 'getLocalURL' ) )
									->getMock();

		$stParams = array(
				'search'	=>	$term,
				'filters'	=>	array( 'is_video' ),
				'ns0'		=>	1,
				'ns14'		=>	1,
				'redirs'	=>	0
		);

		$incr = 0;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'getVal' )
			->with		( 'term' )
			->will		( $this->returnValue( $term ) )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'getVal' )
			->with		( 'namespaces' )
			->will		( $this->returnValue( $namespaces ) )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'getVal' )
			->with		( 'label' )
			->will		( $this->returnValue( $label ) )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'getVal' )
			->with		( 'tooltip' )
			->will		( $this->returnValue( $tooltip ) )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'getVal' )
			->with		( 'params' )
			->will		( $this->returnValue( $params ) )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'getVal' )
			->with		( 'redirs' )
			->will		( $this->returnValue( $redirs ) )
		;
		$mockSpecialPage
			->expects	( $this->once() )
			->method	( 'getLocalURL' )
			->with		( $stParams )
			->will		( $this->returnValue( $href ) );
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'class', $class )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'href', $href )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'title', $tooltip )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'label', $label )
		;
		$mockController
			->expects	( $this->at( $incr++ ) )
			->method	( 'setVal' )
			->with		( 'tooltip', $tooltip )
		;
		$this->mockClass( 'SpecialPage', $mockSpecialPage );
		$this->mockApp();

		$mockController->advancedTabLink();
	}
	
	/**
	 * @covers WikiaSearchController::getSearchConfigFromRequest
	 */
	public function testGetSearchConfigFromRequest() {
		$mockController = $this->getMockBuilder( 'WikiaSearchController' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array( 'getVal', 'getRequest', 'setNamespacesFromRequest', 'isCorporateWiki', 'getResponse' ) )
		                       ->getMock();
		
		$mockRequest = $this->getMockBuilder( 'WikiaRequest' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'getBool' ) )
		                    ->getMock();
		
		$mockUser = $this->getMockBuilder( 'User' )
		                 ->disableOriginalConstructor()
		                 ->getMock();
		
		$mockResponse = $this->getMockBuilder( 'WikiaResponse' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'getFormat', 'setData' ) )
		                     ->getMock();
		
		$configMethods = array( 
				'setQuery', 'setCityId', 'setLimit', 'setPage', 'setRank', 'setAdvanced', 'setHub', '__call', 
				'setIsInterWiki', 'setVideoSearch', 'setGroupResults', 'setFilterQueriesFromCodes', 'isInterWiki'
				);
		
		$mockConfig = $this->getMockBuilder( 'Wikia\Search\Config' )
		                   ->setMethods( $configMethods )
		                   ->getMock();
		
		$query = 'foo';
		$cityId = 123;
		$resultsPerPage = 10;
		$page = 1;
		$rank = 'default';
		
		$controllerIncr = 0;
		
		$wg = (object) array( 'CityId' => $cityId, 'SearchResultsPerPage' => $resultsPerPage, 'User' => $mockUser );
		
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'isCorporateWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'search' )
		    ->will   ( $this->returnValue( $query ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'query', $query )
		    ->will   ( $this->returnValue( $query ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setQuery' )
		    ->with   ( $query )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setCityId' )
		    ->with   ( $cityId )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'limit', $resultsPerPage )
		    ->will   ( $this->returnValue( $resultsPerPage ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setLimit' )
		    ->with   ( $resultsPerPage )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'page', 1 )
		    ->will   ( $this->returnValue( 1 ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setPage' )
		    ->with   ( 1 )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'rank', 'default' )
		    ->will   ( $this->returnValue( 'default' ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setRank' )
		    ->with   ( 'default' )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getRequest' )
		    ->will   ( $this->returnValue( $mockRequest ) )
		;
		$mockRequest
		    ->expects( $this->once() )
		    ->method ( 'getBool' )
		    ->with   ( 'advanced', false )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setAdvanced' )
		    ->with   ( false )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'hub', false )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setHub' )
		    ->with   ( false )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'isCorporateWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setIsInterWiki' )
		    ->with   ( false )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'videoSearch', false )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setVideoSearch' )
		    ->with   ( false )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'isInterWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setGroupResults' )
		    ->with   ( false )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'filters', array() )
		    ->will   ( $this->returnValue( array() ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setFilterQueriesFromCodes' )
		    ->with   ( array() )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->once() )
		    ->method ( 'setNamespacesFromRequest' )
		    //->with   ( $mockConfig, $mockUser ) // mock proxy is screwing this one up
		;
		$mockController
		    ->expects( $this->any() )
		    ->method ( 'getResponse' )
		    ->will   ( $this->returnValue( $mockResponse ) )
		;
		$mockResponse
		    ->expects( $this->once() )
		    ->method ( 'getFormat' )
		    ->will   ( $this->returnValue( 'html' ) )
		;
		$reflWg = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$reflWg->setAccessible( true );
		$reflWg->setValue( $mockController, $wg );
		
		$reflGet = new ReflectionMethod( 'WikiaSearchController', 'getSearchConfigFromRequest' );
		$reflGet->setAccessible( true );
		
		$this->proxyClass( 'Wikia\Search\Config', $mockConfig );
		$this->mockApp();
		
		$reflGet->invoke( $mockController );
	}
	
	/**
	 * @covers WikiaSearchController::getSearchConfigFromRequest
	 */
	public function testGetSearchConfigFromRequestWithJson() {
		$mockController = $this->getMockBuilder( 'WikiaSearchController' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array( 'getVal', 'getRequest', 'setNamespacesFromRequest', 'isCorporateWiki', 'getResponse' ) )
		                       ->getMock();
		
		$mockRequest = $this->getMockBuilder( 'WikiaRequest' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'getBool' ) )
		                    ->getMock();
		
		$mockUser = $this->getMockBuilder( 'User' )
		                 ->disableOriginalConstructor()
		                 ->getMock();
		
		$mockResponse = $this->getMockBuilder( 'WikiaResponse' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'getFormat', 'setData' ) )
		                     ->getMock();
		
		$configMethods = array( 
				'setQuery', 'setCityId', 'setLimit', 'setPage', 'setRank', 'setAdvanced', 'setHub', '__call', 
				'setIsInterWiki', 'setVideoSearch', 'setGroupResults', 'setFilterQueriesFromCodes', 'isInterWiki',
				'getRequestedFields', 'setRequestedFields'
				);
		
		$mockConfig = $this->getMockBuilder( 'Wikia\Search\Config' )
		                   ->setMethods( $configMethods )
		                   ->getMock();
		
		$query = 'foo';
		$cityId = 123;
		$resultsPerPage = 10;
		$page = 1;
		$rank = 'default';
		
		$controllerIncr = 0;
		
		$wg = (object) array( 'CityId' => $cityId, 'SearchResultsPerPage' => $resultsPerPage, 'User' => $mockUser );
		
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'isCorporateWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'search' )
		    ->will   ( $this->returnValue( $query ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'query', $query )
		    ->will   ( $this->returnValue( $query ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setQuery' )
		    ->with   ( $query )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setCityId' )
		    ->with   ( $cityId )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'limit', $resultsPerPage )
		    ->will   ( $this->returnValue( $resultsPerPage ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setLimit' )
		    ->with   ( $resultsPerPage )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'page', 1 )
		    ->will   ( $this->returnValue( 1 ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setPage' )
		    ->with   ( 1 )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'rank', 'default' )
		    ->will   ( $this->returnValue( 'default' ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setRank' )
		    ->with   ( 'default' )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getRequest' )
		    ->will   ( $this->returnValue( $mockRequest ) )
		;
		$mockRequest
		    ->expects( $this->once() )
		    ->method ( 'getBool' )
		    ->with   ( 'advanced', false )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setAdvanced' )
		    ->with   ( false )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'hub', false )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setHub' )
		    ->with   ( false )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'isCorporateWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setIsInterWiki' )
		    ->with   ( false )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'videoSearch', false )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setVideoSearch' )
		    ->with   ( false )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'isInterWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setGroupResults' )
		    ->with   ( false )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'filters', array() )
		    ->will   ( $this->returnValue( array() ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setFilterQueriesFromCodes' )
		    ->with   ( array() )
		    ->will   ( $this->returnValue( $mockConfig ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setNamespacesFromRequest' )
		    //->with   ( $mockConfig, $mockUser ) // mock proxy is screwing this one up
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getResponse' )
		    ->will   ( $this->returnValue( $mockResponse ) )
		;
		$mockResponse
		    ->expects( $this->once() )
		    ->method ( 'getFormat' )
		    ->will   ( $this->returnValue( 'json' ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'jsonfields' )
		    ->will   ( $this->returnValue( 'title,pageid,html' ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'getRequestedFields' )
		    ->will   ( $this->returnValue( [ 'pageid', 'title', 'url' ] ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'setRequestedFields' )
		    ->with   ( [ 'pageid', 'title', 'url', 'html' ] )
		;
		$reflWg = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$reflWg->setAccessible( true );
		$reflWg->setValue( $mockController, $wg );
		
		$reflGet = new ReflectionMethod( 'WikiaSearchController', 'getSearchConfigFromRequest' );
		$reflGet->setAccessible( true );
		
		$this->proxyClass( 'Wikia\Search\Config', $mockConfig );
		$this->mockApp();
		
		$reflGet->invoke( $mockController );
	}
	
	
	/**
	 * @covers WikiaSearchController::setPageTitle
	 */
	public function testSetPageTitle()
	{
		$mockController = $this->getMockBuilder( 'WikiaSearchController' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array() )
		                       ->getMock();
		
		$mockWf = $this->getMockBuilder( 'WikiaFunctionWrapper' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'msg' ) )
		               ->getMock();
		
		$mockQuery = $this->getMock( 'Wikia\Search\Query\Select', array( 'hasTerms', 'getSanitizedQuery' ), array( 'foo' ) );
		
		$mockOut = $this->getMockBuilder( 'OutputPage' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'setPageTitle' ) )
		                ->getMock();
		
		$mockConfig = $this->getMockBuilder( 'Wikia\Search\Config' )
		                   ->disableOriginalConstructor()
		                   ->setMethods( array( 'getQuery', 'getIsInterWiki' ) )
		                   ->getMock();
		
		$sitename = "Foo Wiki";
		$query = "Foo";
		$message = "The contents of this message does not matter here";
		$mockWg = (object) array( 'Out' => $mockOut, 'Sitename' => $sitename );
		
		$reflWg = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$reflWg->setAccessible( true );
		$reflWg->setValue( $mockController, $mockWg );
		
		$reflWf = new ReflectionProperty( 'WikiaSearchController', 'wf' );
		$reflWf->setAccessible( true );
		$reflWf->setValue( $mockController, $mockWf );
		
		$reflSet = new ReflectionMethod( 'WikiaSearchController', 'setPageTitle' );
		$reflSet->setAccessible( true );
		
		$mockConfig
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getQuery' )
		    ->will   ( $this->returnValue( $mockQuery ) )
		;
		$mockQuery
		    ->expects( $this->at( 0 ) )
		    ->method ( 'hasTerms' )
		    ->will   ( $this->returnValue( true ) )
		;
		$mockConfig
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getQuery' )
		    ->will   ( $this->returnValue( $mockQuery ) )
		;
		$mockQuery
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getSanitizedQuery' )
		    ->will   ( $this->returnValue( $query ) )
		;
		$mockWf
		    ->expects( $this->at( 0 ) )
		    ->method ( 'msg' )
		    ->with   ( 'wikiasearch2-page-title-with-query', array( $query, $sitename ) )
		    ->will   ( $this->returnValue( $message ) )
		;
		$mockOut
		    ->expects( $this->at( 0 ) )
		    ->method ( 'setPageTitle' )
		    ->with   ( $message )
		;

		$reflSet->invoke( $mockController, $mockConfig );
		
		$mockConfig
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getQuery' )
		    ->will   ( $this->returnValue( $mockQuery ) )
		;
		$mockQuery
		    ->expects( $this->at( 0 ) )
		    ->method ( 'hasTerms' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getIsInterWiki' )
		    ->will   ( $this->returnValue( true ) )
		;
		$mockWf
		    ->expects( $this->at( 0 ) )
		    ->method ( 'msg' )
		    ->with   ( 'wikiasearch2-page-title-no-query-interwiki' )
		    ->will   ( $this->returnValue( $message ) )
		;
		$mockOut
		    ->expects( $this->at( 0 ) )
		    ->method ( 'setPageTitle' )
		    ->with   ( $message )
		;
		
		$reflSet->invoke( $mockController, $mockConfig );
		
		$mockConfig
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getQuery' )
		    ->will   ( $this->returnValue( $mockQuery ) )
		;
		$mockQuery
		    ->expects( $this->at( 0 ) )
		    ->method ( 'hasTerms' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getIsInterWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockWf
		    ->expects( $this->at( 0 ) )
		    ->method ( 'msg' )
		    ->with   ( 'wikiasearch2-page-title-no-query-intrawiki', array( $sitename ) )
		    ->will   ( $this->returnValue( $message ) )
		;
		$mockOut
		    ->expects( $this->at( 0 ) )
		    ->method ( 'setPageTitle' )
		    ->with   ( $message )
		;
		
		$reflSet->invoke( $mockController, $mockConfig );
	}
	
	/**
	 * @covers WikiaSearchController::setResponseValuesFromConfig
	 */
	public function testSetResponseValuesFromConfigAsJson()
	{
		$mockController = $this->getMockBuilder( 'WikiaSearchController' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array( 'getResponse', 'getVal' ) )
		                       ->getMock();
		
		$mockQuery = $this->getMock( 'Wikia\Search\Query\Select', array( 'getQueryForHtml' ), array( 'foo' ) );
		
		$mockResponse = $this->getMockBuilder( 'WikiaResponse' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'getFormat', 'setData' ) )
		                     ->getMock();
		
		$mockConfig = $this->getMockBuilder( 'Wikia\Search\Config' )
		                   ->setMethods( array( 'getResults' ) )
		                   ->getMock();
		
		$mockResults = $this->getMockBuilder( 'Wikia\Search\ResultSet\Base' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'toArray' ) )
		                    ->getMock();
		
		$mockController
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getResponse' )
		    ->will   ( $this->returnValue( $mockResponse ) )
		;
		$mockResponse
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getFormat' )
		    ->will   ( $this->returnValue( 'json' ) )
		;
		$mockConfig
		    ->expects( $this->once() )
		    ->method ( 'getResults' )
		    ->will   ( $this->returnValue( $mockResults ) ) 
		;
		$mockController
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getVal' )
		    ->with   ( 'jsonfields', 'title,url,pageid' )
		    ->will   ( $this->returnValue( 'title,url,pageid' ) )
		;
		$mockResults
		    ->expects( $this->once() )
		    ->method ( 'toArray' )
		    ->with   ( array( 'title', 'url', 'pageid' ) )
		    ->will   ( $this->returnValue( array( 'foo' ) ) )
		;
		$mockResponse
		    ->expects( $this->once() )
		    ->method ( 'setData' )
		    ->with   ( array( 'foo' ) )
		;
		$mockConfig
		    ->expects( $this->never() )
		    ->method ( 'getIsInterWiki' )
		;
		$reflSet = new ReflectionMethod( 'WikiaSearchController', 'setResponseValuesFromConfig' );
		$reflSet->setAccessible( true );
		$reflSet->invoke( $mockController, $mockConfig );
	}
	
	/**
	 * @covers WikiaSearchController::setResponseValuesFromConfig
	 */
	public function testSetResponseValuesFromConfigDefault()
	{
		$mockController = $this->getMockBuilder( 'WikiaSearchController' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array( 'getResponse', 'setVal', 'getVal', 'sendSelfRequest', 'isCorporateWiki' ) )
		                       ->getMock();
		
		$mockResponse = $this->getMockBuilder( 'WikiaResponse' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'getFormat', 'setData' ) )
		                     ->getMock();
		
		$mockQuery = $this->getMock( 'Wikia\Search\Query\Select', array( 'getQueryForHtml' ), array( 'foo' ) );
		
		$configMethods = array( 
				'getResults', 'getResultsFound', 'getQuery', 
				'getNumPages', 'getPage', 'getLimit',
				'getIsInterWiki', 'getNamespaces', 'getHub', 'hasArticleMatch'
				);
		$mockConfig = $this->getMockBuilder( 'Wikia\Search\Config' )
		                   ->setMethods( $configMethods )
		                   ->getMock();
		
		$mockResults = $this->getMockBuilder( 'Wikia\Search\ResultSet\Base' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'toArray' ) )
		                    ->getMock();
		
		
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getFullUrl' ) )
		                  ->getMock();
		
		$mockUser = $this->getMockBuilder( 'User' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'getSkin' ) )
		                 ->getMock();
		
		$mockWg = (object) array( 'Title' => $mockTitle, 'User' => $mockUser );
		
		$controllerIncr = 0;
		$tabsArgs = array( 'config' => $mockConfig, 'by_category' => false );
		
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getResponse' )
		    ->will   ( $this->returnValue( $mockResponse ) )
		;
		$mockResponse
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getFormat' )
		    ->will   ( $this->returnValue( 'html' ) )
		;
		$mockConfig
		    ->expects( $this->any() )
		    ->method ( 'getIsInterWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'sendSelfRequest' )
		    ->with   ( 'advancedBox', array( 'config' => $mockConfig ) )
		    ->will   ( $this->returnValue( 'advanced box output' ) ) 
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'advancedSearchBox', 'advanced box output' ); 
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'getVal' )
		    ->with   ( 'by_category', false )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockConfig
		    ->expects( $this->any() )
		    ->method ( 'getResults' )
		    ->will   ( $this->returnValue( $mockResults ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'results', $mockResults )
		;
		$mockConfig
		    ->expects( $this->any() )
		    ->method ( 'getResultsFound' )
		    ->will   ( $this->returnValue( 1000 ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'resultsFound', 1000 )
		;
		$mockConfig
		    ->expects( $this->any() )
		    ->method ( 'getTruncatedResultsNum' )
		    ->with   ( true )
		    ->will   ( $this->returnValue( '1,000' ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'resultsFoundTruncated', '1,000' )
		;
		$mockConfig
		    ->expects( $this->any() )
		    ->method ( 'getNumPages' )
		    ->will   ( $this->returnValue( 100 ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'isOneResultsPageOnly', false )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'pagesCount', 100 )
		;
		$mockConfig
		    ->expects( $this->any() )
		    ->method ( 'getPage' )
		    ->will   ( $this->returnValue( 1 ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'currentPage', 1 )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'sendSelfRequest' )
		    ->with   ( 'pagination', $tabsArgs )
		    ->will   ( $this->returnValue( 'paginationresponse' ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'paginationLinks', 'paginationresponse' )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'sendSelfRequest' )
		    ->with   ( 'tabs', $tabsArgs )
		    ->will   ( $this->returnValue( 'tabresponse' ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'tabs', 'tabresponse' )
		;
		$mockConfig
		    ->expects( $this->any() )
		    ->method ( 'getQuery' )
		    ->will   ( $this->returnValue( $mockQuery ) )
		;
		$mockQuery
		    ->expects( $this->once() )
		    ->method ( 'getQueryForHtml' )
		    ->will   ( $this->returnValue( 'foo' ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'query', 'foo' )
		;
		$mockConfig
		    ->expects( $this->any() )
		    ->method ( 'getLimit' )
		    ->will   ( $this->returnValue( 10 ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'resultsPerPage', 10 )
		;
		$mockTitle
		    ->expects( $this->any() )
		    ->method ( 'getFullUrl' )
		    ->will   ( $this->returnValue( 'foo.wikia.com/wiki/search' ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'pageUrl', 'foo.wikia.com/wiki/search' )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'isInterWiki', false )
		;
		$mockConfig
		    ->expects( $this->any() )
		    ->method ( 'getNamespaces' )
		    ->will   ( $this->returnValue( array( 0, 14 ) ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'namespaces', array( 0, 14 ) )
		;
		$mockConfig
		    ->expects( $this->any() )
		    ->method ( 'getHub' )
		    ->will   ( $this->returnValue( null ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'hub', null )
		;
		$mockConfig
		    ->expects( $this->any() )
		    ->method ( 'hasArticleMatch' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'hasArticleMatch', false )
		;
		$mockUser
		    ->expects( $this->once() )
		    ->method ( 'getSkin' )
		    ->will   ( $this->returnValue( null ) ) // screw it
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'isMonobook', false )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'isCorporateWiki' )
		    ->will   ( $this->returnValue( false ) )
		;
		$mockController
		    ->expects( $this->at( $controllerIncr++ ) )
		    ->method ( 'setVal' )
		    ->with   ( 'isCorporateWiki', false )
		;
		
		$reflWg = new ReflectionProperty( 'WikiaSearchController', 'wg' );
		$reflWg->setAccessible( true );
		$reflWg->setValue( $mockController, $mockWg );

		$reflSet = new ReflectionMethod( 'WikiaSearchController', 'setResponseValuesFromConfig' );
		$reflSet->setAccessible( true );
		$reflSet->invoke( $mockController, $mockConfig );
	}
}