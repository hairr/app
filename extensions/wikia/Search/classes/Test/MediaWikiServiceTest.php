<?php
/**
 * Class definition for \Wikia\Search\Test\MediaWikiServiceTest
 * @author relwell
 */
namespace Wikia\Search\Test;
use Wikia\Search\MediaWikiService;
use \ReflectionProperty;
use \ReflectionMethod;
/**
 * Tests the methods found in \Wikia\Search\MediaWikiService
 * @author relwell
 */
class MediaWikiServiceTest extends BaseTest
{
	/**
	 * @var \Wikia\Search\MediaWikiService
	 */
	protected $service;
	
	/**
	 * @var int
	 */
	protected $pageId;
	
	public function setUp() {
		parent::setUp();
		$this->pageId = 123;
		$this->service = $this->getMockBuilder( '\Wikia\Search\MediaWikiService' )
                                ->disableOriginalConstructor();
		
		// re-initialize static vars
		$staticVars = array( 
				'pageIdsToArticles', 'pageIdsToTitles', 'redirectsToCanonicalIds',
				'pageIdsToFiles', 'redirectArticles', 'wikiDataSources' 
		);
		foreach ( $staticVars as $var ) {
			$refl = new ReflectionProperty( 'Wikia\Search\MediaWikiService', $var );
			$refl->setAccessible( true );
			$refl->setValue( array() );
		}
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getTitleStringFromPageId
	 */
	public function testGetTitleStringFromPageId() {
		$service = $this->service->setMethods( array( 'getTitleString', 'getTitleFromPageId' ) )->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		
		$mockTitleString = 'Mock Title';
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getTitleFrompageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$service
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getTitleString' )
		    ->with   ( $mockTitle )
		    ->will   ( $this->returnValue( $mockTitleString ) )
		;
		$this->assertEquals(
				$mockTitleString,
				$service->getTitleStringFrompageId( $this->pageId ),
				'\Wikia\Search\MediaWikiService::getTitleStringFromPageId should return the string value of a title based on a page ID'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getLocalUrlForPageId
	 */
	public function testGetLocalUrlForPageId() {
		$service = $this->service->setMethods( array( 'getTitleFromPageId' ) )->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getLocalUrl' ) )
		                  ->getMock();
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getTitleFrompageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$mockTitle
		    ->expects( $this->once() )
		    ->method ( 'getLocalUrl' )
		    ->with   ( [ 'foo' => 'bar' ], false )
		    ->will   ( $this->returnValue( 'Stuff?foo=bar' ) )
		;
		$this->assertEquals(
				'Stuff?foo=bar',
				$service->getLocalUrlForPageId( $this->pageId, [ 'foo' => 'bar' ] ),
				'\Wikia\Search\MediaWikiService::getLocalUrlFromPageId should return the string value of local url based on a page ID'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getTitleFromPageId
	 */
	public function testGetTitleFromPageIdFreshPage() {
		$service = $this->service->setMethods( array( 'getPageFromPageId' ) )->getMock();
		
		$mockPage = $this->getMockBuilder( 'Article' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'getTitle' ) )
		                 ->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getPageFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockPage ) )
		;
		$mockPage
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getTitle' )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		
		$getRefl = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getTitleFromPageId' );
		$getRefl->setAccessible( true );

		$pageIdsToTitles = new ReflectionProperty( '\Wikia\Search\MediaWikiService', 'pageIdsToTitles' );
		$pageIdsToTitles->setAccessible( true ) ;
		
		$this->assertEquals(
				$mockTitle,
				$getRefl->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getTitleFromPageId should return an instance of Title corresponding to the provided page ID' 
		);
		$this->assertArrayHasKey(
				$this->pageId,
				$pageIdsToTitles->getValue( $service ),
				'\Wikia\Search\MediaWikiService::getTitleFromPageId should store any titles it access for a page in the pageIdsToTitles array'
		);
	}
	
    /**
	 * @covers \Wikia\Search\MediaWikiService::getTitleFromPageId
	 */
	public function testGetTitleFromPageIdCachedPage() {
		$service = $this->service->setMethods( array( 'getPageFromPageId' ) )->getMock();
		
		$mockPage = $this->getMockBuilder( 'Article' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'getTitle' ) )
		                 ->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		
		$service
		    ->expects( $this->never() )
		    ->method ( 'getPageFromPageId' )
		;
		$mockPage
		    ->expects( $this->never() )
		    ->method ( 'getTitle' )
		;
		
		$getRefl = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getTitleFromPageId' );
		$getRefl->setAccessible( true );

		$pageIdsToTitles = new ReflectionProperty( '\Wikia\Search\MediaWikiService', 'pageIdsToTitles' );
		$pageIdsToTitles->setAccessible( true );
		$pageIdsToTitles->setValue( $service, array( $this->pageId => $mockTitle ) );
		
		$this->assertEquals(
				$mockTitle,
				$getRefl->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getTitleFromPageId should return an instance of Title corresponding to the provided page ID' 
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getCanonicalPageIdFromPageId
	 */
	public function testGetCanonicalPageIdFromPageIdIsCanonical() {
		$service = $this->service->setMethods( array( 'getPageFromPageId' ) )->getMock();
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getPageFromPageId' )
		    ->with   ( $this->pageId )
		;
		
		$getCanonicalPageIdFromPageId = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getCanonicalPageIdFromPageId' );
		$getCanonicalPageIdFromPageId->setAccessible( true );
		
		$this->assertEquals(
				$this->pageId,
				$getCanonicalPageIdFromPageId->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getCanonicalPageIdFromPageId should return the value provided to it if a value is not stored in the redirect ID array'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getCanonicalPageIdFromPageId
	 */
	public function testGetCanonicalPageIdFromPageIdIsException() {
		$service = $this->service->setMethods( array( 'getPageFromPageId' ) )->getMock();
		$ex = $this->getMockBuilder( '\Exception' )
		           ->disableOriginalConstructor()
		           ->getMock();
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getPageFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->throwException( $ex ) )
		;
		
		$getCanonicalPageIdFromPageId = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getCanonicalPageIdFromPageId' );
		$getCanonicalPageIdFromPageId->setAccessible( true );
		
		$this->assertEquals(
				$this->pageId,
				$getCanonicalPageIdFromPageId->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getCanonicalPageIdFromPageId should return the value provided to it if an exception is thrown'
		);
	}
	
    /**
	 * @covers \Wikia\Search\MediaWikiService::getCanonicalPageIdFromPageId
	 */
	public function testGetCanonicalPageIdFromPageIdIsRedirect() {
		$service = $this->service->setMethods( array( 'getPageFromPageId' ) )->getMock();
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getPageFromPageId' )
		    ->with   ( $this->pageId )
		;
		
		$canonicalPageId = 54321;
		
		$redirectsToCanonicalIds = new ReflectionProperty( '\Wikia\Search\MediaWikiService', 'redirectsToCanonicalIds' );
		$redirectsToCanonicalIds->setAccessible( true );
		$redirectsToCanonicalIds->setValue( $service, array( $this->pageId => $canonicalPageId ) );
		
		$getCanonicalPageIdFromPageId = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getCanonicalPageIdFromPageId' );
		$getCanonicalPageIdFromPageId->setAccessible( true );
		
		$this->assertEquals(
				$canonicalPageId,
				$getCanonicalPageIdFromPageId->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getCanonicalPageIdFromPageId should return the value provided to it if a value is not stored in the redirect ID array'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::isPageIdContent
	 */
	public function testIsPageIdContentYes() {
		$service = $this->service->setMethods( array( 'getNamespaceFromPageId', 'getGlobal' ) )->getMock();
		
		$service
		    ->expects( $this->any() )
		    ->method ( 'getNamespaceFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( NS_MAIN ) )
		;
		$service
		    ->expects( $this->any() )
		    ->method ( 'getGlobal' )
		    ->with   ( 'ContentNamespaces' )
		    ->will   ( $this->returnValue( array( NS_MAIN, NS_CATEGORY ) ) ) 
		;
		$this->assertTrue(
				$service->isPageIdContent( $this->pageId )
		);
	}
	
    /**
	 * @covers \Wikia\Search\MediaWikiService::isPageIdContent
	 */
	public function testIsPageIdContentNo() {
		$service = $this->service->setMethods( array( 'getNamespaceFromPageId', 'getGlobal' ) )->getMock();
		
		$service
		    ->expects( $this->any() )
		    ->method ( 'getNamespaceFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( NS_FILE ) )
		;
		$service
		    ->expects( $this->any() )
		    ->method ( 'getGlobal' )
		    ->with   ( 'ContentNamespaces' )
		    ->will   ( $this->returnValue( array( NS_MAIN, NS_CATEGORY ) ) ) 
		;
		$this->assertFalse(
				$service->isPageIdContent( $this->pageId )
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getLanguageCode
	 */
	public function testGetLanguageCode() {
		global $wgContLang;
		$this->assertEquals(
				$wgContLang->getCode(),
				(new MediaWikiService)->getLanguageCode(),
				'\Wikia\Search\MediaWikiService::getLanguageCode should provide an interface to $wgContLang->getCode()'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getUrlFromPageId
	 */
	public function testGetUrlFromPageId() {
		$service = $this->service->setMethods( array( 'getTitleFromPageId' ) )->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getFullUrl' ) )
		                  ->getMock();
		
		$url = 'http://foo.wikia.com/wiki/Bar';
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getTitleFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$mockTitle
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getFullUrl' )
		    ->will   ( $this->returnValue( $url ) )
		;
		$this->assertEquals(
				$url,
				$service->getUrlFromPageId( $this->pageId ),
				'\Wikia\Search\MediaWikiService::getUrlFromPageId should return the full URL from the title instance associated with the provided page id'
		);
	}
	
    /**
	 * @covers \Wikia\Search\MediaWikiService::getNamespaceFromPageId
	 */
	public function testGetNamespaceFromPageId() {
		$service = $this->service->setMethods( array( 'getTitleFromPageId' ) )->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getNamespace' ) )
		                  ->getMock();
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getTitleFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$mockTitle
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getNamespace' )
		    ->will   ( $this->returnValue( NS_TALK ) )
		;
		$this->assertEquals(
				NS_TALK,
				$service->getNamespaceFromPageId( $this->pageId ),
				'\Wikia\Search\MediaWikiService::getNamespaceFromPageId should return the namespace from the title instance associated with the provided page id'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getMainPageArticleId
	 */
	public function testGetMainPageArticleId() {
		$this->assertEquals(
				\Title::newMainPage()->getArticleId(),
				(new MediaWikiService)->getMainPageArticleId()
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getSimpleLanguageCode
	 */
	public function testGetsimpleLanguageCode() {
		$service = $this->service->setMethods( array( 'getLanguageCode' ) )->getMock();
		
		$service
		    ->expects( $this->any() )
		    ->method ( 'getLanguageCode' )
		    ->will   ( $this->returnValue( 'en-ca' ) )
		;
		$this->assertEquals(
				'en',
				$service->getSimpleLanguageCode(),
				'\Wikia\Search\MediaWikiService::getSimpleLanguageCode should strip any extensions from the two-letter language code'
		);
	}
	
	/**
	 * Note: we actually expect an array here but since static method calls are tricky here 
	 * we're using proxyClass with translated version of a response array
	 * @covers \Wikia\Search\MediaWikiService::getParseResponseFromPageId
	 */
	public function testGetParseResponseFromPageId() {
		$mockApiService = $this->getMockBuilder( '\ApiService' )
		                       ->setMethods( array( 'call' ) )
		                       ->getMock();
		
		$mockResultArray = (object) array( 'foo' => 'bar' );
		
		// hack to make this work in our framework
		$this->proxyClass( '\ApiService', $mockResultArray, 'call' );
		$this->mockApp();
		
		$this->assertEquals(
				$mockResultArray,
				(new MediaWikiService)->getParseResponseFromPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getCacheKey
	 */
	public function testGetCacheKey() {
		$service = $this->service->setMethods( array( 'getWikiId'  ) )->getMock();
		
		$mockWf = $this->getMockBuilder( 'WikiaFunctionWrapper' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'SharedMemcKey' ) )
		              ->getMock();
		
		$wid = 567;
		$key = 'foo';
		
		$app = (object) array( 'wf' => $mockWf );
		$reflApp = new ReflectionProperty( 'Wikia\Search\MediaWikiService', 'app' );
		$reflApp->setAccessible( true );
		$reflApp->setValue( $service, $app );
		
		
		$service
		    ->expects( $this->any() )
		    ->method ( 'getWikiId' )
		    ->will   ( $this->returnValue( $wid ) )
		;
		$mockWf
		    ->expects( $this->any() )
		    ->method ( 'SharedMemcKey' )
		    ->with   ( $key, $wid )
		    ->will   ( $this->returnValue( 'bar' ) )
		;
		$this->assertEquals(
				'bar',
				$service->getCacheKey( $key )
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getCacheResult
	 */
	public function testGetCacheResult() {
		
		$service = $this->service->setMethods( array( 'getGlobal' ) )->getMock();
		
		$mockMc = $this->getMockBuilder( '\MemcachedClientForWiki' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'get' ) )
		               ->getMock();

		$key = 'bar';
		$result = 'foo';
		
		$app = (object) array( 'wg' => (object ) array( 'Memc' => $mockMc ) );
		$reflApp = new ReflectionProperty( 'Wikia\Search\MediaWikiService', 'app' );
		$reflApp->setAccessible( true );
		$reflApp->setValue( $service, $app );

		$mockMc
		    ->expects( $this->any() )
		    ->method ( 'get' )
		    ->with   ( $key )
		    ->will   ( $this->returnValue( $result ) )
		;
		$this->assertEquals(
				$result,
				$service->getCacheResult( $key ),
				'\WikiaSearch\MediaWikiService::getCacheResult should provide an interface to $wgMemc->get()'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getCacheResultFromString
	 */
	public function testGetCacheResultFromString() {
		$service = $this->service->setMethods( array( 'getCacheResult', 'getCacheKey' ) )->getMock();
		
		$key = 'foo';
		$val = 'bar';
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getCacheKey' )
		    ->with   ( $key )
		    ->will   ( $this->returnValue( sha1( $key ) ) )
		;
		$service
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getCacheResult' )
		    ->with   ( sha1( $key ) )
		    ->will   ( $this->returnValue( $val ) )
		;
		$this->assertEquals(
				$val,
				$service->getCacheResultFromString( $key ),
				'\WikiaSearch\MediaWikiService::getCacheResultFromString should provide an interface for accessing a cached value from a plaintext key'
		);
	}

	/**
	 * @covers \Wikia\Search\MediaWikiService::setCacheFromStringKey
	 */
	public function testSetCacheFromStringKey() {
		
		$service = $this->service->setMethods( array( 'getCacheKey', 'getWg' ) )->getMock();
		
		$mockMc = $this->getMockBuilder( '\MemcachedClientForWiki' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'set' ) )
		               ->getMock();
		
		$key = 'bar';
		$value = 'foo';
		$ttl = 3600;
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getCacheKey' )
		    ->with   ( $key )
		    ->will   ( $this->returnValue( sha1( $key ) ) )
		;
		$mockMc
		    ->expects( $this->at( 0 ) )
		    ->method ( 'set' )
		    ->with   ( sha1( $key ), $value, $ttl )
		;
		$app = (object) array( 'wg' => (object ) array( 'Memc' => $mockMc ) );
		$reflApp = new ReflectionProperty( 'Wikia\Search\MediaWikiService', 'app' );
		$reflApp->setAccessible( true );
		$reflApp->setValue( $service, $app );
		$this->assertEquals(
				$service,
				$service->setCacheFromStringKey( $key, $value, $ttl ),
				'\WikiaSearch\MediaWikiService::setCacheResultForStringKey should set a cache value in memcached provided a given plaintext key'
		);
	}
	
	/**
	 * One day this test will actually work as advertised.
	 * @covers \Wikia\Search\MediaWikiService::getBacklinksCountFromPageId
	 */
	public function testGetBacklinksCountFromPageId() {
		$service = $this->service->setMethods( array( 'getTitleStringFromPageId' ) )->getMock();
		
		$mockApiService = $this->getMock( '\ApiService', array( 'call' ) );
		
		$title = "Foo Bar";
		
		$data = array( 'query' => array( 'backlinks_count' => 0 ) );
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getTitleStringFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $title ) )
		;
		$mockApiService
		    ->staticExpects( $this->any() )
		    ->method       ( 'call' )
		    ->with         ( $title )
		    ->will         ( $this->returnValue( $data ) )
		;
		
		$this->proxyClass( '\ApiService', $mockApiService );
		$this->mockClass( '\ApiService', $mockApiService );
		$this->mockApp();
		
		$this->assertEquals(
				0,
				$service->getBacklinksCountFromPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getGlobal
	 */
	public function testGetGlobal() {
		$service = new MediaWikiService;
		$app = \F::app();
		$app->wg->Foo = 'bar';
		
		$this->assertEquals(
				'bar',
				$service->getGlobal( 'Foo' ),
				'\WikiaSearch\MediaWikiService::getGlobal should provide an interface to MediaWiki wg-prefixed global variables'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getGlobalWithDefault
	 */
	public function testGetGlobalWithDefault() {
		$service = new MediaWikiService;
		$app = \F::app();
		$app->wg->Foo = null;
		
		$this->assertEquals(
				'bar',
				$service->getGlobalWithDefault( 'Foo', 'bar' ),
				'\WikiaSearch\MediaWikiService::getGlobalWithDefault should return the default value if the global value is null.'
		);
	}
	
    /**
	 * @covers \Wikia\Search\MediaWikiService::setGlobal
	 */
	public function testSetGlobal() {
		$service = new MediaWikiService;
		$app = \F::app();
		
		$this->assertEquals(
				$service,
				$service->setGlobal( 'Foo', 'bar' )
		);
		$this->assertEquals(
				'bar',
				$app->wg->Foo,
				'\WikiaSearch\MediaWikiService::setGlobal should set the provided key as a global variable name with the provided value'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getWikiId
	 */
	public function testGetWikiId() {
		$service = $this->service->setMethods( array( 'getGlobal' ) )->getMock();
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getGlobal' )
		    ->with   ( 'ExternalSharedDB' )
		    ->will   ( $this->returnValue( true ) )
		;
		$service
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getGlobal' )
		    ->with   ( 'CityId' )
		    ->will   ( $this->returnValue( 7734 ) )
		;
		$this->assertEquals(
				7734,
				$service->getWikiId()
		);
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getGlobal' )
		    ->with   ( 'ExternalSharedDB' )
		    ->will   ( $this->returnValue( false ) )
		;
		$service
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getGlobal' )
		    ->with   ( 'SearchWikiId' )
		    ->will   ( $this->returnValue( 7735 ) )
		;
		$this->assertEquals(
				7735,
				$service->getWikiId()
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getMediaDataFromPageId
	 */
	public function testGetMediaDataFromPageId() {
		$service = $this->service->setMethods( array( 'pageIdHasFile', 'getFileForPageId' ) )->getMock();
		
		$mockFile = $this->getMockBuilder( 'File' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'getMetadata' ) )
		                 ->getMock();
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'pageIdHasFile' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( false ) )
		;
		$this->assertEquals(
				'',
				$service->getMediaDataFromPageId( $this->pageId ),
				'\WikiaSearch\MediaWikiService::getMediaDataFromPageId should return an empty string if the page id is not a file'
		);
		
		$serialized = serialize( array( 'foo' => 'bar' ) );
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'pageIdHasFile' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( true ) )
		;
		$service
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getFileForPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockFile ) )
		;
		$mockFile
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getMetadata' )
		    ->will   ( $this->returnValue( $serialized ) )
		;
		$this->assertEquals(
				$serialized,
				$service->getMediaDataFromPageId( $this->pageId ),
				'\WikiaSearch\MediaWikiService::getMediaDataFromPageId should return the serialized file metadata array for a file page id'
		);
	}

    /**
     * @covers\Wikia\Search\MediaWikiService::pageIdHasFile 
     */	
	public function testPageIdHasFile() {
		$service = $this->service->setMethods( array( 'getFileForPageId' ) )->getMock();
		
		$mockFile = $this->getMockBuilder( 'File' )
		                 ->disableOriginalConstructor()
		                 ->getMock();
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getFileForPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( null ) )
		;
		$this->assertFalse(
				$service->pageIdHasFile( $this->pageId )
		);
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getFileForPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockFile ) )
		;
		$this->assertTrue(
				$service->pageIdHasFile( $this->pageId )
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getApiStatsForPageId 
	 */
	public function testGetApiStatsForPageId() {
		$this->assertEquals(
				\ApiService::call( array(
        				'pageids'  => $this->pageId,
        				'action'   => 'query',
        				'prop'     => 'info',
        				'inprop'   => 'url|created|views|revcount',
        				'meta'     => 'siteinfo',
        				'siprop'   => 'statistics|wikidesc|variables|namespaces|category'
        		) ),
			    (new MediaWikiService)->getApiStatsForPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getApiStatsForWiki 
	 */
	public function testGetApiStatsForWiki() {
		global $wgCityId;
		$this->assertEquals(
				\ApiService::call( array(
						'action'   => 'query',
						'prop'     => 'info',
						'inprop'   => 'url|created|views|revcount',
						'meta'     => 'siteinfo',
						'siprop'   => 'statistics'
						) ),
			    (new MediaWikiService)->getApiStatsForWiki( $wgCityId )
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::pageIdExists 
	 */
	public function testPageIdExists() {
		$service = $this->service->setMethods( array( 'getPageFromPageId' ) )->getMock();
		$page = $this->getMockBuilder( 'Article' )
		             ->disableOriginalConstructor()
		             ->setMethods( array( 'exists' ) )
		             ->getMock();
		
		$mockException = $this->getMock( '\Exception' );
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getPageFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->throwException( $mockException ) )
		;
		$this->assertFalse(
			$service->pageIdExists( $this->pageId ),
			'\WikiaSearch\MediaWikiService::pageExists should catch exceptions thrown by \WikiaSearch\MediaWikiService::getPageFromPageId and return false'
		);
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getPageFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $page ) )
		;
		$page
		    ->expects( $this->at( 0 ) )
		    ->method ( 'exists' )
		    ->will   ( $this->returnValue( false ) )
		;
		$this->assertFalse(
				$service->pageIdExists( $this->pageId ),
				'\WikiaSearch\MediaWikiService::pageExists should pass the return value of Article::exists'
		);
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getPageFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $page ) )
		;
		$page
		    ->expects( $this->at( 0 ) )
		    ->method ( 'exists' )
		    ->will   ( $this->returnValue( true ) )
		;
		$this->assertTrue(
				$service->pageIdExists( $this->pageId ),
				'\WikiaSearch\MediaWikiService::pageExists should pass the return value of Article::exists'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getRedirectTitlesForPageId
	 */
	public function testGetRedirectTitlesForPageID() {
		$service = $this->service->setMethods( array( 'getTitleKeyFromPageId' ) )->getMock();
		
		$mockDbr = $this->getMockBuilder( '\DatabaseMysql' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'select', 'fetchObject' ) )
		                ->getMock();
		
		$mockWrapper = $this->getMockBuilder( '\WikiaFunctionWrapper' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'GetDB' ) )
		                    ->getMock();
		
		$mockResult = $this->getMockBuilder( '\ResultWrapper' )
		                   ->disableOriginalConstructor()
		                   ->getMock();
		
		$mockRow = (object) array( 'page_title' => 'Bar_Foo' );
		$titleKey = 'Foo_Bar';
		$method = 'Wikia\Search\MediaWikiService::getRedirectTitlesForPageId';
		$fields = array( 'redirect', 'page' );
		$table = array( 'page_title' );
		$group = array( 'GROUP' => 'rd_title' );
		$join = array( 'page' => array( 'INNER JOIN', array( 'rd_title' => $titleKey, 'page_id = rd_from' ) ) );
		$expectedResult = array( 'Bar Foo' );
		
		$mockWrapper
		    ->expects( $this->once() )
		    ->method ( 'GetDB' )
		    ->with   ( DB_SLAVE )
		    ->will   ( $this->returnValue( $mockDbr ) )
		;
		$service
		    ->expects( $this->once() )
		    ->method ( 'getTitleKeyFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $titleKey ) )
		;
		$mockDbr
		    ->expects( $this->at( 0 ) )
		    ->method ( 'select' )
		    ->with   ( $fields, $table, array(), $method, $group, $join )
		    ->will   ( $this->returnValue( $mockResult ) )
		;
		$mockDbr
		    ->expects( $this->at( 1 ) )
		    ->method ( 'fetchObject' )
		    ->with   ( $mockResult )
		    ->will   ( $this->returnValue( $mockRow ) )
		;
		$mockDbr
		    ->expects( $this->at( 2 ) )
		    ->method ( 'fetchObject' )
		    ->with   ( $mockResult )
		    ->will   ( $this->returnValue( null ) )
		;
		$reflApp = new ReflectionProperty( '\Wikia\Search\MediaWikiService', 'app' );
		$reflApp->setAccessible( true );
		$reflApp->setValue( $service, (object) array( 'wf' => $mockWrapper ) );
		
		$this->assertEquals(
				$expectedResult,
				$service->getRedirectTitlesForPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getMediaDetailFromPageId
	 */
	public function testGetMediaDetailFromPageId() {
		$service = $this->service->setMethods( array( 'getTitleFromPageId' ) )->getMock();
		$fileHelper = $this->getMock( '\WikiaFileHelper' );
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		
		$detailArray = array( 'these my' => 'details' );
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getTitleFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$fileHelper::staticExpects( $this->any() )
		    ->method ( 'getMediaDetail' )
		    ->with   ( $mockTitle )
		    ->will   ( $this->returnValue( $detailArray ) )
		;
		$this->mockClass( '\WikiaFileHelper', $fileHelper );
		$this->mockApp();
		$this->assertTrue(
				is_array( $service->getMediaDetailFromPageId( $this->pageId ) ),
				'\Wikia\Search\MediaWikiService::getMediaDetailFromPageId should return the array result of \WikiaFileHelper::getMediaDetail'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::pageIdIsVideoFile
	 */
	public function testPageIdIsVideoFile() {
		$service = $this->service->setMethods( array( 'getFileForPageId' ) )->getMock();
		
		$mockFile = $this->getMockBuilder( '\LocalFile' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'getHandler' ) )
		                 ->getMock();
		
		$mockVideoHandler = $this->getMockBuilder( '\VideoHandler' )->getMock();
		// again, mocking stuff we don't really want to here because of static methods
		$service
		    ->expects( $this->any() )
		    ->method ( 'getFileForPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockFile ) )
		;
		$mockFile
		    ->expects( $this->any() )
		    ->method ( 'getHandler' )
		    ->will   ( $this->returnValue( $mockVideoHandler ) )
		;
		$this->assertTrue(
				$service->pageIdIsVideoFile( $this->pageId )
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getTitleKeyFromPageId
	 */
	public function testGetTitleKeyFromPageId() {
		$service = $this->service->setMethods( array( 'getTitleFromPageId' ) )->getMock();
		$title = $this->getMockBuilder( '\Title' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'getDbKey' ) )
		              ->getMock();
		$dbKey = 'Foo_Bar_Baz';
		$service
		    ->expects( $this->any() )
		    ->method ( 'getTitleFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $title ) )
		;
		$title
		    ->expects( $this->any() )
		    ->method ( 'getDbKey' )
		    ->will   ( $this->returnValue( $dbKey ) )
		;
		$get = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getTitleKeyFromPageId' );
		$get->setAccessible( true );
		$this->assertEquals(
				$dbKey,
				$get->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getTitleKeyFromPageId should return the db key for the canonical title associated with the provided page ID'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getFileForPageId
	 */
	public function testGetFileForPageId() {
		$service = $this->service->setMethods( array( 'getTitleFromPageId' ) )->getMock();
		$mockFile = $this->getMockBuilder( '\File' )
		                 ->disableOriginalConstructor()
		                 ->getMock();
		
		$mockWrapper = $this->getMockBuilder( '\WikiaFunctionWrapper' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'FindFile' ) )
		                    ->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getTitleFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$mockWrapper
		    ->expects( $this->at( 0 ) )
		    ->method ( 'FindFile' )
		    ->with   ( $mockTitle )
		    ->will   ( $this->returnValue( $mockFile ) )
		;
		$app = new ReflectionProperty( '\Wikia\Search\MediaWikiService', 'app' );
		$app->setAccessible( true );
		$app->setValue( $service, (object) array( 'wf' => $mockWrapper ) );
		$get = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getFileForPageId' );
		$get->setAccessible( true );
		$this->assertEquals(
				$mockFile,
				$get->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getFileForPageId should return a file for the provided page ID'
		);
		$pageIdsToFiles = new ReflectionProperty( '\Wikia\Search\MediaWikiService', 'pageIdsToFiles' );
		$pageIdsToFiles->setAccessible( true );
		$this->assertEquals(
				array( $this->pageId => $mockFile ),
				$pageIdsToFiles->getValue( $service ),
				'\Wikia\Search\MediaWikiService::getFileForPageId should store the file instance keyed by page id'
		);
		$service
		    ->expects( $this->never() )
		    ->method ( 'getTitleStringFromPageId' )
		;
		$this->assertEquals(
				$mockFile,
				$get->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getFileForPageId should return a cached file for the provided page ID if already invoked'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getPageFromPageId
	 */
	public function testGetPageFromPageIdThrowsException() {
		$this->proxyClass( 'Article', null, 'newFromID' );
		$get = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getPageFromPageId' );
		$get->setAccessible( true );
		try {
			$get->invoke( (new MediaWikiService), $this->pageId );
		} catch ( \Exception $e ) {}
		
		$this->assertInstanceOf(
				'\Exception',
				$e,
				'\Wikia\Search\MediaWikiService::getPageFromPageId should throw an exception when provided a nonexistent page id'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getPageFromPageId
	 */
	public function testGetPageFromPageCanonicalArticle() {
		$service = $this->service->getMock();
		$mockArticle = $this->getMockBuilder( '\Article' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( '__call' ) )
		                    ->getMock();
		
		$mockArticle
		    ->expects( $this->any() )
		    ->method ( '__call' )
		    ->with   ( 'isRedirect' )
		    ->will   ( $this->returnValue( false ) )
		;
		$this->proxyClass( 'Article', $mockArticle, 'newFromID' );
		$get = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getPageFromPageId' );
		$get->setAccessible( true );
		$this->assertEquals(
				$mockArticle,
				$get->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getPageFromPageId should return an instance of \Article for a provided page id'
		);
		$pageIdsToArticles = new ReflectionProperty( '\Wikia\Search\MediaWikiService', 'pageIdsToArticles' );
		$pageIdsToArticles->setAccessible( true );
		$this->assertEquals(
				array( $this->pageId => $mockArticle ),
				$pageIdsToArticles->getValue( $service ),
				 '\Wikia\Search\MediaWikiService::getPageFromPageId should cache any instantiations of \Article for a canonical page ID'
		);
		$this->assertEquals(
				$mockArticle,
				$get->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getPageFromPageId should return a cached instance of \Article for a provided page id upon consecutive invocations'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getPageFromPageId
	 */
	public function testGetPageFromPageRedirectArticle() {
		$service = $this->service->getMock();
		$mockArticle = $this->getMockBuilder( '\Article' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( '__call', 'getRedirectTarget', 'getID' ) )
		                    ->getMock();
		$mockTitle = $this->getMockBuilder( '\Title' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		$pageId2 = 321;
		$mockArticle
		    ->expects( $this->at( 0 ) )
		    ->method ( '__call' )
		    ->with   ( 'isRedirect' )
		    ->will   ( $this->returnValue( true ) )
		;
		$mockArticle
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getRedirectTarget' )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$mockArticle
		    ->expects( $this->at( 2 ) )
		    ->method ( 'getID' )
		    ->will   ( $this->returnValue( $pageId2 ) )
		;
		$this->proxyClass( 'Article', $mockArticle, 'newFromID' );
		$this->proxyClass( 'Article', $mockArticle );
		$this->mockClass( 'Article', $mockArticle );
		$this->mockApp();
		$get = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getPageFromPageId' );
		$get->setAccessible( true );
		$this->assertInstanceOf(
				'\WikiaMockProxy',
				$get->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getPageFromPageId should return the canonical instance of \Article for a provided page id'
		);
		$pageIdsToArticles = new ReflectionProperty( '\Wikia\Search\MediaWikiService', 'pageIdsToArticles' );
		$pageIdsToArticles->setAccessible( true );
		$this->assertArrayHasKey(
				$pageId2,
				$pageIdsToArticles->getValue( $service ),
				 '\Wikia\Search\MediaWikiService::getPageFromPageId should cache the canonical \Article for both the redirect and canonical page ID'
		);
		$this->assertArrayHasKey(
				$this->pageId,
				$pageIdsToArticles->getValue( $service ),
				 '\Wikia\Search\MediaWikiService::getPageFromPageId should cache the canonical \Article for both the redirect and canonical page ID'
		);
		$this->assertInstanceOf(
				'\WikiaMockProxy',
				$get->invoke( $service, $this->pageId ),
				'\Wikia\Search\MediaWikiService::getPageFromPageId should return a cached instance of \Article for a provided redirect page id upon consecutive invocations'
		);
		$this->assertInstanceOf(
				'\WikiaMockProxy',
				$get->invoke( $service, $pageId2 ),
				'\Wikia\Search\MediaWikiService::getPageFromPageId should return a cached instance of \Article for a provided canonical page id upon consecutive invocations, even if the redirect was accessed'
		);
	}
	
	/**
	 * @covers \Wikia\Search\MediaWikiService::getTitleString
	 */
	public function testGetTitleStringDefault() {
		$service = $this->service->getMock();
		
		$title = $this->getMockBuilder( '\Title' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'getBaseText', 'getNamespace' ) )
		              ->getMock();
		
		$title
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getNamespace' )
		    ->will   ( $this->returnValue( NS_MAIN ) )
		;
		$title
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getBaseText' )
		    ->will   ( $this->returnValue( 'title' ) )
		;
		$get = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getTitleString' );
		$get->setAccessible( true );
		$this->assertEquals(
				'title',
				$get->invoke( $service, $title )
		);
	}
	
    /**
	 * @covers \Wikia\Search\MediaWikiService::getTitleString
	 */
	public function testGetTitleStringChildWallMessage() {
		$service = $this->service->getMock();
		
		$title = $this->getMockBuilder( '\Title' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'getArticleID', 'getNamespace', '__toString' ) )
		              ->getMock();
		
		$wm = $this->getMockBuilder( '\WallMessage' )
		           ->disableOriginalConstructor()
		           ->setMethods( array( 'load', 'isMain', 'getTopParentObj', 'getMetaTitle' ) )
		           ->getMock();
		
		$title
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getNamespace' )
		    ->will   ( $this->returnValue( NS_WIKIA_FORUM_BOARD_THREAD ) )
		;
		$title
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getArticleID' )
		    ->will    ( $this->returnValue( $this->pageId ) )
		;
		$title
		    ->expects( $this->at( 2 ) )
		    ->method ( '__toString' )
		    ->will   ( $this->returnValue( 'wall message title' ) )
		;
		$wm
		    ->expects( $this->at( 0 ) )
		    ->method ( 'load' )
		;
		$wm
		    ->expects( $this->at( 1 ) )
		    ->method ( 'isMain' )
		    ->will   ( $this->returnValue( false ) )
		;
		$wm
		    ->expects( $this->at( 2 ) )
		    ->method ( 'getTopParentObj' )
		    ->will   ( $this->returnValue( $wm ) )
		;
		$wm
		    ->expects( $this->at( 3 ) )
		    ->method ( 'load' )
		;
		$wm
		    ->expects( $this->at( 4 ) )
		    ->method ( 'getMetaTitle' )
		    ->will   ( $this->returnValue( $title ) )
		;
		$this->proxyClass( '\WallMessage', $wm, 'newFromId' );
		$this->mockApp();
		$get = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getTitleString' );
		$get->setAccessible( true );
		$this->assertEquals(
				'wall message title',
				$get->invoke( $service, $title )
		);
	}
	
    /**
	 * @covers \Wikia\Search\MediaWikiService::getTitleString
	 **/
	public function testGetTitleStringMainWallMessage() {
		$service = $this->service->getMock();
		
		$title = $this->getMockBuilder( '\Title' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'getArticleID', 'getNamespace', '__toString' ) )
		              ->getMock();
		
		$wm = $this->getMockBuilder( '\WallMessage' )
		           ->disableOriginalConstructor()
		           ->setMethods( array( 'load', 'isMain', 'getTopParentObj', 'getMetaTitle' ) )
		           ->getMock();
		
		$title
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getNamespace' )
		    ->will   ( $this->returnValue( NS_WIKIA_FORUM_BOARD_THREAD ) )
		;
		$title
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getArticleID' )
		    ->will    ( $this->returnValue( $this->pageId ) )
		;
		$title
		    ->expects( $this->at( 2 ) )
		    ->method ( '__toString' )
		    ->will   ( $this->returnValue( 'wall message title' ) )
		;
		$wm
		    ->expects( $this->at( 0 ) )
		    ->method ( 'load' )
		;
		$wm
		    ->expects( $this->at( 1 ) )
		    ->method ( 'isMain' )
		    ->will   ( $this->returnValue( true ) )
		;
		$wm
		    ->expects( $this->at( 2 ) )
		    ->method ( 'getMetaTitle' )
		    ->will   ( $this->returnValue( $title ) )
		;
		$this->proxyClass( '\WallMessage', $wm, 'newFromId' );
		$this->mockApp();
		$get = new ReflectionMethod( '\Wikia\Search\MediaWikiService', 'getTitleString' );
		$get->setAccessible( true );
		$this->assertEquals(
				'wall message title',
				$get->invoke( $service, $title )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getNamespaceIdForString
	 */
	public function testGetNamespaceIdForString() {
		$this->assertEquals( NS_CATEGORY, (new MediaWikiService)->getNamespaceIdForString( 'Category' ) );
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getGlobalForWiki
	 */
	public function testGetGlobalForWiki() {
		global $wgSitename, $wgCityId;
		$sitename = $wgSitename;
		$this->assertEquals(
				$wgSitename,
				(new MediaWikiService)->getGlobalForWiki( 'wgSitename', $wgCityId )
		);
		$this->assertEquals(
				$wgSitename,
				(new MediaWikiService)->getGlobalForWiki( 'Sitename', $wgCityId )
		);
		$wf = $this->getMock( 'WikiFactory', [ 'getVarValueByName' ] );
		$wf
		    ->staticExpects( $this->once() )
		    ->method ( 'getVarValueByName' )
		    ->with   ( 'wgFoo', 123 )
		    ->will   ( $this->returnValue( (object) [ 'cv_value' => serialize( [ 'bar' ] ) ] ) )
		;
		$this->proxyClass( 'WikiFactory', $wf );
		$this->mockApp();
		$this->assertEquals(
				[ 'bar' ],
				(new MediaWikiService)->getGlobalForWiki( 'foo', 123 )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::isSkinMobile
	 */
	public function testIsSkinMobile() {
		$user = $this->getMockBuilder( 'User' )
		             ->disableOriginalConstructor()
		             ->setMethods( array( 'getSkin' ) )
		             ->getMock();
		$skin = $this->getMockBuilder( '\SkinWikiaMobile' )
		             ->disableOriginalConstructor()
		             ->getMock();
		$user
		    ->expects( $this->once() )
		    ->method ( 'getSkin' )
		    ->will   ( $this->returnValue( $skin ) )
		;
		$app = (object) array( 'wg' => (object ) array( 'User' => $user ) );
		$service = $this->service->setMethods( null )->getMock();
		$reflApp = new ReflectionProperty( 'Wikia\Search\MediaWikiService', 'app' );
		$reflApp->setAccessible( true );
		$reflApp->setValue( $service, $app );
		$this->assertTrue(
				$service->isSkinMobile()
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::isOnDbCluster
	 */
	public function testIsOnDbCluster() {
		$service = $this->service->setMethods( array( 'getGlobal' ) )->getMock();
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getGlobal' )
		    ->with   ( 'ExternalSharedDB' )
		    ->will   ( $this->returnValue( null ) )
		;
		$this->assertFalse(
				$service->isOnDbCluster()
		);
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getGlobal' )
		    ->with   ( 'ExternalSharedDB' )
		    ->will   ( $this->returnValue( 'this value just needs to not be empty' ) )
		;
		$this->assertTrue(
				$service->isOnDbCluster()
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getDefaultNamespacesFromSearchEngine
	 */
	public function testGetDefaultNamespacesFromSearchEngine() {
		$this->assertEquals(
				\SearchEngine::defaultNamespaces(),
				(new MediaWikiService)->getDefaultNamespacesFromSearchEngine()
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getSearchableNamespacesFromSearchEngine
	 */
	public function testGetSearchableNamespacesFromSearchEngine() {
		$this->assertEquals(
				\SearchEngine::searchableNamespaces(),
				(new MediaWikiService)->getSearchableNamespacesFromSearchEngine()
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getTextForNamespaces
	 */
	public function testGetTextForNamespaces() {
		$this->assertEquals(
				\SearchEngine::namespacesAsText( array( 0, 14 ) ),
				(new MediaWikiService)->getTextForNamespaces( array( 0, 14 ) )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getFirstRevisionTimestampForPageId()
	 */
	public function testGetFirstRevisionTimestampForPageId() {
		$service = $this->service->setMethods( array( 'getFormattedTimestamp', 'getTitleFromPageId' ) )->getMock();
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getFirstRevision' ) )
		                  ->getMock();
		$mockRev = $this->getMockBuilder( 'Revision' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'getTimestamp' ) )
		                ->getMock();
		$timestamp = 'whatever o clock';
		$service
		    ->expects( $this->once() )
		    ->method ( 'getTitleFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$mockTitle
		    ->expects( $this->once() )
		    ->method ( 'getFirstRevision' )
		    ->will   ( $this->returnValue( $mockRev ) )
		;
		$mockRev
		    ->expects( $this->once() )
		    ->method ( 'getTimestamp' )
		    ->will   ( $this->returnValue( $timestamp ) )
		;
		$service
		    ->expects( $this->once() )
		    ->method ( 'getFormattedTimestamp' )
		    ->with   ( $timestamp )
		    ->will   ( $this->returnValue( '11/11/11' ) )
		;
		$this->assertEquals(
				'11/11/11',
				$service->getFirstRevisionTimestampForPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getSnippetForPageId
	 */
	public function testGetSnippetForPageId() {
		$mockservice = $this->getMock( 'ArticleService', array( 'getTextSnippet' ) );
		$service = $this->service->setMethods( array( 'getCanonicalPageIdFromPageId' ) )->getMock();
		$service
		    ->expects( $this->once() )
		    ->method ( 'getCanonicalPageIdFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $this->pageId ) )
		;
		$mockservice
		    ->expects( $this->once() )
		    ->method ( 'getTextSnippet' )
		    ->with   ( 250 )
		    ->will   ( $this->returnValue( 'snippet' ) )
		;
		$this->proxyClass( 'ArticleService', $mockservice );
		$this->mockApp();
		$this->assertEquals(
				'snippet',
				$service->getSnippetForPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getNonCanonicalTitleStringFromPageId
	 */
	public function testGetNonCanonicalTitleStringFromPageId() { 
		$service = $this->service->setMethods( array( 'getTitleStringFromPageId', 'getTitleString' ) )->getMock();
		$mockArticle = $this->getMockBuilder( 'Article' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'getTitle' ) )
		                    ->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		$string = 'title';
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getTitleStringFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $string ) )
		;
		$this->assertEquals(
				$string,
				$service->getNonCanonicalTitleStringFromPageId( $this->pageId )
		);
		$reflRedirs = new ReflectionProperty( 'Wikia\Search\MediaWikiService', 'redirectArticles' );
		$reflRedirs->setAccessible( true );
		$reflRedirs->setValue( $service, array( $this->pageId => $mockArticle ) );
		$mockArticle
		    ->expects( $this->once() )
		    ->method ( 'getTitle' )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$service
		    ->expects( $this->once() )
		    ->method ( 'getTitleString' )
		    ->with   ( $mockTitle )
		    ->will   ( $this->returnValue( $string ) )
	    ;
		$this->assertEquals(
				$string,
				$service->getNonCanonicalTitleStringFromPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getNonCanonicalUrlFromPageId
	 */
	public function testGetNonCanonicalUrlFromPageId() { 
		$service = $this->service->setMethods( array( 'getUrlFromPageId' ) )->getMock();
		$mockArticle = $this->getMockBuilder( 'Article' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'getTitle' ) )
		                    ->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getFullUrl' ) )
		                  ->getMock();
		$string = 'http://foo.wikia.com/wiki/Foo';
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getUrlFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $string ) )
		;
		$this->assertEquals(
				$string,
				$service->getNonCanonicalUrlFromPageId( $this->pageId )
		);
		$reflRedirs = new ReflectionProperty( 'Wikia\Search\MediaWikiService', 'redirectArticles' );
		$reflRedirs->setAccessible( true );
		$reflRedirs->setValue( $service, array( $this->pageId => $mockArticle ) );
		$mockArticle
		    ->expects( $this->once() )
		    ->method ( 'getTitle' )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$mockTitle
		    ->expects( $this->once() )
		    ->method ( 'getFullUrl' )
		    ->will   ( $this->returnValue( $string ) )
		;
		$this->assertEquals(
				$string,
				$service->getNonCanonicalUrlFromPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getArticleMatchForTermAndNamespaces
	 */
	public function testGetArticleMatchForTermAndNamespaces() {
		$service = $this->service->setMethods( array( 'getPageFromPageId' ) )->getMock();
		$mockEngine = $this->getMockBuilder( 'SearchEngine' )
		                   ->disableOriginalConstructor()
		                   ->setMethods( array( 'getNearMatch' ) )
		                   ->getMock();
		
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getNamespace', 'getArticleId' ) )
		                  ->getMock();
		
		$mockMatch = $this->getMockBuilder( 'Wikia\Search\Match\Article' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		
		$term = 'Foo';
		$namespaces = array( 0, 14 );
		
		$mockEngine
		    ->staticExpects( $this->at( 0 ) )
		    ->method       ( 'getNearMatch' )
		    ->with         ( $term )
		    ->will         ( $this->returnValue( null ) ) 
		;
		$this->proxyClass( 'SearchEngine', $mockEngine );
		$this->proxyClass( 'Wikia\Search\Match\Article', $mockMatch );
		$this->mockApp();
		$this->assertNull(
				$service->getArticleMatchForTermAndNamespaces( $term, $namespaces )
		);
		$mockEngine
		    ->staticExpects( $this->at( 0 ) )
		    ->method       ( 'getNearMatch' )
		    ->with         ( $term )
		    ->will         ( $this->returnValue( $mockTitle ) ) 
		;
		$mockTitle
		    ->expects( $this->once() )
		    ->method ( 'getNamespace' )
		    ->will   ( $this->returnValue( 0 ) )
		;
		$mockTitle
		    ->expects( $this->any() )
		    ->method ( 'getArticleId' )
		    ->will   ( $this->returnValue( $this->pageId ) )
		;
		$service
		    ->expects( $this->once() )
		    ->method ( 'getPageFromPageId' )
		    ->with   ( $this->pageId )
		;
		$this->proxyClass( 'SearchEngine', $mockEngine );
		$this->proxyClass( 'Wikia\Search\Match\Article', $mockMatch );
		$this->mockApp();
		$this->assertInstanceOf(
				$service->getArticleMatchForTermAndNamespaces( $term, $namespaces )->_mockClassName,
				$mockMatch
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getWikiMatchByHost
	 */
	public function testGetWikiMatchByHost() {
		$service = $this->service->setMethods( array( 'getWikiIdByHost' ) )->getMock();
		$mockMatch = $this->getMockBuilder( 'Wikia\Search\Match\Wiki' )
		                  ->disableOriginalConstructor()
		                  ->getMock();
		
		$service
		    ->expects( $this->once() )
		    ->method ( 'getWikiIdByHost' )
		    ->with   ( 'foo.wikia.com' )
		    ->will   ( $this->returnValue( 123 ) )
		;
		
		$this->proxyClass( 'Wikia\Search\Match\Wiki', $mockMatch );
		$this->mockApp();
		$this->assertInstanceOf(
				$service->getWikiMatchByHost( 'foo' )->_mockClassName,
				$mockMatch
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getMainPageUrlForWikiId
	 */
	public function testGetMainPageUrlForWikiId() {
		$service = $this->service->setMethods( array( 'getMainPageTitleForWikiId' ) )->getMock();
		$mockTitle = $this->getMockBuilder( 'GlobalTitle' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getFullUrl' ) )
		                  ->getMock();
		$url = 'http://foo.wikia.com/wiki/foo';
		$service
		    ->expects( $this->once() )
		    ->method ( 'getMainPageTitleForWikiId' )
		    ->with   ( 123 )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$mockTitle
		    ->expects( $this->once() )
		    ->method ( 'getFullUrl' )
		    ->will   ( $this->returnValue( $url ) )
		;
		$this->assertEquals(
				$url,
				$service->getMainPageUrlForWikiId( 123 )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getDbNameForWikiId
	 */
	public function testGetDbNameForWikiId() {
		$service = $this->service->setMethods( array( 'getDataSourceForWikiId' ) )->getMock();
		$mockSource = $this->getMockBuilder( 'WikiaDataSource' )
		                   ->disableOriginalConstructor()
		                   ->setMethods( array( 'getDbName' ) )
		                   ->getMock();
		$dbName = 'foo';
		$service
		    ->expects( $this->once() )
		    ->method ( 'getDataSourceForWikiId' )
		    ->with   ( 123 )
		    ->will   ( $this->returnValue( $mockSource ) )
		;
		$mockSource
		    ->expects( $this->once() )
		    ->method ( 'getDbName' )
		    ->will   ( $this->returnValue( $dbName ) )
		;
		$reflGet = new ReflectionMethod( 'Wikia\Search\MediaWikiService', 'getDbNameForWikiId' );
		$reflGet->setAccessible( true );
		$this->assertEquals(
				$dbName,
				$reflGet->invoke( $service, 123 )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getLastRevisionTimestampForPageId()
	 */
	public function testGetLastRevisionTimestampForPageId() {
		$service = $this->service->setMethods( array( 'getFormattedTimestamp', 'getTitleFromPageId' ) )->getMock();
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getLatestRevId' ) )
		                  ->getMock();
		$mockRev = $this->getMockBuilder( 'Revision' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'getTimestamp' ) )
		                ->getMock();
		$timestamp = 'whatever o clock';
		$service
		    ->expects( $this->once() )
		    ->method ( 'getTitleFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$mockTitle
		    ->expects( $this->once() )
		    ->method ( 'getLatestRevId' )
		    ->will   ( $this->returnValue( 456 ) )
		;
		$mockRev
		    ->expects( $this->once() )
		    ->method ( 'getTimestamp' )
		    ->will   ( $this->returnValue( $timestamp ) )
		;
		$service
		    ->expects( $this->once() )
		    ->method ( 'getFormattedTimestamp' )
		    ->with   ( $timestamp )
		    ->will   ( $this->returnValue( '11/11/11' ) )
		;
		$this->proxyClass( 'Revision', $mockRev, 'newFromId' );
		$this->mockApp();
		$this->assertEquals(
				'11/11/11',
				$service->getLastRevisionTimestampForPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getMediaWikiFormattedTimestamp
	 */
	public function testGetMediaWikiFormattedTimestamp() {
		$service = $this->service->setMethods( null )->getMock();
		$lang = $this->getMockBuilder( 'Language' )
		             ->disableOriginalConstructor()
		             ->setMethods( array( 'date' ) )
		             ->getMock();
		$wrapper = $this->getMockBuilder( 'WikiaFunctionWrapper' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'Timestamp' ) )
		                ->getMock();
		
		$app = (object) array( 'wg' => (object) array( 'Lang' => $lang ), 'wf' => $wrapper );
		$reflApp = new ReflectionProperty( 'Wikia\Search\MediaWikiService', 'app' );
		$reflApp->setAccessible( true );
		$reflApp->setValue( $service, $app );
		
		$wrapper
		    ->expects( $this->once() )
		    ->method ( 'Timestamp' )
		    ->with   ( TS_MW, '11/11/11' )
		    ->will   ( $this->returnValue( 'timestamp' ) )
		;
		$lang
		    ->expects( $this->once() )
		    ->method ( 'date' )
		    ->with   ( 'timestamp' ) 
		    ->will   ( $this->returnValue( 'mw formatted timestamp' ) )
		;
		$this->assertEquals(
				'mw formatted timestamp',
				$service->getMediaWikiFormattedTimestamp( '11/11/11' )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::searchSupportsCurrentLanguage
	 */
	public function testSearchSupportsCurrentLanguage() {
		$service = $this->service->setMethods( array( 'searchSupportsLanguageCode', 'getLanguageCode' ) )->getMock();
		$service
		    ->expects( $this->once() )
		    ->method ( 'getLanguageCode' )
		    ->will   ( $this->returnValue( 'en' ) )
		;
		$service
		    ->expects( $this->once() )
		    ->method ( 'searchSupportsLanguageCode' )
		    ->with   ( 'en' )
		    ->will   ( $this->returnValue( true ) )
		;
		$this->assertTrue(
				$service->searchSupportsCurrentLanguage()
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getThumbnailHtmlForPageId
	 */
	public function testGetThumbnailHtmlForPageId() {
		$service = $this->service->setMethods( array( 'getFileForPageId' ) )->getMock();
		$mockFile = $this->getMockBuilder( 'File' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'transform' ) )
		                 ->getMock();
		$mockTransform = $this->getMockBuilder( 'MediaTransformOutput' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( array( 'toHtml' ) )
		                      ->getMock();
		$html = 'this value does not matter';
		$service
		    ->expects( $this->once() )
		    ->method ( 'getFileForPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockFile ) )
		;
		$mockFile
		    ->expects( $this->once() )
		    ->method ( 'transform' )
		    ->with   ( array( 'width' => 160 ) )
		    ->will   ( $this->returnValue( $mockTransform ) )
		;
		$mockTransform
		    ->expects( $this->once() )
		    ->method ( 'toHtml' )
		    ->with   ( array('desc-link'=>true, 'img-class'=>'thumbimage', 'duration'=>true) )
		    ->will   ( $this->returnValue( $html ) )
		;
		$this->assertEquals(
				$html,
				$service->getThumbnailHtmlForPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getVideoViewsForPageId
	 */
	public function testGetVideoViewsForPageId() {
		$service = $this->service->setMethods( array( 'getTitleFromPageId' ) )->getMock();
		$mockTitle = $this->getMockBuilder( 'Title' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getDBKey' ) )
		                  ->getMock();
		// i'm sure marissa mayer will love this
		$wfh = $this->getMockBuilder( 'WikiaFileHelper' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'isFileTypeVideo' ) )
		            ->getMock();
		
		$mqs = $this->getMockBuilder( 'MediaQueryService' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'getTotalVideoViewsByTitle' ) )
		            ->getMock();
		$service
		    ->expects( $this->once() )
		    ->method ( 'getTitleFromPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( $mockTitle ) )
		;
		$wfh
		    ->staticExpects( $this->once() )
		    ->method ( 'isFileTypeVideo' )
		    ->with   ( $mockTitle )
		    ->will   ( $this->returnValue( true ) )
		;
		$mockTitle
		    ->expects( $this->once() )
		    ->method ( 'getDBKey' )
		    ->will   ( $this->returnValue( 'Foo_bar' ) )
		;
		$mqs
		    ->staticExpects( $this->once() )
		    ->method ( 'getTotalVideoViewsByTitle' )
		    ->with   ( 'Foo_bar' )
		    ->will   ( $this->returnValue( 1234 ) )
		;
		$this->proxyClass( 'WikiaFileHelper', $wfh );
		$this->proxyClass( 'MediaQueryService', $mqs );
		$this->mockApp();
		$this->assertEquals(
				1234,
				$service->getVideoViewsForPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getFormattedVideoViewsForPageId
	 */
	public function testGetFormattedVideoViewsForPageId() {
		$service = $this->service->setMethods( array( 'getVideoViewsForPageId', 'formatNumber' ) )->getMock();
		$wrapper = $this->getMockBuilder( 'WikiaFunctionWrapper' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'MsgExt' ) )
		                ->getMock();
		
		$service
		    ->expects( $this->once() )
		    ->method ( 'getVideoViewsForPageId' )
		    ->with   ( $this->pageId )
		    ->will   ( $this->returnValue( 1234 ) )
		;
		$service
		    ->expects( $this->once() )
		    ->method ( 'formatNumber' )
		    ->with   ( 1234 )
		    ->will   ( $this->returnValue( '1,234' ) )
		;
		$wrapper
		    ->expects( $this->once() )
		    ->method ( 'MsgExt' )
		    ->with   ( 'videohandler-video-views', array( 'parsemag' ), '1,234' )
		    ->will   ( $this->returnValue( '1,234 views' ) )
		;
		$reflApp = new ReflectionProperty( '\Wikia\Search\MediaWikiService', 'app' );
		$reflApp->setAccessible( true );
		$reflApp->setValue( $service, (object) array( 'wf' => $wrapper ) );
		$this->mockApp();
		$this->assertEquals(
				'1,234 views',
				$service->getFormattedVideoViewsForPageId( $this->pageId )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::formatNumber
	 */
	public function testFormatNumber() {
		$service = $this->service->setMethods( null )->getMock();
		
		$lang = $this->getMockBuilder( "Language" )
		             ->disableOriginalConstructor()
		             ->setMethods( array( 'formatNum' ) )
		             ->getMock();
		
		$lang
		    ->expects( $this->once() )
		    ->method ( 'formatNum' )
		    ->with   ( 10000 )
		    ->will   ( $this->returnValue( '10,000' ) )
		;
		$wg = (object) array( 'Lang' => $lang );
		$app = new ReflectionProperty( 'Wikia\Search\MediaWikiService', 'app' );
		$app->setAccessible( true );
		$app->setValue( $service, (object) array( 'wg' => $wg ) );
		$this->assertEquals(
				'10,000',
				$service->formatNumber( 10000 )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getVisualizationInfoForWikiId
	 */
	public function testGetVisualizationInfoForWikiId() {
		$service = $this->service->setMethods( array( 'getLanguageCode' ) )->getMock();
		$model = $this->getMock( 'WikisModel', array( 'getDetails' ) );
		$details = [ 'yup' ];
		$info = [ 123 => $details ];
		$model
		    ->expects( $this->exactly( 2 ) )
		    ->method ( 'getDetails' )
		    ->will   ( $this->returnValueMap( [ [ [ 123 ], $info ],
				[ [ 321 ], [] ] ] ) )
		;
		$this->proxyClass( 'WikisModel', $model );
		$this->mockApp();
		$this->assertEquals(
				$details,
				$service->getVisualizationInfoForWikiId( 123 )
		);
		$this->assertEquals(
			[],
			$service->getVisualizationInfoForWikiId( 321 )
		);
	}

	/**
	 * @covers Wikia\Search\MediaWikiService::getStatsInfoForWikiId
	 */
	public function testGetStatsInfoForWikiId() {
		$service = $this->service->setMethods( null )->getMock();
		$wikisvc = $this->getMock( 'WikiService', array( 'getSiteStats', 'getTotalVideos' ) );
		
		$info = array( 'this' => 'yup' );
		$wikisvc
		    ->expects( $this->once() )
		    ->method ( 'getSiteStats' )
		    ->with   ( 123 )
		    ->will   ( $this->returnValue( $info ) )
		;
		$wikisvc
		    ->expects( $this->once() )
		    ->method ( 'getTotalVideos' )
		    ->with   ( 123 )
		    ->will   ( $this->returnValue( 4321 ) )
		;
		$this->proxyClass( 'WikiService', $wikisvc );
		$this->mockApp();
		$method = new ReflectionMethod( 'Wikia\Search\MediaWikiService', 'getStatsInfoForWikiId' );
		$method->setAccessible( true );
		$this->assertEquals(
				array( 'this_count' => 'yup', 'videos_count' => 4321 ),
				$service->getStatsInfoForWikiId( 123 )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getFormattedTimestamp
	 */
	public function testGetFormattedTimestamp() {
		$mockWf = $this->getMock( 'WikiaFunctionWrapper', array( 'Timestamp' ) );
		$service = $this->service->setMethods( null )->getMock();
		$app = new ReflectionProperty( '\Wikia\Search\MediaWikiService' , 'app' );
		$app->setAccessible( true );
		$app->setValue( $service, (object) array( 'wf' => $mockWf ) );
		$timestamp = 'whatever';
		$mockWf
		    ->expects( $this->once() )
		    ->method ( 'Timestamp' )
		    ->with   ( TS_ISO_8601, $timestamp )
		    ->will   ( $this->returnValue( 'result' ) )
		;
		$meth = $app = new ReflectionMethod( '\Wikia\Search\MediaWikiService' , 'getFormattedTimestamp' );
		$meth->setAccessible( true );
		$this->assertEquals(
				'result',
				$meth->invoke( $service, $timestamp )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getDataSourceForWikiId
	 */
	public function testGetDataSourceForWikiId() {
		$service = $this->service->setMethods( null )->getMock();
		$ds = $this->getMockBuilder( 'WikiDataSource' )
		           ->disableOriginalConstructor()
		           ->getMock();
		
		$this->proxyClass( 'WikiDataSource', $ds );
		$this->mockApp();
		$meth = $app = new ReflectionMethod( '\Wikia\Search\MediaWikiService' , 'getDataSourceForWikiId' );
		$meth->setAccessible( true );
		$result = $meth->invoke( $service, 123 );
		$this->assertInstanceOf(
				$result->_mockClassName,
				$ds
		);
		$this->assertAttributeContains(
				$result,
				'wikiDataSources',
				$service
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getMainPageTitleForWikiId
	 */
	public function testGetMainPageTitleForWikiId() {
		$service = $this->service->setMethods( [ 'getDbNameForWikiId', 'getGlobalForWiki' ] )->getMock();
		$apiservice = $this->getMock( 'ApiService', [ 'foreignCall' ] );
		$title = $this->getMockBuilder( 'GlobalTitle' )
		              ->disableOriginalConstructor()
		              ->setMethods( [ 'isRedirect', 'getRedirectTarget' ] )
		              ->getMock();
		
		$service
		    ->expects( $this->once() )
		    ->method ( 'getDbNameForWikiId' )
		    ->with   ( 123 )
		    ->will   ( $this->returnValue( 'foo' ) )
		;
		$service
		    ->expects( $this->once() )
		    ->method ( 'getGlobalForWiki' )
		    ->with   ( 'wgLanguageCode', 123 )
		    ->will   ( $this->returnValue( 'en' ) )
		;
		$fcArray = [ 'action' => 'query', 'meta' => 'allmessages', 'ammessages' => 'mainpage', 'amlang' => 'en' ];
		$responseArray = [ 'query' => ['allmessages' => [ ['*' => 'main' ] ] ] ];
		$apiservice
		    ->staticExpects( $this->once() )
		    ->method ( 'foreignCall' )
		    ->with   ( 'foo', $fcArray )
		    ->will   ( $this->returnValue( $responseArray ) )
		;
		$title
		    ->expects( $this->once() )
		    ->method ( 'isRedirect' )
		    ->will   ( $this->returnValue( true ) )
		;
		$title
		    ->expects( $this->once() )
		    ->method ( 'getRedirectTarget' )
		    ->will   ( $this->returnValue( $title ) )
		;
		$this->proxyClass( 'ApiService', $apiservice );
		$this->proxyClass( 'GlobalTitle', $title, 'newFromText' );
		$this->mockApp();
		$reflGet = new ReflectionMethod( 'Wikia\Search\MediaWikiService', 'getMainPageTitleForWikiId' );
		$reflGet->setAccessible( true );
		$result = $reflGet->invoke( $service, 123 );
		$this->assertEquals(
				$result,
				$title
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getDescriptionTextForWikiId
	 */
	public function testGetDescriptionTextForWikiId() {
		$service = $this->service->setMethods( [ 'getDbNameForWikiId', 'getGlobalForWiki' ] )->getMock();
		$apiservice = $this->getMock( 'ApiService', [ 'foreignCall' ] );
		
		
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getDbNameForWikiId' )
		    ->with   ( 123 )
		    ->will   ( $this->returnValue( 'foo' ) )
		;
		$service
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getGlobalForWiki' )
		    ->with   ( 'wgLanguageCode', 123 )
		    ->will   ( $this->returnValue( 'en' ) )
		;
		$service
		    ->expects( $this->at( 2 ) )
		    ->method ( 'getGlobalForWiki' )
		    ->with   ( 'wgSitename', 123 )
		    ->will   ( $this->returnValue( 'foo wiki' ) )
		;
		$fcArray = [ 'action' => 'query', 'meta' => 'allmessages', 'ammessages' => 'description', 'amlang' => 'en' ];
		$responseArray = [ 'query' => ['allmessages' => [ ['*' => '{{SITENAME}} is a wiki' ] ] ] ];
		$apiservice
		    ->staticExpects( $this->once() )
		    ->method ( 'foreignCall' )
		    ->with   ( 'foo', $fcArray )
		    ->will   ( $this->returnValue( $responseArray ) )
		;
		$this->proxyClass( 'ApiService', $apiservice );
		$this->mockApp();
		$this->assertEquals(
				'foo wiki is a wiki',
				$service->getDescriptionTextForWikiId( 123 )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getHubForWikiId
	 */
	public function testGetHubForWikiId() {
		$service = $this->service->setMethods( null )->getMock();
		$hs = $this->getMock( 'HubService', [ 'getCategoryInfoForCity' ] );
		$hs
		    ->staticExpects( $this->once() )
		    ->method       ( 'getCategoryInfoForCity' )
		    ->with         ( 123 )
		    ->will         ( $this->returnValue( (object) [ 'cat_name' => 'Entertainment' ] ) )
		;
		$this->proxyClass( 'HubService', $hs );
		$this->mockApp();
		$this->assertEquals(
				'Entertainment',
				$service->getHubForWikiId( 123 )
		);
	}
	
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getSubHubForWikiId
	 */
	public function testGetSubHubForWikiId() {
		$service = $this->service->setMethods( null )->getMock();
		$wf = $this->getMock( 'WikiFactory', [ 'getCategory' ] );
		$wf
		    ->staticExpects( $this->once() )
		    ->method       ( 'getCategory' )
		    ->with         ( 123 )
		    ->will         ( $this->returnValue( (object) [ 'cat_name' => 'Entertainment' ] ) )
		;
		$this->proxyClass( 'WikiFactory', $wf );
		$this->mockApp();
		$this->assertEquals(
				'Entertainment',
				$service->getSubHubForWikiId( 123 )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getMainPageTextForWikiId
	 */
	public function testGetMainPageTextForWikiId() {
		$service = $this->service->setMethods( [ 'getMainPageTitleForWikiId', 'getDbNameForWikiId' ] )->getMock();
		$apiservice = $this->getMock( 'ApiService', [ 'foreignCall' ] );
		$title = $this->getMockBuilder( 'GlobalTitle' )
		              ->disableOriginalConstructor()
		              ->setMethods( [ 'getDbKey' ] )
		              ->getMock();
		
		$params = [ 'controller' => 'ArticlesApiController', 'method' => 'getDetails', 'titles' => 'Foo_bar' ];
		$title
		    ->expects( $this->once() )
		    ->method ( 'getDbKey' )
		    ->will   ( $this->returnValue( 'Foo_bar' ) )
		;
		$service
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getMainPageTitleForWikiId' )
		    ->with   ( 123 )
		    ->will   ( $this->returnValue( $title ) )
		;
		$service
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getDbNameForWikiId' )
		    ->with   ( 123 )
		    ->will   ( $this->returnValue( 'foo' ) )
		;
		$responseArray = [ 'items' => [ [ 'abstract' => 'and if you dont know now you know' ] ] ];
		$apiservice
		    ->staticExpects( $this->once() )
		    ->method ( 'foreignCall' )
		    ->with   ( 'foo', $params, \ApiService::WIKIA )
		    ->will   ( $this->returnValue( $responseArray ) )
		;
		$this->proxyClass( 'ApiService', $apiservice );
		$this->mockApp();
		$this->assertEquals(
				'and if you dont know now you know',
				$service->getMainPageTextForWikiId( 123 )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::invokeHook
	 */
	public function testInvokeHook() {
		$service = $this->service->setMethods( null )->getMock();
		$wf = $this->getMock( 'WikiaFunctionWrapper', [ 'RunHooks' ] );
		$app = new ReflectionProperty( '\Wikia\Search\MediaWikiService' , 'app' );
		$app->setAccessible( true );
		$app->setValue( $service, (object) array( 'wf' => $wf ) );
		$wf
		    ->expects( $this->once() )
		    ->method ( 'RunHooks' )
		    ->with   ( 'onwhatever', [ 'foo', 123 ] )
		    ->will   ( $this->returnValue( true ) )
		;
		$this->assertTrue(
				$service->invokeHook( 'onwhatever', [ 'foo', 123 ] )
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::__construct
	 */
	public function test__construct() {
		$service = (new MediaWikiService);
		$this->assertAttributeEquals(
				\F::app(),
				'app',
				$service
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getHostName
	 */
	public function testGetHostName() {
		$service = (new MediaWikiService);
		$this->assertEquals(
				substr( $service->getGlobal( 'Server' ), 7 ),
				$service->getHostName()
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::isPageIdMainPage
	 */
	public function testPageIdIsMainPage() {
		$mockService = $this->getMock( 'Wikia\Search\MediaWikiService', [ 'getMainPageArticleId' ] );
		$mockService
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getMainPageArticleId' )
		    ->will   ( $this->returnValue( 123 ) )
		;
		$this->assertTrue(
				$mockService->isPageIdMainPage( 123 )
		);
		$mockService
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getMainPageArticleId' )
		    ->will   ( $this->returnValue( 234 ) )
		;
		$this->assertFalse(
				$mockService->isPageIdMainPage( 123 )
		);
		$this->assertFalse(
				$mockService->isPageIdMainPage( 0 )
		);
	}

	/**
	 * @covers Wikia\Search\MediaWikiService::shortNumForMsg
	 * @dataProvider dataShortNumForMsg
	 */
	public function testShortNumForMsg($number, $baseMessageId, $usedNumber, $usedMessageId) {
		$this->mockGlobalFunction('message', 'mocked message', 1, array( $usedMessageId, $usedNumber, $number ) );
		$this->mockApp();
		$service = (new MediaWikiService);
		$this->assertEquals('mocked message', $service->shortNumForMsg($number, $baseMessageId));

	}

	public function dataShortNumForMsg() {
		return array(
			array(1, 'message-id', 1, 'message-id'),
			array(999, 'message-id', 999, 'message-id'),
			array(1000, 'message-id', 1, 'message-id-k'),
			array(999999, 'message-id', 999, 'message-id-k'),
			array(1000000, 'message-id', 1, 'message-id-M'),
			array(10000000000, 'message-id', 10000, 'message-id-M'),
		);
	}
	
	/**
	 * @covers Wikia\Search\MediaWikiService::getSimpleMessage
	 */
	public function testGetSimpleMessage() {
		
		$mockWf = $this->getMock( 'WikiaFunctionWrapper', array( 'Message' ) );
		$mockMessage = $this->getMockBuilder( 'Message' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'text' ) )
		                    ->getMock();
		
		$service = $this->service->setMethods( null )->getMock();
		$params = array( 'whatever' );
		$mockWf
		    ->expects( $this->once() )
		    ->method ( 'Message' )
		    ->with   ( 'foo', $params )
		    ->will   ( $this->returnValue( $mockMessage ) )
		;
		$mockMessage
		    ->expects( $this->once() )
		    ->method ( 'text' )
		    ->will   ( $this->returnValue( 'bar whatever' ) )
		;
		
		$app = new ReflectionProperty( '\Wikia\Search\MediaWikiService' , 'app' );
		$app->setAccessible( true );
		$app->setValue( $service, (object) array( 'wf' => $mockWf ) );
		
		$this->assertEquals(
				'bar whatever',
				$service->getSimpleMessage( 'foo', $params )
		);
	}
}